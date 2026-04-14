<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import axios from 'axios';
import VueApexCharts from 'vue3-apexcharts';
import { Zap, Activity, FastForward, Target, BarChart3, Gauge, Clock, ShieldCheck } from 'lucide-vue-next';
import { useTheme } from '@/Composables/useTheme';

const { isDark } = useTheme();

const props = defineProps({
    deviceId: { type: [String, Number], required: true },
    deviceName: { type: String, default: 'Device' }
});

// --- Constants & Thresholds ---
const THRESHOLDS = {
    voltage: { warning: 210, critical: 200, unit: 'V', label: 'Voltage' },
    current: { warning: 15, critical: 20, unit: 'A', label: 'Current' },
    frequency: { warning: 59, critical: 58, unit: 'Hz', label: 'Frequency' },
    pf: { warning: 0.85, critical: 0.75, unit: '', label: 'Power Factor' },
    power: { warning: 3000, critical: 5000, unit: 'W', label: 'Current Power' },
};

// --- State Management ---
const loading = ref(true);
const lastUpdate = ref(null);
const viewMode = ref('live'); 
const activeAlerts = ref([]);
const exportingCsv = ref(false);

const liveData = ref({
    voltage: 0,
    current: 0,
    frequency: 0,
    pf: 0,
    power: 0,
    optimal: 0,
    energy: 0
});

// Full history for CSV export (stores objects with timestamps)
const fullHistory = ref([]);

// Rolling history for charts (last 50 points)
const history = ref({
    voltage: [],
    current: [],
    frequency: [],
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
    const rows = [];
    const deviceKey = `PLUG${props.deviceId}`;

    if (window.db) {
        const snapshot = await window.db.ref('History').once('value');
        const historyData = snapshot.val() || {};
        const timestamps = Object.keys(historyData).sort((a, b) => Number(a) - Number(b));

        timestamps.forEach((ts) => {
            const entry = historyData[ts]?.[deviceKey];
            if (!entry) return;

            rows.push([
                new Date(Number(ts) * 1000).toLocaleString(),
                entry.voltage ?? 0,
                entry.current ?? 0,
                entry.frequency ?? 0,
                entry.power_factor ?? entry.pf ?? 0,
                entry.power ?? 0,
                entry.optimal ?? 0,
                entry.energy ?? 0,
            ]);
        });
    }

    if (rows.length === 0 && fullHistory.value.length > 0) {
        fullHistory.value.forEach((entry) => {
            rows.push([
                entry.timestamp,
                entry.voltage ?? 0,
                entry.current ?? 0,
                entry.frequency ?? 0,
                entry.pf ?? 0,
                entry.power ?? 0,
                entry.optimal ?? 0,
                entry.energy ?? 0,
            ]);
        });
    }

    return rows;
};

// --- CSV Export Logic ---
const exportToCSV = async () => {
    if (exportingCsv.value) return;

    exportingCsv.value = true;
    try {
        const headers = ['Timestamp', 'Voltage (V)', 'Current (A)', 'Frequency (Hz)', 'Power Factor', 'Current Power (W)', 'Optimal (%)', 'Total Energy (kWh)'];
        const rows = await fetchAllDeviceHistoryRows();
        if (rows.length === 0) return;

        const csvContent = [headers, ...rows]
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

// --- Firebase Logic ---
const initListeners = () => {
    requestNotificationPermission();
    if (!window.db) return;

    const monitorPath = `Live/PLUG${props.deviceId}`;
    
    window.db.ref(monitorPath).on('value', (snapshot) => {
        const data = snapshot.val();
        if (!data) return;

        const timestamp = new Date().toLocaleString();

        // Update current values
        liveData.value = {
            voltage: data.voltage || 0,
            current: data.current || 0,
            frequency: data.frequency || 0,
            pf: data.power_factor || data.pf || 0,
            power: data.power || 0,
            optimal: data.optimal || 0,
            energy: data.energy || 0
        };

        checkThresholds(liveData.value);
        lastUpdate.value = new Date().toLocaleTimeString();
        
        // Update history
        const timeLabel = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        
        if (history.value.labels.length > 50) history.value.labels.shift();

        // Only update charts in real-time if in 'live' mode
        if (viewMode.value === 'live') {
            updateHistory('voltage', liveData.value.voltage);
            updateHistory('current', liveData.value.current);
            updateHistory('frequency', liveData.value.frequency);
            updateHistory('power', liveData.value.power);
            updateHistory('energy', liveData.value.energy);
            
            history.value.labels.push(timeLabel);
            if (history.value.labels.length > 50) history.value.labels.shift();
        }

        // Store full data point for CSV
        fullHistory.value.push({
            timestamp,
            ...liveData.value
        });
        if (fullHistory.value.length > 500) fullHistory.value.shift(); // Keep reasonable buffer

        loading.value = false;
    });

    // --- Added: History Listener to pre-populate graphs ---
    const historyPath = 'History';
    window.db.ref(historyPath).limitToLast(50).on('value', (snapshot) => {
        const historyData = snapshot.val();
        if (!historyData) return;

        const deviceKey = `PLUG${props.deviceId}`;
        const timestamps = Object.keys(historyData).sort();

        // Clear existing rolling history to rebuild from Firebase
        history.value = {
            voltage: [],
            current: [],
            frequency: [],
            power: [],
            energy: [],
            labels: []
        };

        timestamps.forEach(ts => {
            const entry = historyData[ts];
            const data = entry[deviceKey];

            if (data) {
                const timeLabel = new Date(parseInt(ts) * 1000).toLocaleTimeString([], { 
                    hour: '2-digit', 
                    minute: '2-digit' 
                });
                
                history.value.voltage.push(data.voltage || 0);
                history.value.current.push(data.current || 0);
                history.value.frequency.push(data.frequency || 0);
                history.value.power.push(data.power || 0);
                history.value.energy.push(data.energy || 0);
                history.value.labels.push(timeLabel);
            }
        });
    });
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
                frequency: data.map(d => d.frequency),
                power: data.map(d => d.power),
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
        // Re-request last 50 points from Firebase to restore live view
        if (window.db) {
            window.db.ref('History').limitToLast(50).once('value', (snapshot) => {
                const historyData = snapshot.val();
                if (!historyData) return;
                const deviceKey = `PLUG${props.deviceId}`;
                const timestamps = Object.keys(historyData).sort();
                history.value = { voltage: [], current: [], frequency: [], power: [], energy: [], labels: [] };
                timestamps.forEach(ts => {
                    const data = historyData[ts][deviceKey];
                    if (data) {
                        history.value.voltage.push(data.voltage || 0);
                        history.value.current.push(data.current || 0);
                        history.value.frequency.push(data.frequency || 0);
                        history.value.power.push(data.power || 0);
                        history.value.energy.push(data.energy || 0);
                        history.value.labels.push(new Date(parseInt(ts) * 1000).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }));
                    }
                });
            });
        }
    } else {
        fetchHistory();
    }
});

