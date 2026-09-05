<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Mail, Lock, Zap, Eye, EyeOff, ShieldCheck, ChevronRight, Activity, Loader2 } from 'lucide-vue-next';
import { ref } from 'vue';
import vueRecaptcha from 'vue3-recaptcha2';

const showPassword = ref(false);
const siteKey = import.meta.env.VITE_RECAPTCHA_SITE_KEY || '';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
    g_recaptcha_response: '',
});

const handleRecaptcha = (response) => {
    form.g_recaptcha_response = response;
};

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log In - WattWise" />

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
                        Manage your <br>
                        <span class="text-electric">home energy easily.</span>
                    </h1>
                    <p class="text-gray-400 text-lg leading-relaxed max-w-sm mb-12">
                        See your live usage, device status, and alerts in one place.
                    </p>

                    <div class="space-y-6">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-lg bg-white/5 flex items-center justify-center flex-shrink-0">
                                <ShieldCheck class="w-5 h-5 text-electric" />
                            </div>
                            <div>
                                <h4 class="text-white font-semibold text-sm mb-1">Secure Login</h4>
                                <p class="text-[11px] text-gray-500 font-medium">Your account and data are protected.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-lg bg-white/5 flex items-center justify-center flex-shrink-0">
                                <Activity class="w-5 h-5 text-muted-green" />
                            </div>
                            <div>
                                <h4 class="text-white font-semibold text-sm mb-1">Live Updates</h4>
                                <p class="text-[11px] text-gray-500 font-medium">Get real-time device readings and alerts.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Subtle Decorative Graph -->
                <div class="absolute bottom-0 left-0 right-0 h-32 opacity-20 pointer-events-none flex items-end gap-1 px-4">
                    <div v-for="h in [30, 45, 25, 60, 40, 70, 50, 85, 45, 60, 30, 50, 75, 40, 60, 80, 50, 30]" :key="h" 
                         class="flex-1 bg-electric rounded-t-sm"
                         :style="{ height: h + '%' }">
                    </div>
                </div>
            </div>

            <!-- Right Side: Login Form -->
            <div class="p-8 lg:p-16 flex flex-col justify-center">
                <div class="lg:hidden flex items-center gap-2 mb-10">
                    <div class="w-8 h-8 bg-electric rounded flex items-center justify-center">
                        <Zap class="w-5 h-5 text-white fill-current" />
                    </div>
                    <span class="text-xl font-bold tracking-tight text-white">WattWise</span>
                </div>

                <div class="mb-10">
                    <h2 class="text-3xl font-bold text-white mb-2 tracking-tight">Welcome back</h2>
                    <p class="text-gray-400">Log in to continue.</p>
                </div>

                <div v-if="status" class="mb-8 p-4 rounded-lg bg-muted-green/10 border border-muted-green/20 text-xs font-bold text-muted-green flex items-center gap-3">
                    <ShieldCheck class="w-5 h-5" />
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-2">
                        <InputLabel for="email" value="Email" class="text-xs font-medium !text-gray-300 ml-1" />
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-electric text-gray-500">
                                <Mail class="h-4.5 w-4.5" />
                            </div>
                            <TextInput
                                id="email"
                                type="email"
                                class="block w-full rounded-md border-white/10 !bg-charcoal-900 pl-11 py-3.5 text-sm !text-white placeholder-gray-500 caret-white shadow-sm transition-all focus:border-electric focus:ring-1 focus:ring-electric"
                                v-model="form.email"
                                required
                                autofocus
                                placeholder="you@example.com"
                            />
                        </div>
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between px-1">
                            <InputLabel for="password" value="Password" class="text-xs font-medium !text-gray-300" />
                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-xs font-medium text-electric hover:text-white transition-colors"
                            >
                                Forgot password?
                            </Link>
                        </div>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-electric text-gray-500">
                                <Lock class="h-4.5 w-4.5" />
                            </div>
                            <TextInput
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                class="block w-full rounded-md border-white/10 !bg-charcoal-900 pl-11 pr-11 py-3.5 text-sm !text-white placeholder-gray-500 caret-white shadow-sm transition-all focus:border-electric focus:ring-1 focus:ring-electric"
                                v-model="form.password"
                                required
                                placeholder="••••••••••••"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-500 hover:text-white transition-colors"
                            >
                                <Eye v-if="!showPassword" class="h-4.5 w-4.5" />
                                <EyeOff v-else class="h-4.5 w-4.5" />
                            </button>
                        </div>
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="flex items-center px-1">
                        <label class="flex items-center group cursor-pointer">
                            <Checkbox name="remember" v-model:checked="form.remember" class="rounded border-white/10 bg-charcoal-900 text-electric focus:ring-electric" />
                            <span class="ms-3 text-xs font-medium text-gray-400 group-hover:text-gray-300 transition-colors">Remember me</span>
                        </label>
                    </div>

                    <div v-if="siteKey" class="flex justify-center p-4 bg-charcoal-900/50 rounded-lg border border-white/5">
                        <vue-recaptcha :sitekey="siteKey" size="normal" theme="dark" @verify="handleRecaptcha" />
                    </div>
                    <InputError :message="form.errors.g_recaptcha_response" class="text-xs text-center" />

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full flex justify-center items-center px-6 py-4 bg-electric hover:bg-electric-hover text-white rounded-md font-bold text-sm transition-all shadow-xl shadow-electric/25 group disabled:opacity-50"
                    >
                        <Loader2 v-if="form.processing" class="w-5 h-5 mr-2 animate-spin" />
                        <span v-if="form.processing">Logging in...</span>
                        <span v-else class="flex items-center gap-2">
                            Log in
                            <ChevronRight class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                        </span>
                    </button>
                </form>

                <div class="mt-12 pt-8 border-t border-white/5 text-center">
                    <p class="text-xs text-gray-400">
                        Don't have an account?
                        <Link
                            :href="route('register')"
                            class="text-electric hover:text-white transition-colors ml-1"
                        >
                            Create one
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
