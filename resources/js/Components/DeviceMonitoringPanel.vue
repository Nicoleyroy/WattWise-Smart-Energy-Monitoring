<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import axios from 'axios';
import VueApexCharts from 'vue3-apexcharts';
import { Zap, Activity, Target, BarChart3, Gauge, Clock, ShieldCheck, Trash2, X } from 'lucide-vue-next';
import { useTheme } from '@/Composables/useTheme';

const { isDark } = useTheme();

const props = defineProps({
    deviceId: { type: [String, Number], required: true },
    deviceName: { type: String, default: 'Device' }
});

// --- Power Value Formatting ---
const formatPowerShort = (watts) => {
    const value = Number(watts);
    if (!Number.isFinite(value)) return '0 W';
    const abs = Math.abs(value);
    if (abs >= 1_000_000) return (value / 1_000_000).toFixed(2) + ' MW';
    if (abs >= 1_000) return (value / 1_000).toFixed(2) + ' kW';
    return value.toFixed(1) + ' W';
};

const formatPowerAxis = (val) => {
    const value = Number(val);
    if (!Number.isFinite(value)) return '0';
    const abs = Math.abs(value);
    if (abs >= 1_000_000) return (value / 1_000_000).toFixed(1) + 'M';
    if (abs >= 1_000) return (value / 1_000).toFixed(1) + 'k';
    return value.toFixed(0);
};

// --- Constants & Thresholds ---
const THRESHOLDS = {
    voltage: { warning: 210, critical: 200, unit: 'V', label: 'Voltage' },
    current: { warning: 15, critical: 20, unit: 'A', label: 'Current' },
    power: { warning: 3000, critical: 5000, unit: 'W', label: 'Current Power' },
};

const METRIC_HELP = {
    voltage: 'Voltage is the electrical pressure that pushes power through the plug. A stable reading helps appliances operate safely.',
    current: 'Current is the amount of electricity flowing through the plug right now. Higher current usually means a heavier load.',
    power: 'Power is the device current rate of electricity use, measured in watts.',
    energy: 'Energy is the total electricity consumed by this plug over time, measured in kilowatt-hours (kWh).',
};

const MAX_VALID_POWER_W = 1_000_000;

const toFiniteNumber = (value, fallback = 0) => {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : fallback;
};

const sanitizePower = (value, fallback = 0) => {
    const parsed = Number(value);
    if (!Number.isFinite(parsed)) {
        return fallback;
    }

    // Treat million-W values as invalid telemetry spikes.
    if (Math.abs(parsed) >= MAX_VALID_POWER_W) {
        return fallback;
    }

    return parsed;
};

// --- State Management ---
const loading = ref(true);
const lastUpdate = ref(null);
const viewMode = ref('live'); 
const activeAlerts = ref([]);
const exportingCsv = ref(false);
const LIVE_APPEND_INTERVAL_MS = 2000;
const MYSQL_SAVE_INTERVAL_MS = 60 * 1000;
let liveAppendTimer = null;
let mysqlFetchTimer = null;
let lastLiveAppendAt = 0;
let lastMysqlSaveAt = 0;
let mysqlSaveInFlight = false;
let listenersAttached = false;
let firebaseReadyHandler = null;

const liveData = ref({
    voltage: 0,
    current: 0,
    power: 0,
    energy: 0
});

// Full history for CSV export (stores objects with timestamps)
const fullHistory = ref([]);

// Rolling history for charts (last 50 points)
const history = ref({
    voltage: [],
    current: [],
    power: [],
    energy: [],
    labels: []
});

// --- Browser Notifications ---
const requestNotificationPermission = () => {
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
};

const sendPushNotification = (title, body) => {
    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification(`WattWise: ${title}`, { body, icon: '/favicon.ico' });
    }
};

const csvEscape = (value) => {
    const normalized = value === null || value === undefined ? '' : String(value);
    return `"${normalized.replace(/"/g, '""')}"`;
};

