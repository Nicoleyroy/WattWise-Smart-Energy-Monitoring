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
  voltage_threshold?: number
  current_threshold?: number
  power_threshold?: number
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

// Threshold configuration
const thresholdForm = ref<{
  id: number | null
  voltage: number | null
  current: number | null
  power: number | null
}>({
  id: null,
  voltage: null,
  current: null,
  power: null
})

const editingThreshold = ref<number | null>(null)

const startEditThreshold = (device: Device) => {
  editingThreshold.value = device.id
  thresholdForm.value = {
    id: device.id,
    voltage: device.voltage_threshold || 230,
    current: device.current_threshold || 10,
    power: device.power_threshold || 2000
  }
}

const saveThreshold = () => {
  // Here you would typically send to backend
  console.log('Saving thresholds:', thresholdForm.value)
  // Reset form
  editingThreshold.value = null
  alert('Threshold configuration saved successfully!')
}

const cancelEditThreshold = () => {
  editingThreshold.value = null
  thresholdForm.value = {
    id: null,
    voltage: null,
    current: null,
    power: null
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
      return 'text-green-400'
    case 'standby':
      return 'text-yellow-400'
    case 'offline':
      return 'text-gray-400'
    default:
      return 'text-gray-400'
  }
}

const getPowerLevel = (power: number): { label: string, color: string, bgColor: string } => {
  if (power === 0) return { label: 'Off', color: 'text-gray-400', bgColor: 'bg-gray-500/20' }
  if (power < 1) return { label: 'Low', color: 'text-green-400', bgColor: 'bg-green-500/20' }
  if (power < 2) return { label: 'Medium', color: 'text-yellow-400', bgColor: 'bg-yellow-500/20' }
  return { label: 'High', color: 'text-red-400', bgColor: 'bg-red-500/20' }
}

const getEfficiencyRating = (dailyUsage: number): { label: string, color: string } => {
  if (dailyUsage < 10) return { label: 'Excellent', color: 'text-green-400' }
  if (dailyUsage < 20) return { label: 'Good', color: 'text-cyan-400' }
  if (dailyUsage < 30) return { label: 'Fair', color: 'text-yellow-400' }
  return { label: 'Poor', color: 'text-red-400' }
}
</script>

