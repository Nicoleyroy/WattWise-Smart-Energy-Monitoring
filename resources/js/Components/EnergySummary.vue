<!-- <script setup>
defineProps({
    currentUsage: {
        type: Number,
        default: 0,
    },
    totalConsumption: {
        type: Number,
        default: 0,
    },
    powerLimit: {
        type: Number,
        default: 0,
    },
    isLive: {
        type: Boolean,
        default: true,
    },
});
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="mb-3 flex items-center gap-2">
                <span class="relative flex h-2.5 w-2.5">
                    <span
                        v-if="isLive"
                        class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"
                    ></span>
                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-red-500"></span>
                </span>
                <span class="text-xs font-black uppercase tracking-[0.2em] text-red-500">Live</span>
            </div>
            <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Current Usage</p>
            <p class="mt-2 text-3xl font-black tracking-tight text-slate-950 dark:text-white">
                {{ currentUsage.toFixed(1) }}
                <span class="text-base text-slate-400">W</span>
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Total Consumption</p>
            <p class="mt-2 text-3xl font-black tracking-tight text-slate-950 dark:text-white">
                {{ totalConsumption.toFixed(3) }}
                <span class="text-base text-slate-400">kWh</span>
            </p>
            <p class="mt-2 text-xs font-semibold text-slate-500 dark:text-slate-400">Today from Firebase Energy node</p>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Power Limit</p>
            <p class="mt-2 text-3xl font-black tracking-tight text-slate-950 dark:text-white">
                {{ powerLimit > 0 ? powerLimit.toFixed(0) : 'Not set' }}
                <span v-if="powerLimit > 0" class="text-base text-slate-400">W</span>
            </p>
            <p class="mt-2 text-xs font-semibold text-slate-500 dark:text-slate-400">
                Red markers indicate readings above this limit
            </p>
        </div>
    </div>
</template> -->
