<script setup>
import { useTheme } from '@/Composables/useTheme';
import { Sun, Moon, Monitor, CheckCircle2 } from 'lucide-vue-next';

const { theme: currentTheme, setTheme } = useTheme();

const themeOptions = [
    {
        id: 'light',
        name: 'Light',
        description: 'Clean and bright interface for daytime use',
        icon: Sun,
        color: 'text-orange-500',
        bg: 'bg-orange-50',
        border: 'border-orange-100'
    },
    {
        id: 'dark',
        name: 'Dark',
        description: 'Sleek dark interface that\'s easy on the eyes',
        icon: Moon,
        color: 'text-blue-500',
        bg: 'bg-blue-50',
        border: 'border-blue-100'
    },
    {
        id: 'system',
        name: 'System',
        description: 'Automatically switch based on your OS settings',
        icon: Monitor,
        color: 'text-gray-500',
        bg: 'bg-gray-50',
        border: 'border-gray-100'
    }
];
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Appearance Settings
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Customize how WattWise looks on your device. Choose a theme that fits your environment.
            </p>
        </header>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <button
                v-for="option in themeOptions"
                :key="option.id"
                @click="setTheme(option.id)"
                class="relative flex flex-col items-start p-4 rounded-xl border-2 transition-all duration-300 text-left group"
                :class="[
                    currentTheme === option.id
                        ? 'border-cyan-500 bg-cyan-50/30 dark:bg-cyan-900/10'
                        : 'border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 hover:border-gray-200 dark:hover:border-gray-700'
                ]"
            >
                <!-- Selected Checkmark -->
                <div 
                    v-if="currentTheme === option.id"
                    class="absolute top-3 right-3 text-cyan-500"
                >
                    <CheckCircle2 class="w-5 h-5 fill-cyan-50" />
                </div>

                <!-- Icon Container -->
                <div 
                    class="p-2 rounded-lg mb-4 transition-transform group-hover:scale-110"
                    :class="[option.bg, option.color, 'dark:bg-gray-800']"
                >
                    <component :is="option.icon" class="w-6 h-6" />
                </div>

                <!-- Content -->
                <h3 class="font-bold text-gray-900 dark:text-gray-100 mb-1">
                    {{ option.name }}
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                    {{ option.description }}
                </p>
            </button>
        </div>

        <!-- Visual Preview Simulation -->
        <div class="mt-10 p-6 rounded-2xl border border-dashed border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-3 h-3 rounded-full bg-red-400"></div>
                <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
                <div class="ml-auto text-[10px] font-mono text-gray-400 uppercase tracking-widest">Live Preview Sample</div>
            </div>
            
            <div class="space-y-4">
                <div class="h-4 bg-white dark:bg-gray-800 rounded-lg w-3/4 shadow-sm"></div>
                <div class="grid grid-cols-3 gap-3">
                    <div class="h-20 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-center">
                        <div class="w-8 h-8 rounded-full bg-cyan-100 dark:bg-cyan-900/30"></div>
                    </div>
                    <div class="h-20 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700"></div>
                    <div class="h-20 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700"></div>
                </div>
                <div class="h-4 bg-white dark:bg-gray-800 rounded-lg w-1/2 shadow-sm"></div>
            </div>
        </div>
    </section>
</template>
