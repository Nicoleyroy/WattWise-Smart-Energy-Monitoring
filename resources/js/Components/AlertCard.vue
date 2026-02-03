<script setup>
import { computed } from 'vue';

const props = defineProps({
    heading: { type: String, required: true },
    subheading: { type: String, required: true },
    title: { type: String, required: true },
    message: { type: String, required: true },
    badgeText: { type: String, default: 'Warning' },
    badgeTone: { type: String, default: 'warning' },
});

const badgeStyles = {
    warning: 'bg-amber-100 text-amber-700',
    info: 'bg-sky-100 text-sky-700',
};

const badgeClass = computed(
    () => badgeStyles[props.badgeTone] || badgeStyles.warning,
);
</script>

<template>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-slate-800">
                    {{ heading }}
                </p>
                <p class="text-xs text-slate-500">{{ subheading }}</p>
            </div>
            <div
                class="flex h-9 w-9 items-center justify-center rounded-full bg-amber-100 text-amber-600"
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
                        d="M12 9v4m0 4h.01M10.29 3.86l-7.4 12.8A2 2 0 0 0 4.6 19h14.8a2 2 0 0 0 1.71-3.04l-7.4-12.8a2 2 0 0 0-3.42 0z"
                    />
                </svg>
            </div>
        </div>

        <div class="mt-4 flex items-start gap-3 rounded-xl bg-slate-50 p-4">
            <div
                class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-100 text-amber-600"
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
                        d="M12 9v4m0 4h.01M10.29 3.86l-7.4 12.8A2 2 0 0 0 4.6 19h14.8a2 2 0 0 0 1.71-3.04l-7.4-12.8a2 2 0 0 0-3.42 0z"
                    />
                </svg>
            </div>
            <div class="flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="text-sm font-semibold text-slate-800">
                        {{ title }}
                    </p>
                    <span
                        :class="[
                            'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold',
                            badgeClass,
                        ]"
                    >
                        {{ badgeText }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-slate-500">
                    {{ message }}
                </p>
            </div>
        </div>
    </div>
</template>

