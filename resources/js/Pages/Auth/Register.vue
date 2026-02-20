<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { User, Mail, Lock, Zap, Eye, EyeOff, Check, X } from 'lucide-vue-next';
import { ref, computed } from 'vue';

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

// Password strength checker
const passwordStrength = computed(() => {
    const password = form.password;
    if (!password) return { score: 0, label: '', color: '', percentage: 0 };
    
    let score = 0;
    
    // Length check
    if (password.length >= 8) score++;
    if (password.length >= 12) score++;
    
    // Complexity checks
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++; // Mixed case
    if (/\d/.test(password)) score++; // Numbers
    if (/[^a-zA-Z0-9]/.test(password)) score++; // Special characters
    
    const strength = {
        0: { label: '', color: '', percentage: 0 },
        1: { label: 'Weak', color: 'bg-red-500', percentage: 20 },
        2: { label: 'Fair', color: 'bg-orange-500', percentage: 40 },
        3: { label: 'Good', color: 'bg-yellow-500', percentage: 60 },
        4: { label: 'Strong', color: 'bg-green-500', percentage: 80 },
        5: { label: 'Very Strong', color: 'bg-emerald-500', percentage: 100 }
    };
    
    return { ...strength[score], score };
});

const passwordRequirements = computed(() => [
    { met: form.password.length >= 8, text: 'At least 8 characters' },
    { met: /[a-z]/.test(form.password) && /[A-Z]/.test(form.password), text: 'Mixed case letters' },
    { met: /\d/.test(form.password), text: 'Contains numbers' },
    { met: /[^a-zA-Z0-9]/.test(form.password), text: 'Special characters' }
]);
</script>

<template>
    <Head title="Register" />

    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 flex items-center justify-center px-4 py-12 sm:px-6 lg:px-8 relative overflow-hidden">
        <!-- Animated Background Elements -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-20 left-10 w-72 h-72 bg-cyan-500/10 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-20 right-10 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-cyan-500/5 rounded-full blur-3xl"></div>
        </div>

        <div class="w-full max-w-md relative z-10">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-white mb-2">WattWise</h1>
                <p class="text-cyan-400 text-sm font-medium">Smart Energy Monitoring</p>
            </div>

            <!-- Card -->
            <div class="bg-slate-800/80 backdrop-blur-xl rounded-2xl shadow-2xl border border-slate-700/50 p-8">
                <h2 class="text-2xl font-bold text-white mb-2">Create your account</h2>
                <p class="text-gray-400 text-sm mb-6">Start monitoring your energy consumption</p>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <InputLabel for="name" value="Full Name" class="text-gray-300 font-medium" />

                        <div class="relative mt-2">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <User class="h-5 w-5 text-gray-400" />
                            </div>
                            <TextInput
                                id="name"
                                type="text"
                                class="block w-full pl-10 rounded-lg border-slate-600 bg-slate-700/50 text-white placeholder-gray-400 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 focus:bg-slate-700 transition-all"
                                v-model="form.name"
                                required
                                autofocus
                                autocomplete="name"
                                placeholder="John Doe"
                            />
                        </div>

                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="email" value="Email Address" class="text-gray-300 font-medium" />

                        <div class="relative mt-2">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <Mail class="h-5 w-5 text-gray-400" />
                            </div>
                            <TextInput
                                id="email"
                                type="email"
                                class="block w-full pl-10 rounded-lg border-slate-600 bg-slate-700/50 text-white placeholder-gray-400 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 focus:bg-slate-700 transition-all"
                                v-model="form.email"
                                required
                                autocomplete="username"
                                placeholder="you@example.com"
                            />
                        </div>

                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div>
                        <InputLabel for="password" value="Password" class="text-gray-300 font-medium" />

                        <div class="relative mt-2">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <Lock class="h-5 w-5 text-gray-400" />
                            </div>
                            <TextInput
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                class="block w-full pl-10 pr-10 rounded-lg border-slate-600 bg-slate-700/50 text-white placeholder-gray-400 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 focus:bg-slate-700 transition-all"
                                v-model="form.password"
                                required
                                autocomplete="new-password"
                                placeholder="••••••••"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-300 transition-colors"
                            >
                                <Eye v-if="!showPassword" class="h-5 w-5" />
                                <EyeOff v-else class="h-5 w-5" />
                            </button>
                        </div>

                        <!-- Password Strength Indicator -->
                        <div v-if="form.password" class="mt-3 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-400">Password strength:</span>
                                <span class="text-xs font-medium" :class="{
                                    'text-red-400': passwordStrength.score <= 2,
                                    'text-yellow-400': passwordStrength.score === 3,
                                    'text-green-400': passwordStrength.score >= 4
                                }">
                                    {{ passwordStrength.label }}
                                </span>
                            </div>
                            <div class="w-full h-2 bg-slate-700 rounded-full overflow-hidden">
                                <div 
                                    class="h-full transition-all duration-300 rounded-full"
                                    :class="passwordStrength.color"
                                    :style="{ width: passwordStrength.percentage + '%' }"
                                ></div>
                            </div>
                            
                            <!-- Password Requirements -->
                            <div class="mt-3 space-y-1.5">
                                <div 
                                    v-for="(req, index) in passwordRequirements" 
                                    :key="index"
                                    class="flex items-center gap-2 text-xs transition-colors"
                                    :class="req.met ? 'text-green-400' : 'text-gray-500'"
                                >
                                    <Check v-if="req.met" class="h-3.5 w-3.5" />
                                    <X v-else class="h-3.5 w-3.5" />
                                    <span>{{ req.text }}</span>
                                </div>
                            </div>
                        </div>

                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div>
                        <InputLabel
                            for="password_confirmation"
                            value="Confirm Password"
                            class="text-gray-300 font-medium"
                        />

                        <div class="relative mt-2">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <Lock class="h-5 w-5 text-gray-400" />
                            </div>
                            <TextInput
                                id="password_confirmation"
                                :type="showConfirmPassword ? 'text' : 'password'"
                                class="block w-full pl-10 pr-10 rounded-lg border-slate-600 bg-slate-700/50 text-white placeholder-gray-400 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 focus:bg-slate-700 transition-all"
                                v-model="form.password_confirmation"
                                required
                                autocomplete="new-password"
                                placeholder="••••••••"
                            />
                            <button
                                type="button"
                                @click="showConfirmPassword = !showConfirmPassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-300 transition-colors"
                            >
                                <Eye v-if="!showConfirmPassword" class="h-5 w-5" />
                                <EyeOff v-else class="h-5 w-5" />
                            </button>
                        </div>

                        <InputError
                            class="mt-2"
                            :message="form.errors.password_confirmation"
                        />
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        :class="[
                            'w-full flex justify-center items-center px-4 py-3 border border-transparent rounded-lg shadow-lg text-base font-semibold text-white bg-blue-600 ',
                            { 'opacity-50 cursor-not-allowed': form.processing }
                        ]"
                    >
                        <Zap v-if="!form.processing" class="w-5 h-5 mr-2" />
                        <svg v-else class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span v-if="form.processing">Creating account...</span>
                        <span v-else>Create account</span>
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-400">
                        Already have an account?
                        <Link
                            :href="route('login')"
                            class="font-semibold text-cyan-400 hover:text-cyan-300 transition-colors ml-1"
                        >
                            Sign in
                        </Link>
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <p class="mt-8 text-center text-sm text-gray-400">
                © 2026 WattWise. Monitor your energy, save the planet.
            </p>
        </div>
    </div>
</template>
