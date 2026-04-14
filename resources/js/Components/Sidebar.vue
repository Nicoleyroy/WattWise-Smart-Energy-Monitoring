<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import Modal from '@/Components/Modal.vue';

const page = usePage();
const notificationCount = computed(() => page.props.auth.unread_notifications_count || 0);
const user = computed(() => page.props.auth.user);
const profileImageUrl = computed(() => {
    return user.value?.profile_picture ? `/storage/${user.value.profile_picture}` : '';
});

const isActive = (routeName) => {
    return page.url === route(routeName).toString().replace(window.location.origin, '');
};

const isDeviceSettingsActive = computed(() => /^\/device\/\d+\/settings/.test(page.url));
const isDeviceSettingsActiveFor = (deviceId) => new RegExp(`^/device/${deviceId}/settings`).test(page.url);
const isExpanded = ref(localStorage.getItem('sidebar_expanded') !== 'false');

const labelClass = computed(() =>
    isExpanded.value
    ? 'opacity-100 max-w-[180px]'
    : 'opacity-0 max-w-0 overflow-hidden',
);

const subMenuClass = computed(() =>
    isExpanded.value
        ? 'max-h-28 opacity-100'
        : 'max-h-0 opacity-0',
);

const applySidebarWidth = () => {
    document.documentElement.style.setProperty('--sidebar-width', isExpanded.value ? '14rem' : '4rem');
};

const toggleSidebar = () => {
    isExpanded.value = !isExpanded.value;
};

const confirmingLogout = ref(false);

const confirmLogout = () => {
    confirmingLogout.value = true;
};

const closeModal = () => {
    confirmingLogout.value = false;
};

const logout = () => {
    router.post(route('logout'));
};

onMounted(() => {
    applySidebarWidth();
});

watch(isExpanded, (value) => {
    localStorage.setItem('sidebar_expanded', String(value));
    applySidebarWidth();
});
</script>

