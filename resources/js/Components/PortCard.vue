<script setup>
import { computed } from 'vue';

const props = defineProps({
    name: { type: String, required: true },
    power: { type: String, required: true },
    metricLabel: { type: String, required: true },
    metricValue: { type: String, required: true },
    todayKwh: { type: String, required: true },
    status: { type: String, required: true },
    statusTone: { type: String, default: 'healthy' },
    modelValue: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const statusStyles = {
    healthy: 'bg-emerald-100 text-emerald-700',
    warning: 'bg-amber-100 text-amber-700',
    muted: 'bg-slate-100 text-slate-500',
};

const statusClass = computed(
    () => statusStyles[props.statusTone] || statusStyles.healthy,
);

const toggleTrackClass = computed(() =>
    props.modelValue ? 'bg-emerald-500' : 'bg-slate-200',
);

const toggleDotClass = computed(() =>
    props.modelValue ? 'translate-x-6' : 'translate-x-1',
);

const toggleLabelClass = computed(() =>
    props.modelValue ? 'text-emerald-600' : 'text-slate-400',
);

const toggle = () => emit('update:modelValue', !props.modelValue);
</script>

<template>
    <div class="rounded-2xl border border-gray-100/80 bg-white p-6 shadow-sm hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300">
        <!-- Header -->
        <div class="flex items-start justify-between mb-6">
            <div>
                <p class="text-lg font-bold text-gray-900 tracking-tight">{{ name }}</p>
                <p :class="['text-xs font-semibold uppercase tracking-wider', toggleLabelClass]">
                    {{ modelValue ? 'Active' : 'Standby' }}
                </p>
            </div>
            
            <!-- Toggle Switch -->
            <button
                type="button"
                role="switch"
                :aria-checked="modelValue"
                @click="toggle"
                class="relative inline-flex h-7 w-12 items-center rounded-full transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:ring-offset-2"
                :class="modelValue ? 'bg-emerald-500 shadow-inner shadow-emerald-600/50' : 'bg-slate-200 shadow-inner shadow-slate-300/50'"
            >
                <span
                    class="inline-block h-5 w-5 transform rounded-full bg-white transition-transform duration-300 shadow-sm shadow-black/20"
                    :class="modelValue ? 'translate-x-[22px]' : 'translate-x-1'"
                />
            </button>
        </div>

        <!-- Metrics Grid -->
        <div class="mt-2 space-y-4">
            <!-- Power -->
            <div class="flex items-center justify-between group">
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-50 border border-slate-100 text-slate-400 group-hover:text-emerald-500 group-hover:bg-emerald-50 transition-colors"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 3L4 14h7l-1 7 9-11h-7l1-7z" />
                        </svg>
                    </span>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Power</span>
                </div>
                <span class="text-base font-bold text-gray-900 bg-slate-50 px-2.5 py-1 rounded-lg border border-slate-100">
                    {{ power }}
                </span>
            </div>
            
            <!-- Custom Metric (Voltage/etc) -->
            <div class="flex items-center justify-between group">
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-50 border border-slate-100 text-slate-400 group-hover:text-blue-500 group-hover:bg-blue-50 transition-colors"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.1 0-2 .9-2 2 0 .74.4 1.38 1 1.72V15a1 1 0 0 0 2 0v-3.28c.6-.34 1-.98 1-1.72 0-1.1-.9-2-2-2z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 10a6 6 0 1 1 12 0c0 2.21-1.2 4.15-3 5.19V18a2 2 0 0 1-4 0v-2.81A5.99 5.99 0 0 1 6 10z" />
                        </svg>
                    </span>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ metricLabel }}</span>
                </div>
                <span class="text-base font-bold text-gray-900 bg-slate-50 px-2.5 py-1 rounded-lg border border-slate-100">
                    {{ metricValue }}
                </span>
            </div>
            
            <!-- Today's Energy -->
            <div class="flex items-center justify-between group">
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-50 border border-slate-100 text-slate-400 group-hover:text-amber-500 group-hover:bg-amber-50 transition-colors"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 5h14a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z" />
                        </svg>
                    </span>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Today</span>
                </div>
                <span class="text-base font-bold text-gray-900 bg-slate-50 px-2.5 py-1 rounded-lg border border-slate-100">
                    {{ todayKwh }}
                </span>
            </div>
        </div>

        <div class="mt-6 pt-5 border-t border-slate-100">
            <span
                :class="[
                    'inline-flex items-center rounded-lg px-2.5 py-1 text-[11px] font-bold uppercase tracking-widest border',
                    statusTone === 'healthy' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                    statusTone === 'warning' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                    'bg-slate-50 text-slate-600 border-slate-200'
                ]"
            >
                <div class="w-1.5 h-1.5 rounded-full mr-2" :class="statusTone === 'healthy' ? 'bg-emerald-500' : statusTone === 'warning' ? 'bg-amber-500' : 'bg-slate-400'"></div>
                {{ status }}
            </span>
        </div>
    </div>
</template>

