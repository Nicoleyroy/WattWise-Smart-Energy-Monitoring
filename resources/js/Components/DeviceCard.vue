<script setup>
import { ref, computed } from 'vue';
import { Settings, Zap } from 'lucide-vue-next';

const props = defineProps({
    name: {
        type: String,
        required: true
    },
    status: {
        type: String,
        default: 'Active',
        validator: (value) => ['Active', 'Standby', 'Offline'].includes(value)
    },
    currentPower: {
        type: Number,
        required: true
    },
    thresholdLimit: {
        type: Number,
        required: true
    },
    todayUsage: {
        type: Number,
        required: true
    },
    isOn: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits(['update:isOn', 'settings']);

const toggleDevice = () => {
    emit('update:isOn', !props.isOn);
};

const openSettings = () => {
    emit('settings');
};

const thresholdPercentage = computed(() => {
    return Math.min((props.currentPower / props.thresholdLimit) * 100, 100);
});

const statusColor = computed(() => {
    return props.status === 'Active' ? 'text-cyan-400' : 
           props.status === 'Standby' ? 'text-gray-400' : 
           'text-red-400';
});

const powerColor = computed(() => {
    const percentage = thresholdPercentage.value;
    if (percentage >= 80) return 'text-red-400';
    if (percentage >= 60) return 'text-yellow-400';
    return 'text-green-400';
});

const progressColor = computed(() => {
    const percentage = thresholdPercentage.value;
    if (percentage >= 80) return 'bg-red-500';
    if (percentage >= 60) return 'bg-yellow-500';
    return 'bg-green-500';
});
</script>

<template>
    <div class="bg-slate-800 border border-slate-700 rounded-xl p-6 hover:border-slate-600 transition-all">
        <!-- Header -->
        <div class="flex items-start justify-between mb-6">
            <div class="flex items-center gap-3">
                <!-- Device Icon -->
                <div class="w-10 h-10 rounded-lg bg-cyan-500/20 flex items-center justify-center">
                    <Zap class="w-5 h-5 text-cyan-400" />
                </div>
                
                <!-- Name & Status -->
                <div>
                    <h3 class="text-white font-medium">{{ name }}</h3>
                    <p class="text-sm" :class="statusColor">{{ status }}</p>
                </div>
            </div>
            
            <!-- Controls -->
            <div class="flex items-center gap-2">
                <button 
                    @click="openSettings"
                    class="p-2 hover:bg-slate-700 rounded-lg transition-colors"
                >
                    <Settings class="w-4 h-4 text-gray-400" />
                </button>
                
                <!-- Toggle Switch -->
                <button
                    @click="toggleDevice"
                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                    :class="isOn ? 'bg-cyan-500' : 'bg-slate-600'"
                >
                    <span
                        class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                        :class="isOn ? 'translate-x-6' : 'translate-x-1'"
                    />
                </button>
            </div>
        </div>
        
        <!-- Current Power -->
        <div class="mb-6">
            <p class="text-sm text-gray-400 mb-1">Current Power</p>
            <div class="flex items-baseline gap-1">
                <span class="text-4xl font-bold" :class="powerColor">
                    {{ currentPower }}
                </span>
                <span class="text-lg text-gray-400">W</span>
            </div>
        </div>
        
        <!-- Threshold Usage -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm text-gray-400">Threshold Usage</p>
                <p class="text-sm text-gray-300">{{ Math.round(thresholdPercentage) }}%</p>
            </div>
            
            <!-- Progress Bar -->
            <div class="w-full h-2 bg-slate-700 rounded-full overflow-hidden">
                <div 
                    class="h-full rounded-full transition-all duration-300"
                    :class="progressColor"
                    :style="{ width: `${thresholdPercentage}%` }"
                />
            </div>
            
            <!-- Min/Max Labels -->
            <div class="flex items-center justify-between mt-1">
                <span class="text-xs text-gray-500">0W</span>
                <span class="text-xs text-gray-500">{{ thresholdLimit }}W limit</span>
            </div>
        </div>
        
        <!-- Today's Usage -->
        <div class="flex items-center justify-between pt-4 border-t border-slate-700">
            <div class="flex items-center gap-2 text-cyan-400">
                <Zap class="w-4 h-4" />
                <span class="text-sm">Today's Usage</span>
            </div>
            <span class="text-white font-semibold">{{ todayUsage }} kWh</span>
        </div>
    </div>
</template>
