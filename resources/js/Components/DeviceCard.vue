<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Settings, Zap, Clock } from 'lucide-vue-next';

const props = defineProps({
    deviceId: {
        type: Number,
        required: true,
    },
    name: {
        type: String,
        required: true,
    },
    status: {
        type: String,
        default: 'Active',
        validator: (value) =>
            [
                'Active',
                'Standby',
                'Offline',
                'Daily Limit Reached',
                'Weekly Limit Reached',
                'Monthly Limit Reached',
                'Maintenance Needed',
            ].includes(value),
    },
    currentPower: {
        type: Number,
        required: true,
    },
    dailyLimit: {
        type: Number,
        required: true,
    },
    dailyKwh: {
        type: Number,
        required: true,
    },
    thresholdType: {
        type: String,
        default: 'daily',
        validator: (value) => ['daily', 'weekly', 'monthly'].includes(value),
    },
    usageKwh: {
        type: Number,
        default: null,
    },
    uptime: {
        type: String,
        default: 'N/A',
    },
    isOn: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['update:isOn']);

const toggleDevice = () => {
    emit('update:isOn', !props.isOn);
};

const normalizedThresholdType = computed(() =>
    ['daily', 'weekly', 'monthly'].includes(props.thresholdType)
        ? props.thresholdType
        : 'daily',
);

const activeUsageKwh = computed(() =>
    props.usageKwh === null ? Number(props.dailyKwh || 0) : Number(props.usageKwh || 0),
);

const dailyUsagePercentage = computed(() => {
    if (!props.dailyLimit || props.dailyLimit <= 0) return 0;
    return Math.min((activeUsageKwh.value / props.dailyLimit) * 100, 100);
});

const limitReached = computed(() => {
    return props.dailyLimit > 0 && activeUsageKwh.value >= props.dailyLimit;
});

const limitReachedLabel = computed(() =>
    `${normalizedThresholdType.value.charAt(0).toUpperCase() + normalizedThresholdType.value.slice(1)} Limit Reached`,
);

const statusColor = computed(() => {
    if (limitReached.value) return 'text-red-500';
    return props.status === 'Active'
        ? 'text-teal-600'
        : props.status === 'Standby'
          ? 'text-gray-400'
          : 'text-red-500';
});

const powerColor = computed(() => {
    const percentage = dailyUsagePercentage.value;
    if (percentage >= 100) return 'text-red-600';
    if (percentage >= 80) return 'text-red-500';
    if (percentage >= 60) return 'text-orange-500';
    return 'text-teal-600';
});

const progressColor = computed(() => {
    const percentage = dailyUsagePercentage.value;
    if (percentage >= 100) return 'bg-red-600';
    if (percentage >= 80) return 'bg-red-500';
    if (percentage >= 60) return 'bg-orange-500';
    return 'bg-teal-500';
});

const safePower = computed(() => {
    const value = Number(props.currentPower);
    return Number.isFinite(value) ? value : 0;
});

const formattedPowerValue = computed(() => {
    const absolute = Math.abs(safePower.value);
    if (absolute >= 1_000_000_000) return (safePower.value / 1_000_000_000).toFixed(2);
    if (absolute >= 1_000_000) return (safePower.value / 1_000_000).toFixed(2);
    if (absolute >= 1_000) return (safePower.value / 1_000).toFixed(2);
    return safePower.value.toFixed(1);
});

const formattedPowerUnit = computed(() => {
    const absolute = Math.abs(safePower.value);
    if (absolute >= 1_000_000_000) return 'GW';
    if (absolute >= 1_000_000) return 'MW';
    if (absolute >= 1_000) return 'kW';
    return 'W';
});
</script>

<template>
    <div
        class="group relative overflow-hidden rounded-2xl border border-gray-100/80 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-xl dark:border-gray-800 dark:bg-gray-900"
    >
        <div class="relative z-10 mb-8 flex items-start justify-between">
            <div class="flex items-center gap-4">
                

                <div>
                    <h3
                        class="text-lg font-bold tracking-tight text-gray-900 dark:text-gray-100"
                    >
                        {{ name }}
                    </h3>
                    <div class="mt-0.5 flex items-center gap-1.5">
                        <span class="relative flex h-2 w-2">
                            <span
                                :class="[
                                    'absolute inline-flex h-full w-full animate-ping rounded-full opacity-75',
                                    isOn && !limitReached
                                        ? 'bg-teal-400'
                                        : 'bg-gray-400',
                                ]"
                            ></span>
                            <span
                                :class="[
                                    'relative inline-flex h-2 w-2 rounded-full',
                                    isOn && !limitReached
                                        ? 'bg-teal-500'
                                        : 'bg-gray-400',
                                ]"
                            ></span>
                        </span>
                        <p
                            class="text-xs font-semibold uppercase tracking-wider"
                            :class="statusColor"
                        >
                            {{ limitReached ? limitReachedLabel : status }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <Link
                    :href="`/device/${deviceId}/settings`"
                    class="rounded-xl border border-gray-200/50 bg-gray-50 p-2 transition-colors hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700"
                >
                    <Settings
                        class="h-4 w-4 text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
                    />
                </Link>

                <button
                    @click="toggleDevice"
                    class="relative inline-flex h-7 w-12 items-center rounded-full transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                    :class="
                        isOn && !limitReached
                            ? 'bg-teal-500 shadow-inner shadow-teal-600/50'
                            : 'bg-slate-200 shadow-inner shadow-slate-300/50 dark:bg-gray-700 dark:shadow-black/20'
                    "
                >
                    <span
                        class="inline-block h-5 w-5 transform rounded-full bg-white shadow-sm shadow-black/20 transition-transform duration-300 dark:bg-gray-200"
                        :class="isOn && !limitReached ? 'translate-x-[22px]' : 'translate-x-1'"
                    />
                </button>
            </div>
        </div>

        <div class="relative z-10 mb-8">
            <p
                class="mb-1.5 text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500"
            >
                Current Power
            </p>
            <div class="flex items-baseline gap-1">
                <span class="text-5xl font-black tracking-tighter" :class="powerColor">
                    {{ formattedPowerValue }}
                </span>
                <span class="text-xl font-bold text-gray-400 dark:text-gray-500">{{ formattedPowerUnit }}</span>
            </div>
        </div>

        <div class="relative z-10 mb-6">
            <div class="mb-2 flex items-end justify-between">
                <p
                    class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500"
                >
                    {{ normalizedThresholdType }} Energy Limit
                </p>
                <p class="text-sm font-bold text-gray-800 dark:text-gray-200">
                    {{ Math.round(dailyUsagePercentage) }}%
                </p>
            </div>

            <div
                class="relative flex h-2.5 w-full overflow-hidden rounded-full bg-gray-100 shadow-inner dark:bg-gray-800"
            >
                <div
                    class="relative h-full rounded-full shadow-[inset_0_-1px_2px_rgba(0,0,0,0.1)] transition-all duration-700 ease-out"
                    :class="progressColor"
                    :style="{ width: `${dailyUsagePercentage}%` }"
                >
                    <div class="absolute inset-0 rounded-full bg-white/20"></div>
                </div>
            </div>

            <div class="mt-2 flex items-center justify-between">
                <span
                    class="text-[10px] font-bold uppercase text-gray-400 dark:text-gray-500"
                >
                    {{ activeUsageKwh.toFixed(3) }} kWh used
                </span>
                <span
                    class="text-[10px] font-bold uppercase text-gray-400 dark:text-gray-500"
                >
                    {{ dailyLimit > 0 ? dailyLimit.toFixed(3) : 'No' }} kWh limit
                </span>
            </div>
        </div>

        <div class="relative z-10 mb-6 flex items-center justify-between border-t border-gray-100 pt-4 text-xs dark:border-gray-800">
            <span class="flex items-center gap-2 font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">
                <Clock class="h-3.5 w-3.5" />
                Uptime
            </span>
            <span class="font-semibold text-gray-700 dark:text-gray-300">{{ uptime }}</span>
        </div>

        <div
            v-if="limitReached"
            class="relative z-10 mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs font-bold text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300"
        >
            {{ normalizedThresholdType }} limit reached. This plug was turned off.
        </div>

        <div
            class="relative z-10 flex items-center justify-between border-t border-gray-100 pt-5 dark:border-gray-800"
        >
            <div
                class="flex items-center gap-2 text-slate-500 transition-colors duration-300 group-hover:text-teal-600 dark:text-gray-400 dark:group-hover:text-teal-400"
            >
                <Zap class="h-4 w-4" />
                <span class="text-xs font-semibold uppercase tracking-wide">
                    {{ normalizedThresholdType }} Energy Usage
                </span>
            </div>
            <span
                class="rounded-lg border border-gray-100 bg-gray-50 px-2.5 py-1 text-sm font-bold text-gray-900 transition-colors dark:border-gray-800 dark:bg-gray-800 dark:text-gray-100"
            >
                {{ activeUsageKwh.toFixed(3) }} / {{ dailyLimit.toFixed(3) }} kWh
            </span>
        </div>
    </div>
</template>