<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import AlertCard from '@/Components/AlertCard.vue';
import PortCard from '@/Components/PortCard.vue';
import SummaryCard from '@/Components/SummaryCard.vue';
import Sidebar from '@/Components/Sidebar.vue';
import EnergyGraph from '@/Components/EnergyGraph.vue';
import DeviceCard from '@/Components/DeviceCard.vue';

// Reactive data
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
        title: 'Record',
        value: '0 kWh',
        subtitle: 'Monthly energy logs',
        tone: 'solid-green',
        icon: 'record',
    },
    {
        id: 3,
        title: "Today's Usage",
        value: '0 kWh',
        subtitle: 'Energy consumed',
        tone: 'light',
        icon: 'usage',
        iconClass: 'bg-indigo-50 text-indigo-600',
    },
    {
        id: 4,
        title: 'Threshold',
        value: '0',
        subtitle: 'Active thresholds',
        tone: 'light',
        icon: 'threshold',
        iconClass: 'bg-amber-50 text-amber-600',
    },
]);

const alertData = ref({
    heading: 'Maintenance Alerts',
    subheading: 'No alerts',
    title: '',
    message: '',
    badgeText: '',
});


const ports = ref([]);
const loading = ref(true);
const error = ref(null);

const devices = ref([
    {
        id: 1,
        name: 'Plug 1',
        status: 'Active',
        currentPower: 46,
        thresholdLimit: 100,
        todayUsage: 1.2,
        isOn: true
    },
    {
        id: 2,
        name: 'Plug 2',
        status: 'Active',
        currentPower: 108,
        thresholdLimit: 200,
        todayUsage: 3.4,
        isOn: true
    },
    {
        id: 3,
        name: 'Plug 3',
        status: 'Standby',
        currentPower: 0,
        thresholdLimit: 500,
        todayUsage: 2.1,
        isOn: false
    }
]);

const handleDeviceToggle = (deviceId, newState) => {
    const device = devices.value.find(d => d.id === deviceId);
    if (device) {
        device.isOn = newState;
        device.status = newState ? 'Active' : 'Standby';
        if (!newState) {
            device.currentPower = 0;
        }
    }
};

const handleDeviceSettings = (deviceId) => {
    console.log('Open settings for device:', deviceId);
};

</script>

<template>
    <Head title="Dashboard" />

    <div class="flex min-h-screen bg-slate-900">
        <!-- Sidebar Component -->
        <Sidebar />
        

        <!-- Main Content -->
        <div class="flex-1 overflow-auto ml-16">
            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-1">
            <header
                class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-3xl font-bold text-white">
                        Dashboard
                    </h1>
                    <p class="text-sm text-slate-500">
                        Monitor your energy consumption in real-time
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="relative inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:text-slate-700"
                        aria-label="Notifications"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            class="h-5 w-5"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M15 17h5l-1.4-1.4a2 2 0 0 1-.6-1.4V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0"
                            />
                        </svg>
                        <span
                            class="absolute right-2 top-2 h-2 w-2 rounded-full bg-rose-500"
                        ></span>
                    </button>
                </div>
            </header>

            

            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <SummaryCard
                    v-for="card in summaryCards"
                    :key="card.id"
                    :title="card.title"
                    :value="card.value"
                    :subtitle="card.subtitle"
                    :tone="card.tone"
                    :icon-class="card.iconClass"
                >
                    <template #icon>
                        <svg
                            v-if="card.icon === 'bolt'"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            class="h-4 w-4"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M13 3L4 14h7l-1 7 9-11h-7l1-7z"
                            />
                        </svg>
                        <svg
                            v-else-if="card.icon === 'record'"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            class="h-4 w-4"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M8 7h8M8 11h8M8 15h6M6 3h9l3 3v15a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"
                            />
                        </svg>
                        <svg
                            v-else-if="card.icon === 'usage'"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            class="h-4 w-4"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M3 12h4l3 8 4-16 3 8h4"
                            />
                        </svg>
                        <svg
                            v-else
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            class="h-4 w-4"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 3v18m9-9H3"
                            />
                        </svg>
                    </template>
                </SummaryCard>
            </section>

            <section class="mt-6" v-if="alertData.title || alertData.message">
                <AlertCard
                    :heading="alertData.heading"
                    :subheading="alertData.subheading"
                    :title="alertData.title"
                    :message="alertData.message"
                    :badge-text="alertData.badgeText"
                />
            </section>

            <!-- Device Cards Section -->
            <section class="mt-6">
                <h2 class="text-xl font-semibold text-white mb-4">Plug </h2>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                    <DeviceCard
                        v-for="device in devices"
                        :key="device.id"
                        :name="device.name"
                        :status="device.status"
                        :current-power="device.currentPower"
                        :threshold-limit="device.thresholdLimit"
                        :today-usage="device.todayUsage"
                        :is-on="device.isOn"
                        @update:is-on="(newState) => handleDeviceToggle(device.id, newState)"
                        @settings="() => handleDeviceSettings(device.id)"
                    />
                </div>
            </section>
            <br>

             <EnergyGraph />
            <section
                class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3"
            >
                <div v-if="loading && ports.length === 0" class="col-span-full text-center py-12">
                    <p class="text-slate-500">Loading dashboard data...</p>
                </div>
                <div v-else-if="error && ports.length === 0" class="col-span-full text-center py-12">
                    <p class="text-red-500">{{ error }}</p>
                    <button
                        @click="fetchDashboardData"
                        class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                    >
                        Retry
                    </button>
                </div>
                <PortCard
                    v-for="port in ports"
                    v-else
                    :key="port.id"
                    :model-value="port.isOn"
                    @update:model-value="(value) => togglePort(port.id, port.isOn)"
                    :name="port.name"
                    :power="port.power"
                    :metric-label="port.metricLabel"
                    :metric-value="port.metricValue"
                    :today-kwh="port.todayKwh"
                    :status="port.status"
                    :status-tone="port.statusTone"
                />
            </section>

            
            </div>
        </div>
    </div>
</template>
