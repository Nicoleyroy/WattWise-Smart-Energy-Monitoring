<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    value: { type: String, required: true },
    subtitle: { type: String, required: true },
    tone: { type: String, default: 'light' },
    iconClass: { type: String, default: '' },
});

const toneStyles = {
    'solid-blue': {
        container: 'bg-sky-500 text-white',
        title: 'text-sky-100',
        subtitle: 'text-sky-100',
        value: 'text-white',
        icon: 'bg-white/20 text-white',
    },
    'solid-green': {
        container: 'bg-emerald-500 text-white',
        title: 'text-emerald-100',
        subtitle: 'text-emerald-100',
        value: 'text-white',
        icon: 'bg-white/20 text-white',
    },
    light: {
        container: 'bg-white border border-slate-200 text-slate-900',
        title: 'text-slate-500',
        subtitle: 'text-slate-500',
        value: 'text-slate-900',
        icon: 'bg-slate-100 text-slate-500',
    },
};

const styles = computed(() => toneStyles[props.tone] || toneStyles.light);
const resolvedIconClass = computed(() => props.iconClass || styles.value.icon);
</script>

<template>
    <div :class="['rounded-2xl p-5 shadow-sm', styles.container]">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p :class="['text-sm font-medium', styles.title]">{{ title }}</p>
                <p
                    :class="[
                        'mt-2 text-3xl font-semibold tracking-tight',
                        styles.value,
                    ]"
                >
                    {{ value }}
                </p>
                <p :class="['mt-1 text-xs', styles.subtitle]">
                    {{ subtitle }}
                </p>
            </div>
            <div
                :class="[
                    'flex h-9 w-9 items-center justify-center rounded-full',
                    resolvedIconClass,
                ]"
            >
                <slot name="icon" />
            </div>
        </div>
    </div>
</template>

