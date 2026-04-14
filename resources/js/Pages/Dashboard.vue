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

const devices = ref([
    {
        id: 1,
        name: 'Plug 1',
        status: 'Offline',
        currentPower: 0,
        dailyLimit: 0,
        dailyKwh: 0,
        usageKwh: 0,
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
        usageKwh: 0,
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
        (total, device) => total + Number(device.currentPower || 0),
        0,
    );
    const totalDailyKwh = devices.value.reduce(
        (total, device) => total + Number(device.dailyKwh || 0),
        0,
    );
    const configuredLimits = devices.value.filter(
        (device) => Number(device.dailyLimit || 0) > 0,
    ).length;

    summaryCards.value[0].value = `${totalPower.toFixed(1)}W`;
    summaryCards.value[1].value = `${devices.value.reduce((total, device) => total + Number(device.usageKwh || 0), 0).toFixed(3)} kWh`;
    summaryCards.value[2].value = `${totalDailyKwh.toFixed(3)} kWh`;
    summaryCards.value[3].value = configuredLimits.toString();
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

                device.currentPower = Number(data.power || 0);
                if (!device.status.endsWith('Limit Reached')) {
                    device.status = data.anomaly
                        ? 'Maintenance Needed'
                        : device.isOn
                          ? 'Active'
                          : 'Standby';
                }
            });

            refreshSummaryCards();
        });

        window.db.ref('plugs').on('value', (snapshot) => {
            const plugs = snapshot.val();
            if (!plugs) return;
            latestPlugs.value = plugs;

            devices.value.forEach((device) => {
                const plug = plugs[`plug${device.id}`];
                if (!plug) return;

                const savedName = localStorage.getItem(`device_${device.id}_name`);
                device.name = (plug.name && String(plug.name).trim()) || savedName || `Plug ${device.id}`;

                device.currentPower = Number(
                    plug.current_power ?? device.currentPower ?? 0,
                );
                device.dailyKwh = Number(plug.daily_kwh || 0);
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

            refreshSummaryCards();
        });

        window.db.ref('Energy').on('value', (snapshot) => {
            const energy = snapshot.val() || {};
            latestEnergy.value = energy;

            devices.value.forEach((device) => {
                device.usageKwh = getUsageByType(
                    energy[`PLUG${device.id}`],
                    latestPlugs.value[`plug${device.id}`],
                    device.thresholdType,
                );

                if (device.dailyLimit > 0 && device.usageKwh >= device.dailyLimit) {
                    device.status = getLimitReachedStatus(device.thresholdType);
                }
            });
        });

        window.db.ref('Control').on('value', (snapshot) => {
            const controls = snapshot.val();
            if (!controls) return;
            devices.value.forEach((device) => {
                const deviceKey = `PLUG${device.id}`;
                if (controls[deviceKey] !== undefined) {
                    device.isOn = controls[deviceKey];
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
                    device.status = status[deviceKey] === 'ON' ? 'Active' : 'Standby';
                }
            });
        });
    }

    const handleStorageChange = () => {
        devices.value.forEach((device) => {
            const savedName = localStorage.getItem(`device_${device.id}_name`);
            if (savedName) device.name = savedName;
        });
    };

    window.addEventListener('storage', handleStorageChange);
    window.addEventListener('focus', handleStorageChange);

    onUnmounted(() => {
        if (window.db) {
            window.db.ref('Live').off();
            window.db.ref('plugs').off();
            window.db.ref('Energy').off();
            window.db.ref('Control').off();
            window.db.ref('Status').off();
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

        <div class="flex-1 overflow-auto transition-[margin] duration-300" style="margin-left: var(--sidebar-width, 4rem);">
            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <header
                    class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h1
                            class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-gray-100"
                        >
                            Dashboard
                        </h1>
                        <p
                            class="mt-1 text-sm font-medium text-slate-500 dark:text-gray-400"
                        >
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
                        class="mx-auto mb-16 grid max-w-7xl grid-cols-1 items-stretch gap-8 lg:grid-cols-3"
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
                                <EnergyGraph :plug-id="device.id" height="380" />
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>