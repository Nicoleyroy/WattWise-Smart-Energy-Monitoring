<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Sidebar from '@/Components/Sidebar.vue';
import { Bell, AlertTriangle, Info, CheckCircle, X, Zap, Clock, Filter, Trash2, Eye, CheckCheck } from 'lucide-vue-next';

const props = defineProps({
    notifications: {
        type: Array,
        default: () => []
    }
});

const notifications = ref([
    {
        id: 1,
        type: 'alert',
        priority: 'high',
        title: 'Critical: Threshold Exceeded',
        message: 'Living Room TV has exceeded 100% of threshold limit (2.5 kW)',
        device: 'Living Room TV',
        time: '5 minutes ago',
        timestamp: new Date(Date.now() - 5 * 60000),
        read: false,
        action: 'View Device'
    },
    {
        id: 2,
        type: 'warning',
        priority: 'medium',
        title: 'High Power Consumption',
        message: 'Air Conditioner is consuming 85% of its power threshold',
        device: 'Air Conditioner',
        time: '15 minutes ago',
        timestamp: new Date(Date.now() - 15 * 60000),
        read: false,
        action: 'View Device'
    },
    {
        id: 3,
        type: 'warning',
        priority: 'medium',
        title: 'Device Offline',
        message: 'Water Heater has been offline for 30 minutes',
        device: 'Water Heater',
        time: '30 minutes ago',
        timestamp: new Date(Date.now() - 30 * 60000),
        read: false,
        action: 'Check Status'
    },
    {
        id: 4,
        type: 'info',
        priority: 'low',
        title: 'Schedule Completed',
        message: 'Kitchen Appliances turned off as scheduled at 10:00 PM',
        device: 'Kitchen Appliances',
        time: '1 hour ago',
        timestamp: new Date(Date.now() - 60 * 60000),
        read: false,
        action: 'View Schedule'
    },
    {
        id: 5,
        type: 'success',
        priority: 'low',
        title: 'Energy Goal Achieved',
        message: 'You saved 3.2 kWh today! That\'s 15% below your daily goal',
        time: '2 hours ago',
        timestamp: new Date(Date.now() - 2 * 60 * 60000),
        read: true
    },
    {
        id: 6,
        type: 'info',
        priority: 'low',
        title: 'Device Connected',
        message: 'EV Charger has been successfully connected to the network',
        device: 'EV Charger',
        time: '3 hours ago',
        timestamp: new Date(Date.now() - 3 * 60 * 60000),
        read: true
    },
    {
        id: 7,
        type: 'success',
        priority: 'low',
        title: 'Threshold Updated',
        message: 'Smart Refrigerator threshold settings saved successfully',
        device: 'Smart Refrigerator',
        time: '5 hours ago',
        timestamp: new Date(Date.now() - 5 * 60 * 60000),
        read: true
    },
    {
        id: 8,
        type: 'info',
        priority: 'low',
        title: 'Daily Report Ready',
        message: 'Your energy consumption report for today is available',
        time: '6 hours ago',
        timestamp: new Date(Date.now() - 6 * 60 * 60000),
        read: true
    }
]);

const unreadCount = ref(notifications.value.filter(n => !n.read).length);
const filterType = ref('all'); // all, unread, read

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
            return 'text-yellow-400 bg-yellow-400/20';
        case 'alert':
            return 'text-red-400 bg-red-400/20';
        case 'success':
            return 'text-green-400 bg-green-400/20';
        case 'info':
        default:
            return 'text-cyan-400 bg-cyan-400/20';
    }
};

