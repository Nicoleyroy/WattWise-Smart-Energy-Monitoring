<script setup>
import { Head } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import axios from 'axios';
import AlertCard from '@/Components/AlertCard.vue';
import DeviceCard from '@/Components/DeviceCard.vue';
import EnergyGraph from '@/Components/EnergyGraph.vue';
import Sidebar from '@/Components/Sidebar.vue';
import SummaryCard from '@/Components/SummaryCard.vue';

const summaryCards = ref([
    {
        id: 1,
        title: 'Current Power',
        value: '0W',
        subtitle: 'Active consumption',
        tone: 'solid-blue',
        icon: 'bolt',
    },
    {
        id: 2,
        title: 'This Month',
        value: '0 kWh',
        subtitle: 'Accumulated monthly usage',
        tone: 'solid-green',
        icon: 'record',
    },
    {
        id: 3,
        title: 'Today Usage',
        value: '0 kWh',
        subtitle: 'Current daily consumption',
        tone: 'light',
        icon: 'usage',
        iconClass:
            'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400',
    },
    {
        id: 4,
        title: 'Configured Limits',
        value: '0',
        subtitle: 'Devices with active limits',
        tone: 'light',
        icon: 'threshold',
        iconClass:
            'bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400',
    },
]);

const maintenanceAlerts = ref([]);
const notifiedDevices = ref(new Set());
const latestEnergy = ref({});
const latestPlugs = ref({});
const uptimeSince = new Map();
const powerStateKnown = new Set();
const visibleDeviceIds = ref(new Set([1]));
const deviceSectionElements = new Map();
let uptimeTimer = null;
let summaryRefreshTimer = null;
let deviceObserver = null;

const formatUptime = (seconds) => {
    if (!Number.isFinite(seconds) || seconds < 0) return 'N/A';
    if (seconds < 60) return `${Math.floor(seconds)} seconds`;
    const totalMinutes = Math.floor(seconds / 60);
    const days = Math.floor(totalMinutes / 1440);
    const hours = Math.floor((totalMinutes % 1440) / 60);
    const minutes = totalMinutes % 60;
    if (days > 0) return `${days} ${days === 1 ? 'day' : 'days'}${hours ? ` ${hours} ${hours === 1 ? 'hour' : 'hours'}` : ''}`;
    if (hours > 0) return `${hours} ${hours === 1 ? 'hour' : 'hours'}`;
    return `${minutes} ${minutes === 1 ? 'minute' : 'minutes'}`;
};

const updateDeviceUptime = (device, isOn) => {
    if (!isOn) {
        uptimeSince.delete(device.id);
        device.uptime = 'N/A';
        return;
    }

    if (!uptimeSince.has(device.id)) {
        const now = Date.now();
        uptimeSince.set(device.id, now);
        if (window.db) {
            window.db.ref('Uptime').transaction((current) => {
                if (current && (current.online_since || current.uptime_seconds)) {
                    return current;
                }
                return {
                    online_since: now,
                    last_updated: now,
                };
            });
        }
    }

    const startedAt = uptimeSince.get(device.id);
    if (startedAt) {
        device.uptime = formatUptime((Date.now() - startedAt) / 1000);
    }
};

const devices = ref([
    {
        id: 1,
        name: 'Plug 1',
        status: 'Offline',
        currentPower: 0,
        dailyLimit: 0,
        dailyKwh: 0,
        monthlyKwh: 0,
        usageKwh: 0,
        uptime: 'N/A',
        thresholdType: 'daily',
        isOn: false,
        lastUpdated: null,
    },
    {
        id: 2,
        name: 'Plug 2',
        status: 'Offline',
        currentPower: 0,
        dailyLimit: 0,
        dailyKwh: 0,
        monthlyKwh: 0,
        usageKwh: 0,
        uptime: 'N/A',
        thresholdType: 'daily',
        isOn: false,
        lastUpdated: null,
    },
]);