<template>
    <aside :class="[
            'group/sidebar fixed left-0 top-0 h-screen bg-gray-900 flex flex-col py-6 shadow-xl transition-all duration-300 ease-in-out z-50',
            isExpanded ? 'w-56' : 'w-16'
        ]">
        <button
            @click="toggleSidebar"
            class="absolute top-6 -right-3 h-7 w-7 rounded-full border border-gray-700 bg-gray-900 text-gray-300 hover:text-white hover:border-cyan-400/70 transition-all shadow-md flex items-center justify-center"
            :title="isExpanded ? 'Minimize sidebar' : 'Expand sidebar'"
        >
            <svg v-if="isExpanded" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
        </button>

        <!-- Logo -->
        <div class="mb-8 px-4 flex items-center gap-3">
            <div class="flex items-center gap-3">
            <svg class="h-7 w-7 text-cyan-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            <span :class="['text-white font-bold text-lg whitespace-nowrap transition-all duration-300', labelClass]">
                WattWise
            </span>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-2 overflow-y-auto">
            <div v-if="isExpanded" class="px-3 pb-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-cyan-300/60">
                Main
            </div>

            <!-- Dashboard -->
            <Link
                :href="route('dashboard')"
                :class="[
                    'flex items-center gap-3 px-3 py-3 rounded-lg transition-all',
                    isActive('dashboard')
                        ? 'bg-blue-500/20 text-cyan-400'
                        : 'text-gray-400 hover:bg-gray-800 hover:text-white'
                ]"
                title="Dashboard"
            >
                <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span :class="['whitespace-nowrap transition-all duration-300', labelClass]">
                    Dashboard
                </span>
            </Link>

            <div v-if="isExpanded" class="mx-2 my-4 border-t border-gray-800/80"></div>
            <div v-if="isExpanded" class="px-3 pb-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-cyan-300/60">
                Device
            </div>

            <!-- Device Settings (Major Page) -->
            <div
                :class="[
                    'rounded-lg transition-all duration-300 overflow-hidden',
                    isDeviceSettingsActive
                        ? 'bg-cyan-500/8'
                        : 'bg-transparent'
                ]"
                title="Device Settings"
            >
                <div :class="[
                    'flex items-center gap-3 px-3 py-2.5 transition-colors',
                    isDeviceSettingsActive ? 'text-cyan-300' : 'text-cyan-300/90'
                ]">
                    <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v8m4-4H8m10 8H6a2 2 0 01-2-2V6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2z" />
                    </svg>
                    <span :class="['whitespace-nowrap font-bold transition-all duration-300', labelClass]">
                        Device Settings
                    </span>
                </div>

                <div :class="['overflow-hidden transition-all duration-300', subMenuClass]">
                    <div class="px-3 pb-2 space-y-0.5">
                    <Link
                        :href="route('device.settings', { id: 1 })"
                        class="flex items-center gap-2 rounded-md px-0 py-1.5 text-[0.95rem] font-medium transition-colors"
                        :class="isDeviceSettingsActiveFor(1)
                            ? 'text-white'
                            : 'text-gray-300 hover:text-white'"
                    >
                        <span class="h-2 w-2 rounded-full bg-cyan-300"></span>
                        Plug 1
                    </Link>
                    <Link
                        :href="route('device.settings', { id: 2 })"
                        class="flex items-center gap-2 rounded-md px-0 py-1.5 text-[0.95rem] font-medium transition-colors"
                        :class="isDeviceSettingsActiveFor(2)
                            ? 'text-white'
                            : 'text-gray-300 hover:text-white'"
                    >
                        <span class="h-2 w-2 rounded-full bg-cyan-300"></span>
                        Plug 2
                    </Link>
                    </div>
                </div>
            </div>

            <div v-if="isExpanded" class="mx-2 my-4 border-t border-gray-800/80"></div>
            <div v-if="isExpanded" class="px-3 pb-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-cyan-300/60">
                Support
            </div>

            <!-- Notifications -->
            <Link
                :href="route('notifications')"
                :class="[
                    'flex items-center gap-3 px-3 py-3 rounded-lg relative transition-all',
                    isActive('notifications')
                        ? 'bg-blue-500/20 text-cyan-400'
                        : 'text-gray-400 hover:bg-gray-800 hover:text-white'
                ]"
                title="Notifications"
            >
                <div class="relative flex-shrink-0">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4a2 2 0 0 1-.6-1.4V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0" />
                    </svg>
                    <span v-if="notificationCount > 0" class="absolute -top-1 -right-1 h-2.5 w-2.5 rounded-full bg-red-500"></span>
                </div>
                <div :class="['flex items-center justify-between flex-1 whitespace-nowrap transition-all duration-300', labelClass]">
                    <span>Notifications</span>
                </div>
            </Link>
            <!-- Definition of Terms -->
            <Link
                :href="route('definitions')"
                :class="[
                    'flex items-center gap-3 px-3 py-3 rounded-lg transition-all',
                    isActive('definitions')
                        ? 'bg-blue-500/20 text-cyan-400'
                        : 'text-gray-400 hover:bg-gray-800 hover:text-white'
                ]"
                title="Definition of Terms"
            >
                <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                <span :class="['whitespace-nowrap transition-all duration-300', labelClass]">
                    Glossary
                </span>
            </Link>

            <!-- Settings -->
            <Link
                :href="route('profile.edit')"
                :class="[
                    'flex items-center gap-3 px-3 py-3 rounded-lg transition-all',
                    isActive('profile.edit')
                        ? 'bg-blue-500/20 text-cyan-400'
                        : 'text-gray-400 hover:bg-gray-800 hover:text-white'
                ]"
                title="Settings"
            >
                <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span :class="['whitespace-nowrap transition-all duration-300', labelClass]">
                    Settings
                </span>
            </Link>
        </nav>

        <div class="px-2 pt-4 border-t border-gray-800/80">
            <div v-if="isExpanded" class="px-2 pb-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-cyan-300/60">
                Account
            </div>

            <!-- User Profile Section -->
            <Link
                :href="route('profile.edit')"
                class="mb-3 px-2 py-2 rounded-lg flex items-center gap-3 hover:bg-gray-800/70 transition-colors"
            >
                <!-- Profile Image -->
                <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-gradient-to-br from-cyan-400 to-teal-500 flex items-center justify-center overflow-hidden">
                    <img 
                        v-if="profileImageUrl"
                        :src="profileImageUrl" 
                        :alt="user.name"
                        class="w-full h-full object-cover"
                    />
                    <div 
                        v-else 
                        class="w-full h-full bg-gradient-to-br from-cyan-400 to-teal-500 flex items-center justify-center text-white font-semibold text-sm"
                    >
                        {{ user.name?.charAt(0).toUpperCase() || 'U' }}
                    </div>
                </div>

                <!-- User Name (Hidden when collapsed) -->
                <p :class="['text-white font-semibold text-sm truncate whitespace-nowrap transition-all duration-300', labelClass]">{{ user.name }}</p>
            </Link>

            <!-- Logout -->
            <button
                @click="confirmLogout"
                class="flex w-full items-center gap-3 px-3 py-3 rounded-lg text-gray-400 hover:bg-red-500/20 hover:text-red-400 transition-all cursor-pointer"
                title="Logout"
            >
                <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span :class="['whitespace-nowrap text-left transition-all duration-300', labelClass]">
                    Logout
                </span>
            </button>
        </div>

        <!-- Logout Confirmation Modal -->
        <Modal :show="confirmingLogout" @close="closeModal" maxWidth="sm">
            <div class="p-6 bg-white dark:bg-gray-900 rounded-lg border border-gray-100 dark:border-gray-800 transition-colors duration-300">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2 whitespace-normal">
                    Confirm Logout
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-6 whitespace-normal">
                    Are you sure you want to log out of your account?
                </p>
                <div class="flex justify-end gap-3">
                    <button
                        @click="closeModal"
                        class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors font-medium text-sm cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        @click="logout"
                        class="px-4 py-2 bg-red-50 text-white rounded-lg hover:bg-red-600 transition-colors font-medium text-sm shadow-sm cursor-pointer"
                    >
                        Log Out
                    </button>
                </div>
            </div>
        </Modal>
    </aside>
</template>
