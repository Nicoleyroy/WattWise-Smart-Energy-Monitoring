<script setup>
import { ref } from 'vue';
import Sidebar from '@/Components/Sidebar.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link } from '@inertiajs/vue3';

const showingMobileMenu = ref(false);
</script>

<template>
    <div class="flex min-h-screen bg-slate-50 dark:bg-gray-950 transition-colors duration-300">
        <!-- Sidebar -->
        <Sidebar />

        <!-- Main Content Area -->
        <div class="flex-1 transition-[margin] duration-300" style="margin-left: var(--sidebar-width, 4rem);">
            <!-- Top Header Bar -->
            <header class="sticky top-0 z-50 mb-6 rounded-2xl border border-slate-200/80 bg-white/80 px-5 py-4 shadow-sm shadow-slate-200/60 backdrop-blur-xl transition-colors duration-300 dark:border-gray-800 dark:bg-gray-950/80 dark:shadow-none sm:px-6">
                <div class="flex items-center justify-between gap-3">
                    <!-- Page Title (if provided via slot) -->
                    <div v-if="$slots.header" class="min-w-0">
                        <slot name="header" />
                    </div>
                    <div v-else class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-100 text-cyan-600 dark:bg-cyan-900/40 dark:text-cyan-400">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-lg font-bold text-slate-900 dark:text-gray-100">
                                WattWise
                            </div>
                            <div class="text-xs font-medium text-slate-500 dark:text-gray-400">
                                Energy monitoring
                            </div>
                        </div>
                    </div>

                    <!-- User Dropdown (Desktop) -->
                    <div class="hidden sm:flex sm:items-center">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 dark:focus:ring-offset-gray-950"
                                >
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-cyan-100 text-xs font-bold text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-300">
                                        {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                                    </span>
                                    {{ $page.props.auth.user.name }}
                                    <svg
                                        class="ml-2 h-4 w-4"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </button>
                            </template>

                            <template #content>
                                <DropdownLink :href="route('profile.edit')">
                                    Profile Settings
                                </DropdownLink>
                                <DropdownLink
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                >
                                    Log Out
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>

                    <!-- Mobile Menu Button -->
                    <button
                        @click="showingMobileMenu = !showingMobileMenu"
                        class="sm:hidden inline-flex items-center justify-center rounded-md p-2 text-slate-400 dark:text-gray-500 hover:bg-slate-100 dark:hover:bg-gray-800 hover:text-slate-500 dark:hover:text-gray-400 focus:outline-none"
                    >
                        <svg
                            class="h-6 w-6"
                            stroke="currentColor"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <path
                                :class="{ hidden: showingMobileMenu, 'inline-flex': !showingMobileMenu }"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                            <path
                                :class="{ hidden: !showingMobileMenu, 'inline-flex': showingMobileMenu }"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Mobile Menu -->
                <div
                    v-show="showingMobileMenu"
                    class="sm:hidden border-t border-slate-200 dark:border-gray-800"
                >
                    <div class="space-y-1 px-4 pb-3 pt-2 bg-white dark:bg-gray-900">
                        <div class="mb-3 border-b border-slate-200 dark:border-gray-800 pb-3">
                            <div class="text-base font-medium text-slate-800 dark:text-gray-200">
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="text-sm font-medium text-slate-500 dark:text-gray-400">
                                {{ $page.props.auth.user.email }}
                            </div>
                        </div>

                        <Link
                            :href="route('dashboard')"
                            class="block rounded-lg px-3 py-2 text-base font-medium text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-800"
                        >
                            Dashboard
                        </Link>
                        <Link
                            :href="route('devices')"
                            class="block rounded-lg px-3 py-2 text-base font-medium text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-800"
                        >
                            Devices
                        </Link>
                        <Link
                            :href="route('profile.edit')"
                            class="block rounded-lg px-3 py-2 text-base font-medium text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-800"
                        >
                            Profile Settings
                        </Link>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="block w-full rounded-lg px-3 py-2 text-left text-base font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20"
                        >
                            Log Out
                        </Link>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="px-4 pb-4 sm:px-6 sm:pb-6 lg:px-8 lg:pb-8">
                <slot />
            </main>
        </div>
    </div>
</template>
