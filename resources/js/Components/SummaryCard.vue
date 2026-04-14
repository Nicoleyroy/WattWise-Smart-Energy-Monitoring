<script setup>
import { computed } from 'vue';
import { Zap, BarChart3, Activity, Gauge } from 'lucide-vue-next';

const props = defineProps({
    title: { type: String, required: true },
    value: { type: String, required: true },
    subtitle: { type: String, required: true },
    tone: { type: String, default: 'light' },
    icon: { type: String, default: 'bolt' },
    iconClass: { type: String, default: '' },
});

const toneStyles = {
    'solid-blue': {
        container: 'bg-gradient-to-br from-blue-700 to-indigo-700 dark:from-blue-900 dark:to-indigo-950 border-blue-600/50 dark:border-blue-800',
        title: 'text-blue-100/90 dark:text-blue-300',
        subtitle: 'text-blue-100/75 dark:text-blue-400',
        value: 'text-white dark:text-white',
        icon: 'bg-white/10 dark:bg-blue-950/50 text-white dark:text-blue-200 border-white/20',
    },
    'solid-green': {
        container: 'bg-gradient-to-br from-emerald-700 to-teal-700 dark:from-emerald-900 dark:to-teal-950 border-emerald-600/50 dark:border-emerald-800',
        title: 'text-emerald-100/90 dark:text-emerald-300',
        subtitle: 'text-emerald-100/75 dark:text-emerald-400',
        value: 'text-white dark:text-white',
        icon: 'bg-white/10 dark:bg-emerald-950/50 text-white dark:text-emerald-200 border-white/20',
    },
    light: {
        container: 'bg-white/95 dark:bg-gray-900 border-gray-200/70 dark:border-gray-800',
        title: 'text-gray-500 dark:text-gray-400',
        subtitle: 'text-gray-400 dark:text-gray-500',
        value: 'text-slate-900 dark:text-gray-100',
        icon: 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-700',
    },
};

const iconMap = {
    bolt: Zap,
    record: BarChart3,
    usage: Activity,
    threshold: Gauge,
};

const styles = computed(() => toneStyles[props.tone] || toneStyles.light);
const resolvedIconClass = computed(() => props.iconClass || styles.value.icon);
const resolvedIcon = computed(() => iconMap[props.icon] || Zap);
</script>

<template>
    <div :class="['rounded-2xl p-6 shadow-sm hover:shadow-lg transition-all border duration-300 relative overflow-hidden', styles.container]">
        <!-- Subtle background glow -->
        <div class="absolute -top-10 -right-10 w-32 h-32 rounded-full opacity-10" :class="resolvedIconClass"></div>
        
        <div class="flex items-start justify-between gap-4 relative z-10">
            <div class="flex-1">
                <p :class="['text-xs font-bold tracking-[0.12em] uppercase', styles.title]">{{ title }}</p>
                <p :class="['mt-2 text-5xl font-black tracking-tight leading-none', styles.value]">
                    {{ value }}
                </p>
                <p :class="['mt-2 text-sm font-medium', styles.subtitle]">
                    {{ subtitle }}
                </p>
            </div>
            <div
                :class="[
                    'flex h-12 w-12 items-center justify-center rounded-2xl shadow-sm border border-white/20',
                    resolvedIconClass,
                ]"
            >
                <component :is="resolvedIcon" class="h-5 w-5" />
            </div>
        </div>
    </div>
</template>
