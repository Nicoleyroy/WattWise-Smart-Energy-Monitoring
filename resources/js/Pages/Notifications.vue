<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import Sidebar from '@/Components/Sidebar.vue';
import { Bell, AlertTriangle, Info, CheckCircle, X, Zap, Clock, Filter, Trash2, Eye, CheckCheck } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    initialNotifications: {
        type: Array,
        default: () => []
    }
});

const notifications = ref([...props.initialNotifications]);

// Map Firebase structure to UI structure
const mapNotification = (id, data) => ({
    id: id,
    type: data.type || 'info',
    severity: data.severity || 'info',
    title: data.title || 'Notification',
    message: data.message || '',
    device: data.device_name || null,
    time: 'Just now',
    timestamp: new Date(data.timestamp || Date.now()),
    read: false,
    action: data.action || null
});

onMounted(() => {
    if (window.db) {
        window.db.ref('notifications').on('child_added', (snapshot) => {
            const deviceNodes = snapshot.val();
            if (!deviceNodes) return;
            
            Object.keys(deviceNodes).forEach(key => {
                const notifData = deviceNodes[key];
                if (!notifications.value.some(n => n.id === notifData.id || (n.title === notifData.title && n.message === notifData.message))) {
                    notifications.value.unshift(mapNotification(notifData.id, notifData));
                    unreadCount.value++;
                    
                    if (notifData.severity === 'critical') {
                        // Play sound or show toast
                    }
                }
            });
        });
    }
});

onUnmounted(() => {
    if (window.db) {
        window.db.ref('notifications').off();
    }
});

const unreadCount = ref(notifications.value.filter(n => !n.read).length);
const filterType = ref('all'); // all, unread, read

const selectedNotification = ref(null);

const openNotification = (notification) => {
    if (!notification.read) {
        selectedNotification.value = notification;
    }
};

const closeNotification = () => {
    selectedNotification.value = null;
};

const filteredNotifications = computed(() => {
    if (filterType.value === 'unread') {
        return notifications.value.filter(n => !n.read);
    } else if (filterType.value === 'read') {
        return notifications.value.filter(n => n.read);
    }
    return notifications.value;
});

const groupedNotifications = computed(() => {
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    const thisWeek = new Date(today);
    thisWeek.setDate(thisWeek.getDate() - 7);

    const groups = {
        today: [],
        yesterday: [],
        thisWeek: [],
        older: []
    };

    filteredNotifications.value.forEach(notification => {
        const timestamp = notification.timestamp || new Date();
        if (timestamp >= today) {
            groups.today.push(notification);
        } else if (timestamp >= yesterday) {
            groups.yesterday.push(notification);
        } else if (timestamp >= thisWeek) {
            groups.thisWeek.push(notification);
        } else {
            groups.older.push(notification);
        }
    });

    return groups;
});

const getIcon = (type) => {
    switch (type) {
        case 'warning':
        case 'alert':
            return AlertTriangle;
        case 'success':
            return CheckCircle;
        case 'info':
        default:
            return Info;
    }
};

const getIconColor = (type) => {
    switch (type) {
        case 'warning':
            return 'text-amber-600 bg-amber-100';
        case 'alert':
            return 'text-red-600 bg-red-100';
        case 'success':
            return 'text-green-600 bg-green-100';
        case 'info':
        default:
            return 'text-cyan-600 bg-cyan-100';
    }
};

const getPriorityBadge = (priority) => {
    switch (priority) {
        case 'high':
            return { label: 'High Priority', class: 'bg-red-100 text-red-700 border-red-200' };
        case 'medium':
            return { label: 'Medium', class: 'bg-amber-100 text-amber-700 border-amber-200' };
        case 'low':
        default:
            return null;
    }
};

const clearAllRead = () => {
    notifications.value = notifications.value.filter(n => !n.read);
    unreadCount.value = notifications.value.filter(n => !n.read).length;
};

const markAsRead = async (id) => {
    const notification = notifications.value.find(n => n.id === id);
    if (notification && !notification.read) {
        try {
            await axios.post(`/api/notifications/${id}/read`);
            notification.read = true;
            unreadCount.value--;
            router.reload({ only: ['auth'] });
        } catch (e) {
            console.error('Failed to mark notification as read', e);
            // Optimistic fallback for demo
            notification.read = true;
            unreadCount.value--;
            router.reload({ only: ['auth'] });
        }
    }
};

