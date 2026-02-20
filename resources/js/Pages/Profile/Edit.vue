<script setup>
import Sidebar from '@/Components/Sidebar.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { User, Lock, Trash2 } from 'lucide-vue-next';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const activeTab = ref('profile');
</script>

<template>
    <Head title="Settings" />

    <div class="flex min-h-screen bg-slate-900">
        <!-- Sidebar -->
        <Sidebar />

        <!-- Main Content -->
        <main class="flex-1 ml-16">
            <!-- Header -->
            <header class="flex items-center justify-between px-8 py-6 border-b border-slate-700 bg-slate-800">
                <div>
                    <h1 class="text-2xl font-bold text-white">Settings</h1>
                    <p class="text-sm text-gray-400 mt-1">
                        Manage your account settings and preferences
                    </p>
                </div>
            </header>

            <div class="p-8">
                <div class="max-w-5xl mx-auto">
                    <!-- Tabs -->
                    <div class="flex gap-4 mb-8 border-b border-slate-700">
                        <button
                            @click="activeTab = 'profile'"
                            class="flex items-center gap-2 px-4 py-3 text-sm font-medium transition-colors border-b-2"
                            :class="activeTab === 'profile' 
                                ? 'text-cyan-400 border-cyan-400' 
                                : 'text-gray-400 border-transparent hover:text-white'"
                        >
                            <User class="w-4 h-4" />
                            Profile Information
                        </button>
                        <button
                            @click="activeTab = 'password'"
                            class="flex items-center gap-2 px-4 py-3 text-sm font-medium transition-colors border-b-2"
                            :class="activeTab === 'password' 
                                ? 'text-cyan-400 border-cyan-400' 
                                : 'text-gray-400 border-transparent hover:text-white'"
                        >
                            <Lock class="w-4 h-4" />
                            Update Password
                        </button>
                        <button
                            @click="activeTab = 'delete'"
                            class="flex items-center gap-2 px-4 py-3 text-sm font-medium transition-colors border-b-2"
                            :class="activeTab === 'delete' 
                                ? 'text-red-400 border-red-400' 
                                : 'text-gray-400 border-transparent hover:text-white'"
                        >
                            <Trash2 class="w-4 h-4" />
                            Delete Account
                        </button>
                    </div>

                    <!-- Tab Content -->
                    <div class="bg-slate-800 border border-slate-700 rounded-xl p-8">
                        <div v-show="activeTab === 'profile'">
                            <UpdateProfileInformationForm
                                :must-verify-email="mustVerifyEmail"
                                :status="status"
                            />
                        </div>

                        <div v-show="activeTab === 'password'">
                            <UpdatePasswordForm />
                        </div>

                        <div v-show="activeTab === 'delete'">
                            <DeleteUserForm />
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
