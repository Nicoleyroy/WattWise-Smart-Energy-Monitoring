<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

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
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in" />

    <div class="min-h-screen bg-slate-100 flex items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="w-full max-w-md">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-slate-900">WattWise</h1>
                <p class="mt-2 text-sm text-slate-500">Smart Energy Monitoring</p>
            </div>

            <!-- Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
                <h2 class="text-xl font-semibold text-slate-900 mb-6">Sign in to your account</h2>

                <div v-if="status" class="mb-4 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-sm font-medium text-emerald-700">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <InputLabel for="email" value="Email" class="text-slate-700 font-medium" />

                        <TextInput
                            id="email"
                            type="email"
                            class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                        />

                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div>
                        <InputLabel for="password" value="Password" class="text-slate-700 font-medium" />

                        <TextInput
                            id="password"
                            type="password"
                            class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                        />

                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <Checkbox name="remember" v-model:checked="form.remember" />
                            <span class="ms-2 text-sm text-slate-600">Remember me</span>
                        </label>

                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm font-medium text-sky-600 hover:text-sky-500 transition"
                        >
                            Forgot password?
                        </Link>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        :class="[
                            'w-full flex justify-center items-center px-4 py-2.5 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 transition',
                            { 'opacity-50 cursor-not-allowed': form.processing }
                        ]"
                    >
                        <span v-if="form.processing">Signing in...</span>
                        <span v-else>Sign in</span>
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-sm text-slate-600">
                        Don't have an account?
                        <Link
                            :href="route('register')"
                            class="font-semibold text-sky-600 hover:text-sky-500 transition"
                        >
                            Sign up
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
