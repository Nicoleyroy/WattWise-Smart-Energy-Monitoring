<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { User, Mail, Lock, Zap, Eye, EyeOff, Loader2, Cpu, ChevronRight, ShieldCheck, Activity } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import vueRecaptcha from 'vue3-recaptcha2';

const showPassword = ref(false);
const showConfirmPassword = ref(false);
const siteKey = import.meta.env.VITE_RECAPTCHA_SITE_KEY || '';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    g_recaptcha_response: '',
});

const handleRecaptcha = (response) => {
    form.g_recaptcha_response = response;
};

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const passwordStrength = computed(() => {
    const password = form.password;
    if (!password) return { score: 0, color: 'bg-white/5' };
    let score = 0;
    if (password.length >= 8) score++;
    if (password.length >= 12) score++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
    if (/\d/.test(password)) score++;
    if (/[^a-zA-Z0-9]/.test(password)) score++;
    const colors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-green-500', 'bg-emerald-500'];
    return { score, color: colors[score - 1] || 'bg-red-500' };
});
</script>

<template>
    <Head title="Register - WattWise" />

    <div class="min-h-screen bg-charcoal-900 flex items-center justify-center p-6 selection:bg-electric/30">
        <!-- Background Elements -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-electric/10 blur-[120px] rounded-full"></div>
            <div class="absolute -bottom-[10%] -right-[10%] w-[40%] h-[40%] bg-muted-green/5 blur-[120px] rounded-full"></div>
        </div>

        <div class="w-full max-w-[1100px] grid lg:grid-cols-2 bg-charcoal-800 rounded-2xl border border-white/5 shadow-2xl overflow-hidden relative z-10">
            <!-- Left Side: Branding/Visual -->
            <div class="hidden lg:flex flex-col justify-between p-12 bg-charcoal-900 border-r border-white/5 relative overflow-hidden">
                <div class="relative z-10">
                    <Link href="/" class="flex items-center gap-2 mb-16 group">
                        <div class="w-10 h-10 bg-electric rounded flex items-center justify-center group-hover:scale-110 transition-transform">
                            <Zap class="w-6 h-6 text-white fill-current" />
                        </div>
                        <span class="text-2xl font-bold tracking-tight text-white">WattWise</span>
                    </Link>

                    <h1 class="text-4xl font-extrabold text-white leading-tight mb-6">
                        Create your <br>
                        <span class="text-electric">WattWise account.</span>
                    </h1>
                    <p class="text-gray-400 text-lg leading-relaxed max-w-sm mb-12">
                        Set up your account to start monitoring energy use.
                    </p>

                    <div class="space-y-6">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-lg bg-white/5 flex items-center justify-center flex-shrink-0">
                                <ShieldCheck class="w-5 h-5 text-electric" />
                            </div>
                            <div>
                                <h4 class="text-white font-semibold text-sm mb-1">Quick Setup</h4>
                                <p class="text-[11px] text-gray-500 font-medium">Create your account in just a few steps.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-lg bg-white/5 flex items-center justify-center flex-shrink-0">
                                <Activity class="w-5 h-5 text-muted-green" />
                            </div>
                            <div>
                                <h4 class="text-white font-semibold text-sm mb-1">Live Sync</h4>
                                <p class="text-[11px] text-gray-500 font-medium">Your data updates in real time.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Subtle Decorative Graph -->
                <div class="absolute bottom-0 left-0 right-0 h-32 opacity-20 pointer-events-none flex items-end gap-1 px-4">
                    <div v-for="h in [20, 35, 45, 30, 65, 40, 75, 50, 85, 45, 60, 40, 55, 75, 40, 60, 80, 50]" :key="h" 
                         class="flex-1 bg-electric rounded-t-sm"
                         :style="{ height: h + '%' }">
                    </div>
                </div>
            </div>

            <!-- Right Side: Registration Form -->
            <div class="p-8 lg:p-12 flex flex-col justify-center">
                <div class="lg:hidden flex items-center gap-2 mb-10">
                    <div class="w-8 h-8 bg-electric rounded flex items-center justify-center">
                        <Zap class="w-5 h-5 text-white fill-current" />
                    </div>
                    <span class="text-xl font-bold tracking-tight text-white">WattWise</span>
                </div>

                <div class="mb-8">
                    <h2 class="text-3xl font-bold text-white mb-2 tracking-tight">Create your account</h2>
                    <p class="text-gray-400">Fill in your details below.</p>
                </div>

                <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                    <!-- Name -->
                    <div class="space-y-1.5 md:col-span-2">
                        <InputLabel for="name" value="Full name" class="text-xs font-medium text-gray-400 ml-1" />
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-electric text-gray-500">
                                <User class="h-4 w-4" />
                            </div>
                            <TextInput
                                id="name"
                                type="text"
                                class="block w-full rounded-md border-white/10 bg-charcoal-900 pl-11 py-2.5 text-sm text-white placeholder-gray-700 shadow-sm focus:border-electric focus:ring-1 focus:ring-electric"
                                v-model="form.name"
                                required
                                autofocus
                                placeholder="Full name"
                            />
                        </div>
                        <InputError :message="form.errors.name" />
                    </div>

                    <!-- Email -->
                    <div class="space-y-1.5">
                        <InputLabel for="email" value="Email" class="text-xs font-medium !text-gray-300 ml-1" />
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-electric text-gray-500">
                                <Mail class="h-4 w-4" />
                            </div>
                            <TextInput
                                id="email"
                                type="email"
                                class="block w-full rounded-md border-white/10 !bg-charcoal-900 pl-11 py-2.5 text-sm !text-white placeholder-gray-500 caret-white shadow-sm focus:border-electric focus:ring-1 focus:ring-electric"
                                v-model="form.email"
                                required
                                placeholder="you@example.com"
                            />
                        </div>
                        <InputError :message="form.errors.email" />
                    </div>

                    <!-- Password -->
                    <div class="space-y-1.5">
                        <InputLabel for="password" value="Password" class="text-xs font-medium !text-gray-300 ml-1" />
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-electric text-gray-500">
                                <Lock class="h-4 w-4" />
                            </div>
                            <TextInput
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                class="block w-full rounded-md border-white/10 !bg-charcoal-900 pl-11 pr-11 py-2.5 text-sm !text-white placeholder-gray-500 caret-white shadow-sm focus:border-electric focus:ring-1 focus:ring-electric"
                                v-model="form.password"
                                required
                                placeholder="••••••••"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-500 hover:text-white transition-colors"
                            >
                                <Eye v-if="!showPassword" class="h-4 w-4" />
                                <EyeOff v-else class="h-4 w-4" />
                            </button>
                        </div>
                        
                        <div v-if="form.password" class="flex gap-1 h-0.5 mt-2 px-1">
                            <div v-for="i in 5" :key="i" class="flex-1 rounded-full overflow-hidden bg-white/5">
                                <div v-if="i <= passwordStrength.score" :class="passwordStrength.color" class="h-full transition-all duration-500"></div>
                            </div>
                        </div>
                        <InputError :message="form.errors.password" />
                    </div>

                    <!-- Confirm Password -->
                    <div class="space-y-1.5">
                        <InputLabel for="password_confirmation" value="Confirm password" class="text-xs font-medium !text-gray-300 ml-1" />
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-electric text-gray-500">
                                <Lock class="h-4 w-4" />
                            </div>
                            <TextInput
                                id="password_confirmation"
                                :type="showConfirmPassword ? 'text' : 'password'"
                                class="block w-full rounded-md border-white/10 !bg-charcoal-900 pl-11 pr-11 py-2.5 text-sm !text-white placeholder-gray-500 caret-white shadow-sm focus:border-electric focus:ring-1 focus:ring-electric"
                                v-model="form.password_confirmation"
                                required
                                placeholder="••••••••"
                            />
                            <button
                                type="button"
                                @click="showConfirmPassword = !showConfirmPassword"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-500 hover:text-white transition-colors"
                            >
                                <Eye v-if="!showConfirmPassword" class="h-4 w-4" />
                                <EyeOff v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <!-- Action & Recaptcha -->
                    <div class="md:col-span-2 space-y-6 pt-4">
                        <div v-if="siteKey" class="flex justify-center p-3 bg-charcoal-900/50 rounded-lg border border-white/5">
                            <vue-recaptcha :sitekey="siteKey" size="normal" theme="dark" @verify="handleRecaptcha" />
                        </div>
                        <InputError :message="form.errors.g_recaptcha_response" class="text-xs text-center" />

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full flex justify-center items-center px-6 py-4 bg-electric hover:bg-electric-hover text-white rounded-md font-bold text-sm transition-all shadow-xl shadow-electric/25 group disabled:opacity-50"
                        >
                            <Loader2 v-if="form.processing" class="w-5 h-5 mr-2 animate-spin" />
                            <span v-if="form.processing">Creating account...</span>
                            <span v-else class="flex items-center gap-2">
                                Create account
                                <ChevronRight class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                            </span>
                        </button>
                    </div>
                </form>

                <div class="mt-8 pt-6 border-t border-white/5 text-center">
                    <p class="text-xs text-gray-400">
                        Already have an account?
                        <Link
                            :href="route('login')"
                            class="text-electric hover:text-white transition-colors ml-1"
                        >
                            Log in
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus,
input:-webkit-autofill:active {
    -webkit-text-fill-color: #ffffff;
    caret-color: #ffffff;
    box-shadow: 0 0 0 1000px #060d1b inset;
    transition: background-color 9999s ease-in-out 0s;
}
</style>
