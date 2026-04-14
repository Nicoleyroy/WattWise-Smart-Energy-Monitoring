<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import { BookOpen, Zap, Activity, ShieldCheck, HelpCircle, ArrowLeft, Info, Power, SlidersHorizontal } from 'lucide-vue-next';

const terms = [
    {
        name: 'Voltage',
        symbol: 'V',
        unit: 'Volts',
        icon: Zap,
        color: 'text-yellow-500',
        bgColor: 'bg-yellow-50',
        description: 'Electrical pressure from a power source. In standard Philippines outlets, this is typically between 220V and 240V. Think of it like water pressure in a pipe.'
    },
    {
        name: 'Current',
        symbol: 'A',
        unit: 'Amperes',
        icon: Activity,
        color: 'text-purple-500',
        bgColor: 'bg-purple-50',
        description: 'The rate at which electricity flows through the device. Higher current means more electrons are moving through the circuit per second.'
    },
    {
        name: 'Power',
        symbol: 'W',
        unit: 'Watts',
        icon: Power,
        color: 'text-cyan-500',
        bgColor: 'bg-cyan-50',
        description: 'The real-time rate of energy consumption, calculated as Voltage × Current. This tells you exactly how much electricity your device is using at this very moment.'
    },
    {
        name: 'Energy',
        symbol: 'kWh',
        unit: 'Kilowatt-hours',
        icon: BookOpen,
        color: 'text-orange-500',
        bgColor: 'bg-orange-50',
        description: 'The total amount of electricity consumed over a period of time. One kWh is equal to using 1,000 watts for one hour. This is the metric power companies use for billing.'
    },
    {
        name: 'Frequency',
        symbol: 'Hz',
        unit: 'Hertz',
        icon: SlidersHorizontal,
        color: 'text-blue-500',
        bgColor: 'bg-blue-50',
        description: 'The number of times the alternating current (AC) changes direction per second. The standard frequency in the Philippines is 60Hz.'
    },
    {
        name: 'Power Factor',
        symbol: 'PF',
        unit: 'Ratio (0.0 - 1.0)',
        icon: ShieldCheck,
        color: 'text-green-500',
        bgColor: 'bg-green-50',
        description: 'A measure of how efficiently the device uses the supplied electricity. A value of 1.0 is ideal (100% efficiency), while lower values indicate some energy is being wasted as heat or reactive power.'
    },
    {
        name: 'Relay Status',
        symbol: 'ON/OFF',
        unit: 'State',
        icon: Power,
        color: 'text-slate-700',
        bgColor: 'bg-slate-100',
        description: 'Indicates whether the internal electronic switch (relay) is closed (ON) or open (OFF). When OFF, the WattWise plug physically disconnects the power to your appliance.'
    }
];
</script>