const markAllAsRead = async () => {
    try {
        await axios.post('/api/notifications/read-all');
        notifications.value.forEach(n => n.read = true);
        unreadCount.value = 0;
        router.reload({ only: ['auth'] });
    } catch (e) {
        console.error('Failed to mark all as read', e);
    }
};

const deleteNotification = async (id) => {
    const index = notifications.value.findIndex(n => n.id === id);
    if (index !== -1) {
        try {
            await axios.delete(`/api/notifications/${id}`);
            if (!notifications.value[index].read) {
                unreadCount.value--;
            }
            notifications.value.splice(index, 1);
        } catch (e) {
            console.error('Failed to delete notification', e);
            // Optimistic fallback
            if (!notifications.value[index].read) {
                unreadCount.value--;
            }
            notifications.value.splice(index, 1);
        }
    }
};
</script>

<template>
    <Head title="Notifications" />

    <div class="flex min-h-screen bg-white dark:bg-gray-950 transition-colors duration-300">
        <!-- Sidebar -->
        <Sidebar />

        <!-- Main Content -->
        <main class="flex-1 bg-white dark:bg-gray-950 transition-colors duration-300" style="margin-left: var(--sidebar-width, 4rem);">
            <!-- Header -->
            <header class="sticky top-0 z-50 mb-6 rounded-xl border border-slate-200 bg-white shadow-sm transition-colors duration-300 dark:border-slate-800 dark:bg-slate-950">
                <div class="flex items-start justify-between gap-4 px-6 py-5">
                    <div>
                        <div class="mb-1 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wider text-cyan-600 dark:text-cyan-400">
                            Operations feed
                        </div>
                        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                            Notifications
                        </h1>
                        <p class="mt-1 text-sm font-medium text-gray-600 dark:text-gray-400">
                            {{ unreadCount }} unread notification{{ unreadCount !== 1 ? 's' : '' }}
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            v-if="notifications.filter(n => n.read).length > 0"
                            @click="clearAllRead"
                            class="px-4 py-2 text-sm bg-gray-200 dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-700 transition-colors flex items-center gap-2 font-semibold"
                        >
                            <Trash2 class="w-4 h-4" />
                            Clear Read
                        </button>
                        <button
                            v-if="unreadCount > 0"
                            @click="markAllAsRead"
                            class="px-4 py-2 text-sm bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 transition-colors flex items-center gap-2 font-bold shadow-lg shadow-cyan-600/20"
                        >
                            <CheckCheck class="w-4 h-4" />
                            Mark all as read
                        </button>
                    </div>
                </div>

                <!-- Filter Tabs -->
                <div class="flex gap-2 overflow-x-auto border-t border-slate-200 px-6 py-3 dark:border-slate-800">
                    <button
                        @click="filterType = 'all'"
                        :class="[
                            'px-4 py-2 rounded-lg text-sm font-bold transition-all',
                            filterType === 'all'
                                ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/20'
                                : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
                        ]"
                    >
                        All ({{ notifications.length }})
                    </button>
                    <button
                        @click="filterType = 'unread'"
                        :class="[
                            'px-4 py-2 rounded-lg text-sm font-bold transition-all flex items-center gap-2',
                            filterType === 'unread'
                                ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/20'
                                : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
                        ]"
                    >
                        Unread
                        <span v-if="unreadCount > 0" class="px-2 py-0.5 rounded-full bg-red-500 text-white text-[10px] font-black">
                            {{ unreadCount }}
                        </span>
                    </button>
                    <button
                        @click="filterType = 'read'"
                        :class="[
                            'px-4 py-2 rounded-lg text-sm font-bold transition-all',
                            filterType === 'read'
                                ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/20'
                                : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
                        ]"
                    >
                        Read ({{ notifications.filter(n => n.read).length }})
                    </button>
                </div>
            </header>

            <div class="p-8 bg-white dark:bg-gray-950 transition-colors">
                <!-- Notifications List -->
                <div v-if="filteredNotifications.length > 0" class="space-y-6 max-w-5xl mx-auto">
                    <!-- Today -->
                    <div v-if="groupedNotifications.today.length > 0">
                        <h2 class="text-xs font-black text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2 uppercase tracking-widest pl-2">
                            <Clock class="w-4 h-4" />
                            Today
                        </h2>
                        <div class="space-y-3">
                            <div
                                v-for="notification in groupedNotifications.today"
                                :key="notification.id"
                                @click="openNotification(notification)"
                                class="group relative rounded-2xl transition-all hover:shadow-xl"
                                :class="notification.read 
                                    ? 'bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800' 
                                    : 'bg-gray-50 dark:bg-gray-900 border-2 border-red-200 dark:border-red-900/50 hover:border-red-300 dark:hover:border-red-800 cursor-pointer shadow-sm'"
                            >
                                <div class="p-5">
                                    <div class="flex gap-4">
                                        <!-- Icon -->
                                        <div class="flex-shrink-0">
                                            <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-colors shadow-sm" :class="getIconColor(notification.type).includes('amber') ? 'text-amber-600 bg-amber-100 dark:text-amber-400 dark:bg-amber-900/30' : getIconColor(notification.type).includes('red') ? 'text-red-600 bg-red-100 dark:text-red-400 dark:bg-red-900/30' : getIconColor(notification.type).includes('green') ? 'text-green-600 bg-green-100 dark:text-green-400 dark:bg-green-900/30' : 'text-cyan-600 bg-cyan-100 dark:text-cyan-400 dark:bg-cyan-900/30'">
                                                <component :is="getIcon(notification.type)" class="w-6 h-6" />
                                            </div>
                                        </div>

                                        <!-- Content -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2 mb-1.5">
                                                        <h3 class="text-gray-900 dark:text-gray-100 font-bold tracking-tight">{{ notification.title }}</h3>
                                                        <span
                                                            v-if="!notification.read"
                                                            class="h-2.5 w-2.5 rounded-full bg-red-500 flex-shrink-0 animate-pulse shadow-sm shadow-red-500/50"
                                                        ></span>
                                                        <span
                                                            v-if="getPriorityBadge(notification.priority)"
                                                            :class="['text-[10px] px-2 py-0.5 rounded-full border font-black uppercase tracking-wider', 
                                                                getPriorityBadge(notification.priority).class === 'bg-red-100 text-red-700 border-red-200' ? 'bg-red-100 dark:bg-red-950 text-red-700 dark:text-red-400 border-red-200 dark:border-red-900' : 'bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-900']"
                                                        >
                                                            {{ getPriorityBadge(notification.priority).label }}
                                                        </span>
                                                    </div>
                                                    <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">{{ notification.message }}</p>
                                                    <div class="flex items-center gap-3 mt-4">
                                                        <span v-if="notification.device" class="text-[10px] text-cyan-600 dark:text-cyan-400 bg-cyan-50 dark:bg-cyan-900/30 px-2.5 py-1 rounded-md flex items-center gap-1.5 font-black uppercase tracking-wider">
                                                            <Zap class="w-3.5 h-3.5" />
                                                            {{ notification.device }}
                                                        </span>
                                                        <span class="text-[10px] text-gray-500 dark:text-gray-500 flex items-center gap-1.5 font-bold uppercase tracking-wider">
                                                            <Clock class="w-3.5 h-3.5" />
                                                            {{ notification.time }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <!-- Delete Button -->
                                                <button
                                                    @click.stop="deleteNotification(notification.id)"
                                                    class="flex-shrink-0 p-2 rounded-lg text-gray-400 dark:text-gray-600 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 opacity-0 group-hover:opacity-100 transition-all active:scale-90"
                                                >
                                                    <X class="w-5 h-5" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div v-if="notification.action || !notification.read" class="border-t border-gray-100 dark:border-gray-800 px-5 py-3.5 flex items-center justify-between gap-3 bg-gray-50/30 dark:bg-gray-800/20 rounded-b-2xl">
                                    <button
                                        v-if="notification.action"
                                        class="text-xs text-cyan-600 dark:text-cyan-400 hover:text-cyan-700 dark:hover:text-cyan-300 transition-colors flex items-center gap-2 font-black uppercase tracking-widest pl-1"
                                    >
                                        <Eye class="w-4 h-4" />
                                        {{ notification.action }}
                                    </button>
                                    <button
                                        v-if="!notification.read"
                                        @click="markAsRead(notification.id)"
                                        class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors flex items-center gap-2 font-bold uppercase tracking-widest"
                                    >
                                        <CheckCircle class="w-4 h-4" />
                                        Mark as read
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Yesterday -->
                    <div v-if="groupedNotifications.yesterday.length > 0">
                        <h2 class="text-xs font-black text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2 uppercase tracking-widest pl-2">Yesterday</h2>
                        <div class="space-y-3">
                            <div
                                v-for="notification in groupedNotifications.yesterday"
                                :key="notification.id"
                                @click="openNotification(notification)"
                                class="group relative rounded-2xl transition-all hover:shadow-xl"
                                :class="notification.read 
                                    ? 'bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-sm' 
                                    : 'bg-gray-50 dark:bg-gray-900 border-2 border-red-200 dark:border-red-900/50 hover:border-red-300 dark:hover:border-red-800 cursor-pointer'"
                            >
                                <div class="p-5">
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0">
                                            <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-colors shadow-sm" :class="getIconColor(notification.type).includes('amber') ? 'text-amber-600 bg-amber-100 dark:text-amber-400 dark:bg-amber-900/30' : getIconColor(notification.type).includes('red') ? 'text-red-600 bg-red-100 dark:text-red-400 dark:bg-red-900/30' : getIconColor(notification.type).includes('green') ? 'text-green-600 bg-green-100 dark:text-green-400 dark:bg-green-900/30' : 'text-cyan-600 bg-cyan-100 dark:text-cyan-400 dark:bg-cyan-900/30'">
                                                <component :is="getIcon(notification.type)" class="w-6 h-6" />
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2 mb-1.5">
                                                        <h3 class="text-gray-900 dark:text-gray-100 font-bold tracking-tight">{{ notification.title }}</h3>
                                                        <span v-if="!notification.read" class="h-2.5 w-2.5 rounded-full bg-red-500 flex-shrink-0 animate-pulse shadow-sm shadow-red-500/50"></span>
                                                    </div>
                                                    <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">{{ notification.message }}</p>
                                                    <div class="flex items-center gap-3 mt-4">
                                                        <span v-if="notification.device" class="text-[10px] text-cyan-600 dark:text-cyan-400 bg-cyan-50 dark:bg-cyan-900/30 px-2.5 py-1 rounded-md font-black uppercase tracking-wider">
                                                            {{ notification.device }}
                                                        </span>
                                                        <span class="text-[10px] text-gray-500 dark:text-gray-500 font-bold uppercase tracking-wider">{{ notification.time }}</span>
                                                    </div>
                                                </div>
                                                <button
                                                    @click.stop="deleteNotification(notification.id)"
                                                    class="flex-shrink-0 p-2 rounded-lg text-gray-400 dark:text-gray-600 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 opacity-0 group-hover:opacity-100 transition-all active:scale-90"
                                                >
                                                    <X class="w-5 h-5" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Older -->
                    <div v-if="groupedNotifications.older.length > 0">
                        <h2 class="text-xs font-black text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2 uppercase tracking-widest pl-2">Older</h2>
                        <div class="space-y-3">
                            <div
                                v-for="notification in groupedNotifications.older"
                                :key="notification.id"
                                @click="openNotification(notification)"
                                class="group relative rounded-2xl p-5 transition-all shadow-sm"
                                :class="notification.read 
                                    ? 'bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 opacity-60' 
                                    : 'bg-gray-50 dark:bg-gray-900 border-2 border-red-200 dark:border-red-900/50 hover:border-red-300 dark:hover:border-red-800 cursor-pointer'"
                            >
                                <div class="flex gap-4">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center transition-colors" :class="getIconColor(notification.type).includes('amber') ? 'text-amber-600 bg-amber-100 dark:text-amber-400 dark:bg-amber-900/30' : getIconColor(notification.type).includes('red') ? 'text-red-600 bg-red-100 dark:text-red-400 dark:bg-red-900/30' : getIconColor(notification.type).includes('green') ? 'text-green-600 bg-green-100 dark:text-green-400 dark:bg-green-900/30' : 'text-cyan-600 bg-cyan-100 dark:text-cyan-400 dark:bg-cyan-900/30'">
                                            <component :is="getIcon(notification.type)" class="w-5 h-5" />
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex-1">
                                                <h3 class="text-gray-700 dark:text-gray-200 font-bold tracking-tight">{{ notification.title }}</h3>
                                                <p class="text-gray-500 dark:text-gray-400 text-sm mt-1 leading-relaxed">{{ notification.message }}</p>
                                                <span class="text-[10px] text-gray-500 dark:text-gray-600 mt-3 inline-block font-bold uppercase tracking-wider">{{ notification.time }}</span>
                                            </div>
                                            <button
                                                @click.stop="deleteNotification(notification.id)"
                                                class="flex-shrink-0 p-1.5 rounded-lg text-gray-400 dark:text-gray-700 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 opacity-0 group-hover:opacity-100 transition-all active:scale-90"
                                            >
                                                <X class="w-5 h-5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-24 bg-white dark:bg-gray-950 transition-colors">
                    <div class="inline-flex items-center justify-center w-24 h-24 rounded-3xl bg-gray-100 dark:bg-gray-900 mb-8 ring-8 ring-gray-100/50 dark:ring-gray-900/30 shadow-inner">
                        <Bell class="w-12 h-12 text-gray-400 dark:text-gray-600" />
                    </div>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-gray-100 mb-3 tracking-tight">
                        {{ filterType === 'unread' ? 'All caught up!' : 'No notifications' }}
                    </h3>
                    <p class="text-gray-500 dark:text-gray-400 max-w-sm mx-auto font-medium text-lg leading-relaxed">
                        {{ filterType === 'unread' 
                            ? 'You have read all your notifications. We\'ll let you know when something new pops up!' 
                            : 'This is where you\'ll see alerts, updates, and news about your home energy.' 
                        }}
                    </p>
                </div>
            </div>
        </main>
        
        <!-- Interactive Notification Modal -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-4"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-4"
        >
            <div
                v-if="selectedNotification"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6"
            >
                <!-- Backdrop with Blur -->
                <div
                    class="absolute inset-0 bg-gray-950/60 dark:bg-black/80 backdrop-blur-md transition-opacity"
                    @click="closeNotification"
                ></div>

                <!-- Modal Panel -->
                <div class="relative w-full max-w-lg transform overflow-hidden rounded-3xl bg-white dark:bg-gray-900 shadow-2xl transition-all border border-gray-100 dark:border-gray-800">
                    <!-- Modal Header -->
                    <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gray-50/50 dark:bg-gray-800/50 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 shadow-sm" :class="getIconColor(selectedNotification.type).includes('amber') ? 'text-amber-600 bg-amber-100 dark:text-amber-400 dark:bg-amber-900/40' : getIconColor(selectedNotification.type).includes('red') ? 'text-red-600 bg-red-100 dark:text-red-400 dark:bg-red-900/40' : getIconColor(selectedNotification.type).includes('green') ? 'text-green-600 bg-green-100 dark:text-green-400 dark:bg-green-900/40' : 'text-cyan-600 bg-cyan-100 dark:text-cyan-400 dark:bg-cyan-900/40'">
                                <component :is="getIcon(selectedNotification.type)" class="w-5 h-5" />
                            </div>
                            <h3 class="text-xl font-black text-gray-900 dark:text-gray-100 tracking-tight">{{ selectedNotification.title }}</h3>
                        </div>
                        <button @click="closeNotification" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 p-2.5 rounded-xl transition-all active:scale-95">
                            <X class="w-6 h-6" />
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-8 py-10 bg-white dark:bg-gray-950 transition-colors">
                        <p class="text-gray-600 dark:text-gray-300 text-xl leading-relaxed font-medium">{{ selectedNotification.message }}</p>
                        
                        <div class="mt-10 flex flex-wrap items-center gap-4 text-sm">
                            <span v-if="selectedNotification.device" class="text-cyan-700 dark:text-cyan-400 bg-cyan-50 dark:bg-cyan-900/30 border border-cyan-100 dark:border-cyan-800/50 px-4 py-2 rounded-xl font-bold flex items-center gap-2 shadow-sm uppercase tracking-wider text-[10px]">
                                <Zap class="w-4 h-4 text-cyan-500" />
                                {{ selectedNotification.device }}
                            </span>
                            <span class="text-gray-500 dark:text-gray-400 flex items-center gap-2 font-bold bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-800 px-4 py-2 rounded-xl shadow-sm uppercase tracking-wider text-[10px]">
                                <Clock class="w-4 h-4 text-gray-400 dark:text-gray-500" />
                                {{ selectedNotification.time }}
                            </span>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-5 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-3 transition-colors">
                        <button
                            @click="closeNotification"
                            class="px-6 py-3 text-sm font-black uppercase tracking-widest text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:bg-gray-50 dark:hover:bg-gray-800 transition-all hover:shadow"
                        >
                            Cancel
                        </button>
                        <button
                            @click="() => { markAsRead(selectedNotification.id); closeNotification(); }"
                            class="px-6 py-3 text-sm font-black uppercase tracking-widest text-white bg-cyan-600 border border-transparent rounded-xl shadow-lg shadow-cyan-600/20 hover:bg-cyan-700 hover:shadow-cyan-500/40 hover:-translate-y-0.5 transition-all flex items-center gap-2"
                        >
                            <CheckCircle class="w-4 h-4" />
                            Mark as Read
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>
