<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

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
</script>

<template>
    <Head title="Register" />

    <div class="min-h-screen bg-slate-100 flex items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="w-full max-w-md">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-slate-900">WattWise</h1>
                <p class="mt-2 text-sm text-slate-500">Smart Energy Monitoring</p>
            </div>

            <!-- Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
                <h2 class="text-xl font-semibold text-slate-900 mb-6">Create your account</h2>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <InputLabel for="name" value="Name" class="text-slate-700 font-medium" />

                        <TextInput
                            id="name"
                            type="text"
                            class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500"
                            v-model="form.name"
                            required
                            autofocus
                            autocomplete="name"
                        />

                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="email" value="Email" class="text-slate-700 font-medium" />

                        <TextInput
                            id="email"
                            type="email"
                            class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500"
                            v-model="form.email"
                            required
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
                            autocomplete="new-password"
                        />

                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div>
                        <InputLabel
                            for="password_confirmation"
                            value="Confirm Password"
                            class="text-slate-700 font-medium"
                        />

                        <TextInput
                            id="password_confirmation"
                            type="password"
                            class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500"
                            v-model="form.password_confirmation"
                            required
                            autocomplete="new-password"
                        />

                        <InputError
                            class="mt-2"
                            :message="form.errors.password_confirmation"
                        />
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        :class="[
                            'w-full flex justify-center items-center px-4 py-2.5 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 transition',
                            { 'opacity-50 cursor-not-allowed': form.processing }
                        ]"
                    >
                        <span v-if="form.processing">Creating account...</span>
                        <span v-else>Create account</span>
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-sm text-slate-600">
                        Already have an account?
                        <Link
                            :href="route('login')"
                            class="font-semibold text-sky-600 hover:text-sky-500 transition"
                        >
                            Sign in
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