const getUsageByType = (energy, plug, thresholdType) => {
    const type = ['daily', 'weekly', 'monthly'].includes(thresholdType)
        ? thresholdType
        : 'daily';
    if (type === 'weekly') {
        return Number(energy?.weekly_kwh ?? plug?.weekly_kwh ?? 0);
    }
    if (type === 'monthly') {
        return Number(energy?.monthly_kwh ?? plug?.monthly_kwh ?? 0);
    }
    return Number(energy?.daily_kwh ?? plug?.daily_kwh ?? 0);
};

const getLimitReachedStatus = (thresholdType) => {
    const type = ['daily', 'weekly', 'monthly'].includes(thresholdType)
        ? thresholdType
        : 'daily';
    return `${type.charAt(0).toUpperCase() + type.slice(1)} Limit Reached`;
};

const toFiniteNumber = (value) => {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : 0;
};

const MAX_VALID_POWER_W = 1_000_000;

const sanitizePowerWatts = (value, fallback = 0) => {
    const parsed = Number(value);
    if (!Number.isFinite(parsed)) {
        return toFiniteNumber(fallback);
    }

    // Drop obvious bad telemetry spikes in the million-W range.
    if (Math.abs(parsed) >= MAX_VALID_POWER_W) {
        return toFiniteNumber(fallback);
    }

    return parsed;
};

const formatPower = (watts) => {
    const value = toFiniteNumber(watts);
    const absolute = Math.abs(value);

    if (absolute >= 1_000_000_000) {
        return `${(value / 1_000_000_000).toFixed(2)} GW`;
    }
    if (absolute >= 1_000_000) {
        return `${(value / 1_000_000).toFixed(2)} MW`;
    }
    if (absolute >= 1_000) {
        return `${(value / 1_000).toFixed(2)} kW`;
    }
    return `${value.toFixed(1)} W`;
};

const fetchAlerts = async () => {
    try {
        const response = await axios.get('/api/alerts');
        if (response.data?.data) {
            maintenanceAlerts.value = response.data.data;
        }
    } catch (e) {
        console.error('Failed to fetch maintenance alerts', e);
    }
};

const deleteAlert = async (id) => {
    try {
        await axios.delete(`/api/alerts/${id}`);
        maintenanceAlerts.value = maintenanceAlerts.value.filter(
            (alert) => alert.id !== id,
        );
    } catch (e) {
        console.error('Failed to delete alert', e);
    }
};

const handleDeviceToggle = async (deviceId, newState) => {
    try {
        await axios.post(`/api/ports/${deviceId}/toggle`, { state: newState });
    } catch (e) {
        console.error('Failed to toggle device', e);
    }
};

const triggerDailyLimitAlert = async (device) => {
    try {
        const response = await axios.post('/api/alerts/threshold-exceeded', {
            device_id: device.id,
            device_name: device.name,
            daily_kwh: device.dailyKwh,
            daily_limit: device.dailyLimit,
        });

        if (response.data?.alert) {
            maintenanceAlerts.value.unshift(response.data.alert);
        }
    } catch (e) {
        console.error('Failed to trigger daily limit alert', e);
    }
};

const refreshSummaryCards = () => {
    const totalPower = devices.value.reduce(
        (total, device) => total + toFiniteNumber(device.currentPower),
        0,
    );
    const totalMonthlyKwh = devices.value.reduce(
        (total, device) => total + toFiniteNumber(device.monthlyKwh),
        0,
    );
    const totalDailyKwh = devices.value.reduce(
        (total, device) => total + toFiniteNumber(device.dailyKwh),
        0,
    );
    const configuredLimits = devices.value.filter(
        (device) => Number(device.dailyLimit || 0) > 0,
    ).length;

    summaryCards.value[0].value = formatPower(totalPower);
    summaryCards.value[1].value = `${totalMonthlyKwh.toFixed(3)} kWh`;
    summaryCards.value[2].value = `${totalDailyKwh.toFixed(3)} kWh`;
    summaryCards.value[3].value = configuredLimits.toString();
};