<template>
  <Head title="Devices" />

  <div class="flex min-h-screen bg-slate-900">

    <!-- Sidebar -->
    <Sidebar />

    <!-- Main -->
    <main class="flex-1 ml-16">

      <!-- Header -->
      <header class="sticky top-0 z-40 flex items-center justify-between px-8 py-4 border-b border-slate-700/50 bg-black-800/80 backdrop-blur-lg shadow-lg">
        <div>
          <h1 class="text-2xl font-bold text-white">Devices</h1>
          <p class="text-sm text-gray-400 mt-1">
            View and manage your connected devices
          </p>
        </div>

      </header>

      <div class="p-8">

        <!-- Grid -->
        <div v-if="filtered.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

          <div
            v-for="device in filtered"
            :key="device.id"
            class="bg-slate-800 border border-slate-700 rounded-xl p-6 hover:border-cyan-500/50 hover:shadow-lg hover:shadow-cyan-500/10 transition-all duration-300 group"
          >
            <!-- Device Header -->
            <div class="flex items-start justify-between mb-4">
              <div class="flex items-center gap-3">
                <!-- Device Icon -->
                <div class="w-12 h-12 rounded-lg bg-cyan-500/20 flex items-center justify-center">
                  <component :is="getDeviceIcon(device.category)" class="h-6 w-6 text-cyan-400" />
                </div>
                
                <!-- Device Info -->
                <div class="flex-1">
                  <div v-if="editingName !== device.id" class="flex items-center gap-2 group/name">
                    <h3 class="text-white font-semibold">{{ device.name }}</h3>
                    <button 
                      @click.stop="startEditName(device)"
                      class="opacity-0 group-hover/name:opacity-100 transition-opacity"
                    >
                      <Edit2 class="h-3.5 w-3.5 text-gray-400 hover:text-cyan-400 transition-colors" />
                    </button>
                  </div>
                  <div v-else class="flex items-center gap-2" @click.stop>
                    <input 
                      v-model="nameForm"
                      @keyup.enter="saveName(device)"
                      @keyup.esc="cancelEditName"
                      type="text"
                      class="bg-slate-700 border border-slate-600 rounded px-2 py-1 text-white text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500"
                      autofocus
                    />
                    <button 
                      @click.stop="saveName(device)"
                      class="text-green-400 hover:text-green-300 transition-colors"
                    >
                      <Check class="h-4 w-4" />
                    </button>
                    <button 
                      @click.stop="cancelEditName"
                      class="text-red-400 hover:text-red-300 transition-colors"
                    >
                      <X class="h-4 w-4" />
                    </button>
                  </div>
                  <p class="text-xs text-gray-400">{{ device.category }}</p>
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
                <p class="text-xs text-gray-400 mb-1 flex items-center gap-1">
                  <Power class="h-3 w-3" />
                  Power
                </p>
                <p class="text-white font-semibold">{{ Number(device.power).toFixed(2) }} kW</p>
                <span 
                  :class="[getPowerLevel(device.power).color, getPowerLevel(device.power).bgColor]"
                  class="inline-block text-xs px-2 py-0.5 rounded-full mt-1"
                >
                  {{ getPowerLevel(device.power).label }}
                </span>
              </div>
              <div>
                <p class="text-xs text-gray-400 mb-1 flex items-center gap-1">
                  <Gauge class="h-3 w-3" />
                  Daily
                </p>
                <p class="text-white font-semibold">{{ device.daily_usage }} kWh</p>
                <span 
                  :class="getEfficiencyRating(device.daily_usage).color"
                  class="text-xs flex items-center gap-1 mt-1"
                >
                  <TrendingUp class="h-3 w-3" />
                  {{ getEfficiencyRating(device.daily_usage).label }}
                </span>
              </div>
              <!-- <div>
                <p class="text-xs text-gray-400 mb-1">Monthly</p>
                <p class="font-semibold" :class="device.monthly_cost < 0 ? 'text-green-400' : 'text-white'">
                  {{ device.monthly_cost < 0 ? '-' : '' }}${{ Math.abs(device.monthly_cost) }}
                </p>
                <p class="text-xs text-gray-400 mt-1">Cost</p>
              </div> -->
            </div>

            <!-- Progress Bar -->
            <div class="mb-4">
              <div class="flex items-center justify-between text-xs text-gray-400 mb-1.5">
                <span>Monthly Usage</span>
                <span>{{ Math.round((device.daily_usage * 30 / 100)) }}%</span>
              </div>
              <div class="w-full h-2 bg-slate-700 rounded-full overflow-hidden">
                <div 
                  class="h-full rounded-full bg-gradient-to-r from-cyan-500 to-cyan-400 transition-all"
                  :style="{ width: `${Math.min(device.daily_usage * 30 / 100, 100)}%` }"
                ></div>
              </div>
            </div>

            <!-- Device Details -->
            <div 
              class="pt-4 border-t border-slate-700 space-y-4"
            >
              <div class="flex justify-between text-sm bg-slate-700/30 px-3 py-2 rounded-lg">
                <span class="text-gray-400 flex items-center gap-2">
                  <Clock class="h-4 w-4" />
                  Uptime
                </span>
                <span class="text-white font-medium">{{ device.uptime }}</span>
              </div>

              <!-- Threshold Configuration Section -->
              <div class="mt-4 space-y-3 bg-slate-700/20 rounded-lg p-4">
                <div class="flex items-center justify-between">
                  <h4 class="text-sm font-semibold text-white flex items-center gap-2">
                    <Settings class="h-4 w-4 text-cyan-400" />
                    Threshold Configuration
                  </h4>
                  <button 
                    v-if="editingThreshold !== device.id"
                    @click.stop="startEditThreshold(device)"
                    class="text-xs bg-cyan-500/20 hover:bg-cyan-500/30 text-cyan-400 px-3 py-1.5 rounded-md transition-colors flex items-center gap-1"
                  >
                    <Settings class="h-3 w-3" />
                    Edit
                  </button>
                </div>

                <!-- View Mode -->
                <div v-if="editingThreshold !== device.id" class="grid grid-cols-3 gap-3">
                  <div class="bg-slate-800 rounded-lg p-3">
                    <p class="text-xs text-gray-400 mb-1">Voltage</p>
                    <p class="text-white font-semibold text-lg">{{ device.voltage_threshold || 230 }}</p>
                    <p class="text-xs text-gray-400">Volts</p>
                  </div>
                  <div class="bg-slate-800 rounded-lg p-3">
                    <p class="text-xs text-gray-400 mb-1">Current</p>
                    <p class="text-white font-semibold text-lg">{{ device.current_threshold || 10 }}</p>
                    <p class="text-xs text-gray-400">Amps</p>
                  </div>
                  <div class="bg-slate-800 rounded-lg p-3">
                    <p class="text-xs text-gray-400 mb-1">Power</p>
                    <p class="text-white font-semibold text-lg">{{ device.power_threshold || 2000 }}</p>
                    <p class="text-xs text-gray-400">Watts</p>
                  </div>
                </div>

                <!-- Edit Mode -->
                <div v-else class="space-y-3" @click.stop>
                  <div class="bg-slate-800 rounded-lg p-3">
                    <label class="flex items-center gap-2 text-xs text-gray-400 mb-2">
                      <AlertTriangle class="h-3 w-3 text-yellow-400" />
                      Voltage Limit (V)
                    </label>
                    <input 
                      v-model.number="thresholdForm.voltage"
                      type="number"
                      step="1"
                      min="0"
                      placeholder="230"
                      class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-all"
                    />
                  </div>
                  <div class="bg-slate-800 rounded-lg p-3">
                    <label class="flex items-center gap-2 text-xs text-gray-400 mb-2">
                      <AlertTriangle class="h-3 w-3 text-yellow-400" />
                      Current Limit (A)
                    </label>
                    <input 
                      v-model.number="thresholdForm.current"
                      type="number"
                      step="0.1"
                      min="0"
                      placeholder="10"
                      class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-all"
                    />
                  </div>
                  <div class="bg-slate-800 rounded-lg p-3">
                    <label class="flex items-center gap-2 text-xs text-gray-400 mb-2">
                      <AlertTriangle class="h-3 w-3 text-yellow-400" />
                      Power Limit (W)
                    </label>
                    <input 
                      v-model.number="thresholdForm.power"
                      type="number"
                      step="1"
                      min="0"
                      placeholder="2000"
                      class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-all"
                    />
                  </div>
                  <div class="flex gap-2 pt-2">
                    <button 
                      @click.stop="saveThreshold"
                      class="flex-1 bg-cyan-500 hover:bg-cyan-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-all hover:shadow-lg hover:shadow-cyan-500/50 flex items-center justify-center gap-2"
                    >
                      <Check class="h-4 w-4" />
                      Save
                    </button>
                    <button 
                      @click.stop="cancelEditThreshold"
                      class="flex-1 bg-slate-700 hover:bg-slate-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-colors flex items-center justify-center gap-2"
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
                    class="flex-1 bg-purple-500/20 hover:bg-purple-500/30 text-purple-400 hover:text-purple-300 px-3 py-2 rounded-lg text-sm font-medium transition-all flex items-center justify-center gap-2"
                  >
                    <Calendar class="h-4 w-4" />
                    Schedule
                  </button>
                  <button 
                    v-if="device.status === 'online'"
                    @click.stop="toggleDeviceStatus(device)"
                    class="flex-1 bg-red-500/20 hover:bg-red-500/30 text-red-400 hover:text-red-300 px-3 py-2 rounded-lg text-sm font-medium transition-all flex items-center justify-center gap-2"
                  >
                    <Power class="h-4 w-4" />
                    Turn Off
                  </button>
                  <button 
                    v-else
                    @click.stop="toggleDeviceStatus(device)"
                    class="flex-1 bg-green-500/20 hover:bg-green-500/30 text-green-400 hover:text-green-300 px-3 py-2 rounded-lg text-sm font-medium transition-all flex items-center justify-center gap-2"
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
          <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-800 mb-4">
            <Zap class="w-8 h-8 text-gray-500" />
          </div>
          <h3 class="text-xl font-semibold text-white mb-2">No devices found</h3>
          <p class="text-gray-400">No devices available</p>
        </div>
      </div>
    </main>

    <!-- Schedule Modal -->
    <div 
      v-if="scheduleModal"
      class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4"
      @click="closeScheduleModal"
    >
      <div 
        class="bg-slate-800 rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto border border-slate-700"
        @click.stop
      >
        <!-- Modal Header -->
        <div class="sticky top-0 bg-slate-800 border-b border-slate-700 px-6 py-4 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
              <Calendar class="h-5 w-5 text-purple-400" />
            </div>
            <div>
              <h2 class="text-xl font-bold text-white">Schedule Device</h2>
              <p class="text-sm text-gray-400">{{ currentDevice?.name }}</p>
            </div>
          </div>
          <button 
            @click="closeScheduleModal"
            class="text-gray-400 hover:text-white transition-colors"
          >
            <X class="h-6 w-6" />
          </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6 space-y-6">
          <!-- Add New Schedule Form -->
          <div class="bg-slate-700/30 rounded-xl p-4 space-y-4">
            <h3 class="text-sm font-semibold text-white flex items-center gap-2">
              <Plus class="h-4 w-4 text-cyan-400" />
              Add New Schedule
            </h3>

            <!-- Action Selection -->
            <div>
              <label class="block text-xs text-gray-400 mb-2">Action</label>
              <div class="flex gap-2">
                <button
                  @click="scheduleForm.action = 'on'"
                  :class="[
                    'flex-1 px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center justify-center gap-2',
                    scheduleForm.action === 'on'
                      ? 'bg-green-500/20 text-green-400 border-2 border-green-500'
                      : 'bg-slate-700 text-gray-400 border-2 border-slate-600 hover:border-slate-500'
                  ]"
                >
                  <Power class="h-4 w-4" />
                  Turn On
                </button>
                <button
                  @click="scheduleForm.action = 'off'"
                  :class="[
                    'flex-1 px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center justify-center gap-2',
                    scheduleForm.action === 'off'
                      ? 'bg-red-500/20 text-red-400 border-2 border-red-500'
                      : 'bg-slate-700 text-gray-400 border-2 border-slate-600 hover:border-slate-500'
                  ]"
                >
                  <Power class="h-4 w-4" />
                  Turn Off
                </button>
              </div>
            </div>

            <!-- Time Selection -->
            <div>
              <label class="block text-xs text-gray-400 mb-2">Time</label>
              <input 
                v-model="scheduleForm.time"
                type="time"
                class="w-full bg-slate-700 border border-slate-600 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500"
              />
            </div>

            <!-- Days Selection -->
            <div>
              <label class="block text-xs text-gray-400 mb-2">Repeat on</label>
              <div class="flex gap-2 flex-wrap">
                <button
                  v-for="day in daysOfWeek"
                  :key="day"
                  @click="toggleDay(day)"
                  :class="[
                    'px-4 py-2 rounded-lg text-sm font-medium transition-all',
                    scheduleForm.days.includes(day)
                      ? 'bg-cyan-500 text-white'
                      : 'bg-slate-700 text-gray-400 hover:bg-slate-600'
                  ]"
                >
                  {{ day }}
                </button>
              </div>
            </div>

            <!-- Add Button -->
            <button
              @click="addSchedule"
              class="w-full bg-cyan-500 hover:bg-cyan-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-all hover:shadow-lg hover:shadow-cyan-500/50 flex items-center justify-center gap-2"
            >
              <Plus class="h-4 w-4" />
              Add Schedule
            </button>
          </div>

          <!-- Existing Schedules List -->
          <div>
            <h3 class="text-sm font-semibold text-white mb-3 flex items-center gap-2">
              <Clock class="h-4 w-4 text-cyan-400" />
              Active Schedules
            </h3>

            <div v-if="currentDevice?.schedules && currentDevice.schedules.length > 0" class="space-y-3">
              <div
                v-for="schedule in currentDevice.schedules"
                :key="schedule.id"
                class="bg-slate-700/30 rounded-lg p-4 flex items-center justify-between hover:bg-slate-700/50 transition-all"
              >
                <div class="flex items-center gap-4 flex-1">
                  <div 
                    :class="[
                      'w-10 h-10 rounded-lg flex items-center justify-center',
                      schedule.action === 'on' ? 'bg-green-500/20' : 'bg-red-500/20'
                    ]"
                  >
                    <Power :class="[
                      'h-5 w-5',
                      schedule.action === 'on' ? 'text-green-400' : 'text-red-400'
                    ]" />
                  </div>
                  <div class="flex-1">
                    <div class="flex items-center gap-2">
                      <span class="text-white font-semibold">{{ schedule.time }}</span>
                      <span 
                        :class="[
                          'text-xs px-2 py-0.5 rounded-full',
                          schedule.action === 'on' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'
                        ]"
                      >
                        Turn {{ schedule.action }}
                      </span>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">
                      {{ schedule.days.join(', ') }}
                    </p>
                  </div>
                  <div class="flex items-center gap-2">
                    <button
                      @click="toggleSchedule(schedule)"
                      :class="[
                        'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                        schedule.enabled ? 'bg-cyan-500' : 'bg-slate-600'
                      ]"
                    >
                      <span
                        :class="[
                          'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                          schedule.enabled ? 'translate-x-6' : 'translate-x-1'
                        ]"
                      />
                    </button>
                    <button
                      @click="deleteSchedule(schedule.id)"
                      class="text-gray-400 hover:text-red-400 transition-colors p-2"
                    >
                      <Trash2 class="h-4 w-4" />
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div v-else class="text-center py-8">
              <Clock class="h-12 w-12 text-gray-600 mx-auto mb-3" />
              <p class="text-gray-400 text-sm">No schedules yet</p>
              <p class="text-gray-500 text-xs mt-1">Add a schedule to automate this device</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
