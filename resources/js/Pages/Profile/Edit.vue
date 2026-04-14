<script setup>
import Sidebar from '@/Components/Sidebar.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import DataBackupForm from './Partials/DataBackupForm.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { User, Lock, DownloadCloud, Settings } from 'lucide-vue-next';
import ThemeToggleForm from './Partials/ThemeToggleForm.vue';

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

    <div class="flex min-h-screen bg-gray-50 dark:bg-gray-950 transition-colors duration-300">
        <!-- Sidebar -->
        <Sidebar />

        <!-- Main Content -->
        <main class="flex-1 transition-[margin] duration-300" style="margin-left: var(--sidebar-width, 4rem);">
            <!-- Header -->
            <header class="flex items-center justify-between px-8 py-6 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 transition-colors duration-300">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Settings</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Manage your account settings and preferences
                    </p>
                </div>
            </header>

            <div class="p-8">
                <div class="max-w-5xl mx-auto">
                    <!-- Tabs -->
                    <div class="flex gap-4 mb-8 border-b border-gray-200 dark:border-gray-800 overflow-x-auto whitespace-nowrap">
                        <button
                            @click="activeTab = 'profile'"
                            class="flex items-center gap-2 px-4 py-3 text-sm font-medium transition-colors border-b-2"
                            :class="activeTab === 'profile' 
                                ? 'text-cyan-600 border-cyan-600 dark:text-cyan-400 dark:border-cyan-400' 
                                : 'text-gray-600 dark:text-gray-400 border-transparent hover:text-gray-900 dark:hover:text-gray-100'"
                        >
                            <User class="w-4 h-4" />
                            Profile Information
                        </button>
                        <button
                            @click="activeTab = 'password'"
                            class="flex items-center gap-2 px-4 py-3 text-sm font-medium transition-colors border-b-2"
                            :class="activeTab === 'password' 
                                ? 'text-cyan-600 border-cyan-600 dark:text-cyan-400 dark:border-cyan-400' 
                                : 'text-gray-600 dark:text-gray-400 border-transparent hover:text-gray-900 dark:hover:text-gray-100'"
                        >
                            <Lock class="w-4 h-4" />
                            Update Password
                        </button>
                        <button
                            @click="activeTab = 'preferences'"
                            class="flex items-center gap-2 px-4 py-3 text-sm font-medium transition-colors border-b-2"
                            :class="activeTab === 'preferences' 
                                ? 'text-cyan-600 border-cyan-600 dark:text-cyan-400 dark:border-cyan-400' 
                                : 'text-gray-600 dark:text-gray-400 border-transparent hover:text-gray-900 dark:hover:text-gray-100'"
                        >
                            <Settings class="w-4 h-4" />
                            Preferences
                        </button>
                        <button
                            @click="activeTab = 'backup'"
                            class="flex items-center gap-2 px-4 py-3 text-sm font-medium transition-colors border-b-2"
                            :class="activeTab === 'backup' 
                                ? 'text-cyan-600 border-cyan-600 dark:text-cyan-400 dark:border-cyan-400' 
                                : 'text-gray-600 dark:text-gray-400 border-transparent hover:text-gray-900 dark:hover:text-gray-100'"
                        >
                            <DownloadCloud class="w-4 h-4" />
                            Data Backup
                        </button>
                    </div>

                    <!-- Tab Content -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-8 transition-colors duration-300">
                        <div v-show="activeTab === 'profile'">
                            <UpdateProfileInformationForm
                                :must-verify-email="mustVerifyEmail"
                                :status="status"
                            />
                        </div>

                        <div v-show="activeTab === 'password'">
                            <UpdatePasswordForm />
                        </div>

                        <div v-show="activeTab === 'preferences'">
                            <ThemeToggleForm />
                        </div>

                        <div v-show="activeTab === 'backup'">
                            <DataBackupForm />
                        </div>

                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
