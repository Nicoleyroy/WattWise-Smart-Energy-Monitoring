<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { 
    Zap, 
    BarChart3, 
    Smartphone, 
    Clock, 
    Plug, 
    Search, 
    Activity, 
    ChevronRight,
    ShieldCheck,
    Cpu,
    BookOpen,
    MousePointerClick,
    SlidersHorizontal,
    Calculator
} from 'lucide-vue-next';
import { ref } from 'vue';

const features = [
    {
        icon: Zap,
        title: 'Real-time Monitoring',
        description: 'Track instantaneous power consumption with millisecond precision and high-fidelity data visualization.'
    },
    {
        icon: Smartphone,
        title: 'Remote Device Control',
        description: 'Toggle appliances and manage your power grid from anywhere in the world through our secure cloud interface.'
    },
    {
        icon: BarChart3,
        title: 'Energy Usage Tracking',
        description: 'Analyze historical data patterns and receive AI-driven insights to optimize your monthly energy expenditure.'
    },
    {
        icon: Clock,
        title: 'Smart Scheduling',
        description: 'Automate your home with intelligent scheduling that adapts to your lifestyle and peak energy hours.'
    }
];

const steps = [
    {
        icon: Plug,
        title: 'Connect your device',
        description: 'Easily integrate our smart sensors with your existing electrical infrastructure.'
    },
    {
        icon: Search,
        title: 'Monitor energy usage',
        description: 'Watch your consumption patterns emerge on our intuitive real-time dashboard.'
    },
    {
        icon: Activity,
        title: 'Control and automate',
        description: 'Set custom thresholds and schedules to maintain peak efficiency automatically.'
    }
];

const mobileMenuOpen = ref(false);
</script>