const scheduleSummaryRefresh = () => {
    if (summaryRefreshTimer) return;

    summaryRefreshTimer = window.setTimeout(() => {
        summaryRefreshTimer = null;
        refreshSummaryCards();
    }, 250);
};

const setDeviceSectionRef = (element, deviceId) => {
    if (!element) {
        deviceSectionElements.delete(deviceId);
        return;
    }

    deviceSectionElements.set(deviceId, element);
    deviceObserver?.observe(element);
};

onMounted(() => {
    fetchAlerts();
    devices.value.forEach((device) => {
        const savedName = localStorage.getItem(`device_${device.id}_name`);
        if (savedName) {
            device.name = savedName;
        }
    });

    if (window.db) {
        window.db.ref('/Live').on('value', (snapshot) => {
            const liveData = snapshot.val();
            if (!liveData) return;

            devices.value.forEach((device) => {
                const deviceKey = `PLUG${device.id}`;
                const data = liveData[deviceKey];
                if (!data) return;

                if (powerStateKnown.has(device.id)) {
                    updateDeviceUptime(device, device.isOn);
                }

                device.currentPower = sanitizePowerWatts(
                    data.power,
                    device.currentPower,
                );
                if (!device.status.endsWith('Limit Reached')) {
                    device.status = data.anomaly
                        ? 'Maintenance Needed'
                        : device.isOn
                          ? 'Active'
                          : 'Standby';
                }
            });

            scheduleSummaryRefresh();
        });

        uptimeTimer = window.setInterval(() => {
            const now = Date.now();
            devices.value.forEach((device) => {
                const startedAt = uptimeSince.get(device.id);
                if (startedAt && powerStateKnown.has(device.id) && device.isOn) {
                    device.uptime = formatUptime((now - startedAt) / 1000);
                }
            });
        }, 1000);

        window.db.ref('plugs').on('value', (snapshot) => {
            const plugs = snapshot.val();
            if (!plugs) return;
            latestPlugs.value = plugs;

            devices.value.forEach((device) => {
                const plug = plugs[`plug${device.id}`];
                if (!plug) return;

                const savedName = localStorage.getItem(`device_${device.id}_name`);
                device.name = (plug.name && String(plug.name).trim()) || savedName || `Plug ${device.id}`;

                device.currentPower = sanitizePowerWatts(
                    plug.current_power,
                    device.currentPower,
                );
                device.dailyKwh = Number(plug.daily_kwh || 0);
                device.monthlyKwh = Number(plug.monthly_kwh || 0);
                device.dailyLimit = Number((plug.threshold_value ?? plug.daily_limit) || 0);
                device.thresholdType = ['daily', 'weekly', 'monthly'].includes(plug.threshold_type)
                    ? plug.threshold_type
                    : 'daily';
                device.usageKwh = getUsageByType(
                    latestEnergy.value[`PLUG${device.id}`],
                    plug,
                    device.thresholdType,
                );
                device.lastUpdated = plug.last_updated || null;

                if (device.dailyLimit > 0 && device.usageKwh >= device.dailyLimit) {
                    device.status = getLimitReachedStatus(device.thresholdType);
                    if (!notifiedDevices.value.has(device.id)) {
                        triggerDailyLimitAlert(device);
                        notifiedDevices.value.add(device.id);
                    }
                } else {
                    notifiedDevices.value.delete(device.id);
                    device.status = device.isOn ? 'Active' : 'Standby';
                }
            });

            scheduleSummaryRefresh();
        });

        window.db.ref('Energy').on('value', (snapshot) => {
            const energy = snapshot.val() || {};
            latestEnergy.value = energy;

            devices.value.forEach((device) => {
                device.dailyKwh = Number(
                    energy[`PLUG${device.id}`]?.daily_kwh
                        ?? latestPlugs.value[`plug${device.id}`]?.daily_kwh
                        ?? device.dailyKwh
                        ?? 0,
                );
                device.usageKwh = getUsageByType(
                    energy[`PLUG${device.id}`],
                    latestPlugs.value[`plug${device.id}`],
                    device.thresholdType,
                );
                device.monthlyKwh = Number(
                    energy[`PLUG${device.id}`]?.monthly_kwh
                        ?? latestPlugs.value[`plug${device.id}`]?.monthly_kwh
                        ?? device.monthlyKwh
                        ?? 0,
                );

                if (device.dailyLimit > 0 && device.usageKwh >= device.dailyLimit) {
                    device.status = getLimitReachedStatus(device.thresholdType);
                }
            });

            scheduleSummaryRefresh();
        });

        window.db.ref('Control').on('value', (snapshot) => {
            const controls = snapshot.val();
            if (!controls) return;
            devices.value.forEach((device) => {
                const deviceKey = `PLUG${device.id}`;
                if (controls[deviceKey] !== undefined) {
                    device.isOn = controls[deviceKey];
                    powerStateKnown.add(device.id);
                    updateDeviceUptime(device, device.isOn);
                }
            });
        });

        window.db.ref('Status').on('value', (snapshot) => {
            const status = snapshot.val();
            if (!status) return;
            devices.value.forEach((device) => {
                const deviceKey = `PLUG${device.id}`;
                if (
                    status[deviceKey] &&
                    !(device.dailyLimit > 0 && device.usageKwh >= device.dailyLimit)
                ) {
                    const isOn = status[deviceKey] === 'ON';
                    device.status = isOn ? 'Active' : 'Standby';
                    device.isOn = isOn;
                    powerStateKnown.add(device.id);
                    updateDeviceUptime(device, isOn);
                }
            });
        });

        window.db.ref('Uptime').on('value', (snapshot) => {
            const uptimes = snapshot.val() || {};
            const rootOnlineSince = Number(uptimes.online_since);
            const rootUptimeSeconds = Number(uptimes.uptime_seconds);

            devices.value.forEach((device) => {
                const key = `PLUG${device.id}`;
                const node = uptimes[key] || {};
                const onlineSince = Number(node.online_since) || (Number.isFinite(rootOnlineSince) && rootOnlineSince > 0 ? rootOnlineSince : null);
                const uptimeSec = (Number.isFinite(Number(node.uptime_seconds)) && Number(node.uptime_seconds) > 0)
                    ? Number(node.uptime_seconds)
                    : (Number.isFinite(rootUptimeSeconds) && rootUptimeSeconds > 0 ? rootUptimeSeconds : null);

                if (onlineSince && device.isOn) {
                    uptimeSince.set(device.id, onlineSince);
                    device.uptime = formatUptime((Date.now() - onlineSince) / 1000);
                } else if (uptimeSec && device.isOn) {
                    const calculatedSince = Date.now() - (uptimeSec * 1000);
                    uptimeSince.set(device.id, calculatedSince);
                    device.uptime = formatUptime(uptimeSec);
                } else if (!device.isOn) {
                    uptimeSince.delete(device.id);
                    device.uptime = 'N/A';
                }
            });
        });
    }

    deviceObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    visibleDeviceIds.value = new Set([
                        ...visibleDeviceIds.value,
                        Number(entry.target.dataset.deviceId),
                    ]);
                }
            });
        },
        { rootMargin: '800px 0px' },
    );

    deviceSectionElements.forEach((element) => deviceObserver.observe(element));

    const handleStorageChange = () => {
        devices.value.forEach((device) => {
            const savedName = localStorage.getItem(`device_${device.id}_name`);
            if (savedName) device.name = savedName;
        });
    };

    window.addEventListener('storage', handleStorageChange);
    window.addEventListener('focus', handleStorageChange);

    onUnmounted(() => {
        if (uptimeTimer) window.clearInterval(uptimeTimer);
        if (summaryRefreshTimer) window.clearTimeout(summaryRefreshTimer);
        deviceObserver?.disconnect();
        deviceObserver = null;
        if (window.db) {
            window.db.ref('Live').off();
            window.db.ref('plugs').off();
            window.db.ref('Energy').off();
            window.db.ref('Control').off();
            window.db.ref('Status').off();
            window.db.ref('Uptime').off();
        }
        window.removeEventListener('storage', handleStorageChange);
        window.removeEventListener('focus', handleStorageChange);
    });
});
</script>

