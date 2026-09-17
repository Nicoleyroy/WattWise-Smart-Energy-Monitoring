<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { ArrowRight, PlugZap } from 'lucide-vue-next'
import Sidebar from '@/Components/Sidebar.vue'

defineProps({
	devices: { type: Array, default: () => [] },
})
</script>

<template>
	<Head title="Devices" />
	<div class="flex min-h-screen bg-slate-50 dark:bg-gray-950">
		<Sidebar />
		<main class="flex-1 px-4 pb-8 pt-6 sm:px-6 lg:px-8" style="margin-left: var(--sidebar-width, 4rem);">
			<div class="mx-auto max-w-7xl">
				<header class="mb-6 rounded-xl border border-slate-200 bg-white px-6 py-5 shadow-sm dark:border-slate-800 dark:bg-slate-950">
					<p class="mb-1 text-[11px] font-semibold uppercase tracking-wider text-cyan-600 dark:text-cyan-400">Device inventory</p>
					<h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-gray-100">Devices</h1>
					<p class="mt-1 text-sm text-slate-500 dark:text-gray-400">View connected WattWise plugs and open their controls.</p>
				</header>
				<div class="grid gap-4 md:grid-cols-2">
					<article v-for="device in devices" :key="device.id" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950">
						<div class="flex items-start justify-between gap-4">
							<div class="flex items-center gap-3"><div class="rounded-lg bg-cyan-50 p-2.5 text-cyan-600 dark:bg-cyan-950/40 dark:text-cyan-400"><PlugZap class="h-5 w-5" /></div><div><h2 class="font-bold text-slate-900 dark:text-gray-100">{{ device.name }}</h2><p class="text-sm text-slate-500 dark:text-slate-400">{{ device.category || `Plug ${device.id}` }}</p></div></div>
							<span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="device.status === 'online' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'">{{ device.status }}</span>
						</div>
						<div class="mt-5 grid grid-cols-2 gap-3 text-sm"><div><p class="text-slate-500 dark:text-slate-400">Power</p><p class="mt-1 font-semibold text-slate-900 dark:text-gray-100">{{ device.power }} kW</p></div><div><p class="text-slate-500 dark:text-slate-400">Daily usage</p><p class="mt-1 font-semibold text-slate-900 dark:text-gray-100">{{ device.daily_usage }} kWh</p></div></div>
						<Link :href="route('device.settings', { id: device.id })" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-cyan-600 hover:text-cyan-700 dark:text-cyan-400">Open device <ArrowRight class="h-4 w-4" /></Link>
					</article>
				</div>
			</div>
		</main>
	</div>
</template>
