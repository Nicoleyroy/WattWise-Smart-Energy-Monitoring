<script setup>
import axios from 'axios'
import { Head, Link } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import Sidebar from '@/Components/Sidebar.vue'
import DeviceMonitoringPanel from '@/Components/DeviceMonitoringPanel.vue'
import { Zap, ArrowLeft, Settings, Calendar, Power, Pencil, Check, X, Activity, Plus, Trash2, ToggleLeft, ToggleRight, Archive, Download } from 'lucide-vue-next'

const props = defineProps({
    device: { type: Object, required: true }
})

const currentTab = ref('monitoring')

const deviceData = ref({
    name: props.device?.name || 'Smart Device',
    plugId: props.device?.id || 1,
    status: 'online',
    power: 0,
    dailyLimit: 0,
    dailyKwh: 0,
    thresholdType: 'daily',
})

const voltage = ref(0), current = ref(0), power = ref(0), energy = ref(0)
const isFirebaseConnected = ref(false)
const isDeviceOn = ref(true)
const showThresholdModal = ref(false)
const dailyLimitForm = ref({ kwh: 0, type: 'daily' }), isSavingThreshold = ref(false), thresholdMessage = ref('')
const showEnergyExportModal = ref(false)
const isExportingEnergy = ref(false)
const energyExportMessage = ref('')
const todayIso = new Date().toISOString().slice(0, 10)
const defaultStartIso = new Date(Date.now() - (6 * 24 * 60 * 60 * 1000)).toISOString().slice(0, 10)
const energyExportForm = ref({
    startDate: defaultStartIso,
    endDate: todayIso,
})
const latestPlugData = ref({})
const latestEnergyData = ref({})
const isEditingDeviceName = ref(false)
const deviceNameInput = ref('')
const isSavingDeviceName = ref(false)
const MAX_VALID_POWER_W = 1_000_000

const sanitizePower = (value, fallback = 0) => {
    const parsed = Number(value)
    if (!Number.isFinite(parsed)) return fallback
    if (Math.abs(parsed) >= MAX_VALID_POWER_W) return fallback
    return parsed
}

// ── Schedule state ──
const schedules = ref([])
const editingScheduleId = ref(null)
const isLoadingSchedule = ref(false)
const showScheduleModal = ref(false)
const scheduleForm = ref({ name: '', date: '', startTime: '', endTime: '', daysOfWeek: [] })
const isSavingSchedule = ref(false)
const scheduleMessage = ref('')
const scheduleHistory = ref([])
const isLoadingScheduleHistory = ref(false)
const scheduleHistoryView = ref('active')
const scheduleHistoryPage = ref(1)
const scheduleHistoryPerPage = 10
const isUpdatingHistoryArchive = ref(false)
const maxSchedulesPerDevice = 4
const showDeleteScheduleModal = ref(false)
const scheduleToDelete = ref(null)
const weekDayOptions = [
    { value: 1, label: 'Mon' },
    { value: 2, label: 'Tue' },
    { value: 3, label: 'Wed' },
    { value: 4, label: 'Thu' },
    { value: 5, label: 'Fri' },
    { value: 6, label: 'Sat' },
    { value: 0, label: 'Sun' },
]

// ── Schedule API calls ──
const fetchSchedules = async () => {
    isLoadingSchedule.value = true
    try {
        const response = await axios.get(`/api/devices/${props.device.id}/schedules`)
        schedules.value = Array.isArray(response.data) ? response.data : []
    } catch (error) {
        console.error('Failed to fetch schedule:', error)
    } finally {
        isLoadingSchedule.value = false
    }
}

const fetchScheduleHistory = async () => {
    isLoadingScheduleHistory.value = true
    try {
        const response = await axios.get(`/api/devices/${props.device.id}/schedule/history`)
        scheduleHistory.value = Array.isArray(response.data) ? response.data : []
    } catch (error) {
        console.error('Failed to fetch schedule history:', error)
    } finally {
        isLoadingScheduleHistory.value = false
    }
}

const openAddSchedule = () => {
    if (schedules.value.length >= maxSchedulesPerDevice) {
        return
    }
    editingScheduleId.value = null
    scheduleForm.value = { name: '', date: '', startTime: '', endTime: '', daysOfWeek: [] }
    scheduleMessage.value = ''
    showScheduleModal.value = true
}

const startEditDeviceName = () => {
    deviceNameInput.value = deviceData.value.name || ''
    isEditingDeviceName.value = true
}

const cancelEditDeviceName = () => {
    isEditingDeviceName.value = false
    deviceNameInput.value = ''
}

const saveDeviceName = async () => {
    const name = (deviceNameInput.value || '').trim()
    if (!name) return

    isSavingDeviceName.value = true
    try {
        await axios.post(`/api/devices/${props.device.id}/name`, { name })
        deviceData.value.name = name
        localStorage.setItem(`device_${props.device.id}_name`, name)
        isEditingDeviceName.value = false
    } catch (e) {
        console.error('Failed to update device name:', e)
    } finally {
        isSavingDeviceName.value = false
    }
}

const openEditSchedule = (schedule) => {
    if (!schedule) return
    const startDt = new Date(schedule.start_time)
    const endDt = new Date(schedule.end_time)
    const pad = (n) => String(n).padStart(2, '0')

    editingScheduleId.value = schedule.id
    const scheduleDays = Array.isArray(schedule.days_of_week)
        ? schedule.days_of_week.map((d) => Number(d)).filter((d) => d >= 0 && d <= 6)
        : []
    scheduleForm.value = {
        name: schedule.name,
        date: `${startDt.getFullYear()}-${pad(startDt.getMonth() + 1)}-${pad(startDt.getDate())}`,
        startTime: `${pad(startDt.getHours())}:${pad(startDt.getMinutes())}`,
        endTime: `${pad(endDt.getHours())}:${pad(endDt.getMinutes())}`,
        daysOfWeek: scheduleDays,
    }
    scheduleMessage.value = ''
    showScheduleModal.value = true
}

const saveSchedule = async () => {
    if (!scheduleForm.value.name.trim() || !scheduleForm.value.date || !scheduleForm.value.startTime || !scheduleForm.value.endTime) {
        scheduleMessage.value = 'Please fill in all fields.'
        return
    }
    isSavingSchedule.value = true
    scheduleMessage.value = ''
    try {
        const startLocal = new Date(`${scheduleForm.value.date}T${scheduleForm.value.startTime}:00`)
        const endLocal = new Date(`${scheduleForm.value.date}T${scheduleForm.value.endTime}:00`)

        const payload = {
            name: scheduleForm.value.name.trim(),
            start_time: startLocal.toISOString(),
            end_time: endLocal.toISOString(),
            days_of_week: [...(scheduleForm.value.daysOfWeek || [])]
                .map((d) => Number(d))
                .filter((d) => d >= 0 && d <= 6),
        }

        if (editingScheduleId.value) {
            await axios.put(`/api/devices/${props.device.id}/schedules/${editingScheduleId.value}`, payload)
            scheduleMessage.value = 'Schedule updated!'
        } else {
            await axios.post(`/api/devices/${props.device.id}/schedules`, payload)
            scheduleMessage.value = 'Schedule created!'
        }

        await fetchSchedules()
        await fetchScheduleHistory()
        setTimeout(() => { showScheduleModal.value = false }, 1000)
    } catch (e) {
        const msg = e.response?.data?.errors
            ? Object.values(e.response.data.errors).flat().join(' ')
            : (e.response?.data?.message || 'Error saving schedule.')
        scheduleMessage.value = msg
    } finally {
        isSavingSchedule.value = false
    }
}

