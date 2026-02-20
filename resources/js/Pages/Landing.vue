<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { Zap, BarChart3, Bell, Shield, TrendingDown, Smartphone, Users, CheckCircle } from 'lucide-vue-next';
import { ref } from 'vue';

const features = [
    {
        icon: 'chart',
        title: 'Real-Time Monitoring',
        description: 'Track your energy consumption in real-time with detailed analytics and insights.'
    },
    {
        icon: 'bell',
        title: 'Smart Alerts',
        description: 'Get notified when your energy usage exceeds custom thresholds.'
    },
    {
        icon: 'trending',
        title: 'Cost Savings',
        description: 'Reduce your electricity bills by identifying and eliminating energy waste.'
    },
    {
        icon: 'smartphone',
        title: 'Multi-Device Support',
        description: 'Monitor multiple devices and outlets from a single dashboard.'
    },
    {
        icon: 'shield',
        title: 'Secure & Private',
        description: 'Your data is encrypted and stored securely with enterprise-grade protection.'
    },
    {
        icon: 'users',
        title: 'Easy Setup',
        description: 'Get started in minutes with our simple plug-and-play IoT devices.'
    }
];


const mobileMenuOpen = ref(false);
</script>

<template>
    <Head title="Smart Energy Monitoring - WattWise" />

    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
        <!-- Navigation -->
        <nav class="fixed top-0 left-0 right-0 z-50 bg-slate-900/80 backdrop-blur-xl border-b border-slate-700/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Logo -->
                    <div class="flex items-center gap-2">
                       
                        <span class="text-xl font-bold text-white">WattWise</span>
                    </div>

                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex items-center gap-8">
                        <a href="#features" class="text-gray-300 hover:text-cyan-400 transition-colors">Features</a>
                        <a href="#how-it-works" class="text-gray-300 hover:text-cyan-400 transition-colors">How It Works</a>
                        <Link :href="route('login')" class="text-gray-300 hover:text-cyan-400 transition-colors">Sign In</Link>
                       
                    </div>

                    <!-- Mobile Menu Button -->
                    <button 
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        class="md:hidden text-white p-2"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Mobile Menu -->
                <div v-if="mobileMenuOpen" class="md:hidden py-4 space-y-3">
                    <a href="#features" class="block text-gray-300 hover:text-cyan-400 transition-colors">Features</a>
                    <a href="#how-it-works" class="block text-gray-300 hover:text-cyan-400 transition-colors">How It Works</a>
            
                    <Link :href="route('login')" class="block text-gray-300 hover:text-cyan-400 transition-colors">Sign In</Link>
                    <Link :href="route('register')" class="block text-cyan-400 font-semibold">Get Started</Link>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="relative pt-32 pb-20 px-4 sm:px-6 lg:px-8 overflow-hidden">
            <!-- Animated Background -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute top-20 left-10 w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl animate-pulse"></div>
                <div class="absolute bottom-20 right-10 w-[500px] h-[500px] bg-blue-500/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
            </div>

            <div class="max-w-7xl mx-auto relative z-10">
                <div class="text-center max-w-4xl mx-auto">
                    
                    
                    <h1 class="text-5xl sm:text-6xl lg:text-7xl font-bold text-white mb-6 leading-tight">
                       <br> Monitor Your Energy,
                        <span class="bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent"> Save the Planet</span>
                    </h1>
                    
                    <p class="text-xl text-gray-300 mb-10 max-w-2xl mx-auto">
                        Take control of your electricity consumption with real-time monitoring, smart alerts, and detailed analytics. Save money while reducing your carbon footprint.
                    </p>
<br>
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                      
                        <a 
                            href="#features"
                            class="px-8 py-4 bg-slate-700/50 text-white rounded-lg font-semibold text-lg border border-slate-600 hover:bg-slate-700 transition-all"
                        >
                            Learn More
                        </a>
                    </div>
<br>
                    <!-- Stats -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mt-20">
                        <div v-for="stat in stats" :key="stat.label" class="text-center">
                            <div class="text-3xl md:text-4xl font-bold text-cyan-400 mb-2">{{ stat.value }}</div>
                            <div class="text-sm text-gray-400">{{ stat.label }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="py-20 px-4 sm:px-6 lg:px-8 bg-slate-800/30">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold text-white mb-4">Powerful Features</h2>
                    <p class="text-xl text-gray-400 max-w-2xl mx-auto">
                        Everything you need to monitor and optimize your energy consumption
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div 
                        v-for="feature in features" 
                        :key="feature.title"
                        class="bg-slate-800/50 backdrop-blur-xl rounded-2xl p-8 border border-slate-700/50 hover:border-cyan-500/50 transition-all hover:shadow-xl hover:shadow-cyan-500/10"
                    >
                        <div class="flex items-center justify-center w-14 h-14 rounded-xl bg-gradient-to-br from-cyan-500/20 to-blue-600/20 mb-6">
                            <BarChart3 v-if="feature.icon === 'chart'" class="w-7 h-7 text-cyan-400" />
                            <Bell v-else-if="feature.icon === 'bell'" class="w-7 h-7 text-cyan-400" />
                            <TrendingDown v-else-if="feature.icon === 'trending'" class="w-7 h-7 text-cyan-400" />
                            <Smartphone v-else-if="feature.icon === 'smartphone'" class="w-7 h-7 text-cyan-400" />
                            <Shield v-else-if="feature.icon === 'shield'" class="w-7 h-7 text-cyan-400" />
                            <Users v-else class="w-7 h-7 text-cyan-400" />
                        </div>
                        <h3 class="text-xl font-semibold text-white mb-3">{{ feature.title }}</h3>
                        <p class="text-gray-400">{{ feature.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works Section -->
        <section id="how-it-works" class="py-20 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold text-white mb-4">How It Works</h2>
                    <p class="text-xl text-gray-400 max-w-2xl mx-auto">
                        Get started in three simple steps
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="text-center">
                        <div class="flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-cyan-500 to-blue-600 text-white text-2xl font-bold mx-auto mb-6">
                            1
                        </div>
                        <h3 class="text-xl font-semibold text-white mb-3">Connect Device</h3>
                        <p class="text-gray-400">Plug in our IoT device to your power outlet and connect it to your WiFi network.</p>
                    </div>

                    <div class="text-center">
                        <div class="flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-cyan-500 to-blue-600 text-white text-2xl font-bold mx-auto mb-6">
                            2
                        </div>
                        <h3 class="text-xl font-semibold text-white mb-3">Create Account</h3>
                        <p class="text-gray-400">Sign up for free and add your devices to start monitoring your energy usage.</p>
                    </div>

                    <div class="text-center">
                        <div class="flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-cyan-500 to-blue-600 text-white text-2xl font-bold mx-auto mb-6">
                            3
                        </div>
                        <h3 class="text-xl font-semibold text-white mb-3">Monitor & Save</h3>
                        <p class="text-gray-400">Track your consumption in real-time and start saving on your electricity bills.</p>
                    </div>
                </div>
            </div>
        </section>



        <!-- Footer -->
        <footer class="border-t border-slate-700/50 py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="flex flex-col md:flex-row items-center justify-between">
                    <div class="flex items-center gap-2 mb-4 md:mb-0">
                       
                        <span class="text-xl font-bold text-white">WattWise</span>
                    </div>
                    <p class="text-sm text-gray-400">
                        © 2026 WattWise. Monitor your energy, save the planet.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
