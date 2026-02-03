<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import AlertCard from '@/Components/AlertCard.vue';
import PortCard from '@/Components/PortCard.vue';
import SummaryCard from '@/Components/SummaryCard.vue';

const summaryCards = [
    {
        id: 1,
        title: 'Current Power',
        value: '3650W',
        subtitle: 'Active consumption',
        tone: 'solid-blue',
        icon: 'bolt',
    },
    {
        id: 2,
        title: 'Record',
        value: '112.5 kWh',
        subtitle: 'Monthly energy logs',
        tone: 'solid-green',
        icon: 'record',
    },
    {
        id: 3,
        title: "Today's Usage",
        value: '23.0 kWh',
        subtitle: 'Energy consumed',
        tone: 'light',
        icon: 'usage',
        iconClass: 'bg-indigo-50 text-indigo-600',
    },
    {
        id: 4,
        title: 'Threshold',
        value: '3',
        subtitle: 'Active thresholds',
        tone: 'light',
        icon: 'threshold',
        iconClass: 'bg-amber-50 text-amber-600',
    },
];

const alertData = {
    heading: 'Maintenance Alerts',
    subheading: '1 appliance needs attention',
    title: 'Refrigerator',
    message: 'Refrigerator may need maintenance soon. Schedule a check.',
    badgeText: 'Warning',
};

const ports = ref([
    {
        id: 1,
        name: 'Port 1',
        isOn: true,
        power: '1500W',
        metricLabel: 'Cost/Hour',
        metricValue: '₱18.75',
        todayKwh: '12.5 kWh',
        status: 'Healthy',
        statusTone: 'healthy',
    },
    {
        id: 2,
        name: 'Port 2',
        isOn: true,
        power: '150W',
        metricLabel: 'Cost/Hour',
        metricValue: '₱3.60',
        todayKwh: '3.6 kWh',
        status: 'Check Soon',
        statusTone: 'warning',
    },
    {
        id: 3,
        name: 'Port 3',
        isOn: false,
        power: '80W',
        metricLabel: 'Cost/Hour',
        metricValue: '₱0.96',
        todayKwh: '1.2 kWh',
        status: 'Healthy',
        statusTone: 'healthy',
    },
    {
        id: 4,
        name: 'Port 4',
        isOn: false,
        power: '500W',
        metricLabel: 'Usage/Hour',
        metricValue: '1.2 kWh',
        todayKwh: '4.8 kWh',
        status: 'Healthy',
        statusTone: 'healthy',
    },
    {
        id: 5,
        name: 'Port 5',
        isOn: true,
        power: '2000W',
        metricLabel: 'Cost/Hour',
        metricValue: '₱24.00',
        todayKwh: '18.4 kWh',
        status: 'Check Soon',
        statusTone: 'warning',
    },
    {
        id: 6,
        name: 'Port 6',
        isOn: false,
        power: '1200W',
        metricLabel: 'Cost/Hour',
        metricValue: '₱14.40',
        todayKwh: '9.2 kWh',
        status: 'Healthy',
        statusTone: 'healthy',
    },
]);
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

            <section class="mt-6">
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
                <PortCard
                    v-for="port in ports"
                    :key="port.id"
                    v-model="port.isOn"
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