const updateHistory = (key, value) => {
    history.value[key].push(value);
    if (history.value[key].length > 50) history.value[key].shift();
};

const destroyListeners = () => {
    if (window.db) {
        window.db.ref(`Live/PLUG${props.deviceId}`).off();
        window.db.ref('History').off();
    }
};

onMounted(() => initListeners());
onUnmounted(() => destroyListeners());

// --- Chart Configurations ---
const commonOptions = computed(() => ({
    chart: { 
        toolbar: { show: false }, 
        animations: { enabled: true, easing: 'linear', dynamicAnimation: { speed: 1000 } }, 
        background: 'transparent' 
    },
    stroke: { curve: 'smooth', width: 2 },
    grid: { borderColor: isDark.value ? '#334155' : '#e2e8f0', strokeDashArray: 4 },
    theme: { mode: isDark.value ? 'dark' : 'light' },
    xaxis: { 
        labels: { show: false }, 
        axisBorder: { show: false } 
    },
    tooltip: { theme: isDark.value ? 'dark' : 'light' }
}));

const voltageChartOptions = computed(() => ({
    ...commonOptions.value,
    colors: ['#f59e0b'],
    yaxis: { min: 180, max: 260, labels: { style: { colors: isDark.value ? '#94a3b8' : '#64748b' } } },
    annotations: { yaxis: [{ y: THRESHOLDS.voltage.warning, borderColor: '#f59e0b', label: { text: 'Warning', style: { color: '#fff', background: '#f59e0b' } } }, { y: THRESHOLDS.voltage.critical, borderColor: '#ef4444', label: { text: 'Critical', style: { color: '#fff', background: '#ef4444' } } }] }
}));

const powerFactorOptions = computed(() => ({
    chart: { type: 'radialBar', height: 250, background: 'transparent' },
    theme: { mode: isDark.value ? 'dark' : 'light' },
    plotOptions: {
        radialBar: {
            startAngle: -135, endAngle: 135,
            hollow: { size: '70%' },
            track: { background: isDark.value ? '#1e293b' : '#f1f5f9', strokeWidth: '97%' },
            dataLabels: {
                name: { show: true, color: isDark.value ? '#94a3b8' : '#64748b', fontSize: '13px', offsetY: -10 },
                value: { color: isDark.value ? '#f1f5f9' : '#334155', fontSize: '30px', show: true, formatter: (val) => (val / 100).toFixed(2) }
            }
        }
    },
    fill: { gradient: { shade: isDark.value ? 'dark' : 'light', type: 'horizontal', gradientToColors: ['#06b6d4'], stops: [0, 100] } },
    colors: ['#6366f1'],
    labels: ['Power Factor']
}));