const openDeleteScheduleModal = (schedule) => {
    if (!schedule) return
    scheduleToDelete.value = schedule
    showDeleteScheduleModal.value = true
}

const cancelDeleteSchedule = () => {
    showDeleteScheduleModal.value = false
    scheduleToDelete.value = null
}

const deleteSchedule = async () => {
    const schedule = scheduleToDelete.value
    if (!schedule) return
    try {
        await axios.delete(`/api/devices/${props.device.id}/schedules/${schedule.id}`)
        await fetchSchedules()
        await fetchScheduleHistory()
        cancelDeleteSchedule()
    } catch (e) { console.error('Failed to delete schedule:', e) }
}

const toggleScheduleActive = async (schedule) => {
    if (!schedule) return
    try {
        await axios.post(`/api/devices/${props.device.id}/schedules/${schedule.id}/activate`, {
            is_active: !schedule.is_active
        })
        await fetchSchedules()
        await fetchScheduleHistory()
    } catch (e) { console.error('Failed to toggle schedule:', e) }
}

const formatScheduleDate = (dateStr) => {
    if (!dateStr) return '—'
    return new Date(dateStr).toLocaleDateString('en-US', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' })
}

const formatScheduleTime = (dateStr) => {
    if (!dateStr) return '—'
    return new Date(dateStr).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true })
}

const formatScheduleDays = (days) => {
    if (!Array.isArray(days) || days.length === 0) return []
    const labelsByValue = { 0: 'Sun', 1: 'Mon', 2: 'Tue', 3: 'Wed', 4: 'Thu', 5: 'Fri', 6: 'Sat' }
    return [...days]
        .map((d) => Number(d))
        .filter((d) => d >= 0 && d <= 6)
        .sort((a, b) => (a === 0 ? 7 : a) - (b === 0 ? 7 : b))
        .map((d) => labelsByValue[d])
}

const isSchedulePast = (dateStr) => {
    if (!dateStr) return false
    return new Date(dateStr) < new Date()
}

const formatHistoryEvent = (event) => {
    const map = {
        schedule_created: 'Created',
        schedule_updated: 'Updated',
        schedule_deleted: 'Deleted',
        schedule_enabled: 'Enabled',
        schedule_disabled: 'Disabled',
        schedule_started: 'Started',
        schedule_completed: 'Completed',
        schedule_auto_completed: 'Auto-completed',
    }
    return map[event] || 'Updated'
}

const getHistoryEventClass = (event) => {
    const tones = {
        schedule_started: 'bg-cyan-50 text-cyan-700 dark:bg-cyan-950/40 dark:text-cyan-400',
        schedule_completed: 'bg-green-50 text-green-700 dark:bg-green-950/40 dark:text-green-400',
        schedule_auto_completed: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400',
        schedule_created: 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400',
        schedule_updated: 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400',
        schedule_deleted: 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-400',
        schedule_enabled: 'bg-teal-50 text-teal-700 dark:bg-teal-950/40 dark:text-teal-400',
        schedule_disabled: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    }

    return tones[event] || 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'
}

const getHistoryStatusClass = (status) => {
    const normalized = (status || '').toLowerCase()
    if (normalized === 'active') {
        return 'bg-cyan-50 dark:bg-cyan-950/40 text-cyan-700 dark:text-cyan-400'
    }
    if (normalized === 'completed') {
        return 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300'
    }
    if (normalized === 'pending') {
        return 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400'
    }
    return 'bg-green-50 dark:bg-green-950/40 text-green-700 dark:text-green-400'
}

const formatScheduleStatus = (status) => {
    const normalized = (status || '').toLowerCase()
    if (normalized === 'pending') return 'Waiting to Start'
    if (normalized === 'active') return 'Running'
    if (normalized === 'completed') return 'Finished'
    if (normalized === 'event' || !normalized) return 'Logged'
    return status
}

const formatDateTime = (dateStr) => {
    if (!dateStr) return '—'
    return new Date(dateStr).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    })
}

const formatDateShort = (dateStr) => {
    if (!dateStr) return '—'
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    })
}

const formatTimeShort = (dateStr) => {
    if (!dateStr) return '—'
    return new Date(dateStr).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    })
}

const filteredScheduleHistory = computed(() => {
    const archivedView = scheduleHistoryView.value === 'archived'
    return scheduleHistory.value.filter((item) => Boolean(item.archived) === archivedView)
})

const totalScheduleHistoryPages = computed(() => {
    const total = Math.ceil(filteredScheduleHistory.value.length / scheduleHistoryPerPage)
    return Math.max(1, total)
})

const paginatedScheduleHistory = computed(() => {
    const start = (scheduleHistoryPage.value - 1) * scheduleHistoryPerPage
    return filteredScheduleHistory.value.slice(start, start + scheduleHistoryPerPage)
})

const scheduleHistoryRowStart = computed(() => ((scheduleHistoryPage.value - 1) * scheduleHistoryPerPage) + 1)

const goToScheduleHistoryPage = (page) => {
    const safePage = Math.min(Math.max(1, Number(page) || 1), totalScheduleHistoryPages.value)
    scheduleHistoryPage.value = safePage
}

const scheduleLimitReached = computed(() => schedules.value.length >= maxSchedulesPerDevice)

const scheduleHistoryCount = computed(() => ({
    active: scheduleHistory.value.filter((item) => !item.archived).length,
    archived: scheduleHistory.value.filter((item) => Boolean(item.archived)).length,
}))

const archiveHistoryItem = async (item, archived = true) => {
    if (!item?.id) return
    isUpdatingHistoryArchive.value = true
    try {
        await axios.post(`/api/devices/${props.device.id}/schedule/history/${item.id}/archive`, { archived })
        await fetchScheduleHistory()
    } catch (error) {
        console.error('Failed to archive schedule history item:', error)
    } finally {
        isUpdatingHistoryArchive.value = false
    }
}

