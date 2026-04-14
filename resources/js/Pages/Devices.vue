<script setup lang="ts">
import { ref, computed } from "vue"
import { Head, Link } from "@inertiajs/vue3"
import Sidebar from '@/Components/Sidebar.vue'
import {
  Zap, Cpu, Fan, Thermometer, Car, Sun, Battery, Waves,
  Plus, AlertTriangle, Gauge, Power, Check, X, Clock, TrendingUp, Settings, Edit2, Calendar, Trash2
} from "lucide-vue-next"
interface Schedule {
  id: number
  deviceId: number
  action: 'on' | 'off'
  time: string
  days: string[]
  enabled: boolean
}

interface Device {
  id: number
  name: string
  status: "online" | "offline" | "standby"
  power: number
  daily_usage: number
  monthly_cost: number
  uptime: string
  category: string
 daily_limit?: number
  schedules?: Schedule[]
}

const props = defineProps<{
  devices: Device[]
}>()

const filtered = computed(() => {
  return props.devices.slice(0, 3)
})

const onlineCount = computed(() =>
  props.devices.filter(d => d.status === "online").length
)

const totalPower = computed(() =>
  props.devices.reduce((s, d) => s + Number(d.power), 0).toFixed(1)
)

// Device rename
const editingName = ref<number | null>(null)
const nameForm = ref<string>('')

const startEditName = (device: Device) => {
  editingName.value = device.id
  nameForm.value = device.name
}

const saveName = (device: Device) => {
  if (nameForm.value.trim()) {
    // Here you would typically send to backend
    console.log('Renaming device:', device.id, 'to:', nameForm.value)
    device.name = nameForm.value.trim()
    editingName.value = null
    alert('Device renamed successfully!')
  }
}

const cancelEditName = () => {
  editingName.value = null
  nameForm.value = ''
}

// Device toggle on/off
const toggleDeviceStatus = (device: Device) => {
  // Toggle between online and offline
  if (device.status === 'online') {
    device.status = 'offline'
    device.power = 0
    console.log('Device turned off:', device.name)
  } else {
    device.status = 'online'
    // Restore some power value (you can customize this)
    device.power = device.daily_usage / 24 // Estimate based on daily usage
    console.log('Device turned on:', device.name)
  }
}

// Scheduling functionality
const scheduleModal = ref<boolean>(false)
const currentDevice = ref<Device | null>(null)
const schedules = ref<Schedule[]>([])
let scheduleIdCounter = 1

const scheduleForm = ref({
  action: 'on' as 'on' | 'off',
  time: '08:00',
  days: [] as string[]
})

const daysOfWeek = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']

const openScheduleModal = (device: Device) => {
  currentDevice.value = device
  scheduleModal.value = true
  // Load existing schedules for this device
  if (!device.schedules) {
    device.schedules = []
  }
}

const closeScheduleModal = () => {
  scheduleModal.value = false
  currentDevice.value = null
  scheduleForm.value = {
    action: 'on' as 'on' | 'off',
    time: '08:00',
    days: [] as string[]
  }
}

const toggleDay = (day: string) => {
  const index = scheduleForm.value.days.indexOf(day)
  if (index > -1) {
    scheduleForm.value.days.splice(index, 1)
  } else {
    scheduleForm.value.days.push(day)
  }
}

const addSchedule = () => {
  if (!currentDevice.value || scheduleForm.value.days.length === 0) {
    alert('Please select at least one day')
    return
  }

  const newSchedule: Schedule = {
    id: scheduleIdCounter++,
    deviceId: currentDevice.value.id,
    action: scheduleForm.value.action,
    time: scheduleForm.value.time,
    days: [...scheduleForm.value.days],
    enabled: true
  }

  if (!currentDevice.value.schedules) {
    currentDevice.value.schedules = []
  }
  currentDevice.value.schedules.push(newSchedule)
  
  console.log('Schedule added:', newSchedule)
  
  // Reset form
  scheduleForm.value = {
    action: 'on' as 'on' | 'off',
    time: '08:00',
    days: [] as string[]
  }
  
  alert('Schedule added successfully!')
}

