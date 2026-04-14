<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;
const profileImagePreview = ref(user.profile_picture ? `/storage/${user.profile_picture}` : '');
const fileInput = ref(null);

const form = useForm({
    name: user.name,
    profile_picture: null,
});

const selectFile = () => {
    fileInput.value.click();
};

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.profile_picture = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            profileImagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};
</script>

<template>
    <section>
        <header class="mb-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                Profile Information
            </h2>

            <p class="mt-2 text-sm text-gray-700 dark:text-gray-400 font-medium">
                Update your account's profile information and email address.
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="space-y-6"
        >
            <!-- Profile Picture -->
            <div>
                <InputLabel value="Profile Picture" class="text-gray-900 dark:text-gray-100" />
                
                <div class="mt-4 flex items-end gap-6">
                    <div class="flex-shrink-0">
                        <div 
                            v-if="profileImagePreview" 
                            class="h-24 w-24 rounded-lg overflow-hidden bg-gray-200 dark:bg-gray-800"
                        >
                            <img 
                                :src="profileImagePreview" 
                                alt="Profile preview" 
                                class="h-full w-full object-cover"
                            />
                        </div>
                        <div 
                            v-else 
                            class="h-24 w-24 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400 dark:text-gray-500"
                        >
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="selectFile"
                        class="px-4 py-2.5 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 transition-colors font-medium text-sm"
                    >
                        Upload Image
                    </button>

                    <input 
                        ref="fileInput" 
                        type="file" 
                        accept="image/*" 
                        @change="handleFileSelect" 
                        class="hidden"
                    />
                </div>

                <InputError class="mt-2" :message="form.errors.profile_picture" />
            </div>

            <div>
                <InputLabel for="name" value="Name" class="text-gray-900 dark:text-gray-100" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-2 block w-full bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:border-cyan-500 focus:ring-cyan-500"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email" class="text-gray-900 dark:text-gray-100" />

                <input
                    id="email"
                    type="email"
                    class="mt-2 block w-full rounded-md shadow-sm bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 placeholder-gray-500 dark:placeholder-gray-400 cursor-not-allowed focus:border-cyan-500 focus:ring-cyan-500"
                    :value="user.email"
                    disabled
                    readonly
                    autocomplete="username"
                />

                <p class="mt-2 text-xs text-gray-700 dark:text-gray-400 font-medium">Email cannot be changed</p>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800 dark:text-gray-300">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-cyan-600 underline hover:text-cyan-700 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2 focus:ring-offset-white"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4 pt-4">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-6 py-2.5 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
                >
                    Save Changes
                </button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-green-400"
                    >
                        Saved successfully!
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
