<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import AlertCard from '@/Components/AlertCard.vue';
import PortCard from '@/Components/PortCard.vue';
import SummaryCard from '@/Components/SummaryCard.vue';

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

// Polling interval (5 seconds for real-time updates)
let pollingInterval = null;
const POLL_INTERVAL = 10000;

// Format value with unit
const formatValue = (value, unit) => {
    if (typeof value === 'number') {
        return `${value}${unit}`;
    }
    return value;
};

// Format port data from API
const formatPort = (port) => {
    return {
        id: port.id,
        name: port.name || `Port ${port.id}`,
        isOn: port.is_on ?? false,
        power: formatValue(port.power, 'W'),
        metricLabel: 'Cost/Hour',
        metricValue: `₱${(port.cost_per_hour || 0).toFixed(2)}`,
        todayKwh: formatValue(port.today_kwh, ' kWh'),
        status: port.status || 'Healthy',
        statusTone: port.status_tone || 'healthy',
    };
};

// Fetch dashboard data
const fetchDashboardData = async () => {
    try {
        loading.value = true;
        error.value = null;

        const response = await axios.get('/api/dashboard/data');

        if (response.data) {
            const data = response.data;

            // Update summary cards
            if (data.summary) {
                summaryCards.value[0].value = formatValue(data.summary.current_power?.value || 0, 'W');
                summaryCards.value[1].value = formatValue(data.summary.record?.value || 0, ' kWh');
                summaryCards.value[2].value = formatValue(data.summary.today_usage?.value || 0, ' kWh');
                summaryCards.value[3].value = String(data.summary.thresholds?.count || 0);
            }

            // Update ports
            if (data.ports && Array.isArray(data.ports)) {
                ports.value = data.ports.map(formatPort);
            }

            // Update alerts
            if (data.alerts) {
                const alerts = data.alerts.alerts || [];
                if (alerts.length > 0) {
                    const firstAlert = alerts[0];
                    alertData.value = {
                        heading: data.alerts.heading || 'Maintenance Alerts',
                        subheading: data.alerts.subheading || `${alerts.length} appliance(s) need attention`,
                        title: firstAlert.title || '',
                        message: firstAlert.message || '',
                        badgeText: firstAlert.badge_text || 'Warning',
                    };
                } else {
                    alertData.value = {
                        heading: 'Maintenance Alerts',
                        subheading: 'No alerts',
                        title: '',
                        message: '',
                        badgeText: '',
                    };
                }
            }
        }
    } catch (err) {
        console.error('Failed to fetch dashboard data:', err);
        error.value = 'Failed to load dashboard data. Please check your IoT connection.';
        // Keep mock data visible on error
    } finally {
        loading.value = false;
    }
};

// Toggle port ON/OFF
const togglePort = async (portId, currentState) => {
    try {
        const newState = !currentState;
        
        // Optimistic update
        const port = ports.value.find(p => p.id === portId);
        if (port) {
            port.isOn = newState;
        }

        const response = await axios.post(`/api/ports/${portId}/toggle`, {
            state: newState,
        });

        if (response.data && response.data.port) {
            // Update with server response
            const index = ports.value.findIndex(p => p.id === portId);
            if (index !== -1) {
                ports.value[index] = formatPort(response.data.port);
            }
        }
    } catch (err) {
        console.error('Failed to toggle port:', err);
        // Revert optimistic update
        const port = ports.value.find(p => p.id === portId);
        if (port) {
            port.isOn = currentState;
        }
        alert('Failed to toggle port. Please try again.');
    }
};

// Start polling for real-time updates
const startPolling = () => {
    pollingInterval = setInterval(() => {
        fetchDashboardData();
    }, POLL_INTERVAL);
};

// Stop polling
const stopPolling = () => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
};

// Lifecycle hooks
onMounted(() => {
    fetchDashboardData();
    startPolling();
});

onUnmounted(() => {
    stopPolling();
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="min-h-screen bg-slate-100">
        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
            <header
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900">
                        WattWise
                    </h1>
                    <p class="text-sm text-slate-500">
                        Smart Energy Monitoring
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
                    <Link
                        method="post"
                        as="button"
                        :href="route('logout')"
                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            class="h-4 w-4"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"
                            />
                        </svg>
                        Logout
                    </Link>
                </div>
            </header>

            <section class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
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
                        class="mt-4 px-4 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700"
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
</template>
