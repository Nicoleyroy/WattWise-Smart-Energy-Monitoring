<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { getApp, getApps, initializeApp } from 'firebase/app';
import { createUserWithEmailAndPassword, getAuth } from 'firebase/auth';
import { getDatabase, ref as dbRef, update } from 'firebase/database';
import { User, Mail, Lock, Zap, Eye, EyeOff, Check, X } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import vueRecaptcha from 'vue3-recaptcha2';

const showPassword = ref(false);
const showConfirmPassword = ref(false);
const siteKey = import.meta.env.VITE_RECAPTCHA_SITE_KEY || '';

const firebaseConfig = {
    apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
    projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
    storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId: import.meta.env.VITE_FIREBASE_APP_ID,
};

const getFirebaseAuthClient = () => {
    const app = getApps().length ? getApp() : initializeApp(firebaseConfig);
    return getAuth(app);
};

const getFirebaseDbClient = () => {
    const app = getApps().length ? getApp() : initializeApp(firebaseConfig);
    return getDatabase(app);
};

const form = useForm({
    name: '',
    email: '',
    deviceId: '',
    password: '',
    password_confirmation: '',
    g_recaptcha_response: '',
});

const handleRecaptcha = (response) => {
    form.g_recaptcha_response = response;
};

const submit = async () => {
    try {
        const auth = getFirebaseAuthClient();
        const credential = await createUserWithEmailAndPassword(auth, form.email, form.password);
        const uid = credential.user.uid;

        form.post(route('register'), {
            onSuccess: async () => {
                const db = getFirebaseDbClient();

                await update(dbRef(db, `devices/${form.deviceId}`), {
                    owner: uid,
                    status: 'linked',
                });

                await update(dbRef(db, `users/${uid}/devices`), {
                    [form.deviceId]: true,
                });
            },
            onFinish: () => form.reset('password', 'password_confirmation'),
        });

        return uid;
    } catch (error) {
        form.setError('email', error?.message || 'Unable to sign up with Firebase.');
        return null;
    }
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

    <div class="relative min-h-screen overflow-hidden bg-slate-950 px-4 py-8 sm:px-6 lg:px-10">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_15%_15%,rgba(34,211,238,0.2),transparent_42%),radial-gradient(circle_at_85%_5%,rgba(59,130,246,0.16),transparent_40%),linear-gradient(180deg,#020617_0%,#0f172a_100%)]"></div>
            <div class="absolute inset-0 opacity-20" style="background-image:linear-gradient(rgba(148,163,184,0.18)_1px,transparent_1px),linear-gradient(90deg,rgba(148,163,184,0.18)_1px,transparent_1px);background-size:36px 36px;"></div>
        </div>

        <div class="relative z-10 mx-auto grid min-h-[calc(100vh-4rem)] w-full max-w-6xl items-center gap-8 lg:grid-cols-[1.1fr_0.9fr]">
            <section class="hidden rounded-3xl border border-cyan-300/15 bg-gradient-to-br from-cyan-500/16 via-blue-500/10 to-cyan-400/12 p-10 shadow-2xl backdrop-blur-md lg:block">
                <p class="mb-6 inline-flex items-center rounded-full border border-cyan-200/25 bg-cyan-300/10 px-4 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-cyan-100">Account Setup</p>
                <h1 class="max-w-xl text-4xl font-bold leading-tight text-white">Create your WattWise workspace.</h1>
                <p class="mt-4 max-w-lg text-base leading-relaxed text-slate-200/95">Set up your account, verify your device identity, and activate secure real-time monitoring in minutes.</p>
                <div class="mt-6 h-px w-full max-w-xl bg-gradient-to-r from-cyan-200/40 via-slate-200/20 to-transparent"></div>
                <div class="mt-7 grid max-w-xl grid-cols-2 gap-3">
                    <div class="rounded-xl border border-slate-200/20 bg-slate-900/30 p-4">
                        <p class="text-[11px] uppercase tracking-[0.16em] text-slate-300">Provisioning</p>
                        <p class="mt-1 text-2xl font-semibold text-white">Guided</p>
                    </div>
                    <div class="rounded-xl border border-slate-200/20 bg-slate-900/30 p-4">
                        <p class="text-[11px] uppercase tracking-[0.16em] text-slate-300">Device Security</p>
                        <p class="mt-1 text-2xl font-semibold text-white">Verified</p>
                    </div>
                </div>
            </section>

            <section class="w-full">
            <div class="mx-auto w-full max-w-xl rounded-3xl border border-slate-700/70 bg-slate-900/85 p-8 shadow-[0_24px_90px_rgba(2,6,23,0.65)] backdrop-blur-xl sm:p-9">
                <div class="mb-6">
                    <p class="mb-3 inline-flex items-center rounded-full border border-cyan-300/30 bg-cyan-400/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-cyan-200">Register</p>
                    <h2 class="text-3xl font-bold text-white">Create your account</h2>
                    <p class="mt-2 text-sm text-slate-300">Complete registration to connect and monitor your devices.</p>
                </div>

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
                                class="block w-full rounded-xl border-slate-600/80 bg-slate-800/60 pl-10 text-white placeholder-slate-400 shadow-sm transition-all focus:border-cyan-500 focus:ring-cyan-500 focus:bg-slate-800"
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
                                class="block w-full rounded-xl border-slate-600/80 bg-slate-800/60 pl-10 text-white placeholder-slate-400 shadow-sm transition-all focus:border-cyan-500 focus:ring-cyan-500 focus:bg-slate-800"
                                v-model="form.email"
                                required
                                autocomplete="username"
                                placeholder="you@example.com"
                            />
                        </div>

                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div>
                        <InputLabel for="deviceId" value="Device ID" class="text-gray-300 font-medium" />

                        <div class="relative mt-2">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <Zap class="h-5 w-5 text-gray-400" />
                            </div>
                            <TextInput
                                id="deviceId"
                                type="text"
                                class="block w-full rounded-xl border-slate-600/80 bg-slate-800/60 pl-10 text-white placeholder-slate-400 shadow-sm transition-all focus:border-cyan-500 focus:ring-cyan-500 focus:bg-slate-800"
                                v-model="form.deviceId"
                                required
                                autocomplete="off"
                                placeholder="Enter your device ID"
                            />
                        </div>

                        <InputError class="mt-2" :message="form.errors.deviceId" />
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
                                class="block w-full rounded-xl border-slate-600/80 bg-slate-800/60 pl-10 pr-10 text-white placeholder-slate-400 shadow-sm transition-all focus:border-cyan-500 focus:ring-cyan-500 focus:bg-slate-800"
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
                        <span v-if="form.processing">Creating account...</span>
                        <span v-else>Create account</span>
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-400">
                        Already have an account?
                        <Link
                            :href="route('login')"
                            class="font-semibold text-cyan-300 hover:text-cyan-200 transition-colors ml-1"
                        >
                            Sign in
                        </Link>
                    </p>
                </div>

                <p class="mt-6 text-center text-xs text-slate-400">© 2026 WattWise. Monitor your energy, save the planet.</p>
            </div>
            </section>
        </div>
    </div>
</template>