const escapeCsv = (value) => {
    if (value === null || value === undefined) return ''
    const str = String(value)
    if (/[",\n]/.test(str)) {
        return `"${str.replace(/"/g, '""')}"`
    }
    return str
}

const exportScheduleHistory = () => {
    const headers = ['Event', 'Schedule', 'Window Start', 'Window End', 'Logged At', 'Status', 'View']
    const records = filteredScheduleHistory.value.map((item) => {
        return [
            formatHistoryEvent(item.event),
            item.name || 'Schedule',
            formatDateTime(item.start_time),
            formatDateTime(item.end_time),
            formatDateTime(item.timestamp),
            formatScheduleStatus(item.status || 'event'),
            scheduleHistoryView.value,
        ]
    })

    const reportRows = [
        ['Report', 'Schedule History Export'],
        ['Device', deviceData.value.name || 'Device'],
        ['Device ID', `PLUG${props.device.id}`],
        ['View', scheduleHistoryView.value],
        ['Exported At', new Date().toISOString()],
        [],
        headers,
        ...records,
        [],
        ['Footer'],
        ['Total Records', records.length],
    ]

    const lines = reportRows.map((row) => row.map(escapeCsv).join(','))

    const csv = `\uFEFF${lines.join('\n')}`
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const date = new Date().toISOString().slice(0, 10)
    const scope = scheduleHistoryView.value
    const filename = `${(deviceData.value.name || 'device').replace(/\s+/g, '_')}_schedule_history_${scope}_${date}.csv`

    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
}

const exportEnergyByDate = async () => {
    energyExportMessage.value = ''

    if (!energyExportForm.value.startDate || !energyExportForm.value.endDate) {
        energyExportMessage.value = 'Please select both start and end dates.'
        return
    }

    if (energyExportForm.value.endDate < energyExportForm.value.startDate) {
        energyExportMessage.value = 'End date cannot be earlier than start date.'
        return
    }

    isExportingEnergy.value = true
    try {
        const response = await axios.get(`/api/devices/${props.device.id}/energy/export`, {
            params: {
                start_date: energyExportForm.value.startDate,
                end_date: energyExportForm.value.endDate,
            },
            responseType: 'blob',
        })

        const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8;' })
        const url = URL.createObjectURL(blob)

        const defaultName = `${(deviceData.value.name || 'device').replace(/\s+/g, '_')}_energy_${energyExportForm.value.startDate}_to_${energyExportForm.value.endDate}.csv`
        const disposition = response.headers?.['content-disposition'] || ''
        const match = disposition.match(/filename="?([^\";]+)"?/i)
        const filename = match?.[1] || defaultName

        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', filename)
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        URL.revokeObjectURL(url)

        energyExportMessage.value = 'Export downloaded successfully.'
        setTimeout(() => {
            showEnergyExportModal.value = false
            energyExportMessage.value = ''
        }, 1000)
    } catch (error) {
        const status = error.response?.status
        energyExportMessage.value = status === 404
            ? 'No data found for the selected date range. Try a wider range.'
            : status === 422
                ? 'Invalid date range. Please check your selected dates.'
                : 'Failed to export energy data.'
    } finally {
        isExportingEnergy.value = false
    }
}

watch(scheduleHistoryView, () => {
    scheduleHistoryPage.value = 1
})

watch(filteredScheduleHistory, () => {
    if (scheduleHistoryPage.value > totalScheduleHistoryPages.value) {
        scheduleHistoryPage.value = totalScheduleHistoryPages.value
    }
})

const getUsageByThresholdType = (type, energyData, plugData) => {
    const thresholdType = ['daily', 'weekly', 'monthly'].includes(type) ? type : 'daily'

    if (thresholdType === 'weekly') {
        return Number(energyData?.weekly_kwh ?? plugData?.weekly_kwh ?? 0)
    }

    if (thresholdType === 'monthly') {
        return Number(energyData?.monthly_kwh ?? plugData?.monthly_kwh ?? 0)
    }

    return Number(energyData?.daily_kwh ?? plugData?.daily_kwh ?? 0)
}

const refreshThresholdUsage = () => {
    const activeType = ['daily', 'weekly', 'monthly'].includes(dailyLimitForm.value.type)
        ? dailyLimitForm.value.type
        : 'daily'

    deviceData.value.thresholdType = activeType
    deviceData.value.dailyKwh = getUsageByThresholdType(activeType, latestEnergyData.value, latestPlugData.value)
}

// ── Device name ──
onMounted(() => {
    const savedName = localStorage.getItem(`device_${props.device.id}_name`)
    if (savedName) deviceData.value.name = savedName
    fetchSchedules()
    fetchScheduleHistory()
    fetchDeviceThreshold()
    if (window.db) {
        isFirebaseConnected.value = true
        const deviceKey = `PLUG${props.device.id}`
        window.db.ref(`Live/${deviceKey}`).on('value', (snapshot) => {
            const data = snapshot.val()
            if (!data) return
            voltage.value = data.voltage || 0
            current.value = data.current || 0
            power.value = sanitizePower(data.power, power.value)
            energy.value = data.energy || 0
            deviceData.value.power = sanitizePower(data.power, deviceData.value.power)
        })
        window.db.ref(`Control/${deviceKey}`).on('value', (snapshot) => {
            const state = snapshot.val()
            if (state !== null) isDeviceOn.value = state
        })
         window.db.ref(`plugs/plug${props.device.id}`).on('value', (snapshot) => {
            const plug = snapshot.val()
            if (plug) {
                latestPlugData.value = plug
                if (plug.name && String(plug.name).trim()) {
                    deviceData.value.name = String(plug.name).trim()
                    localStorage.setItem(`device_${props.device.id}_name`, deviceData.value.name)
                }
                const thresholdType = ['daily', 'weekly', 'monthly'].includes(plug.threshold_type)
                    ? plug.threshold_type
                    : 'daily'
                const thresholdValue = Number(plug.threshold_value ?? plug.daily_limit ?? 0)

                deviceData.value.dailyLimit = thresholdValue
                dailyLimitForm.value.kwh = thresholdValue
                dailyLimitForm.value.type = thresholdType

                refreshThresholdUsage()
            }
        })

        window.db.ref(`Energy/${deviceKey}`).on('value', (snapshot) => {
            latestEnergyData.value = snapshot.val() || {}
            refreshThresholdUsage()
        })

    }
})

onUnmounted(() => {
    if (window.db) {
        const deviceKey = `PLUG${props.device.id}`
        window.db.ref(`Live/${deviceKey}`).off()
        window.db.ref(`Control/${deviceKey}`).off()
        window.db.ref(`plugs/plug${props.device.id}`).off()
        window.db.ref(`Energy/${deviceKey}`).off()
   
    }
})

const fetchDeviceThreshold = async () => {
    try {
        const response = await axios.get(`/api/devices/${props.device.id}/threshold`)
        const thresholdValue = response.data?.threshold_value ?? response.data?.daily_limit
        const thresholdType = response.data?.threshold_type ?? 'daily'

        if (thresholdValue !== undefined) {
            deviceData.value.dailyLimit = Number(thresholdValue || 0)
            dailyLimitForm.value.kwh = Number(thresholdValue || 0)
            dailyLimitForm.value.type = ['daily', 'weekly', 'monthly'].includes(thresholdType) ? thresholdType : 'daily'
            refreshThresholdUsage()
        }
    } catch (e) {}
}

const handleTurnOff = async () => {
    const newState = !isDeviceOn.value
    try {
        await axios.post(`/api/ports/${props.device.id}/toggle`, { state: newState })
        isDeviceOn.value = newState
    } catch (e) {
        console.error('Failed to toggle device:', e)
    }
}

const saveThresholds = async () => {
    isSavingThreshold.value = true
    try {
        await axios.post(`/api/devices/${props.device.id}/threshold`, {
            threshold_value: dailyLimitForm.value.kwh,
            threshold_type: dailyLimitForm.value.type,
        })
        deviceData.value.dailyLimit = dailyLimitForm.value.kwh
        deviceData.value.thresholdType = dailyLimitForm.value.type
        refreshThresholdUsage()
        thresholdMessage.value = 'Saved successfully!'
        setTimeout(() => showThresholdModal.value = false, 1500)
   } catch (e) { thresholdMessage.value = 'Error saving daily limit' }
    
        finally { isSavingThreshold.value = false }
}

const formattedVoltage = computed(() => voltage.value.toFixed(1))
const formattedCurrent = computed(() => current.value.toFixed(2))
const formattedPower = computed(() => power.value.toFixed(1))
const formattedEnergy = computed(() => energy.value.toFixed(2))
const controlActionLabel = computed(() => (isDeviceOn.value ? 'OFF' : 'ON'))
const controlActionClass = computed(() => (
    isDeviceOn.value
        ? 'bg-red-600 hover:bg-red-500 text-white shadow-red-500/20'
        : 'bg-green-600 hover:bg-green-500 text-white shadow-green-500/20'
))
</script>

<template>
    <Head :title="`${deviceData.name} Settings`" />
    <div class="flex min-h-screen bg-white dark:bg-gray-950 transition-colors duration-300">
        <Sidebar />
        <div class="flex-1 overflow-y-auto h-screen bg-white dark:bg-gray-950 transition-all duration-300" style="margin-left: var(--sidebar-width, 4rem);">
            <div class="mx-auto max-w-7xl px-6 py-8">
                <Link href="/dashboard" class="inline-flex items-center gap-2 text-cyan-500 dark:text-cyan-400 hover:text-cyan-600 dark:hover:text-cyan-300 mb-8 transition-colors group">
                    <ArrowLeft class="w-4 h-4 group-hover:-translate-x-1 transition-transform" />
                    <span class="text-sm font-medium">Back to Dashboard</span>
                </Link>

                <!-- Device Header -->
                <div class="sticky top-0 z-50 -mx-6 mb-8 rounded-xl border border-slate-200 bg-white px-6 pt-5 shadow-sm transition-colors dark:border-slate-800 dark:bg-slate-950">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex min-w-0 items-center gap-4 sm:gap-6">
                            
                            <div class="min-w-0">
                                <p class="mb-1 text-[11px] font-semibold uppercase tracking-wider text-cyan-600 dark:text-cyan-400">Device control</p>
                                <div v-if="!isEditingDeviceName" class="flex items-center gap-2">
                                    <h1 class="truncate text-xl font-bold tracking-tight text-gray-900 dark:text-gray-100 sm:text-2xl">{{ deviceData.name }}</h1>
                                    <button @click="startEditDeviceName"
                                        class="p-1.5 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-cyan-600 dark:hover:text-cyan-400 transition-colors"
                                        title="Rename device">
                                        <Pencil class="w-4 h-4" />
                                    </button>
                                </div>
                                <div v-else class="flex items-center gap-2">
                                    <input v-model="deviceNameInput" type="text" maxlength="80"
                                        class="px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100 font-bold text-lg focus:ring-2 focus:ring-cyan-500 outline-none" />
                                    <button @click="saveDeviceName" :disabled="isSavingDeviceName || !deviceNameInput.trim()"
                                        class="p-2 rounded-lg bg-cyan-600 text-white hover:bg-cyan-500 disabled:opacity-50 transition-colors">
                                        <Check class="w-4 h-4" />
                                    </button>
                                    <button @click="cancelEditDeviceName"
                                        class="p-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                                        <X class="w-4 h-4" />
                                    </button>
                                </div>
                                <p class="mt-1 text-sm font-medium text-gray-600 dark:text-gray-400">Plug Unit {{ deviceData.plugId }} <span class="mx-1 text-gray-300 dark:text-gray-700">/</span> {{ isFirebaseConnected ? 'Live link active' : 'Connecting' }}</p>
                            </div>
                        </div>
                        <button @click="handleTurnOff"
                            class="group shrink-0 flex items-center gap-2 rounded-lg px-4 py-3 text-sm font-bold transition-all shadow-lg hover:shadow-xl active:scale-95 sm:gap-3 sm:px-6 sm:py-3.5 sm:text-base"
                            :class="controlActionClass">
                            <Power class="w-5 h-5 group-hover:scale-110 transition-transform" />
                            <span class="hidden sm:inline">{{ controlActionLabel }}</span>
                        </button>
                    </div>

                    <!-- Tabs -->
                    <div class="mt-6 flex items-center gap-2">
                        <button @click="currentTab = 'monitoring'"
                            class="flex items-center gap-2 rounded-t-lg px-4 py-3 text-sm font-semibold transition-colors sm:px-6"
                            :class="currentTab === 'monitoring' ? 'bg-cyan-500 text-white shadow-cyan-500/20' : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'">
                            <Activity class="w-4 h-4" /> Live Monitoring
                        </button>
                        <button @click="currentTab = 'settings'"
                            class="flex items-center gap-2 rounded-t-lg px-4 py-3 text-sm font-semibold transition-colors sm:px-6"
                            :class="currentTab === 'settings' ? 'bg-cyan-500 text-white shadow-cyan-500/20' : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'">
                            <Settings class="w-4 h-4" /> General Settings
                        </button>
                    </div>
                </div>

                <!-- Live Monitoring Tab -->
                <div v-if="currentTab === 'monitoring'">
                    <DeviceMonitoringPanel :deviceId="props.device.id" :deviceName="deviceData.name" />
                </div>

                <!-- General Settings Tab -->
                <div v-else>
                    <!-- Metrics -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="bg-white dark:bg-gray-900 border border-cyan-400/50 dark:border-cyan-500/30 rounded-xl p-6 shadow-sm hover:shadow-md transition-all">
                            <p class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-widest mb-3">Voltage</p>
                            <div class="flex items-baseline gap-2">
                                <span class="text-4xl font-black text-gray-900 dark:text-gray-100">{{ formattedVoltage }}</span>
                                <span class="text-xl font-bold text-gray-500">V</span>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-900 border border-cyan-400/50 dark:border-cyan-500/30 rounded-xl p-6 shadow-sm hover:shadow-md transition-all">
                            <p class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-widest mb-3">Current</p>
                            <div class="flex items-baseline gap-2">
                                <span class="text-4xl font-black text-gray-900 dark:text-gray-100">{{ formattedCurrent }}</span>
                                <span class="text-xl font-bold text-gray-500">A</span>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-900 border border-cyan-400/50 dark:border-cyan-500/30 rounded-xl p-6 shadow-sm hover:shadow-md transition-all">
                            <p class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-widest mb-3">Power</p>
                            <div class="flex items-baseline gap-2">
                                <span class="text-4xl font-black text-gray-900 dark:text-gray-100">{{ formattedPower }}</span>
                                <span class="text-xl font-bold text-gray-500">W</span>
                            </div>
                        </div>
                    </div>

                    <!-- Energy Limit + Export -->
                    <div class="mb-10 grid grid-cols-1 xl:grid-cols-2 gap-8 items-stretch">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-2xl font-black text-gray-900 dark:text-gray-100 tracking-tight">Energy Limit</h2>
                                <button @click="showThresholdModal = true"
                                    class="inline-flex items-center justify-center px-4 py-2.5 min-w-[128px] bg-cyan-500/10 dark:bg-cyan-500/20 text-cyan-600 dark:text-cyan-400 rounded-xl border border-cyan-500/30 hover:bg-cyan-500/20 dark:hover:bg-cyan-500/30 transition-colors font-bold text-sm">
                                    Edit Limit
                                </button>
                            </div>
                            <div class="bg-white dark:bg-gray-900 border border-cyan-400/50 dark:border-cyan-500/30 rounded-2xl p-7 text-center shadow-sm min-h-[238px] flex flex-col justify-center">
                                <p class="text-5xl font-black text-gray-900 dark:text-gray-100 leading-none">{{ deviceData.dailyKwh.toFixed(3) }} / {{ deviceData.dailyLimit.toFixed(3) }}</p>
                                <p class="text-sm font-black text-cyan-600 dark:text-cyan-400 mt-3 uppercase tracking-[0.18em]">{{ deviceData.thresholdType }} consumption / limit</p>
                                <p class="text-sm text-gray-500 dark:text-gray-500 mt-3 font-medium">Only one threshold is active at a time: daily, weekly, or monthly.</p>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-2xl font-black text-gray-900 dark:text-gray-100 tracking-tight">Energy Data Export</h2>
                            </div>
                            <div class="bg-white dark:bg-gray-900 border border-cyan-400/50 dark:border-cyan-500/30 rounded-2xl p-7 shadow-sm min-h-[238px] flex flex-col justify-center">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                                    <div>
                                        <label class="block text-xs font-black text-gray-500 dark:text-gray-500 uppercase tracking-widest mb-2">Start Date</label>
                                        <input
                                            v-model="energyExportForm.startDate"
                                            type="date"
                                            class="w-full px-4 py-3.5 bg-gray-50 dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition-all text-gray-900 dark:text-gray-100 font-bold"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-xs font-black text-gray-500 dark:text-gray-500 uppercase tracking-widest mb-2">End Date</label>
                                        <input
                                            v-model="energyExportForm.endDate"
                                            type="date"
                                            class="w-full px-4 py-3.5 bg-gray-50 dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition-all text-gray-900 dark:text-gray-100 font-bold"
                                        />
                                    </div>

                                    <div>
                                        <button @click="exportEnergyByDate" :disabled="isExportingEnergy"
                                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-3.5 bg-cyan-600 text-white rounded-xl hover:bg-cyan-500 transition-all font-bold uppercase tracking-wider text-sm shadow-lg shadow-cyan-500/20 disabled:opacity-50 disabled:cursor-not-allowed">
                                            <Download class="w-4 h-4" />
                                            {{ isExportingEnergy ? 'Exporting...' : 'Export CSV' }}
                                        </button>
                                    </div>
                                </div>

                                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400 font-medium">Exports readings for this device only, within the selected date range.</p>

                                <div v-if="energyExportMessage" class="mt-4 text-sm px-4 py-2.5 rounded-lg font-bold"
                                    :class="energyExportMessage.includes('successfully') ? 'bg-cyan-50 dark:bg-cyan-900/30 text-cyan-600 dark:text-cyan-400' : 'bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400'">
                                    {{ energyExportMessage }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-8">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-2xl font-black text-gray-900 dark:text-gray-100 tracking-tight">Scheduling</h2>
                            <button @click="openAddSchedule" :disabled="scheduleLimitReached"
                                class="flex items-center gap-2 px-4 py-2.5 rounded-lg border transition-colors font-bold text-sm disabled:opacity-60 disabled:cursor-not-allowed"
                                :class="scheduleLimitReached
                                    ? 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-700'
                                    : 'bg-cyan-500/10 dark:bg-cyan-500/20 text-cyan-600 dark:text-cyan-400 border-cyan-500/30 hover:bg-cyan-500/20 dark:hover:bg-cyan-500/30'">
                                <Plus class="w-4 h-4" />
                                {{ scheduleLimitReached ? `Limit Reached (${schedules.length}/4)` : `Add Schedule (${schedules.length}/4)` }}
                            </button>
                        </div>

                        <!-- Loading -->
                        <div v-if="isLoadingSchedule" class="py-10 text-center text-gray-400 dark:text-gray-600 text-sm font-medium italic">
                            Loading schedule data...
                        </div>

                        <!-- Empty state -->
                        <div v-else-if="!schedules.length"
                            class="bg-white dark:bg-gray-900 border-2 border-dashed border-gray-200 dark:border-gray-800 rounded-xl p-12 text-center transition-colors">
                            <Calendar class="w-12 h-12 text-gray-300 dark:text-gray-700 mx-auto mb-4" />
                            <p class="text-gray-600 dark:text-gray-400 text-sm font-bold uppercase tracking-wider">No active schedule</p>
                            <p class="text-gray-400 dark:text-gray-500 text-xs mt-2 max-w-xs mx-auto">Configure a schedule to automatically manage this device's power state during a set interval.</p>
                        </div>

                        <!-- Schedule card -->
                        <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <div v-for="schedule in schedules" :key="schedule.id" class="bg-white dark:bg-gray-900 border-2 rounded-2xl p-6 transition-all duration-300 hover:shadow-xl group"
                                :class="schedule.is_active ? 'border-cyan-400 dark:border-cyan-500/50 shadow-cyan-500/5' : 'border-gray-200 dark:border-gray-800 opacity-70'">

                                <!-- Name -->
                                <div class="flex items-start justify-between mb-5">
                                    <div class="flex items-center gap-3">
                                        <h3 class="font-black text-gray-900 dark:text-gray-100 truncate flex-1 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">{{ schedule.name }}</h3>
                                    </div>
                                    <span class="shrink-0 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border"
                                        :class="schedule.status === 'active'
                                            ? 'bg-green-50 dark:bg-green-950/40 text-green-700 dark:text-green-400 border-green-200 dark:border-green-900/50'
                                            : (schedule.status === 'completed' ? 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-400 border-gray-200 dark:border-gray-700' : 'bg-cyan-50 dark:bg-cyan-950/40 text-cyan-700 dark:text-cyan-400 border-cyan-200 dark:border-cyan-900/50')">
                                        {{ formatScheduleStatus(schedule.status || 'pending') }}
                                    </span>
                                </div>

                                <!-- Date & Time Range -->
                                <div class="space-y-4 mb-6">
                                    <div class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300">
                                        <Calendar class="w-4 h-4 text-cyan-500" />
                                        <span>{{ formatScheduleDate(schedule.start_time) }}</span>
                                    </div>
                                    <div v-if="Array.isArray(schedule.days_of_week) && schedule.days_of_week.length" class="flex flex-wrap items-center gap-2">
                                        <span class="text-[10px] text-cyan-600 dark:text-cyan-400 uppercase tracking-widest font-black">Repeats:</span>
                                        <span v-for="day in formatScheduleDays(schedule.days_of_week)" :key="`${schedule.id}-${day}`"
                                            class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-cyan-50 dark:bg-cyan-950/40 text-cyan-700 dark:text-cyan-400 border border-cyan-200 dark:border-cyan-900/50">
                                            {{ day }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-800 transition-colors">
                                        <div class="flex flex-col">
                                            <span class="text-[10px] text-gray-400 dark:text-gray-500 uppercase font-black tracking-widest mb-1">Start (ON)</span>
                                            <span class="text-xl font-black text-gray-900 dark:text-gray-100 font-mono tracking-tight">{{ formatScheduleTime(schedule.start_time) }}</span>
                                        </div>
                                        <div class="h-10 w-px bg-gray-200 dark:bg-gray-700 mx-4"></div>
                                        <div class="flex flex-col text-right">
                                            <span class="text-[10px] text-gray-400 dark:text-gray-500 uppercase font-black tracking-widest mb-1">End (OFF)</span>
                                            <span class="text-xl font-black text-gray-900 dark:text-gray-100 font-mono tracking-tight">{{ formatScheduleTime(schedule.end_time) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action buttons -->
                                <div class="flex items-center gap-2 pt-4 border-t border-gray-100 dark:border-gray-800">
                                    <button @click="toggleScheduleActive(schedule)"
                                        class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-black uppercase tracking-wider transition-all"
                                        :class="schedule.is_active
                                            ? 'bg-cyan-50 dark:bg-cyan-950/40 text-cyan-600 dark:text-cyan-400 hover:bg-cyan-100 dark:hover:bg-cyan-900/60 shadow-sm'
                                            : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-700'">
                                        <ToggleRight v-if="schedule.is_active" class="w-4 h-4" />
                                        <ToggleLeft v-else class="w-4 h-4" />
                                        {{ schedule.is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                    <button @click="openEditSchedule(schedule)"
                                        class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-black uppercase tracking-wider bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 transition-all shadow-sm">
                                        <Pencil class="w-3.5 h-3.5" />
                                        Edit
                                    </button>
                                    <button @click="openDeleteScheduleModal(schedule)"
                                        class="ml-auto flex items-center justify-center p-2 rounded-lg bg-red-50 dark:bg-red-950/30 text-red-500 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/50 transition-all shadow-sm">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6">
                            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="text-lg font-black text-gray-900 dark:text-gray-100 tracking-tight">Schedule History</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Operational log of schedule changes and execution states.</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button @click="scheduleHistoryView = 'active'"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider border transition-colors"
                                        :class="scheduleHistoryView === 'active' ? 'bg-cyan-500 text-white border-cyan-500' : 'bg-white dark:bg-gray-900 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-700'">
                                        Active ({{ scheduleHistoryCount.active }})
                                    </button>
                                    <button @click="scheduleHistoryView = 'archived'"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider border transition-colors"
                                        :class="scheduleHistoryView === 'archived' ? 'bg-cyan-500 text-white border-cyan-500' : 'bg-white dark:bg-gray-900 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-700'">
                                        Archived ({{ scheduleHistoryCount.archived }})
                                    </button>
                                    <button @click="exportScheduleHistory"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider border border-cyan-500/40 text-cyan-600 dark:text-cyan-400 hover:bg-cyan-50 dark:hover:bg-cyan-950/40 transition-colors">
                                        <Download class="w-3.5 h-3.5" />
                                        Export CSV
                                    </button>
                                </div>
                            </div>

                            <div v-if="isLoadingScheduleHistory" class="py-6 text-sm text-gray-400 dark:text-gray-600 italic">
                                Loading schedule history...
                            </div>

                            <div v-else-if="filteredScheduleHistory.length === 0"
                                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-6 text-sm text-gray-500 dark:text-gray-400">
                                No {{ scheduleHistoryView }} schedule history yet.
                            </div>

                            <div v-else class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-slate-50 dark:bg-gray-800/70 border-b border-gray-200 dark:border-gray-800">
                                            <tr>
                                                <th class="px-4 py-3.5 text-left text-[11px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">#</th>
                                                <th class="px-4 py-3.5 text-left text-[11px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">Activity</th>
                                                <th class="px-4 py-3.5 text-left text-[11px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">Schedule</th>
                                                <th class="px-4 py-3.5 text-left text-[11px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">Window</th>
                                                <th class="px-4 py-3.5 text-left text-[11px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">Logged</th>
                                                <th class="px-4 py-3.5 text-left text-[11px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">State</th>
                                                <th class="px-4 py-3.5 text-right text-[11px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                            <tr v-for="(item, index) in paginatedScheduleHistory" :key="item.id" class="hover:bg-slate-50/80 dark:hover:bg-gray-800/40 transition-colors">
                                                <td class="px-4 py-4 align-top text-xs font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                                    {{ scheduleHistoryRowStart + index }}
                                                </td>
                                                <td class="px-4 py-4 align-top">
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border"
                                                        :class="getHistoryEventClass(item.event)">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-current opacity-70"></span>
                                                        {{ formatHistoryEvent(item.event) }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-4 align-top">
                                                    <div class="font-bold text-gray-900 dark:text-gray-100">
                                                        {{ item.name || 'Schedule' }}
                                                    </div>
                                                    <div class="mt-1 text-[11px] uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400">
                                                        {{ formatDateShort(item.start_time) }}
                                                    </div>
                                                </td>
                                                <td class="px-4 py-4 align-top text-xs text-gray-600 dark:text-gray-300">
                                                    <div class="inline-flex items-center gap-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 px-2.5 py-1.5">
                                                        <span class="text-[10px] font-black uppercase tracking-wider text-cyan-600 dark:text-cyan-400">On</span>
                                                        <span class="font-bold text-gray-800 dark:text-gray-200">{{ formatTimeShort(item.start_time) }}</span>
                                                    </div>
                                                    <div class="my-1 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">to</div>
                                                    <div class="inline-flex items-center gap-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 px-2.5 py-1.5">
                                                        <span class="text-[10px] font-black uppercase tracking-wider text-rose-600 dark:text-rose-400">Off</span>
                                                        <span class="font-bold text-gray-800 dark:text-gray-200">{{ formatTimeShort(item.end_time) }}</span>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-4 align-top text-xs whitespace-nowrap">
                                                    <div class="font-semibold text-gray-700 dark:text-gray-300">{{ formatDateShort(item.timestamp) }}</div>
                                                    <div class="text-gray-500 dark:text-gray-400">{{ formatTimeShort(item.timestamp) }}</div>
                                                </td>
                                                <td class="px-4 py-4 align-top">
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest"
                                                        :class="getHistoryStatusClass(item.status)">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-current opacity-70"></span>
                                                        {{ formatScheduleStatus(item.status || 'event') }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-4 align-top text-right">
                                                    <button
                                                        v-if="scheduleHistoryView === 'active'"
                                                        @click="archiveHistoryItem(item, true)"
                                                        :disabled="isUpdatingHistoryArchive"
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-60">
                                                        <Archive class="w-3 h-3" />
                                                        Archive
                                                    </button>
                                                    <button
                                                        v-else
                                                        @click="archiveHistoryItem(item, false)"
                                                        :disabled="isUpdatingHistoryArchive"
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest border border-cyan-500/40 text-cyan-600 dark:text-cyan-400 hover:bg-cyan-50 dark:hover:bg-cyan-950/40 disabled:opacity-60">
                                                        Restore
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="flex items-center justify-between border-t border-gray-100 dark:border-gray-800 px-4 py-3 bg-gray-50/70 dark:bg-gray-900/60">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Showing {{ paginatedScheduleHistory.length ? scheduleHistoryRowStart : 0 }}
                                        to {{ paginatedScheduleHistory.length ? (scheduleHistoryRowStart + paginatedScheduleHistory.length - 1) : 0 }}
                                        of {{ filteredScheduleHistory.length }}
                                    </p>
                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="goToScheduleHistoryPage(scheduleHistoryPage - 1)"
                                            :disabled="scheduleHistoryPage <= 1"
                                            class="px-2.5 py-1.5 rounded-md text-xs font-semibold border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            Previous
                                        </button>
                                        <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 px-1">
                                            Page {{ scheduleHistoryPage }} of {{ totalScheduleHistoryPages }}
                                        </span>
                                        <button
                                            @click="goToScheduleHistoryPage(scheduleHistoryPage + 1)"
                                            :disabled="scheduleHistoryPage >= totalScheduleHistoryPages"
                                            class="px-2.5 py-1.5 rounded-md text-xs font-semibold border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            Next
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

               <!-- Energy Limit Modal -->
            
                <div v-if="showThresholdModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 transition-all overflow-y-auto">
                    <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-md w-full p-8 border border-cyan-400/50 dark:border-cyan-500/30 shadow-2xl scale-in-center">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-2 rounded-lg bg-cyan-50 dark:bg-cyan-900/40 text-cyan-600 dark:text-cyan-400">
                                <Settings class="w-6 h-6" />
                            </div>
                            <h3 class="text-2xl font-black text-gray-900 dark:text-gray-100 tracking-tight">Edit Energy Limit</h3>
                        </div>
                        <div class="mb-4">
                            <label class="block text-xs font-black text-gray-500 dark:text-gray-500 uppercase tracking-widest mb-2">Threshold Type</label>
                            <select v-model="dailyLimitForm.type"
                                class="w-full px-5 py-3.5 bg-gray-50 dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition-all text-gray-900 dark:text-gray-100 font-bold">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                        <div class="mb-6">
                            <label class="block text-xs font-black text-gray-500 dark:text-gray-500 uppercase tracking-widest mb-2">Limit Value (kWh)</label>
                              <input v-model.number="dailyLimitForm.kwh" type="number" min="0" step="0.001"
                               class="w-full px-5 py-3.5 bg-gray-50 dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition-all text-xl font-black text-gray-900 dark:text-gray-100 font-mono" />
                        </div>
                        <div v-if="thresholdMessage" class="mb-6 text-sm px-4 py-2.5 rounded-lg font-bold"
                            :class="thresholdMessage.includes('Saved') ? 'bg-cyan-50 dark:bg-cyan-900/30 text-cyan-600 dark:text-cyan-400' : 'bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400'">
                            {{ thresholdMessage }}
                        </div>
                        <div class="flex gap-4">
                            <button @click="showThresholdModal = false"
                                class="flex-1 px-6 py-3.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-all font-bold uppercase tracking-wider text-sm">
                                Cancel
                            </button>
                            <button @click="saveThresholds" :disabled="isSavingThreshold"
                                class="flex-1 px-6 py-3.5 bg-cyan-600 text-white rounded-xl hover:bg-cyan-500 transition-all font-bold uppercase tracking-wider text-sm shadow-lg shadow-cyan-500/20 disabled:opacity-50">
                                {{ isSavingThreshold ? 'Saving...' : 'Save Changes' }}
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="showEnergyExportModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 transition-all overflow-y-auto">
                    <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-md w-full p-8 border border-cyan-400/50 dark:border-cyan-500/30 shadow-2xl scale-in-center">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-2 rounded-lg bg-cyan-50 dark:bg-cyan-900/40 text-cyan-600 dark:text-cyan-400">
                                <Download class="w-6 h-6" />
                            </div>
                            <h3 class="text-2xl font-black text-gray-900 dark:text-gray-100 tracking-tight">Export Energy Data</h3>
                        </div>

                        <div class="space-y-4 mb-6">
                            <div>
                                <label class="block text-xs font-black text-gray-500 dark:text-gray-500 uppercase tracking-widest mb-2">Start Date</label>
                                <input
                                    v-model="energyExportForm.startDate"
                                    type="date"
                                    class="w-full px-5 py-3.5 bg-gray-50 dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition-all text-gray-900 dark:text-gray-100 font-bold"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-black text-gray-500 dark:text-gray-500 uppercase tracking-widest mb-2">End Date</label>
                                <input
                                    v-model="energyExportForm.endDate"
                                    type="date"
                                    class="w-full px-5 py-3.5 bg-gray-50 dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition-all text-gray-900 dark:text-gray-100 font-bold"
                                />
                            </div>
                        </div>

                        <div v-if="energyExportMessage" class="mb-6 text-sm px-4 py-2.5 rounded-lg font-bold"
                            :class="energyExportMessage.includes('successfully') ? 'bg-cyan-50 dark:bg-cyan-900/30 text-cyan-600 dark:text-cyan-400' : 'bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400'">
                            {{ energyExportMessage }}
                        </div>

                        <div class="flex gap-4">
                            <button @click="showEnergyExportModal = false"
                                class="flex-1 px-6 py-3.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-all font-bold uppercase tracking-wider text-sm">
                                Cancel
                            </button>
                            <button @click="exportEnergyByDate" :disabled="isExportingEnergy"
                                class="flex-1 px-6 py-3.5 bg-cyan-600 text-white rounded-xl hover:bg-cyan-500 transition-all font-bold uppercase tracking-wider text-sm shadow-lg shadow-cyan-500/20 disabled:opacity-50">
                                {{ isExportingEnergy ? 'Exporting...' : 'Export CSV' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ─── Schedule Add / Edit Modal ─── -->
                <div v-if="showScheduleModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[60] p-4 transition-all overflow-y-auto">
                    <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-lg w-full p-8 border border-cyan-400/50 dark:border-cyan-500/30 shadow-2xl transition-all">

                        <!-- Header -->
                        <div class="flex items-center justify-between mb-8">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-cyan-50 dark:bg-cyan-900/40 text-cyan-600 dark:text-cyan-400">
                                    <Calendar class="w-6 h-6" />
                                </div>
                                <h3 class="text-2xl font-black text-gray-900 dark:text-gray-100 tracking-tight">
                                    {{ editingScheduleId ? 'Update Schedule' : 'New Schedule' }}
                                </h3>
                            </div>
                            <button @click="showScheduleModal = false"
                                class="p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                <X class="w-6 h-6 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300" />
                            </button>
                        </div>

                        <div class="space-y-6">
                            <!-- Name -->
                            <div>
                                <label class="block text-xs font-black text-gray-500 dark:text-gray-500 uppercase tracking-widest mb-2">Schedule Name</label>
                                <input v-model="scheduleForm.name" type="text" placeholder="e.g. Daily Auto-off"
                                    class="w-full px-5 py-3.5 bg-gray-50 dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-cyan-500 outline-none transition-all text-gray-900 dark:text-gray-100 font-bold" />
                            </div>

                            <!-- Date -->
                            <div>
                                <label class="block text-xs font-black text-gray-500 dark:text-gray-500 uppercase tracking-widest mb-2">Effective Date</label>
                                <input v-model="scheduleForm.date" type="date"
                                    class="w-full px-5 py-3.5 bg-gray-50 dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-cyan-500 outline-none transition-all text-gray-900 dark:text-gray-100 font-bold font-mono" />
                            </div>

                            <div>
                                <label class="block text-xs font-black text-gray-500 dark:text-gray-500 uppercase tracking-widest mb-2">Days of Week (optional recurring)</label>
                                <div class="grid grid-cols-4 sm:grid-cols-7 gap-2">
                                    <label v-for="day in weekDayOptions" :key="day.value"
                                        class="flex items-center justify-center gap-1.5 px-2 py-2 rounded-lg border text-xs font-black uppercase tracking-widest cursor-pointer transition-colors"
                                        :class="scheduleForm.daysOfWeek.includes(day.value)
                                            ? 'bg-cyan-500 text-white border-cyan-500'
                                            : 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700'">
                                        <input
                                            v-model="scheduleForm.daysOfWeek"
                                            :value="day.value"
                                            type="checkbox"
                                            class="hidden"
                                        />
                                        <span>{{ day.label }}</span>
                                    </label>
                                </div>
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    Select one or more days to repeat this schedule every week. Leave empty for one-time scheduling.
                                </p>
                            </div>

                            <!-- Times -->
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-black text-gray-500 dark:text-gray-500 uppercase tracking-widest mb-2">Start (ON)</label>
                                    <input v-model="scheduleForm.startTime" type="time"
                                        class="w-full px-5 py-3.5 bg-gray-50 dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-cyan-500 outline-none transition-all text-gray-900 dark:text-gray-100 font-black font-mono text-lg" />
                                </div>
                                <div>
                                    <label class="block text-xs font-black text-gray-500 dark:text-gray-500 uppercase tracking-widest mb-2">End (OFF)</label>
                                    <input v-model="scheduleForm.endTime" type="time"
                                        class="w-full px-5 py-3.5 bg-gray-50 dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-cyan-500 outline-none transition-all text-gray-900 dark:text-gray-100 font-black font-mono text-lg" />
                                </div>
                            </div>

                            <!-- Message -->
                            <div v-if="scheduleMessage" class="text-sm px-4 py-3 rounded-xl font-bold transition-all"
                                :class="scheduleMessage.includes('Error') || scheduleMessage.includes('fill') || scheduleMessage.includes('Failed')
                                    ? 'bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-900/50'
                                    : 'bg-cyan-50 dark:bg-cyan-950/40 text-cyan-700 dark:text-cyan-400 border border-cyan-200 dark:border-cyan-900/50'">
                                {{ scheduleMessage }}
                            </div>

                            <!-- Buttons -->
                            <div class="flex gap-4 pt-2">
                                <button @click="showScheduleModal = false"
                                    class="flex-1 px-6 py-4 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-all font-black uppercase tracking-wider text-sm">
                                    Cancel
                                </button>
                                <button @click="saveSchedule" :disabled="isSavingSchedule"
                                    class="flex-1 px-6 py-4 bg-cyan-600 text-white rounded-xl hover:bg-cyan-500 transition-all font-black uppercase tracking-wider text-sm shadow-lg shadow-cyan-500/20 disabled:opacity-50">
                                    {{ isSavingSchedule ? 'Processing...' : (editingScheduleId ? 'Update Schedule' : 'Create Schedule') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delete Schedule Confirmation Modal -->
                <div v-if="showDeleteScheduleModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[70] p-4 transition-all">
                    <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-md w-full p-7 border border-red-300/60 dark:border-red-500/30 shadow-2xl">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 rounded-lg bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400">
                                <Trash2 class="w-5 h-5" />
                            </div>
                            <h3 class="text-xl font-black text-gray-900 dark:text-gray-100 tracking-tight">Delete Schedule</h3>
                        </div>

                        <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                            This will permanently delete
                            <span class="font-black text-gray-900 dark:text-gray-100">"{{ scheduleToDelete?.name || 'this schedule' }}"</span>.
                            This action cannot be undone.
                        </p>

                        <div class="mt-6 p-3 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-700">
                            <p class="text-[11px] uppercase tracking-widest font-black text-gray-500 dark:text-gray-400">Schedule Window</p>
                            <p class="text-sm font-bold text-gray-800 dark:text-gray-200 mt-1">
                                {{ scheduleToDelete ? formatScheduleDate(scheduleToDelete.start_time) : '—' }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ scheduleToDelete ? formatScheduleTime(scheduleToDelete.start_time) : '—' }} to {{ scheduleToDelete ? formatScheduleTime(scheduleToDelete.end_time) : '—' }}
                            </p>
                        </div>

                        <div class="flex gap-3 mt-7">
                            <button @click="cancelDeleteSchedule"
                                class="flex-1 px-5 py-3 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold uppercase tracking-wider text-sm hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                                Cancel
                            </button>
                            <button @click="deleteSchedule"
                                class="flex-1 px-5 py-3 rounded-xl bg-red-600 text-white font-bold uppercase tracking-wider text-sm hover:bg-red-500 transition-colors shadow-lg shadow-red-500/20">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>
