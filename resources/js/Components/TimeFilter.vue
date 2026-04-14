<!-- <script setup>
const model = defineModel({
    type: String,
    default: 'hourly',
});

const filters = [
    { key: 'hourly', label: 'Hourly' },
    { key: 'daily', label: 'Daily' },
    { key: 'weekly', label: 'Weekly' },
    { key: 'monthly', label: 'Monthly' },
];
</script>

<template>
    <div
        class="inline-flex rounded-2xl border border-slate-200/70 bg-slate-100/80 p-1 shadow-inner dark:border-gray-800 dark:bg-gray-950/80"
    >
        <button
            v-for="filter in filters"
            :key="filter.key"
            type="button"
            class="rounded-xl px-4 py-2 text-xs font-black uppercase tracking-wider transition-all duration-300 sm:px-5"
            :class="
                model === filter.key
                    ? 'bg-white text-teal-600 shadow-sm dark:bg-gray-800 dark:text-teal-300'
                    : 'text-slate-500 hover:bg-white/60 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-gray-800/70 dark:hover:text-white'
            "
            @click="model = filter.key"
        >
            {{ filter.label }}
        </button>
    </div>
</template> -->