const optimalOptions = computed(() => ({
    ...powerFactorOptions.value,
    colors: ['#10b981'],
    plotOptions: {
        ...powerFactorOptions.value.plotOptions,
        radialBar: {
            ...powerFactorOptions.value.plotOptions.radialBar,
            dataLabels: {
                ...powerFactorOptions.value.plotOptions.radialBar.dataLabels,
                value: { color: isDark.value ? '#f1f5f9' : '#334155', fontSize: '30px', show: true, formatter: (val) => val + '%' }
            }
        }
    },
    labels: ['Optimal Efficiency']
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
</style>

<template>
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm transition-colors duration-300">
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
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800 flex flex-wrap items-center justify-between gap-4">
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
                <!-- CSV Export -->
                <button 
                    @click="exportToCSV"
                    :disabled="exportingCsv"
                    class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-xs font-bold transition-all border border-gray-300 dark:border-gray-700 shadow-sm disabled:opacity-60 disabled:cursor-not-allowed"
                >
                    <BarChart3 class="w-4 h-4" />
                    {{ exportingCsv ? 'EXPORTING...' : 'EXPORT TO CSV' }}
                </button>

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
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
                <div v-for="(val, key) in liveData" :key="key" 
                    class="p-4 rounded-xl border transition-all duration-300 flex flex-col justify-between h-24 shadow-sm hover:shadow-md"
                    :class="getStatusClass(key)">
                    <p class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400 font-bold">{{ key.replace('_', ' ') }}</p>
                    <div class="flex items-baseline gap-1">
                        <span class="text-xl font-bold">
                            {{ typeof val === 'number' ? (key === 'pf' ? val.toFixed(2) : val.toFixed(1)) : val }}
                        </span>
                        <span class="text-[10px] opacity-60 font-medium uppercase text-gray-500 dark:text-gray-400">
                            {{ key === 'voltage' ? 'V' : key === 'current' ? 'A' : key === 'frequency' ? 'Hz' : key === 'power' ? 'W' : key === 'energy' ? 'kWh' : key === 'optimal' ? '%' : '' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Main Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Voltage History -->
                <div class="bg-white dark:bg-gray-900 rounded-xl p-5 border border-gray-200 dark:border-gray-800 shadow-sm border transition-colors duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                            <Zap class="w-4 h-4 text-amber-500" /> Voltage Stability (V)
                        </h4>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ viewMode === 'live' ? 'Live Stream' : 'Archive Data' }}</span>
                    </div>
                    <VueApexCharts type="line" height="200" :options="voltageChartOptions" :series="[{ name: 'Voltage', data: history.voltage }]" />
                </div>

                <!-- Current History -->
                <div class="bg-white dark:bg-gray-900 rounded-xl p-5 border border-gray-200 dark:border-gray-800 shadow-sm border transition-colors duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                            <Activity class="w-4 h-4 text-indigo-500" /> Current Load (A)
                        </h4>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ viewMode === 'live' ? 'Live Stream' : 'Archive Data' }}</span>
                    </div>
                    <VueApexCharts type="line" height="200" :options="{ ...commonOptions.value, colors: ['#6366f1'] }" :series="[{ name: 'Current', data: history.current }]" />
                </div>
            </div>

            <!-- Gauges and Secondary Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Power Factor Gauge -->
                <div class="bg-white dark:bg-gray-900 rounded-xl p-5 border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col items-center justify-center transition-colors duration-300">
                    <VueApexCharts type="radialBar" height="250" :options="powerFactorOptions" :series="[liveData.pf * 100]" />
                </div>

                <!-- Optimal Efficiency Gauge -->
                <div class="bg-white dark:bg-gray-900 rounded-xl p-5 border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col items-center justify-center transition-colors duration-300">
                    <VueApexCharts type="radialBar" height="250" :options="optimalOptions" :series="[liveData.optimal]" />
                </div>

                <!-- Total Energy Card -->
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

            <!-- Bottom Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Power Consumption -->
                <div class="bg-white dark:bg-gray-900 rounded-xl p-5 border border-gray-200 dark:border-gray-800 shadow-sm transition-colors duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                            <BarChart3 class="w-4 h-4 text-cyan-600" /> Active Power (W)
                        </h4>
                    </div>
                    <VueApexCharts type="area" height="200" :options="{ ...commonOptions.value, colors: ['#06b6d4'], fill: { gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.1 } } }" :series="[{ name: 'Power', data: history.power }]" />
                </div>

                <!-- Frequency -->
                <div class="bg-white dark:bg-gray-900 rounded-xl p-5 border border-gray-200 dark:border-gray-800 shadow-sm transition-colors duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                            <FastForward class="w-4 h-4 text-rose-500" /> Line Frequency (Hz)
                        </h4>
                    </div>
                    <VueApexCharts type="line" height="200" :options="{ ...commonOptions.value, colors: ['#f43f5e'], yaxis: { min: 59, max: 61, labels: { style: { colors: 'currentColor' } } } }" :series="[{ name: 'Frequency', data: history.frequency }]" />
                </div>
            </div>
        </div>
    </div>
</template>