const deleteSchedule = (scheduleId: number) => {
  if (!currentDevice.value || !currentDevice.value.schedules) return
  
  const index = currentDevice.value.schedules.findIndex(s => s.id === scheduleId)
  if (index > -1) {
    currentDevice.value.schedules.splice(index, 1)
    console.log('Schedule deleted:', scheduleId)
  }
}

const toggleSchedule = (schedule: Schedule) => {
  schedule.enabled = !schedule.enabled
  console.log('Schedule toggled:', schedule)
}


// Daily energy limit configuration
const dailyLimitForm = ref<{
  id: number | null
daily_limit: number | null
}>({
  id: null,
  daily_limit: null 
})

const editingDailyLimit = ref<number | null>(null)

const startEditThreshold = (device: Device) => {
  editingDailyLimit.value = device.id
  dailyLimitForm.value = {
    id: device.id,
     daily_limit: device.daily_limit || 0
  }
}

const saveThreshold = () => {
  // Here you would typically send to backend
console.log('Saving daily energy limit:', dailyLimitForm.value)
  // Reset form
  editingDailyLimit.value = null
  alert('Daily energy limit saved successfully!')
}

const cancelEditThreshold = () => {
  editingDailyLimit.value = null
  dailyLimitForm.value = {
    id: null,
    daily_limit: null
  }
}

const getDeviceIcon = (category: string) => {
  const iconMap: Record<string, any> = {
    'Plug 1': Zap,
    'Plug 2': Fan,
    'Plug 3': Waves,

  }
  return iconMap[category] || Cpu
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'online':
      return 'bg-green-500'
    case 'standby':
      return 'bg-yellow-500'
    case 'offline':
      return 'bg-gray-500'
    default:
      return 'bg-gray-500'
  }
}

const getStatusText = (status: string) => {
  switch (status) {
    case 'online':
      return 'text-green-600'
    case 'standby':
      return 'text-amber-600'
    case 'offline':
      return 'text-gray-600'
    default:
      return 'text-gray-600'
  }
}

const getPowerLevel = (power: number): { label: string, color: string, bgColor: string } => {
  if (power === 0) return { label: 'Off', color: 'text-gray-600', bgColor: 'bg-gray-100' }
  if (power < 1) return { label: 'Low', color: 'text-green-600', bgColor: 'bg-green-100' }
  if (power < 2) return { label: 'Medium', color: 'text-amber-600', bgColor: 'bg-amber-100' }
  return { label: 'High', color: 'text-red-600', bgColor: 'bg-red-100' }
}

const getEfficiencyRating = (dailyUsage: number): { label: string, color: string } => {
  if (dailyUsage < 10) return { label: 'Excellent', color: 'text-green-600' }
  if (dailyUsage < 20) return { label: 'Good', color: 'text-cyan-600' }
  if (dailyUsage < 30) return { label: 'Fair', color: 'text-amber-600' }
  return { label: 'Poor', color: 'text-red-600' }
}
</script>

