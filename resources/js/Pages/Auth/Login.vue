<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Mail, Lock, Zap, Eye, EyeOff } from 'lucide-vue-next';
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
    <Head title="Log in" />

    <div class="relative min-h-screen overflow-hidden bg-slate-950 px-4 py-8 sm:px-6 lg:px-10">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(34,211,238,0.2),transparent_42%),radial-gradient(circle_at_80%_0%,rgba(59,130,246,0.16),transparent_40%),linear-gradient(180deg,#020617_0%,#0f172a_100%)]"></div>
            <div class="absolute inset-0 opacity-20" style="background-image:linear-gradient(rgba(148,163,184,0.18)_1px,transparent_1px),linear-gradient(90deg,rgba(148,163,184,0.18)_1px,transparent_1px);background-size:36px 36px;"></div>
        </div>

        <div class="relative z-10 mx-auto grid min-h-[calc(100vh-4rem)] w-full max-w-6xl items-center gap-8 lg:grid-cols-[1.15fr_0.85fr]">
            <section class="hidden rounded-3xl border border-cyan-300/15 bg-gradient-to-br from-cyan-500/16 via-blue-500/10 to-cyan-400/12 p-10 shadow-2xl backdrop-blur-md lg:block">
                <p class="mb-6 inline-flex items-center rounded-full border border-cyan-200/25 bg-cyan-300/10 px-4 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-cyan-100">Operations Overview</p>
                <h1 class="max-w-xl text-4xl font-bold leading-tight text-white">Access your WattWise control center.</h1>
                <p class="mt-4 max-w-lg text-base leading-relaxed text-slate-200/95">Track connected devices, review real-time energy performance, and respond to anomalies with confidence.</p>
                <div class="mt-6 h-px w-full max-w-xl bg-gradient-to-r from-cyan-200/40 via-slate-200/20 to-transparent"></div>
                <div class="mt-7 grid max-w-xl grid-cols-2 gap-3">
                    <div class="rounded-xl border border-slate-200/20 bg-slate-900/30 p-4">
                        <p class="text-[11px] uppercase tracking-[0.16em] text-slate-300">System Availability</p>
                        <p class="mt-1 text-2xl font-semibold text-white">99.9%</p>
                    </div>
                    <div class="rounded-xl border border-slate-200/20 bg-slate-900/30 p-4">
                        <p class="text-[11px] uppercase tracking-[0.16em] text-slate-300">Alert Latency</p>
                        <p class="mt-1 text-2xl font-semibold text-white">&lt; 5s</p>
                    </div>
                </div>
            </section>

            <section class="w-full">
                <div class="mx-auto w-full max-w-lg rounded-3xl border border-slate-700/70 bg-slate-900/85 p-8 shadow-[0_24px_90px_rgba(2,6,23,0.65)] backdrop-blur-xl sm:p-9">
                    <div class="mb-6">
                        <p class="mb-3 inline-flex items-center rounded-full border border-cyan-300/30 bg-cyan-400/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-cyan-200">Sign In</p>
                        <h2 class="text-3xl font-bold text-white">Sign in to your account</h2>
                        <p class="mt-2 text-sm text-slate-300">Continue to your WattWise monitoring workspace.</p>
                    </div>

                <div v-if="status" class="mb-6 p-4 rounded-xl bg-green-500/20 border border-green-500/30 text-sm font-medium text-green-400 flex items-center gap-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <InputLabel for="email" value="Email Address" class="text-gray-300 font-medium" />

                        <div class="relative mt-2">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <Mail class="h-5 w-5 text-gray-400" />
                            </div>
                            <TextInput
                                id="email"
                                type="email"
                                class="block w-full rounded-xl border-slate-600/80 bg-slate-800/60 pl-10 text-white placeholder-slate-400 shadow-sm transition-all focus:border-cyan-500 focus:ring-cyan-500 focus:bg-slate-800"
                                v-model="form.email"
                                required
                                autofocus
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
                                class="block w-full rounded-xl border-slate-600/80 bg-slate-800/60 pl-10 pr-10 text-white placeholder-slate-400 shadow-sm transition-all focus:border-cyan-500 focus:ring-cyan-500 focus:bg-slate-800"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
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

                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center group cursor-pointer">
                            <Checkbox name="remember" v-model:checked="form.remember" class="rounded border-slate-600 bg-slate-700 text-cyan-500 focus:ring-cyan-500 focus:ring-offset-slate-800" />
                            <span class="ms-2 text-sm text-gray-300 group-hover:text-white transition-colors">Remember me</span>
                        </label>

                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm font-medium text-cyan-300 hover:text-cyan-200 transition-colors"
                        >
                            Forgot password?
                        </Link>
                    </div>

                    <div class="flex justify-center mt-4">
                        <vue-recaptcha 
                            :sitekey="siteKey"
                            size="normal" 
                            theme="dark"
                            @verify="handleRecaptcha" 
                        />
                        <InputError class="mt-2" :message="form.errors.g_recaptcha_response" />
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        :class="[
                            'w-full flex justify-center items-center px-4 py-3 border border-cyan-500/20 rounded-xl shadow-lg shadow-cyan-900/20 text-base font-semibold text-white bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500',
                            { 'opacity-50 cursor-not-allowed': form.processing }
                        ]"
                    >
                        <Zap v-if="!form.processing" class="w-5 h-5 mr-2" />
                        <svg v-else class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span v-if="form.processing">Signing in...</span>
                        <span v-else>Sign in</span>
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-400">
                        Don't have an account?
                        <Link
                            :href="route('register')"
                            class="font-semibold text-cyan-300 hover:text-cyan-200 transition-colors ml-1"
                        >
                            Create an account
                        </Link>
                    </p>
                </div>

                <p class="mt-6 text-center text-xs text-slate-400">© 2026 WattWise. Monitor your energy, save the planet.</p>
            </div>
            </section>
        </div>
    </div>
</template>