<template>
    <Head title="WattWise - Professional Energy Monitoring" />

    <div class="min-h-screen bg-charcoal-900 text-gray-300 font-sans selection:bg-electric/30">
        <!-- Navigation -->
        <nav class="fixed top-0 left-0 right-0 z-50 bg-charcoal-900/90 backdrop-blur-md border-b border-white/5">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="flex items-center justify-between h-20">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-electric rounded flex items-center justify-center">
                            <Zap class="w-5 h-5 text-white" />
                        </div>
                        <span class="text-xl font-bold tracking-tight text-white uppercase">WattWise</span>
                    </div>

                    <div class="hidden md:flex items-center gap-10">
                        <a href="#features" class="text-sm font-medium hover:text-electric transition-colors">Features</a>
                        <a href="#how-it-works" class="text-sm font-medium hover:text-electric transition-colors">How It Works</a>
                        <a href="#user-manual" class="text-sm font-medium hover:text-electric transition-colors">User Manual</a>
                        <a href="#about" class="text-sm font-medium hover:text-electric transition-colors">About</a>
                        <div class="h-4 w-px bg-white/10 mx-2"></div>
                        <Link :href="route('login')" class="text-sm font-medium hover:text-white transition-colors">Login</Link>
                        <Link :href="route('register')" class="bg-electric hover:bg-electric-hover text-white px-5 py-2.5 rounded-md text-sm font-bold transition-all shadow-lg shadow-electric/20">
                            Get Started
                        </Link>
                    </div>

                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
            <!-- Mobile Menu -->
            <div v-if="mobileMenuOpen" class="md:hidden bg-charcoal-800 border-b border-white/5 px-6 py-6 space-y-4">
                <a @click="mobileMenuOpen = false" href="#features" class="block text-lg font-medium">Features</a>
                <a @click="mobileMenuOpen = false" href="#how-it-works" class="block text-lg font-medium">How It Works</a>
                <a @click="mobileMenuOpen = false" href="#user-manual" class="block text-lg font-medium">User Manual</a>
                <a @click="mobileMenuOpen = false" href="#about" class="block text-lg font-medium">About</a>
                <hr class="border-white/5">
                <Link :href="route('login')" class="block text-lg font-medium">Login</Link>
                <Link :href="route('register')" class="block bg-electric text-white px-4 py-3 rounded text-center font-bold">Get Started</Link>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="relative pt-40 pb-24 lg:pt-56 lg:pb-40 px-6 lg:px-8">
            <div class="max-w-7xl mx-auto grid lg:grid-cols-2 gap-16 items-center">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-electric/10 border border-electric/20 text-electric text-xs font-bold uppercase tracking-wider mb-6">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-electric opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-electric"></span>
                        </span>
                        v2.0 Systems Online
                    </div>
                    <h1 class="text-5xl lg:text-7xl font-extrabold text-white leading-[1.1] mb-6">
                        WattWise <br>
                        <span class="text-electric">Energy Intelligence.</span>
                    </h1>
                    <p class="text-xl text-gray-400 mb-10 max-w-xl leading-relaxed">
                        Precision monitoring and intelligent control for the modern energy consumer. Optimize your grid, reduce costs, and take command of your electrical footprint.
                    </p>
                    <div class="flex flex-wrap gap-4">
                        <Link :href="route('register')" class="px-8 py-4 bg-electric hover:bg-electric-hover text-white rounded-md font-bold text-lg transition-all flex items-center gap-2 group shadow-xl shadow-electric/25">
                            Start Monitoring
                            <ChevronRight class="w-5 h-5 group-hover:translate-x-1 transition-transform" />
                        </Link>
                        <Link :href="route('login')" class="px-8 py-4 bg-white/5 hover:bg-white/10 text-white border border-white/10 rounded-md font-bold text-lg transition-all">
                            Login to Dashboard
                        </Link>
                    </div>
                </div>

                <div class="relative hidden lg:block">
                    <!-- Dashboard Mockup -->
                    <div class="bg-charcoal-800 rounded-xl border border-white/10 shadow-2xl p-6 overflow-hidden transform rotate-2 hover:rotate-0 transition-transform duration-700">
                        <div class="flex items-center justify-between mb-8">
                            <div class="flex gap-2">
                                <div class="w-3 h-3 rounded-full bg-red-500/50"></div>
                                <div class="w-3 h-3 rounded-full bg-yellow-500/50"></div>
                                <div class="w-3 h-3 rounded-full bg-muted-green/50"></div>
                            </div>
                            <div class="h-6 px-3 rounded bg-white/5 text-[10px] text-gray-500 flex items-center uppercase tracking-widest font-bold">Live Feed</div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="p-4 rounded-lg bg-white/5 border border-white/5">
                                <div class="text-xs text-gray-500 mb-1">Current Load</div>
                                <div class="text-2xl font-bold text-electric">2.45 kW</div>
                                <div class="w-full bg-white/5 h-1.5 rounded-full mt-3 overflow-hidden">
                                    <div class="bg-electric h-full w-2/3"></div>
                                </div>
                            </div>
                            <div class="p-4 rounded-lg bg-white/5 border border-white/5">
                                <div class="text-xs text-gray-500 mb-1">Energy Used (Today)</div>
                                <div class="text-2xl font-bold text-muted-green">5.20 kWh</div>
                                <div class="flex items-center gap-1 text-[10px] text-muted-green mt-2 font-bold uppercase">
                                    <Activity class="w-3 h-3" /> Efficiency: 94%
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div v-for="i in 3" :key="i" class="flex items-center justify-between p-3 rounded bg-white/5 border border-white/5">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded bg-electric/20 flex items-center justify-center">
                                        <Cpu v-if="i==1" class="w-4 h-4 text-electric" />
                                        <Plug v-else-if="i==2" class="w-4 h-4 text-electric" />
                                        <Activity v-else class="w-4 h-4 text-electric" />
                                    </div>
                                    <div class="text-sm font-medium text-white">{{ ['HVAC System', 'Water Heater', 'Smart Plug'][i-1] }}</div>
                                </div>
                                <div class="text-xs font-mono text-gray-500">{{ ['Active', 'Standby', 'Active'][i-1] }}</div>
                            </div>
                        </div>

                        <!-- Graph preview -->
                        <div class="mt-6 pt-6 border-t border-white/5 flex items-end justify-between h-24 gap-1">
                            <div v-for="h in [40, 65, 30, 85, 45, 60, 95, 40, 55, 75, 50, 60]" :key="h" 
                                 class="flex-1 bg-electric/20 hover:bg-electric transition-colors rounded-t-sm"
                                 :style="{ height: h + '%' }">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Decorative Elements -->
                    <div class="absolute -top-10 -right-10 w-64 h-64 bg-electric/20 blur-[100px] rounded-full pointer-events-none -z-10"></div>
                    <div class="absolute -bottom-10 -left-10 w-64 h-64 bg-muted-green/10 blur-[100px] rounded-full pointer-events-none -z-10"></div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="py-32 bg-charcoal-800 relative overflow-hidden">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
                <div class="text-center mb-24">
                    <h2 class="text-sm font-bold text-electric tracking-[0.2em] uppercase mb-4">Core Capabilities</h2>
                    <h3 class="text-4xl lg:text-5xl font-extrabold text-white mb-6 leading-tight">Engineered for Efficiency.</h3>
                    <p class="text-gray-400 max-w-2xl mx-auto text-lg leading-relaxed">
                        Built on industrial-grade protocols to deliver high-precision energy management tools for residential and commercial environments.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div v-for="feature in features" :key="feature.title" 
                        class="p-10 rounded-xl bg-charcoal-900 border border-white/5 hover:border-electric/50 transition-all group">
                        <div class="w-14 h-14 rounded-lg bg-white/5 flex items-center justify-center mb-8 group-hover:bg-electric/10 transition-colors">
                            <component :is="feature.icon" class="w-7 h-7 text-white group-hover:text-electric transition-colors" />
                        </div>
                        <h4 class="text-xl font-bold text-white mb-4">{{ feature.title }}</h4>
                        <p class="text-gray-400 text-sm leading-relaxed">{{ feature.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works Section -->
        <section id="how-it-works" class="py-32 px-6 lg:px-8 bg-charcoal-900">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-24">
                    <h2 class="text-sm font-bold text-electric tracking-[0.2em] uppercase mb-4">The Process</h2>
                    <h3 class="text-4xl lg:text-5xl font-extrabold text-white">Three Steps to Optimization.</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-12 relative">
                    <!-- Connector Line (Desktop) -->
                    <div class="hidden md:block absolute top-16 left-0 w-full h-px bg-gradient-to-r from-transparent via-white/10 to-transparent -z-0"></div>
                    
                    <div v-for="(step, index) in steps" :key="step.title" class="relative z-10 text-center flex flex-col items-center">
                        <div class="w-12 h-12 rounded-full bg-charcoal-900 border-2 border-electric flex items-center justify-center mb-8 shadow-[0_0_20px_rgba(0,112,243,0.3)]">
                            <span class="text-electric font-bold">{{ index + 1 }}</span>
                        </div>
                        <div class="w-16 h-16 rounded-xl bg-white/5 flex items-center justify-center mb-8 group hover:bg-electric transition-all cursor-default">
                            <component :is="step.icon" class="w-8 h-8 text-gray-400 group-hover:text-white" />
                        </div>
                        <h4 class="text-xl font-bold text-white mb-4">{{ step.title }}</h4>
                        <p class="text-gray-400 max-w-xs leading-relaxed">{{ step.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- User Manual Section -->
        <section id="user-manual" class="py-28 px-6 lg:px-8 bg-charcoal-800 border-y border-white/5">
            <div class="max-w-7xl mx-auto">
                <div class="max-w-3xl mb-14">
                    <div class="inline-flex items-center gap-2 text-electric text-sm font-bold uppercase tracking-[0.2em] mb-4">
                        <BookOpen class="w-4 h-4" /> User Manual
                    </div>
                    <h2 class="text-4xl lg:text-5xl font-extrabold text-white mb-5">Use WattWise with confidence.</h2>
                    <p class="text-lg text-gray-400 leading-relaxed">A quick guide to monitoring consumption, controlling your plugs, and tracking your electricity costs.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <article class="rounded-xl bg-charcoal-900 border border-white/5 p-7">
                        <MousePointerClick class="w-7 h-7 text-electric mb-5" />
                        <h3 class="text-xl font-bold text-white mb-3">1. Get started and navigate</h3>
                        <p class="text-sm leading-relaxed text-gray-400">Create an account, then open the Dashboard. Use the sidebar to select Plug 1 or Plug 2. The plug currently open is highlighted with a cyan background, glowing dot, and OPEN label.</p>
                    </article>
                    <article class="rounded-xl bg-charcoal-900 border border-white/5 p-7">
                        <Activity class="w-7 h-7 text-electric mb-5" />
                        <h3 class="text-xl font-bold text-white mb-3">2. Monitor live electricity use</h3>
                        <p class="text-sm leading-relaxed text-gray-400">In a plug’s Live Monitoring tab, view voltage, current, frequency, power factor, current power, optimal efficiency, and total energy. Hover a metric card to see a short explanation of what it measures.</p>
                    </article>
                    <article class="rounded-xl bg-charcoal-900 border border-white/5 p-7">
                        <SlidersHorizontal class="w-7 h-7 text-electric mb-5" />
                        <h3 class="text-xl font-bold text-white mb-3">3. Control and automate a plug</h3>
                        <p class="text-sm leading-relaxed text-gray-400">Use the ON/OFF button at the top of Plug Settings to control the selected plug. In General Settings, set a daily, weekly, or monthly energy limit and create schedules to automatically switch it on or off.</p>
                    </article>
                    <article class="rounded-xl bg-charcoal-900 border border-white/5 p-7">
                        <Calculator class="w-7 h-7 text-electric mb-5" />
                        <h3 class="text-xl font-bold text-white mb-3">4. Track your monthly electricity cost</h3>
                        <p class="text-sm leading-relaxed text-gray-400">Enter your expected monthly kWh and electricity rate per kWh in the Monthly Electricity Bill section. WattWise calculates the plug’s consumed cost and estimated savings or overage from its live monthly energy reading.</p>
                    </article>
                </div>

                <div class="mt-8 rounded-xl border border-electric/20 bg-electric/5 p-6 text-sm text-gray-300 leading-relaxed">
                    <span class="font-bold text-white">Tip:</span> Electricity billing uses kilowatt-hours (kWh). Your rate should be entered as the cost per kWh—not per watt. You can also export a plug’s energy readings from General Settings for your own records.
                </div>
            </div>
        </section>

        <!-- About Section -->
        <section id="about" class="py-32 px-6 lg:px-8 border-y border-white/5 bg-charcoal-800">
            <div class="max-w-4xl mx-auto text-center">
                <div class="inline-flex p-3 rounded-2xl bg-muted-green/10 mb-8">
                    <ShieldCheck class="w-10 h-10 text-muted-green" />
                </div>
                <h3 class="text-4xl font-extrabold text-white mb-8">Empowering Sustainable Habits.</h3>
                <p class="text-xl text-gray-400 leading-relaxed">
                    WattWise was conceived at the intersection of IoT innovation and environmental responsibility. 
                    Our mission is to provide every consumer with the tools necessary to demystify their energy consumption. 
                    By transforming raw data into actionable intelligence, we facilitate a more sustainable and cost-effective approach to electrical utility management.
                </p>
            </div>
        </section>

        <!-- Call-to-Action Section -->
        <section class="py-32 px-6 lg:px-8 relative overflow-hidden">
            <!-- Background Glow -->
            <div class="absolute inset-0 bg-electric/5 pointer-events-none"></div>
            
            <div class="max-w-5xl mx-auto text-center relative z-10">
                <h2 class="text-4xl lg:text-6xl font-extrabold text-white mb-8 leading-tight">
                    Ready to master your <br> <span class="text-electric">energy consumption?</span>
                </h2>
                <p class="text-xl text-gray-400 mb-12 max-w-2xl mx-auto">
                    Join thousands of homeowners and facilities managers who have optimized their grid with WattWise.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-6">
                    <Link :href="route('register')" class="w-full sm:w-auto px-10 py-5 bg-electric hover:bg-electric-hover text-white rounded-md font-bold text-xl transition-all shadow-2xl shadow-electric/40">
                        Start Monitoring Now
                    </Link>
                    <Link :href="route('login')" class="w-full sm:w-auto px-10 py-5 bg-white/5 hover:bg-white/10 text-white border border-white/10 rounded-md font-bold text-xl transition-all">
                        View Demo Dashboard
                    </Link>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-charcoal-900 py-20 px-6 lg:px-8 border-t border-white/5">
            <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-12">
                <div class="md:col-span-2">
                    <div class="flex items-center gap-2 mb-6">
                        <div class="w-8 h-8 bg-electric rounded flex items-center justify-center">
                            <Zap class="w-5 h-5 text-white" />
                        </div>
                        <span class="text-xl font-bold tracking-tight text-white uppercase">WattWise</span>
                    </div>
                    <p class="text-gray-500 max-w-sm leading-relaxed mb-8 text-sm">
                        Advanced energy intelligence systems for high-performance power monitoring and automation. 
                        Optimize your consumption patterns and reduce utility overhead with precision IoT hardware and cloud analytics.
                    </p>
                    <div class="text-xs text-gray-600 font-mono">
                        © 2026 WATTWISE SYSTEMS CORP. ALL RIGHTS RESERVED.
                    </div>
                </div>

                <div>
                    <h5 class="text-white font-bold mb-6 text-sm uppercase tracking-widest">Platform</h5>
                    <ul class="space-y-4 text-sm font-medium">
                        <li><a href="#features" class="hover:text-electric transition-colors">Features</a></li>
                        <li><a href="#how-it-works" class="hover:text-electric transition-colors">How It Works</a></li>
                        <li><a href="#user-manual" class="hover:text-electric transition-colors">User Manual</a></li>
                        <li><Link :href="route('login')" class="hover:text-electric transition-colors">Dashboard</Link></li>
                        <li><Link :href="route('register')" class="hover:text-electric transition-colors">New Registry</Link></li>
                    </ul>
                </div>

                <div>
                    <h5 class="text-white font-bold mb-6 text-sm uppercase tracking-widest">Connect</h5>
                    <ul class="space-y-4 text-sm font-medium">
                        <li><a href="#" class="hover:text-electric transition-colors">Support Center</a></li>
                        <li><a href="#" class="hover:text-electric transition-colors">API Documentation</a></li>
                        <li><a href="#" class="hover:text-electric transition-colors">System Status</a></li>
                        <li><a href="#" class="hover:text-electric transition-colors">Contact Engineering</a></li>
                    </ul>
                </div>
            </div>
        </footer>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Figtree:wght@300;400;500;600;700;800;900&display=swap');

html {
    scroll-behavior: smooth;
}

body {
    font-family: 'Figtree', sans-serif;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}
</style>