<template>
  <Head title="Devices" />

  <div class="flex min-h-screen bg-white dark:bg-gray-950 transition-colors duration-300">

    <!-- Sidebar -->
    <Sidebar />

    <!-- Main -->
    <main class="flex-1 bg-white dark:bg-gray-950 transition-[margin] duration-300 transition-colors" style="margin-left: var(--sidebar-width, 4rem);">

      <!-- Header -->
      <header class="sticky top-0 z-40 flex items-center justify-between px-8 py-4 border-b border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 backdrop-blur-lg shadow-sm">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Devices</h1>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            View and manage your connected devices
          </p>
        </div>

      </header>

      <div class="p-8 bg-white dark:bg-gray-950 transition-colors">

        <!-- Grid -->
        <div v-if="filtered.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

          <div
            v-for="device in filtered"
            :key="device.id"
            class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-6 hover:border-cyan-400 dark:hover:border-cyan-500 hover:shadow-md transition-all duration-300 group"
          >
            <!-- Device Header -->
            <div class="flex items-start justify-between mb-4">
              <div class="flex items-center gap-3">
                <!-- Device Icon -->
                <div class="w-12 h-12 rounded-lg bg-cyan-100 dark:bg-cyan-900/40 flex items-center justify-center transition-colors">
                  <component :is="getDeviceIcon(device.category)" class="h-6 w-6 text-cyan-600 dark:text-cyan-400" />
                </div>
                
                <!-- Device Info -->
                <div class="flex-1">
                  <div v-if="editingName !== device.id" class="flex items-center gap-2 group/name">
                    <h3 class="text-gray-900 dark:text-gray-100 font-semibold">{{ device.name }}</h3>
                    <button 
                      @click.stop="startEditName(device)"
                      class="opacity-0 group-hover/name:opacity-100 transition-opacity"
                    >
                      <Edit2 class="h-3.5 w-3.5 text-gray-400 dark:text-gray-500 hover:text-cyan-400 dark:hover:text-cyan-400 transition-colors" />
                    </button>
                  </div>
                  <div v-else class="flex items-center gap-2" @click.stop>
                    <input 
                      v-model="nameForm"
                      @keyup.enter="saveName(device)"
                      @keyup.esc="cancelEditName"
                      type="text"
                      class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded px-2 py-1 text-gray-900 dark:text-gray-100 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-colors"
                      autofocus
                    />
                    <button 
                      @click.stop="saveName(device)"
                      class="text-green-600 dark:text-green-500 hover:text-green-700 dark:hover:text-green-400 transition-colors"
                    >
                      <Check class="h-4 w-4" />
                    </button>
                    <button 
                      @click.stop="cancelEditName"
                      class="text-red-600 dark:text-red-500 hover:text-red-700 dark:hover:text-red-400 transition-colors"
                    >
                      <X class="h-4 w-4" />
                    </button>
                  </div>
                  <p class="text-xs text-gray-600 dark:text-gray-400">{{ device.category }}</p>
                </div>
              </div>

              <!-- Status Badge -->
              <div class="flex items-center gap-2">
                <span 
                  :class="[getStatusColor(device.status), device.status === 'online' ? 'animate-pulse' : '']" 
                  class="h-2 w-2 rounded-full"
                ></span>
                <span :class="getStatusText(device.status)" class="text-xs font-medium capitalize">
                  {{ device.status }}
                </span>
              </div>
            </div>

            <!-- Device Stats -->
            <div class="grid grid-cols-3 gap-4 mb-4">
              <div>
                <p class="text-xs text-gray-600 dark:text-gray-400 mb-1 flex items-center gap-1">
                  <Power class="h-3 w-3" />
                  Power
                </p>
                <p class="text-gray-900 dark:text-gray-100 font-semibold">{{ Number(device.power).toFixed(2) }} kW</p>
                <span 
                  :class="[getPowerLevel(device.power).color, getPowerLevel(device.power).bgColor === 'bg-gray-100' ? 'dark:bg-gray-800' : getPowerLevel(device.power).bgColor + ' dark:bg-' + getPowerLevel(device.power).bgColor.split('-')[1] + '-950/30']"
                  class="inline-block text-xs px-2 py-0.5 rounded-full mt-1"
                >
                  {{ getPowerLevel(device.power).label }}
                </span>
              </div>
              <div>
                <p class="text-xs text-gray-600 dark:text-gray-400 mb-1 flex items-center gap-1">
                  <Gauge class="h-3 w-3" />
                  Daily
                </p>
                <p class="text-gray-900 dark:text-gray-100 font-semibold">{{ device.daily_usage }} kWh</p>
                <span 
                  :class="getEfficiencyRating(device.daily_usage).color"
                  class="text-xs flex items-center gap-1 mt-1 font-medium"
                >
                  <TrendingUp class="h-3 w-3" />
                  {{ getEfficiencyRating(device.daily_usage).label }}
                </span>
              </div>
            </div>

            <!-- Progress Bar -->
            <div class="mb-4">
              <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400 mb-1.5">
                <span>Monthly Usage</span>
                <span class="font-medium text-gray-900 dark:text-gray-100">{{ Math.round((device.daily_usage * 30 / 100)) }}%</span>
              </div>
              <div class="w-full h-2 bg-gray-200 dark:bg-gray-800 rounded-full overflow-hidden shadow-inner">
                <div 
                  class="h-full rounded-full bg-gradient-to-r from-cyan-500 to-cyan-400 dark:from-cyan-600 dark:to-cyan-500 transition-all shadow-[inset_0_-1px_2px_rgba(0,0,0,0.1)]"
                  :style="{ width: `${Math.min(device.daily_usage * 30 / 100, 100)}%` }"
                ></div>
              </div>
            </div>

            <!-- Device Details -->
            <div 
              class="pt-4 border-t border-gray-200 dark:border-gray-800 space-y-4"
            >
              <div class="flex justify-between text-sm bg-gray-50 dark:bg-gray-800/50 px-3 py-2 rounded-lg transition-colors">
                <span class="text-gray-600 dark:text-gray-400 flex items-center gap-2">
                  <Clock class="h-4 w-4" />
                  Uptime
                </span>
                <span class="text-gray-900 dark:text-gray-100 font-medium">{{ device.uptime }}</span>
              </div>

            <!-- Daily Energy Limit Section -->
             <div class="mt-4 space-y-3 bg-gray-50 dark:bg-gray-800/30 rounded-lg p-4 border border-transparent dark:border-gray-800 transition-colors">
                <div class="flex items-center justify-between">
                  <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                    <Settings class="h-4 w-4 text-cyan-600 dark:text-cyan-400" />
                      Daily Energy Limit
                  </h4>
                  <button 
                    v-if="editingDailyLimit !== device.id"
                    @click.stop="startEditThreshold(device)"
                    class="text-xs bg-cyan-100 dark:bg-cyan-900/40 hover:bg-cyan-200 dark:hover:bg-cyan-800/60 text-cyan-600 dark:text-cyan-400 px-3 py-1.5 rounded-md transition-colors flex items-center gap-1 font-medium"
                  >
                    <Settings class="h-3 w-3" />
                    Edit
                  </button>
                </div>

                <!-- View Mode -->
               
                  <div v-if="editingDailyLimit !== device.id" class="grid grid-cols-1 gap-3">
                  <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3 transition-colors">
                   <p class="text-[10px] text-gray-600 dark:text-gray-400 mb-1 uppercase font-bold tracking-wider">Daily Usage</p>
                    <p class="text-gray-900 dark:text-gray-100 font-bold text-lg">{{ device.daily_usage }} / {{ device.daily_limit || 0 }}</p>
                    <p class="text-[10px] text-gray-500 dark:text-gray-500 uppercase font-medium">kWh</p>
                  </div>
                </div>

                <!-- Edit Mode -->
                <div v-else class="space-y-3" @click.stop>
                  <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3 transition-colors">
                    <label class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 mb-2 font-medium">
                     <AlertTriangle class="h-3 w-3 text-amber-500" />
                      Daily Limit (kWh)
                    </label>
                    <input 
                      v-model.number="dailyLimitForm.daily_limit"
                       type="number"
                      step="0.001"
                      min="0"
                      placeholder="4.8"
                      class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-2 text-gray-900 dark:text-gray-100 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all font-medium"
                    />
                  </div>
                  <div class="flex gap-2 pt-2">
                    <button 
                      @click.stop="saveThreshold"
                      class="flex-1 bg-cyan-600 dark:bg-cyan-600 hover:bg-cyan-700 dark:hover:bg-cyan-500 text-white px-4 py-2.5 rounded-lg text-sm font-bold transition-all hover:shadow-md flex items-center justify-center gap-2"
                    >
                      <Check class="h-4 w-4" />
                      Save
                    </button>
                    <button 
                      @click.stop="cancelEditThreshold"
                      class="flex-1 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-gray-100 px-4 py-2.5 rounded-lg text-sm font-bold transition-colors flex items-center justify-center gap-2"
                    >
                      <X class="h-4 w-4" />
                      Cancel
                    </button>
                  </div>
                </div>
              </div>

              <!-- Quick Actions -->
              <div class="space-y-2 mt-4">
                <div class="flex gap-2">
                  <button 
                    @click.stop="openScheduleModal(device)"
                    class="flex-1 bg-purple-100 dark:bg-purple-900/30 hover:bg-purple-200 dark:hover:bg-purple-800/50 text-purple-600 dark:text-purple-400 px-3 py-2 rounded-lg text-sm font-bold transition-all flex items-center justify-center gap-2"
                  >
                    <Calendar class="h-4 w-4" />
                    Schedule
                  </button>
                  <button 
                    v-if="device.status === 'online'"
                    @click.stop="toggleDeviceStatus(device)"
                    class="flex-1 bg-red-100 dark:bg-red-950/40 hover:bg-red-200 dark:hover:bg-red-900/60 text-red-600 dark:text-red-400 px-3 py-2 rounded-lg text-sm font-bold transition-all flex items-center justify-center gap-2"
                  >
                    <Power class="h-4 w-4" />
                    Turn Off
                  </button>
                  <button 
                    v-else
                    @click.stop="toggleDeviceStatus(device)"
                    class="flex-1 bg-green-100 dark:bg-green-950/40 hover:bg-green-200 dark:hover:bg-green-900/60 text-green-600 dark:text-green-400 px-3 py-2 rounded-lg text-sm font-bold transition-all flex items-center justify-center gap-2"
                  >
                    <Power class="h-4 w-4" />
                    Turn On
                  </button>
                </div>
              </div>
            </div>

          </div>

        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-16">
          <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-900 mb-4 transition-colors">
            <Zap class="w-8 h-8 text-gray-400 dark:text-gray-600" />
          </div>
          <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">No devices found</h3>
          <p class="text-gray-600 dark:text-gray-400 font-medium">No devices are currently connected to your network.</p>
        </div>
      </div>
    </main>

    <!-- Schedule Modal -->
    <div 
      v-if="scheduleModal"
      class="fixed inset-0 bg-black/40 dark:bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 transition-all"
      @click="closeScheduleModal"
    >
      <div 
        class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto border border-gray-200 dark:border-gray-800 transition-all duration-300"
        @click.stop
      >
        <!-- Modal Header -->
        <div class="sticky top-0 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 px-6 py-4 flex items-center justify-between z-10 transition-colors">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/40 flex items-center justify-center transition-colors">
              <Calendar class="h-5 w-5 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
              <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Schedule Device</h2>
              <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">{{ currentDevice?.name }}</p>
            </div>
          </div>
          <button 
            @click="closeScheduleModal"
            class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
          >
            <X class="h-6 w-6" />
          </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6 space-y-6">
          <!-- Add New Schedule Form -->
          <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-5 space-y-5 border border-transparent dark:border-gray-800 transition-colors">
            <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2 uppercase tracking-wider">
              <Plus class="h-4 w-4 text-cyan-600 dark:text-cyan-400" />
              Add New Schedule
            </h3>

            <!-- Action Selection -->
            <div>
              <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-2 uppercase tracking-wide">Action</label>
              <div class="flex gap-2">
                <button
                  @click="scheduleForm.action = 'on'"
                  :class="[
                    'flex-1 px-4 py-2.5 rounded-lg text-sm font-bold transition-all flex items-center justify-center gap-2 border-2',
                    scheduleForm.action === 'on'
                      ? 'bg-green-100 dark:bg-green-950/40 text-green-600 dark:text-green-400 border-green-300 dark:border-green-800'
                      : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                  ]"
                >
                  <Power class="h-4 w-4" />
                  Turn On
                </button>
                <button
                  @click="scheduleForm.action = 'off'"
                  :class="[
                    'flex-1 px-4 py-2.5 rounded-lg text-sm font-bold transition-all flex items-center justify-center gap-2 border-2',
                    scheduleForm.action === 'off'
                      ? 'bg-red-100 dark:bg-red-950/40 text-red-600 dark:text-red-400 border-red-300 dark:border-red-800'
                      : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                  ]"
                >
                  <Power class="h-4 w-4" />
                  Turn Off
                </button>
              </div>
            </div>

            <!-- Time Selection -->
            <div>
              <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-2 uppercase tracking-wide">Time</label>
              <input 
                v-model="scheduleForm.time"
                type="time"
                class="w-full bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg px-4 py-2.5 text-gray-900 dark:text-gray-100 font-medium focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all font-mono"
              />
            </div>

            <!-- Days Selection -->
            <div>
              <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-2 uppercase tracking-wide">Repeat on</label>
              <div class="flex gap-2 flex-wrap">
                <button
                  v-for="day in daysOfWeek"
                  :key="day"
                  @click="toggleDay(day)"
                  :class="[
                    'px-4 py-2 rounded-lg text-sm font-bold transition-all border-2',
                    scheduleForm.days.includes(day)
                      ? 'bg-cyan-600 dark:bg-cyan-600 text-white border-cyan-700 dark:border-cyan-500'
                      : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:border-cyan-400 hover:text-cyan-600'
                  ]"
                >
                  {{ day }}
                </button>
              </div>
            </div>

            <!-- Add Button -->
            <button
              @click="addSchedule"
              class="w-full bg-cyan-600 dark:bg-cyan-600 hover:bg-cyan-700 dark:hover:bg-cyan-500 text-white px-4 py-3 rounded-lg text-sm font-black uppercase tracking-widest transition-all hover:shadow-lg flex items-center justify-center gap-2"
            >
              <Plus class="h-4 w-4" />
              Add Schedule
            </button>
          </div>

          <!-- Existing Schedules List -->
          <div>
            <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2 uppercase tracking-wider">
              <Clock class="h-4 w-4 text-cyan-600 dark:text-cyan-400" />
              Active Schedules
            </h3>

            <div v-if="currentDevice?.schedules && currentDevice.schedules.length > 0" class="space-y-3">
              <div
                v-for="schedule in currentDevice.schedules"
                :key="schedule.id"
                class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4 flex items-center justify-between hover:bg-gray-100 dark:hover:bg-gray-700 border border-transparent dark:border-gray-700 transition-all group"
              >
                <div class="flex items-center gap-4 flex-1">
                  <div 
                    :class="[
                      'w-12 h-12 rounded-xl flex items-center justify-center transition-colors',
                      schedule.action === 'on' ? 'bg-green-100 dark:bg-green-950/40' : 'bg-red-100 dark:bg-red-950/40'
                    ]"
                  >
                    <Power :class="[
                      'h-6 w-6 transition-colors',
                      schedule.action === 'on' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
                    ]" />
                  </div>
                  <div class="flex-1">
                    <div class="flex items-center gap-2">
                      <span class="text-gray-900 dark:text-gray-100 font-black text-lg font-mono">{{ schedule.time }}</span>
                      <span 
                        :class="[
                          'text-[10px] px-2 py-0.5 rounded-full font-black uppercase tracking-wider',
                          schedule.action === 'on' ? 'bg-green-100 dark:bg-green-950/40 text-green-700 dark:text-green-400' : 'bg-red-100 dark:bg-red-950/40 text-red-700 dark:text-red-400'
                        ]"
                      >
                        {{ schedule.action }}
                      </span>
                    </div>
                    <p class="text-[11px] text-gray-600 dark:text-gray-400 font-bold uppercase tracking-tight mt-0.5">
                      {{ schedule.days.join(', ') }}
                    </p>
                  </div>
                  <div class="flex items-center gap-3">
                    <button
                      @click="toggleSchedule(schedule)"
                      :class="[
                        'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none',
                        schedule.enabled ? 'bg-cyan-600' : 'bg-gray-300 dark:bg-gray-600'
                      ]"
                    >
                      <span
                        :class="[
                          'inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow-sm',
                          schedule.enabled ? 'translate-x-6' : 'translate-x-1'
                        ]"
                      />
                    </button>
                    <button
                      @click="deleteSchedule(schedule.id)"
                      class="text-gray-400 dark:text-gray-500 hover:text-red-600 dark:hover:text-red-400 transition-colors p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-950/30"
                    >
                      <Trash2 class="h-4 w-4" />
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div v-else class="text-center py-10 bg-gray-50 dark:bg-gray-800/40 rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-800">
              <Clock class="h-10 w-10 text-gray-400 dark:text-gray-600 mx-auto mb-3" />
              <p class="text-gray-900 dark:text-gray-100 font-bold">No schedules yet</p>
              <p class="text-gray-500 dark:text-gray-400 text-xs mt-1">Add a schedule to automate this device</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