<template>
    <Head title="Definition of Terms" />

    <div class="flex min-h-screen bg-slate-50 dark:bg-gray-950 transition-colors duration-300">
        <Sidebar />

        <main class="flex-1 p-8 bg-slate-50 dark:bg-gray-950 transition-all duration-300" style="margin-left: var(--sidebar-width, 4rem);">
            <div class="max-w-4xl mx-auto">
                <!-- Header -->
                <div class="mb-10">
                    <Link 
                        href="/dashboard" 
                        class="inline-flex items-center gap-2 text-slate-500 dark:text-gray-400 hover:text-cyan-600 dark:hover:text-cyan-400 mb-6 transition-colors group font-bold"
                    >
                        <ArrowLeft class="w-4 h-4 group-hover:-translate-x-1 transition-transform" />
                        <span>Back to Dashboard</span>
                    </Link>

                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-cyan-500 rounded-2xl shadow-xl shadow-cyan-500/20">
                            <BookOpen class="w-8 h-8 text-white" />
                        </div>
                        <div>
                            <h1 class="text-3xl font-black text-slate-900 dark:text-gray-100 tracking-tight">Definition of Terms</h1>
                            <p class="text-slate-500 dark:text-gray-400 font-medium tracking-wide">Understanding your energy consumption metrics</p>
                        </div>
                    </div>
                </div>

                <!-- Terms Grid -->
                <div class="grid gap-6">
                    <div 
                        v-for="term in terms" 
                        :key="term.name"
                        class="bg-white dark:bg-gray-900 rounded-2xl p-6 shadow-sm border border-slate-100 dark:border-gray-800 hover:border-cyan-200 dark:hover:border-cyan-900/50 hover:shadow-xl dark:hover:shadow-cyan-900/10 transition-all group"
                    >
                        <div class="flex items-start gap-6">
                            <div :class="['p-4 rounded-xl shrink-0 transition-all group-hover:scale-110 shadow-sm', 
                                term.bgColor === 'bg-yellow-50' ? 'bg-yellow-50 dark:bg-yellow-900/20' : 
                                term.bgColor === 'bg-purple-50' ? 'bg-purple-50 dark:bg-purple-900/20' : 
                                term.bgColor === 'bg-cyan-50' ? 'bg-cyan-50 dark:bg-cyan-900/20' : 
                                term.bgColor === 'bg-orange-50' ? 'bg-orange-50 dark:bg-orange-900/20' : 
                                term.bgColor === 'bg-blue-50' ? 'bg-blue-50 dark:bg-blue-900/20' : 
                                term.bgColor === 'bg-green-50' ? 'bg-green-50 dark:bg-green-900/20' : 
                                'bg-slate-100 dark:bg-gray-800']">
                                <component :is="term.icon" :class="['w-7 h-7', term.color]" />
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-2">
                                    <h2 class="text-xl font-bold text-slate-900 dark:text-gray-100 tracking-tight">{{ term.name }}</h2>
                                    <span class="px-3 py-1 bg-slate-100 dark:bg-gray-800 rounded-full text-[10px] font-black text-slate-500 dark:text-gray-400 uppercase tracking-widest border border-slate-200 dark:border-gray-700">
                                        {{ term.symbol }} ({{ term.unit }})
                                    </span>
                                </div>
                                <p class="text-slate-600 dark:text-gray-400 leading-relaxed font-medium border-l-4 border-slate-100 dark:border-gray-800 pl-4 py-1.5 italic">
                                    {{ term.description }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FAQ Section -->
                <div class="mt-12 p-10 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 rounded-[2.5rem] text-white shadow-2xl relative overflow-hidden ring-1 ring-white/10">
                    <div class="absolute top-0 right-0 p-8 opacity-5">
                        <HelpCircle class="w-48 h-48" />
                    </div>
                    <div class="relative z-10">
                        <h3 class="text-2xl font-black mb-4 flex items-center gap-3 tracking-tight">
                            <Info class="w-7 h-7 text-cyan-400" />
                            Why do these metrics matter?
                        </h3>
                        <p class="text-slate-300 dark:text-gray-400 leading-relaxed mb-10 max-w-2xl text-lg font-medium">
                            Monitoring these values helps you identify energy-hungry appliances, detect potential electrical issues (like voltage drops or power surges), and optimize your usage to save on electricity costs.
                        </p>
                        <div class="grid md:grid-cols-2 gap-6">
                            <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-6 border border-white/10 hover:bg-white/10 transition-colors">
                                <h4 class="font-black text-cyan-400 mb-3 uppercase tracking-widest text-xs">Safety Monitoring</h4>
                                <p class="text-sm text-slate-400 dark:text-gray-500 font-medium leading-relaxed">Regularly checking Voltage and Current can help prevent overheating and electrical fires.</p>
                            </div>
                            <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-6 border border-white/10 hover:bg-white/10 transition-colors">
                                <h4 class="font-black text-cyan-400 mb-3 uppercase tracking-widest text-xs">Cost Optimization</h4>
                                <p class="text-sm text-slate-400 dark:text-gray-500 font-medium leading-relaxed">Understanding Watts and kWh allows you to calculate exactly how much each device contributes to your bill.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