<template>
    <Head title="Dashboard" />

    <div
        class="flex min-h-screen bg-slate-50 transition-colors duration-300 dark:bg-gray-950"
    >
        <Sidebar />

        <div class="flex-1 overflow-y-auto h-screen transition-[margin] duration-300" style="margin-left: var(--sidebar-width, 4rem);">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <header
                    class="sticky top-0 z-50 mb-6 rounded-xl border border-slate-200 bg-white px-6 py-5 shadow-sm transition-colors duration-300 dark:border-slate-800 dark:bg-slate-950"
                >
                    <div>
                        <p class="mb-1 text-[11px] font-semibold uppercase tracking-wider text-cyan-600 dark:text-cyan-400">
                            System overview
                        </p>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-gray-100">
                            Dashboard
                        </h1>
                        <p class="mt-1 text-sm font-medium text-slate-500 dark:text-gray-400">
                            Monitor your energy consumption in real-time
                        </p>
                    </div>
                </header>

                <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <SummaryCard
                        v-for="card in summaryCards"
                        :key="card.id"
                        v-bind="card"
                    />
                </section>

                <section class="mt-6">
                    <AlertCard
                        :alerts="maintenanceAlerts"
                        @delete="deleteAlert"
                    />
                </section>

                <section class="mt-8">
                    <div class="mb-10 flex items-center">
                        <h2
                            class="text-2xl font-black tracking-tight text-gray-900 dark:text-gray-100"
                        >
                            Plug Status & Analytics
                        </h2>
                        <div
                            class="ml-6 h-px flex-1 bg-gradient-to-r from-gray-200 to-transparent dark:from-gray-800"
                        ></div>
                    </div>

                    <div
                        v-for="device in devices"
                        :key="device.id"
                        :ref="(element) => setDeviceSectionRef(element, device.id)"
                        :data-device-id="device.id"
                        class="dashboard-device-section mx-auto mb-16 grid max-w-7xl grid-cols-1 items-stretch gap-8 lg:grid-cols-3"
                    >
                        <div class="flex h-full flex-col lg:col-span-1">
                            <div
                                class="mb-4 flex items-center gap-2 text-sm font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500"
                            >
                                <span class="h-2 w-2 rounded-full bg-orange-500"></span>
                                {{ device.name }}
                            </div>
                            <DeviceCard
                                :device-id="device.id"
                                :name="device.name"
                                :status="device.status"
                                :current-power="device.currentPower"
                                :daily-limit="device.dailyLimit"
                                :daily-kwh="device.dailyKwh"
                                :usage-kwh="device.usageKwh"
                                :uptime="device.uptime"
                                :threshold-type="device.thresholdType"
                                :is-on="device.isOn"
                                class="flex-1 border-gray-200 shadow-md dark:border-gray-800"
                                @update:is-on="
                                    (newState) =>
                                        handleDeviceToggle(device.id, newState)
                                "
                            />
                        </div>

                        <div
                                class="flex h-full flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white p-6 shadow-sm transition-colors duration-300 dark:border-gray-800 dark:bg-gray-900 lg:col-span-2"
                        >
                            <div
                                class="mb-4 flex items-center gap-2 text-sm font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500"
                            >
                                <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                                {{ device.name }} - Power History
                            </div>
                            <div class="flex flex-1 items-center">
                                <EnergyGraph
                                    v-if="visibleDeviceIds.has(device.id)"
                                    :plug-id="device.id"
                                    height="380"
                                />
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<style scoped>
.dashboard-device-section {
    content-visibility: auto;
    contain: layout paint style;
    contain-intrinsic-size: 0 560px;
}
</style>