const getPriorityBadge = (priority) => {
    switch (priority) {
        case 'high':
            return { label: 'High Priority', class: 'bg-red-500/20 text-red-400 border-red-500/30' };
        case 'medium':
            return { label: 'Medium', class: 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30' };
        case 'low':
        default:
            return null;
    }
};

const clearAllRead = () => {
    notifications.value = notifications.value.filter(n => !n.read);
    unreadCount.value = notifications.value.filter(n => !n.read).length;
};

const markAsRead = (id) => {
    const notification = notifications.value.find(n => n.id === id);
    if (notification && !notification.read) {
        notification.read = true;
        unreadCount.value--;
    }
};

const markAllAsRead = () => {
    notifications.value.forEach(n => n.read = true);
    unreadCount.value = 0;
};

const deleteNotification = (id) => {
    const index = notifications.value.findIndex(n => n.id === id);
    if (index !== -1) {
        if (!notifications.value[index].read) {
            unreadCount.value--;
        }
        notifications.value.splice(index, 1);
    }
};
</script>

<template>
    <Head title="Notifications" />

    <div class="flex min-h-screen bg-slate-900">
        <!-- Sidebar -->
        <Sidebar />

        <!-- Main Content -->
        <main class="flex-1 ml-16">
            <!-- Header -->
            <header class="sticky top-0 z-40 border-b border-slate-700/50 bg-slate-800/80 backdrop-blur-lg shadow-lg">
                <div class="flex items-center justify-between px-8 py-6">
                    <div>
                        <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-cyan-500/20 flex items-center justify-center">
                                <Bell class="w-5 h-5 text-cyan-400" />
                            </div>
                            Notifications
                        </h1>
                        <p class="text-sm text-gray-400 mt-1 ml-13">
                            {{ unreadCount }} unread notification{{ unreadCount !== 1 ? 's' : '' }}
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            v-if="notifications.filter(n => n.read).length > 0"
                            @click="clearAllRead"
                            class="px-4 py-2 text-sm bg-slate-700 text-gray-300 rounded-lg hover:bg-slate-600 transition-colors flex items-center gap-2"
                        >
                            <Trash2 class="w-4 h-4" />
                            Clear Read
                        </button>
                        <button
                            v-if="unreadCount > 0"
                            @click="markAllAsRead"
                            class="px-4 py-2 text-sm bg-cyan-500 text-white rounded-lg hover:bg-cyan-600 transition-colors flex items-center gap-2"
                        >
                            <CheckCheck class="w-4 h-4" />
                            Mark all as read
                        </button>
                    </div>
                </div>

                <!-- Filter Tabs -->
                <div class="flex gap-2 px-8 pb-4">
                    <button
                        @click="filterType = 'all'"
                        :class="[
                            'px-4 py-2 rounded-lg text-sm font-medium transition-all',
                            filterType === 'all'
                                ? 'bg-cyan-500 text-white'
                                : 'bg-slate-700 text-gray-400 hover:bg-slate-600'
                        ]"
                    >
                        All ({{ notifications.length }})
                    </button>
                    <button
                        @click="filterType = 'unread'"
                        :class="[
                            'px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2',
                            filterType === 'unread'
                                ? 'bg-cyan-500 text-white'
                                : 'bg-slate-700 text-gray-400 hover:bg-slate-600'
                        ]"
                    >
                        Unread
                        <span v-if="unreadCount > 0" class="px-2 py-0.5 rounded-full bg-red-500 text-white text-xs font-bold">
                            {{ unreadCount }}
                        </span>
                    </button>
                    <button
                        @click="filterType = 'read'"
                        :class="[
                            'px-4 py-2 rounded-lg text-sm font-medium transition-all',
                            filterType === 'read'
                                ? 'bg-cyan-500 text-white'
                                : 'bg-slate-700 text-gray-400 hover:bg-slate-600'
                        ]"
                    >
                        Read ({{ notifications.filter(n => n.read).length }})
                    </button>
                </div>
            </header>

            <div class="p-8">
                <!-- Notifications List -->
                <div v-if="filteredNotifications.length > 0" class="space-y-6">
                    <!-- Today -->
                    <div v-if="groupedNotifications.today.length > 0">
                        <h2 class="text-sm font-semibold text-gray-400 mb-3 flex items-center gap-2">
                            <Clock class="w-4 h-4" />
                            Today
                        </h2>
                        <div class="space-y-3">
                            <div
                                v-for="notification in groupedNotifications.today"
                                :key="notification.id"
                                class="group relative bg-slate-800 border rounded-xl transition-all hover:shadow-lg hover:shadow-cyan-500/10"
                                :class="notification.read ? 'border-slate-700' : 'border-cyan-500/30 bg-slate-800/80 hover:border-cyan-500/50'"
                            >
                                <div class="p-5">
                                    <div class="flex gap-4">
                                        <!-- Icon -->
                                        <div class="flex-shrink-0">
                                            <div class="w-12 h-12 rounded-xl flex items-center justify-center" :class="getIconColor(notification.type)">
                                                <component :is="getIcon(notification.type)" class="w-6 h-6" />
                                            </div>
                                        </div>

                                        <!-- Content -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <h3 class="text-white font-semibold">{{ notification.title }}</h3>
                                                        <span
                                                            v-if="!notification.read"
                                                            class="h-2 w-2 rounded-full bg-cyan-400 flex-shrink-0 animate-pulse"
                                                        ></span>
                                                        <span
                                                            v-if="getPriorityBadge(notification.priority)"
                                                            :class="['text-xs px-2 py-0.5 rounded-full border', getPriorityBadge(notification.priority).class]"
                                                        >
                                                            {{ getPriorityBadge(notification.priority).label }}
                                                        </span>
                                                    </div>
                                                    <p class="text-gray-400 text-sm mt-1.5">{{ notification.message }}</p>
                                                    <div class="flex items-center gap-3 mt-3">
                                                        <span v-if="notification.device" class="text-xs text-cyan-400 bg-cyan-400/10 px-2 py-1 rounded-md flex items-center gap-1">
                                                            <Zap class="w-3 h-3" />
                                                            {{ notification.device }}
                                                        </span>
                                                        <span class="text-xs text-gray-500 flex items-center gap-1">
                                                            <Clock class="w-3 h-3" />
                                                            {{ notification.time }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <!-- Delete Button -->
                                                <button
                                                    @click.stop="deleteNotification(notification.id)"
                                                    class="flex-shrink-0 p-2 rounded-lg text-gray-400 hover:text-red-400 hover:bg-red-400/10 opacity-0 group-hover:opacity-100 transition-all"
                                                >
                                                    <X class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div v-if="notification.action || !notification.read" class="border-t border-slate-700 px-5 py-3 flex items-center justify-between gap-3">
                                    <button
                                        v-if="notification.action"
                                        class="text-sm text-cyan-400 hover:text-cyan-300 transition-colors flex items-center gap-1.5 font-medium"
                                    >
                                        <Eye class="w-4 h-4" />
                                        {{ notification.action }}
                                    </button>
                                    <button
                                        v-if="!notification.read"
                                        @click="markAsRead(notification.id)"
                                        class="text-sm text-gray-400 hover:text-white transition-colors flex items-center gap-1.5"
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
                        <h2 class="text-sm font-semibold text-gray-400 mb-3">Yesterday</h2>
                        <div class="space-y-3">
                            <div
                                v-for="notification in groupedNotifications.yesterday"
                                :key="notification.id"
                                class="group relative bg-slate-800 border rounded-xl transition-all hover:shadow-lg hover:shadow-cyan-500/10"
                                :class="notification.read ? 'border-slate-700' : 'border-cyan-500/30'"
                            >
                                <div class="p-5">
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0">
                                            <div class="w-12 h-12 rounded-xl flex items-center justify-center" :class="getIconColor(notification.type)">
                                                <component :is="getIcon(notification.type)" class="w-6 h-6" />
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <h3 class="text-white font-semibold">{{ notification.title }}</h3>
                                                        <span v-if="!notification.read" class="h-2 w-2 rounded-full bg-cyan-400 flex-shrink-0"></span>
                                                    </div>
                                                    <p class="text-gray-400 text-sm mt-1.5">{{ notification.message }}</p>
                                                    <div class="flex items-center gap-3 mt-3">
                                                        <span v-if="notification.device" class="text-xs text-cyan-400 bg-cyan-400/10 px-2 py-1 rounded-md">
                                                            {{ notification.device }}
                                                        </span>
                                                        <span class="text-xs text-gray-500">{{ notification.time }}</span>
                                                    </div>
                                                </div>
                                                <button
                                                    @click.stop="deleteNotification(notification.id)"
                                                    class="flex-shrink-0 p-2 rounded-lg text-gray-400 hover:text-red-400 hover:bg-red-400/10 opacity-0 group-hover:opacity-100 transition-all"
                                                >
                                                    <X class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- This Week -->
                    <div v-if="groupedNotifications.thisWeek.length > 0">
                        <h2 class="text-sm font-semibold text-gray-400 mb-3">This Week</h2>
                        <div class="space-y-3">
                            <div
                                v-for="notification in groupedNotifications.thisWeek"
                                :key="notification.id"
                                class="group relative bg-slate-800 border border-slate-700 rounded-xl p-5 transition-all hover:border-slate-600"
                            >
                                <div class="flex gap-4">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="getIconColor(notification.type)">
                                            <component :is="getIcon(notification.type)" class="w-5 h-5" />
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex-1">
                                                <h3 class="text-gray-300 font-semibold">{{ notification.title }}</h3>
                                                <p class="text-gray-500 text-sm mt-1">{{ notification.message }}</p>
                                                <div class="flex items-center gap-3 mt-2">
                                                    <span v-if="notification.device" class="text-xs text-gray-500">{{ notification.device }}</span>
                                                    <span class="text-xs text-gray-600">{{ notification.time }}</span>
                                                </div>
                                            </div>
                                            <button
                                                @click.stop="deleteNotification(notification.id)"
                                                class="flex-shrink-0 p-1.5 rounded-lg text-gray-500 hover:text-red-400 hover:bg-red-400/10 opacity-0 group-hover:opacity-100 transition-all"
                                            >
                                                <X class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Older -->
                    <div v-if="groupedNotifications.older.length > 0">
                        <h2 class="text-sm font-semibold text-gray-400 mb-3">Older</h2>
                        <div class="space-y-3">
                            <div
                                v-for="notification in groupedNotifications.older"
                                :key="notification.id"
                                class="group relative bg-slate-800 border border-slate-700 rounded-xl p-5 transition-all hover:border-slate-600 opacity-60"
                            >
                                <div class="flex gap-4">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="getIconColor(notification.type)">
                                            <component :is="getIcon(notification.type)" class="w-5 h-5" />
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex-1">
                                                <h3 class="text-gray-400 font-semibold">{{ notification.title }}</h3>
                                                <p class="text-gray-600 text-sm mt-1">{{ notification.message }}</p>
                                                <span class="text-xs text-gray-600 mt-2 inline-block">{{ notification.time }}</span>
                                            </div>
                                            <button
                                                @click.stop="deleteNotification(notification.id)"
                                                class="flex-shrink-0 p-1.5 rounded-lg text-gray-500 hover:text-red-400 hover:bg-red-400/10 opacity-0 group-hover:opacity-100 transition-all"
                                            >
                                                <X class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-20">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-slate-800 mb-6 ring-8 ring-slate-800/50">
                        <Bell class="w-10 h-10 text-gray-500" />
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-2">
                        {{ filterType === 'unread' ? 'All caught up!' : 'No notifications' }}
                    </h3>
                    <p class="text-gray-400 max-w-sm mx-auto">
                        {{ filterType === 'unread' 
                            ? 'You have no unread notifications. Great job staying on top of things!' 
                            : 'You don\'t have any notifications yet. We\'ll notify you about important updates.' 
                        }}
                    </p>
                </div>
            </div>
        </main>
    </div>
</template>
