<script setup>
import { computed, onMounted, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import axios from 'axios'
import VueApexCharts from 'vue3-apexcharts'
import { Download, FileText, RefreshCw, TrendingDown, TrendingUp, WalletCards } from 'lucide-vue-next'
import Sidebar from '@/Components/Sidebar.vue'

const timezone = 'Asia/Manila'
const selectedMonth = ref(new Intl.DateTimeFormat('en-CA', { timeZone: timezone, year: 'numeric', month: '2-digit' }).format(new Date()))
const history = ref([])
const selected = ref(null)
const loading = ref(false)
const errorMessage = ref('')
const exporting = ref(false)
const billConfiguration = ref({ ratePerKwh: 0 })
const savingBillConfiguration = ref(false)
const billConfigurationMessage = ref('')

const money = (value) => `₱${Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
const kwh = (value) => `${Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} kWh`
const monthLabel = (value) => {
    if (!value) return 'Monthly Bill History'
    const [year, month] = value.split('-').map(Number)
    return new Intl.DateTimeFormat('en-PH', { month: 'long', year: 'numeric', timeZone: timezone }).format(new Date(Date.UTC(year, month - 1, 1)))
}

const fetchHistory = async () => {
    loading.value = true
    errorMessage.value = ''
    try {
        const response = await axios.get('/api/monthly-bill-history', { params: { month: selectedMonth.value } })
        selected.value = response.data?.data || null
        history.value = Array.isArray(response.data?.history) ? response.data.history : []
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Unable to load monthly bill history.'
    } finally {
        loading.value = false
    }
}

const fetchBillConfiguration = async () => {
    try {
        const bills = await Promise.all([1, 2].map((deviceId) => axios.get(`/api/devices/${deviceId}/monthly-bill`)))
        bills.forEach((response) => {
            const bill = response.data?.data
            if (!bill) return
            const rate = Number(bill.rate_per_kwh || 0)
            if (rate > 0) billConfiguration.value.ratePerKwh = rate
        })
    } catch (error) {
        billConfigurationMessage.value = 'Unable to load billing configuration.'
    }
}

const saveBillConfiguration = async () => {
    savingBillConfiguration.value = true
    billConfigurationMessage.value = ''
    try {
        await Promise.all([1, 2].map((deviceId) => axios.post(`/api/devices/${deviceId}/monthly-bill`, {
            baseline_kwh: Number(selected.value?.total_kwh || 0),
            rate_per_kwh: Number(billConfiguration.value.ratePerKwh || 0),
        })))
        billConfigurationMessage.value = 'Billing rates saved. The estimated bill has been refreshed.'
        await fetchHistory()
    } catch (error) {
        billConfigurationMessage.value = error.response?.data?.message || 'Unable to save billing configuration.'
    } finally {
        savingBillConfiguration.value = false
    }
}

const monthOptions = computed(() => {
    const options = new Set([selectedMonth.value, ...history.value.map((item) => item.month_key)])
    return [...options].sort().reverse()
})

const dailyRecords = computed(() => selected.value?.daily_records || [])
const previousMonthChange = computed(() => selected.value?.change_percent)
const changeIsPositive = computed(() => Number(previousMonthChange.value) >= 0)
const plug1Contribution = computed(() => selected.value?.total_kwh ? (selected.value.plug1_kwh / selected.value.total_kwh) * 100 : 0)
const plug2Contribution = computed(() => selected.value?.total_kwh ? (selected.value.plug2_kwh / selected.value.total_kwh) * 100 : 0)

const chartSeries = computed(() => [
    { name: 'Total kWh', data: dailyRecords.value.map((row) => Number(row.total_kwh || 0)) },
    { name: 'Plug 1', data: dailyRecords.value.map((row) => Number(row.plug1_kwh || 0)) },
    { name: 'Plug 2', data: dailyRecords.value.map((row) => Number(row.plug2_kwh || 0)) },
])

const chartOptions = computed(() => ({
    chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
    colors: ['#0891b2', '#2563eb', '#10b981'],
    stroke: { curve: 'smooth', width: 2 },
    dataLabels: { enabled: false },
    grid: { borderColor: '#334155', strokeDashArray: 4 },
    xaxis: { categories: dailyRecords.value.map((row) => row.date), labels: { style: { colors: '#94a3b8' } } },
    yaxis: { labels: { style: { colors: '#94a3b8' }, formatter: (value) => Number(value).toFixed(2) } },
    legend: { labels: { colors: '#94a3b8' } },
    tooltip: { theme: 'dark', y: { formatter: (value) => `${Number(value).toFixed(3)} kWh` } },
}))

const downloadCsv = async () => {
    exporting.value = true
    try {
        const response = await axios.get('/api/monthly-bill-history/export.csv', {
            params: { month: selectedMonth.value },
            responseType: 'blob',
        })
        const url = URL.createObjectURL(new Blob([response.data], { type: 'text/csv;charset=utf-8;' }))
        const link = document.createElement('a')
        link.href = url
        link.download = `monthly_bill_history_${selectedMonth.value}.csv`
        link.click()
        URL.revokeObjectURL(url)
    } finally {
        exporting.value = false
    }
}

const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character]))

const printPdf = () => {
    if (!selected.value) return
    const rows = dailyRecords.value.map((row) => `<tr><td>${escapeHtml(row.date)}</td><td>${Number(row.plug1_kwh || 0).toFixed(3)}</td><td>${Number(row.plug2_kwh || 0).toFixed(3)}</td><td>${Number(row.total_kwh || 0).toFixed(3)}</td></tr>`).join('')
    const printWindow = window.open('', '_blank', 'width=900,height=700')
    if (!printWindow) return
    printWindow.document.write(`<!doctype html><html><head><title>Estimated Monthly Bill - ${escapeHtml(monthLabel(selectedMonth.value))}</title><style>body{font:14px Arial,sans-serif;color:#172033;padding:32px}h1{margin:0 0 4px}p{color:#64748b}.summary{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:24px 0}.box{border:1px solid #cbd5e1;border-radius:8px;padding:14px}.label{font-size:11px;text-transform:uppercase;color:#64748b}.value{font-size:20px;font-weight:700;margin-top:8px}table{width:100%;border-collapse:collapse;margin-top:24px}th,td{text-align:left;border-bottom:1px solid #e2e8f0;padding:9px}th{background:#f1f5f9}</style></head><body><h1>Estimated Monthly Bill</h1><p>${escapeHtml(monthLabel(selectedMonth.value))} | Device ID: ${escapeHtml(selected.value.device_id)} | Philippine Time</p><div class="summary"><div class="box"><div class="label">Estimated Monthly Bill</div><div class="value">${money(selected.value.estimated_cost)}</div></div><div class="box"><div class="label">Total Consumption</div><div class="value">${kwh(selected.value.total_kwh)}</div></div><div class="box"><div class="label">Electricity Rate</div><div class="value">${money(selected.value.rate_per_kwh)}/kWh</div></div></div><p>This is an estimate based on measured energy consumption and the configured electricity rate. It is not an official electricity bill.</p><table><thead><tr><th>Date</th><th>Plug 1 kWh</th><th>Plug 2 kWh</th><th>Total kWh</th></tr></thead><tbody>${rows}</tbody></table><script>window.onload=()=>{window.print();window.onafterprint=()=>window.close()}<\/script></body></html>`)
    printWindow.document.close()
}

onMounted(async () => {
    await fetchBillConfiguration()
    await fetchHistory()
})
</script>

<template>
    <Head title="Monthly Bill History" />
    <div class="flex min-h-screen bg-slate-50 dark:bg-gray-950">
        <Sidebar />
        <main class="flex-1 px-4 pb-8 pt-6 transition-[margin] duration-300 sm:px-6 lg:px-8" style="margin-left: var(--sidebar-width, 4rem);">
            <div class="mx-auto max-w-7xl">
                <header class="mb-6 flex flex-col gap-4 rounded-xl border border-slate-200 bg-white px-6 py-5 shadow-sm dark:border-slate-800 dark:bg-slate-950 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="mb-1 text-[11px] font-semibold uppercase tracking-wider text-cyan-600 dark:text-cyan-400">Energy cost history</p>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-gray-100">Monthly Bill History</h1>
                        <p class="mt-1 max-w-2xl text-sm text-slate-500 dark:text-gray-400">Estimated costs based on measured energy consumption and your configured electricity rate. This is not an official electricity bill.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <label for="billing-month" class="sr-only">Select month</label>
                        <select id="billing-month" v-model="selectedMonth" @change="fetchHistory" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                            <option v-for="month in monthOptions" :key="month" :value="month">{{ monthLabel(month) }}</option>
                        </select>
                        <button type="button" title="Refresh" @click="fetchHistory" class="rounded-lg border border-slate-300 p-2 text-slate-500 hover:border-cyan-500 hover:text-cyan-600 dark:border-slate-700 dark:text-slate-400 dark:hover:text-cyan-400">
                            <RefreshCw class="h-4 w-4" :class="loading ? 'animate-spin' : ''" />
                        </button>
                    </div>
                </header>

                <div v-if="errorMessage" class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300">{{ errorMessage }}</div>
                <div v-if="loading && !selected" class="rounded-xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500 dark:border-slate-800 dark:bg-slate-950">Loading monthly history...</div>

                <template v-if="selected">
                    <section class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <h2 class="text-lg font-bold text-slate-900 dark:text-gray-100">Bill Configuration</h2>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Energy consumption is collected automatically from Plug 1 and Plug 2.</p>
                            </div>
                            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">User-provided rate</span>
                        </div>
                        <div class="mt-5 grid gap-4 md:grid-cols-2">
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/60">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Total Energy Consumption</p>
                                <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-gray-100">{{ kwh(selected.total_kwh) }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Plug 1 {{ kwh(selected.plug1_kwh) }} + Plug 2 {{ kwh(selected.plug2_kwh) }}</p>
                            </div>
                            <label class="block rounded-lg border border-slate-200 p-4 dark:border-slate-800"><span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Electricity Rate (₱/kWh)</span><input v-model.number="billConfiguration.ratePerKwh" type="number" min="0" step="0.0001" placeholder="12.50" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-semibold text-slate-800 outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200" /></label>
                        </div>
                        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><p class="text-xs text-slate-500 dark:text-slate-400">Estimated Bill = Total Energy Consumption × Electricity Rate. Consumption is based on actual stored readings, not a user target.</p><button type="button" @click="saveBillConfiguration" :disabled="savingBillConfiguration" class="inline-flex items-center justify-center rounded-lg bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyan-700 disabled:cursor-not-allowed disabled:opacity-60">{{ savingBillConfiguration ? 'Saving...' : 'Save Electricity Rate' }}</button></div>
                        <p v-if="billConfigurationMessage" class="mt-3 text-sm font-semibold" :class="billConfigurationMessage.includes('saved') ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">{{ billConfigurationMessage }}</p>
                    </section>

                    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <div v-for="card in [
                            { label: 'Estimated Monthly Bill', value: money(selected.estimated_cost), icon: WalletCards },
                            { label: 'Total Monthly Consumption', value: kwh(selected.total_kwh), icon: TrendingUp },
                            { label: 'Current ₱/kWh Rate', value: `${money(selected.rate_per_kwh)}/kWh`, icon: FileText },
                            { label: 'Change vs Previous Month', value: previousMonthChange === null ? 'N/A' : `${changeIsPositive ? '+' : ''}${Number(previousMonthChange).toFixed(2)}%`, icon: changeIsPositive ? TrendingUp : TrendingDown },
                        ]" :key="card.label" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                            <div class="flex items-start justify-between gap-3"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ card.label }}</p><component :is="card.icon" class="h-5 w-5 text-cyan-600 dark:text-cyan-400" /></div>
                            <p class="mt-3 text-2xl font-bold text-slate-900 dark:text-gray-100">{{ card.value }}</p>
                        </div>
                    </div>

                    <section class="mb-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
                        <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                            <h2 class="text-lg font-bold text-slate-900 dark:text-gray-100">Monthly History</h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Select a month to inspect its daily records and plug breakdown.</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-left text-sm">
                                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-900 dark:text-slate-400">
                                    <tr><th class="px-5 py-3">Month</th><th class="px-5 py-3">Total kWh</th><th class="px-5 py-3">Rate</th><th class="px-5 py-3">Estimated Monthly Bill</th><th class="px-5 py-3">Change</th><th class="px-5 py-3"></th></tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="item in history" :key="item.month_key" class="text-slate-700 dark:text-slate-300">
                                        <td class="px-5 py-3 font-semibold">{{ monthLabel(item.month_key) }}</td>
                                        <td class="px-5 py-3">{{ kwh(item.total_kwh) }}</td>
                                        <td class="px-5 py-3">{{ money(item.rate_per_kwh) }}/kWh</td>
                                        <td class="px-5 py-3 font-semibold">{{ money(item.estimated_cost) }}</td>
                                        <td class="px-5 py-3" :class="item.change_percent > 0 ? 'text-red-600 dark:text-red-400' : item.change_percent < 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500'">{{ item.change_percent === null ? 'N/A' : `${item.change_percent > 0 ? '+' : ''}${Number(item.change_percent).toFixed(2)}%` }}</td>
                                        <td class="px-5 py-3 text-right"><button type="button" class="font-semibold text-cyan-600 hover:text-cyan-700 dark:text-cyan-400" @click="selectedMonth = item.month_key; fetchHistory">View</button></td>
                                    </tr>
                                    <tr v-if="history.length === 0"><td colspan="6" class="px-5 py-8 text-center text-slate-500">No monthly summaries have been generated yet.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <div class="mb-6 grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(300px,1fr)]">
                        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                            <div class="mb-4"><h2 class="text-lg font-bold text-slate-900 dark:text-gray-100">Daily Energy Consumption</h2><p class="text-sm text-slate-500 dark:text-slate-400">{{ monthLabel(selectedMonth) }} | Philippine Time</p></div>
                            <VueApexCharts type="line" height="320" :options="chartOptions" :series="chartSeries" />
                        </section>

                        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                            <h2 class="text-lg font-bold text-slate-900 dark:text-gray-100">Plug Breakdown</h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Consumption and estimated cost by plug</p>
                            <div class="mt-5 space-y-4">
                                <div v-for="plug in [{ label: 'Plug 1', kwh: selected.plug1_kwh, contribution: plug1Contribution }, { label: 'Plug 2', kwh: selected.plug2_kwh, contribution: plug2Contribution }]" :key="plug.label" class="rounded-lg border border-slate-200 p-4 dark:border-slate-800">
                                    <div class="flex items-center justify-between"><span class="font-semibold text-slate-800 dark:text-slate-200">{{ plug.label }}</span><span class="text-sm font-bold text-cyan-600 dark:text-cyan-400">{{ Number(plug.contribution).toFixed(1) }}%</span></div>
                                    <div class="mt-3 flex items-end justify-between"><span class="text-xl font-bold text-slate-900 dark:text-gray-100">{{ kwh(plug.kwh) }}</span><span class="text-sm font-semibold text-slate-600 dark:text-slate-300">{{ money(Number(plug.kwh) * Number(selected.rate_per_kwh)) }}</span></div>
                                    <div class="mt-3 h-2 rounded-full bg-slate-100 dark:bg-slate-800"><div class="h-2 rounded-full bg-cyan-500" :style="{ width: `${Math.min(100, plug.contribution)}%` }"></div></div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <section class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
                        <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="text-lg font-bold text-slate-900 dark:text-gray-100">Monthly Details</h2><p class="text-sm text-slate-500 dark:text-slate-400">{{ monthLabel(selectedMonth) }}</p></div><div class="flex gap-2"><button type="button" @click="downloadCsv" :disabled="exporting" class="inline-flex items-center gap-2 rounded-lg bg-cyan-600 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-700 disabled:opacity-60"><Download class="h-4 w-4" /> Export CSV</button><button type="button" @click="printPdf" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:border-cyan-500 hover:text-cyan-600 dark:border-slate-700 dark:text-slate-200"><FileText class="h-4 w-4" /> Export PDF</button></div></div>
                        <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-900 dark:text-slate-400"><tr><th class="px-5 py-3">Date</th><th class="px-5 py-3">Plug 1 kWh</th><th class="px-5 py-3">Plug 2 kWh</th><th class="px-5 py-3">Total kWh</th></tr></thead><tbody class="divide-y divide-slate-100 dark:divide-slate-800"><tr v-for="row in dailyRecords" :key="row.date" class="text-slate-700 dark:text-slate-300"><td class="px-5 py-3 font-medium">{{ row.date }}</td><td class="px-5 py-3">{{ Number(row.plug1_kwh || 0).toFixed(3) }}</td><td class="px-5 py-3">{{ Number(row.plug2_kwh || 0).toFixed(3) }}</td><td class="px-5 py-3 font-semibold">{{ Number(row.total_kwh || 0).toFixed(3) }}</td></tr><tr v-if="dailyRecords.length === 0"><td colspan="4" class="px-5 py-8 text-center text-slate-500">No stored daily energy records for this month.</td></tr></tbody></table></div>
                    </section>
                </template>
            </div>
        </main>
    </div>
</template>