const fetchAllDeviceHistoryRows = async () => {
    const response = await axios.get('/api/iot/history', {
        params: { device_id: props.deviceId, hours: 24, interval_minutes: 30 },
    });

    if (!response.data?.success || !Array.isArray(response.data.data)) {
        return [];
    }

    return response.data.data.map((entry) => [
        entry.full_timestamp ?? entry.timestamp ?? '',
        entry.voltage ?? 0,
        entry.current ?? 0,
        entry.power ?? 0,
        entry.energy ?? 0,
    ]);
};

// --- CSV Export Logic ---
const exportToCSV = async () => {
    if (exportingCsv.value) return;

    exportingCsv.value = true;
    try {
        const headers = ['Timestamp', 'Voltage (V)', 'Current (A)', 'Power (W)', 'Total Energy (kWh)'];
        const rows = await fetchAllDeviceHistoryRows();
        if (rows.length === 0) return;

        const deviceLabel = props.deviceName || `Device ${props.deviceId}`;
        const exportedAt = new Date().toISOString();

        const reportRows = [
            ['Report', 'Live Monitoring Energy Export'],
            ['Device', deviceLabel],
            ['Device ID', `PLUG${props.deviceId}`],
            ['Exported At', exportedAt],
            [],
            headers,
            ...rows,
            [],
            ['Footer'],
            ['Total Records', rows.length],
        ];

        const csvContent = reportRows
            .map((row) => row.map(csvEscape).join(','))
            .join('\n');

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const dateStr = new Date().toISOString().split('T')[0];

        link.href = URL.createObjectURL(blob);
        link.setAttribute('download', `device_${props.deviceId}_energy_report_${dateStr}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } finally {
        exportingCsv.value = false;
    }
};

// --- Alert Logic ---
const checkThresholds = (data) => {
    const newAlerts = [];
    
    Object.keys(THRESHOLDS).forEach(key => {
        const val = data[key];
        const t = THRESHOLDS[key];
        
        if (val <= t.critical && key !== 'current' && key !== 'power') { // Drop is bad for V, F, PF
             addAlert(newAlerts, key, 'critical', val);
        } else if ((key === 'current' || key === 'power') && val >= t.critical) { // Rise is bad for A, W
             addAlert(newAlerts, key, 'critical', val);
        } else if (val <= t.warning && key !== 'current' && key !== 'power') {
             addAlert(newAlerts, key, 'warning', val);
        } else if ((key === 'current' || key === 'power') && val >= t.warning) {
             addAlert(newAlerts, key, 'warning', val);
        }
    });

    activeAlerts.value = newAlerts;
};

const addAlert = (list, key, level, val) => {
    const t = THRESHOLDS[key];
    const alert = {
        key,
        level,
        message: `${t.label} is ${level}: ${val}${t.unit}`
    };
    list.push(alert);
    
    if (level === 'critical') {
        sendPushNotification('Critical Alert', alert.message);
    }
};

const saveLiveReadingToMysql = async (reading) => {
    const now = Date.now();
    if (mysqlSaveInFlight || now - lastMysqlSaveAt < MYSQL_SAVE_INTERVAL_MS) return;

    lastMysqlSaveAt = now;
    mysqlSaveInFlight = true;

    try {
        await axios.post('/api/iot/energy', {
            device_id: `PLUG${props.deviceId}`,
            voltage: reading.voltage,
            current: reading.current,
            power: reading.power,
            energy: reading.energy,
        });
    } catch (error) {
        console.error('Failed to save live energy reading:', error);
    } finally {
        mysqlSaveInFlight = false;
    }
};

const fetchLiveReadingForMysql = async () => {
    if (!window.db) return;

    try {
        const snapshot = await window.db.ref(`Live/PLUG${props.deviceId}`).once('value');
        const data = snapshot.val();
        if (!data) return;

        await saveLiveReadingToMysql({
            voltage: toFiniteNumber(data.voltage, 0),
            current: toFiniteNumber(data.current, 0),
            power: sanitizePower(data.power, 0),
            energy: toFiniteNumber(data.energy, 0),
        });
    } catch (error) {
        console.error('Failed to fetch Firebase live reading for MySQL:', error);
    }
};

// --- Firebase Logic ---
const initListeners = () => {
    requestNotificationPermission();
    if (!window.db) return;
    if (listenersAttached) return;
    listenersAttached = true;

    const monitorPath = `Live/PLUG${props.deviceId}`;
    
    window.db.ref(monitorPath).on('value', (snapshot) => {
        const data = snapshot.val();
        if (!data) return;

        const timestamp = new Date().toLocaleString();

        // Update current values
        liveData.value = {
            voltage: toFiniteNumber(data.voltage, 0),
            current: toFiniteNumber(data.current, 0),
            power: sanitizePower(data.power, liveData.value.power ?? 0),
            energy: toFiniteNumber(data.energy, 0)
        };

        checkThresholds(liveData.value);
        lastUpdate.value = new Date().toLocaleTimeString();
        
        // Keep live chart moving even with sparse Firebase updates.
        appendLivePoint(true);

        // Store full data point for CSV
        fullHistory.value.push({
            timestamp,
            ...liveData.value
        });
        if (fullHistory.value.length > 500) fullHistory.value.shift(); // Keep reasonable buffer

        loading.value = false;
    });

    startLiveTicker();
    fetchLiveReadingForMysql();
    mysqlFetchTimer = setInterval(fetchLiveReadingForMysql, MYSQL_SAVE_INTERVAL_MS);
};
const fetchHistory = async () => {
    loading.value = true;
    try {
        const hours = viewMode.value === '1h' ? 1 : 24;
        const response = await axios.get('/api/iot/history', {
            params: { device_id: props.deviceId, hours }
        });

        if (response.data.success) {
            const data = response.data.data;
            history.value = {
                voltage: data.map(d => d.voltage),
                current: data.map(d => d.current),
                power: data.map(d => sanitizePower(d.power, 0)),
                energy: data.map(d => d.energy),
                labels: data.map(d => d.timestamp)
            };
        }
    } catch (error) {
        console.error('Failed to fetch history:', error);
    } finally {
        loading.value = false;
    }
};

watch(viewMode, (newMode) => {
    if (newMode === 'live') {
        history.value = {
                voltage: [], current: [], power: [], energy: [], labels: []
        };
        lastLiveAppendAt = 0;
        appendLivePoint(true);
        startLiveTicker();
    } else {
        stopLiveTicker();
        fetchHistory();
    }
});

const updateHistory = (key, value) => {
    history.value[key].push(value);
    if (history.value[key].length > 50) history.value[key].shift();
};

const appendLivePoint = (force = false) => {
    if (viewMode.value !== 'live') return;

    const now = Date.now();
    if (!force && now - lastLiveAppendAt < Math.floor(LIVE_APPEND_INTERVAL_MS * 0.8)) {
        return;
    }

    updateHistory('voltage', Number(liveData.value.voltage ?? 0));
    updateHistory('current', Number(liveData.value.current ?? 0));
    updateHistory('power', sanitizePower(liveData.value.power, 0));
    updateHistory('energy', Number(liveData.value.energy ?? 0));

    history.value.labels.push(new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }));
    if (history.value.labels.length > 50) history.value.labels.shift();

    lastLiveAppendAt = now;
};

const startLiveTicker = () => {
    if (liveAppendTimer) return;

    liveAppendTimer = setInterval(() => {
        appendLivePoint();
    }, LIVE_APPEND_INTERVAL_MS);
};

const stopLiveTicker = () => {
    if (!liveAppendTimer) return;

    clearInterval(liveAppendTimer);
    liveAppendTimer = null;
};

const destroyListeners = () => {
    stopLiveTicker();
    if (mysqlFetchTimer) {
        clearInterval(mysqlFetchTimer);
        mysqlFetchTimer = null;
    }
    listenersAttached = false;

    if (window.db) {
        window.db.ref(`Live/PLUG${props.deviceId}`).off();
    }
};

onMounted(() => {
    firebaseReadyHandler = () => initListeners();
    window.addEventListener('firebase-ready', firebaseReadyHandler);
    initListeners();
});
onUnmounted(() => {
    if (firebaseReadyHandler) window.removeEventListener('firebase-ready', firebaseReadyHandler);
    destroyListeners();
});

// --- Chart Configurations ---
const axisLabelColor = computed(() => '#ffffff');

const commonOptions = computed(() => ({
    chart: { 
        toolbar: { show: false }, 
        animations: { enabled: true, easing: 'linear', dynamicAnimation: { speed: 1000 } }, 
        background: 'transparent',
        foreColor: axisLabelColor.value,
    },
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2 },
    grid: {
        borderColor: isDark.value ? '#334155' : '#e2e8f0',
        strokeDashArray: 4,
        padding: { bottom: 14 }
    },
    theme: { mode: isDark.value ? 'dark' : 'light' },
    xaxis: { 
        categories: history.value.labels,
        tickAmount: 6,
        axisTicks: { show: true },
        labels: {
            show: false,
            rotate: 0,
            rotateAlways: false,
            hideOverlappingLabels: true,
            trim: true,
            offsetY: 4,
            maxHeight: 56,
            style: { colors: axisLabelColor.value, fontSize: '10px' },
        },
        axisBorder: { show: false } 
    },
    yaxis: {
        labels: {
            style: { colors: axisLabelColor.value, fontSize: '11px' },
            formatter: formatPowerAxis
        }
    },
    tooltip: { theme: isDark.value ? 'dark' : 'light' }
}));

const voltageChartOptions = computed(() => ({
    ...commonOptions.value,
    colors: ['#f59e0b'],
    yaxis: {
        min: 180,
        max: 260,
        labels: {
            style: { colors: '#ffffff' },
            formatter: (val) => Number(val).toFixed(0)
        }
    },
    annotations: { yaxis: [{ y: THRESHOLDS.voltage.warning, borderColor: '#f59e0b', label: { text: 'Warning', style: { color: '#fff', background: '#f59e0b' } } }, { y: THRESHOLDS.voltage.critical, borderColor: '#ef4444', label: { text: 'Critical', style: { color: '#fff', background: '#ef4444' } } }] }
}));

const currentChartOptions = computed(() => ({
    ...commonOptions.value,
    colors: ['#6366f1'],
    yaxis: {
        labels: {
            style: { colors: axisLabelColor.value, fontSize: '11px' },
            formatter: (val) => Number(val).toFixed(3)
        }
    }
}));

// --- Threshold Helpers ---
const getStatusClass = (key) => {
    const alert = activeAlerts.value.find(a => a.key === key);
    if (!alert) return 'text-emerald-600 dark:text-emerald-400 border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900';
    if (alert.level === 'critical') return 'text-red-600 dark:text-red-400 border-red-500 dark:border-red-600 animate-pulse-red bg-red-50 dark:bg-red-950/20 shadow-[0_0_15px_rgba(239,68,68,0.2)]';
    if (alert.level === 'warning') return 'text-amber-600 dark:text-amber-400 border-amber-400 dark:border-amber-600 bg-amber-50 dark:bg-amber-950/20';
    return 'text-emerald-600 dark:text-emerald-400 border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900';
};
</script>

<style>
@keyframes pulse-red {
    0%, 100% { border-color: #ef4444; }
    50% { border-color: #7f1d1d; }
}

.animate-pulse-red {
    animation: pulse-red 1s infinite;
}

/* ApexCharts can apply inline SVG fills; force readable white text. */
.apexcharts-xaxis-label,
.apexcharts-yaxis-label,
.apexcharts-text tspan {
    fill: #ffffff !important;
    color: #ffffff !important;
    opacity: 1 !important;
}

.apexcharts-xaxistooltip,
.apexcharts-yaxistooltip {
    color: #ffffff !important;
    border-color: #334155 !important;
    background: #0f172a !important;
}

.apexcharts-xaxistooltip-text,
.apexcharts-yaxistooltip-text {
    color: #ffffff !important;
}

/* Hide noisy chart controls/ticks that make the UI unreadable. */
.apexcharts-toolbar {
    display: none !important;
}

.apexcharts-xaxis-texts-g,
.apexcharts-xaxis-tick,
.apexcharts-xaxis line {
    display: none !important;
}
</style>

<template>
    <div class="relative bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm transition-colors duration-300">
        <!-- Alert Banner -->
        <div v-if="activeAlerts.length > 0" class="bg-red-50 dark:bg-red-950/30 border-b border-red-200 dark:border-red-900/50 p-3 overflow-hidden">
            <div class="flex items-center gap-4 animate-marquee whitespace-nowrap">
                <div v-for="(alert, i) in activeAlerts" :key="i" class="flex items-center gap-2 text-red-600 dark:text-red-400 font-bold text-sm">
                    <Activity class="w-4 h-4 animate-pulse" />
                    {{ alert.message.toUpperCase() }}
                    <span v-if="i < activeAlerts.length - 1" class="mx-4 text-red-300 dark:text-red-900">|</span>
                </div>
            </div>
        </div>

        <!-- Dashboard Header -->
        <div class="px-6 py-4 pr-16 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-cyan-100 dark:bg-cyan-900/40 rounded-lg">
                    <Activity class="w-5 h-5 text-cyan-600 dark:text-cyan-400" />
                </div>
                <div>
                    <h3 class="text-gray-900 dark:text-gray-100 font-bold">{{ viewMode === 'live' ? 'Live Monitoring' : viewMode.toUpperCase() + ' History' }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Device ID: {{ deviceId }} • {{ lastUpdate ? 'Last updated ' + lastUpdate : 'Waiting for data...' }}</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <!-- View Toggle -->
                <div class="flex bg-gray-100 dark:bg-gray-800 p-1 rounded-lg border border-gray-200 dark:border-gray-700">
                    <button 
                        v-for="mode in ['live', '1h', '24h']" 
                        :key="mode"
                        @click="viewMode = mode"
                        class="px-3 py-1.5 text-xs font-medium rounded-md transition-all capitalize"
                        :class="viewMode === mode ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                    >
                        {{ mode }}
                    </button>
                </div>
            </div>
        </div>

        <div class="p-6 space-y-8">
            <!-- Metric Cards Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div v-for="(val, key) in liveData" :key="key" 
                    class="group relative p-4 rounded-xl border transition-all duration-300 flex flex-col justify-between h-24 shadow-sm hover:shadow-md cursor-help"
                    :class="getStatusClass(key)">
                    <p class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400 font-bold">{{ key.replace('_', ' ') }} <span aria-hidden="true" class="text-cyan-500">ⓘ</span></p>
                    <div class="flex items-baseline gap-1">
                        <span class="text-xl font-bold">
                            {{ typeof val === 'number' ? val.toFixed(1) : val }}
                        </span>
                        <span class="text-[10px] opacity-60 font-medium uppercase text-gray-500 dark:text-gray-400">
                            {{ key === 'voltage' ? 'V' : key === 'current' ? 'A' : key === 'power' ? 'W' : key === 'energy' ? 'kWh' : '' }}
                        </span>
                    </div>
                    <div role="tooltip" class="pointer-events-none absolute z-30 left-1/2 bottom-[calc(100%+0.5rem)] w-56 -translate-x-1/2 rounded-lg bg-gray-900 px-3 py-2 text-xs normal-case leading-relaxed text-white shadow-xl opacity-0 transition-opacity duration-150 group-hover:opacity-100 group-focus-within:opacity-100 dark:bg-gray-700">
                        {{ METRIC_HELP[key] }}
                        <span class="absolute left-1/2 top-full -translate-x-1/2 border-x-4 border-t-4 border-x-transparent border-t-gray-900 dark:border-t-gray-700"></span>
                    </div>
                </div>
            </div>

            <!-- Main Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Voltage History -->
                <div class="bg-white dark:bg-gray-900 rounded-xl p-5 border border-gray-200 dark:border-gray-800 shadow-sm transition-colors duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                            <Zap class="w-4 h-4 text-amber-500" /> Voltage Stability (V)
                        </h4>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ viewMode === 'live' ? 'Live Stream' : 'Archive Data' }}</span>
                    </div>
                    <VueApexCharts type="line" height="200" :options="voltageChartOptions" :series="[{ name: 'Voltage', data: history.voltage }]" />
                    <p class="mt-2 text-xs text-gray-400 dark:text-gray-400">Timeline: newest reading is on the right. Updates every 2 seconds in Live mode.</p>
                </div>

                <!-- Current History -->
                <div class="bg-white dark:bg-gray-900 rounded-xl p-5 border border-gray-200 dark:border-gray-800 shadow-sm transition-colors duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                            <Activity class="w-4 h-4 text-indigo-500" /> Current Load (A)
                        </h4>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ viewMode === 'live' ? 'Live Stream' : 'Archive Data' }}</span>
                    </div>
                    <VueApexCharts type="line" height="200" :options="currentChartOptions" :series="[{ name: 'Current', data: history.current }]" />
                    <p class="mt-2 text-xs text-gray-400 dark:text-gray-400">Tip: if the line looks flat, the current is stable. Spikes indicate sudden load changes.</p>
                </div>
            </div>

            <!-- Bottom Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Power Consumption -->
                <div class="bg-white dark:bg-gray-900 rounded-xl p-5 border border-gray-200 dark:border-gray-800 shadow-sm transition-colors duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                            <BarChart3 class="w-4 h-4 text-cyan-600" /> Active Power
                        </h4>
                        <div class="flex items-center gap-6">
                            <div class="flex flex-col items-center">
                                <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">Highest</span>
                                <span class="text-lg font-black text-cyan-700 dark:text-cyan-400">{{ formatPowerShort(Math.max(...history.power.filter(v => typeof v === 'number' && !isNaN(v)))) }}</span>
                            </div>
                            <div class="flex flex-col items-center">
                                <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">Lowest</span>
                                <span class="text-lg font-black text-gray-400 dark:text-gray-500">{{ formatPowerShort(Math.min(...history.power.filter(v => typeof v === 'number' && !isNaN(v)))) }}</span>
                            </div>
                        </div>
                    </div>
                    <VueApexCharts type="area" height="200" :options="{ ...commonOptions.value, colors: ['#06b6d4'], fill: { gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.1 } } }" :series="[{ name: 'Power', data: history.power }]" />
                    <p class="mt-2 text-xs text-gray-400 dark:text-gray-400">Power trend over recent samples. Higher plateaus mean the device is consuming more watts.</p>
                </div>

                <!-- Total Energy -->
                <div class="bg-gradient-to-br from-cyan-50 to-blue-50 dark:from-cyan-950 dark:to-blue-950 rounded-xl p-6 border border-cyan-100 dark:border-cyan-900/50 flex flex-col items-center justify-center text-center shadow-sm transition-colors duration-300">
                    <div class="w-16 h-16 bg-cyan-100 dark:bg-cyan-900 rounded-full flex items-center justify-center mb-4">
                        <ShieldCheck class="w-8 h-8 text-cyan-600 dark:text-cyan-400" />
                    </div>
                    <p class="text-sm text-cyan-800 dark:text-cyan-300 font-medium mb-1">Cumulative Energy</p>
                    <div class="flex items-baseline gap-2">
                        <h2 class="text-5xl font-black text-cyan-950 dark:text-cyan-50">{{ liveData.energy.toFixed(2) }}</h2>
                        <span class="text-xl text-cyan-700 dark:text-cyan-400 font-bold">kWh</span>
                    </div>
                    <p class="text-xs text-cyan-600/70 dark:text-cyan-400/50 mt-4">Safe consumption levels maintained</p>
                </div>

            </div>
        </div>

    </div>
</template>
