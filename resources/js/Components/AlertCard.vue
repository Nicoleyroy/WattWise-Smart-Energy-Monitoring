<script setup>
import { computed } from 'vue';
import { AlertTriangle, ShieldAlert, X, Clock } from 'lucide-vue-next';

const props = defineProps({
    alerts: { type: Array, default: () => [] },
});

const emit = defineEmits(['delete']);

const statusStyles = {
    critical: {
        badge: 'bg-red-100 dark:bg-red-950/40 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-900/50',
        icon: 'bg-red-100 dark:bg-red-950/40 text-red-600 dark:text-red-400',
        border: 'border-red-200/80 dark:border-red-900/50',
        rowAccent: 'border-l-red-500',
    },
    warning: {
        badge: 'bg-amber-100 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-900/50',
        icon: 'bg-amber-100 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400',
        border: 'border-amber-200/80 dark:border-amber-900/50',
        rowAccent: 'border-l-amber-500',
    },
};

const getStyle = (status) => statusStyles[status] || statusStyles.warning;

const criticalCount = computed(() =>
    props.alerts.filter((a) => (a?.status || '').toLowerCase() === 'critical').length,
);

const warningCount = computed(() =>
    props.alerts.filter((a) => (a?.status || '').toLowerCase() === 'warning').length,
);

const sortedAlerts = computed(() => {
    const severityRank = { critical: 2, warning: 1 };

    return [...props.alerts].sort((a, b) => {
        const aRank = severityRank[(a?.status || '').toLowerCase()] || 0;
        const bRank = severityRank[(b?.status || '').toLowerCase()] || 0;
        if (aRank !== bRank) return bRank - aRank;

        const aTime = new Date(a?.created_at || 0).getTime();
        const bTime = new Date(b?.created_at || 0).getTime();
        return bTime - aTime;
    });
});

const formatTime = (timestamp) => {
    if (!timestamp) return '';
    const date = new Date(timestamp);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);
    if (diff < 60) return 'Just now';
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return date.toLocaleDateString();
};
</script>

<template>
    <div class="rounded-2xl border border-slate-200/90 bg-gradient-to-b from-white to-slate-50/60 p-5 shadow-sm transition-colors duration-300 dark:border-gray-800 dark:from-gray-900 dark:to-gray-900/90">
        <!-- Header -->
        <div class="mb-4 flex items-start justify-between gap-4">
            <div class="space-y-1">
                <p class="text-sm font-semibold text-slate-800 dark:text-gray-200">Maintenance Alerts</p>
                <p class="text-xs text-slate-500 dark:text-gray-400">
                    {{ alerts.length > 0 ? `${alerts.length} active ${alerts.length === 1 ? 'alert' : 'alerts'}` : 'No active alerts' }}
                </p>
                <div v-if="alerts.length > 0" class="pt-1 flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-full border border-red-200 bg-red-100 px-2.5 py-0.5 text-[11px] font-semibold text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-400">
                        {{ criticalCount }} Critical
                    </span>
                    <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-100 px-2.5 py-0.5 text-[11px] font-semibold text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-400">
                        {{ warningCount }} Warning
                    </span>
                </div>
            </div>
            <div class="flex h-9 w-9 items-center justify-center rounded-full border border-amber-200 bg-amber-100 text-amber-600 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-400">
                <AlertTriangle class="h-4 w-4" />
            </div>
        </div>

        <!-- Alerts List -->
        <div
            v-if="alerts.length > 0"
            class="space-y-3 max-h-80 overflow-y-auto pr-1 [&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar-track]:rounded-full [&::-webkit-scrollbar-track]:bg-slate-100 dark:[&::-webkit-scrollbar-track]:bg-slate-800/60 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-slate-300 dark:[&::-webkit-scrollbar-thumb]:bg-slate-600"
        >
            <div
                v-for="alert in sortedAlerts"
                :key="alert.id"
                class="group flex items-start gap-3 rounded-xl border border-l-4 bg-white/70 p-4 transition-all hover:shadow-sm dark:bg-gray-800/45"
                :class="[getStyle(alert.status).border, getStyle(alert.status).rowAccent]"
            >
                <!-- Icon -->
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                    :class="getStyle(alert.status).icon"
                >
                    <ShieldAlert v-if="alert.status === 'critical'" class="h-4 w-4" />
                    <AlertTriangle v-else class="h-4 w-4" />
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <p class="text-sm font-semibold text-slate-800 dark:text-gray-200">
                            Plug {{ alert.plug_number }}
                        </p>
                        <span
                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize"
                            :class="getStyle(alert.status).badge"
                        >
                            {{ alert.status }}
                        </span>
                    </div>
                    <p class="text-xs leading-relaxed text-slate-600 dark:text-gray-400">{{ alert.message }}</p>
                    <div class="mt-2 flex items-center gap-1 text-xs text-slate-400 dark:text-gray-500">
                        <Clock class="w-3 h-3" />
                        <span :title="new Date(alert.created_at).toLocaleString()">{{ formatTime(alert.created_at) }}</span>
                    </div>
                </div>

                <!-- Delete Button -->
                <button
                    @click.stop="emit('delete', alert.id)"
                    class="shrink-0 rounded-lg p-1.5 text-slate-400 transition-all hover:bg-red-50 hover:text-red-500 dark:text-gray-500 dark:hover:bg-red-900/20 dark:hover:text-red-400 opacity-70 group-hover:opacity-100"
                    title="Dismiss alert"
                >
                    <X class="w-4 h-4" />
                </button>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="flex items-start gap-3 rounded-xl bg-green-50 dark:bg-emerald-950/20 p-4 border border-green-200 dark:border-emerald-900/50">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-emerald-900/40 text-green-600 dark:text-emerald-400">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-green-800 dark:text-emerald-400">All Systems Normal</p>
                <p class="text-xs text-green-600 dark:text-emerald-500/80">No maintenance alerts at this time.</p>
            </div>
        </div>
    </div>
</template>
