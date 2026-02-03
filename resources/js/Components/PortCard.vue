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
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-800">{{ name }}</p>
                <p :class="['text-xs font-medium', toggleLabelClass]">
                    {{ modelValue ? 'On' : 'Off' }}
                </p>
            </div>
            <button
                type="button"
                role="switch"
                :aria-checked="modelValue"
                @click="toggle"
                :class="[
                    'relative inline-flex h-6 w-11 items-center rounded-full transition',
                    toggleTrackClass,
                ]"
            >
                <span
                    :class="[
                        'inline-block h-4 w-4 transform rounded-full bg-white transition',
                        toggleDotClass,
                    ]"
                />
            </button>
        </div>

        <div class="mt-4 space-y-3 text-xs text-slate-500">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span
                        class="flex h-6 w-6 items-center justify-center rounded-lg bg-slate-100 text-slate-500"
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
                                d="M13 3L4 14h7l-1 7 9-11h-7l1-7z"
                            />
                        </svg>
                    </span>
                    <span>Power</span>
                </div>
                <span class="text-sm font-semibold text-slate-800">
                    {{ power }}
                </span>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span
                        class="flex h-6 w-6 items-center justify-center rounded-lg bg-slate-100 text-slate-500"
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
                                d="M12 8c-1.1 0-2 .9-2 2 0 .74.4 1.38 1 1.72V15a1 1 0 0 0 2 0v-3.28c.6-.34 1-.98 1-1.72 0-1.1-.9-2-2-2z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 10a6 6 0 1 1 12 0c0 2.21-1.2 4.15-3 5.19V18a2 2 0 0 1-4 0v-2.81A5.99 5.99 0 0 1 6 10z"
                            />
                        </svg>
                    </span>
                    <span>{{ metricLabel }}</span>
                </div>
                <span class="text-sm font-semibold text-slate-800">
                    {{ metricValue }}
                </span>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span
                        class="flex h-6 w-6 items-center justify-center rounded-lg bg-slate-100 text-slate-500"
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
                                d="M8 7V3m8 4V3M4 11h16M5 5h14a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z"
                            />
                        </svg>
                    </span>
                    <span>Today</span>
                </div>
                <span class="text-sm font-semibold text-slate-800">
                    {{ todayKwh }}
                </span>
            </div>
        </div>

        <div class="mt-4">
            <span
                :class="[
                    'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold',
                    statusClass,
                ]"
            >
                {{ status }}
            </span>
        </div>
    </div>
</template>

