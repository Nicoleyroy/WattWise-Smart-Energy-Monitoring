<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import { useTheme } from '@/Composables/useTheme';

// ─── 24H HOURLY UTILS ─────────────────────────────────────────────────────────

const getCurrentHourStartTs = () => {
    const d = new Date();
    d.setMinutes(0, 0, 0);
    return Math.floor(d.getTime() / 1000);
};

const getLast24HoursStartTs = () => getCurrentHourStartTs() - 23 * 3600;



const getLastHourStartTs = () => Math.floor(Date.now() / 1000) - 3600;

const buildOneHourSeries = (historyData, deviceKey) => {
    const startTs = getLastHourStartTs();
    const nowTs = Math.floor(Date.now() / 1000);
    const data = collectPowerSamples(historyData, deviceKey)
        .filter((sample) => sample.timestamp >= startTs && sample.timestamp <= nowTs)
        .map((sample) => ({
            x: sample.timestamp * 1000,
            y: parseFloat(sample.power.toFixed(1)),
        }));

    const livePower = Number(liveData.value?.[deviceKey]?.power);
    if (Number.isFinite(livePower)) {
        data.push({
            x: Date.now(),
            y: parseFloat(livePower.toFixed(1)),
        });
    }

    return data.sort((a, b) => a.x - b.x);
};

// Build a static array of 24 data points (one per hour, 00:00–23:00 today).
// Each point starts with y = null (no data yet).
const buildHourlyScaffold = () => {
const firstHour = getLast24HoursStartTs();
  return Array.from({ length: 24 }, (_, h) => ({
  x: (firstHour + h * 3600) * 1000,
    y: null,
    _sum: 0,
    _count: 0
  }));
};



const toSeconds = (timestamp) => {
  const numericTimestamp = Number(timestamp);
  if (!Number.isFinite(numericTimestamp)) return null;

  return numericTimestamp > 9999999999
    ? Math.floor(numericTimestamp / 1000)
    : Math.floor(numericTimestamp);
};

const collectPowerSamples = (historyData, deviceKey) => {
  if (Array.isArray(historyData?.[deviceKey])) {
    return historyData[deviceKey];
  }

  const samples = [];

  if (!historyData) return samples;

  // Current ESP32 shape: /History/PLUG1/{timestamp}/power
  const deviceHistory = historyData[deviceKey];
  if (deviceHistory && typeof deviceHistory === 'object') {
    Object.entries(deviceHistory).forEach(([timestamp, reading]) => {
      const tsNum = toSeconds(timestamp);
      const power = Number(reading?.power);

      if (tsNum !== null && Number.isFinite(power)) {
        samples.push({ timestamp: tsNum, power });
      }
    });
  }

  // Older simulator/backend shape: /History/{timestamp}/PLUG1/power
  Object.entries(historyData).forEach(([timestamp, entry]) => {
    const tsNum = toSeconds(timestamp);
    const power = Number(entry?.[deviceKey]?.power);

    if (tsNum !== null && Number.isFinite(power)) {
      samples.push({ timestamp: tsNum, power });
    }
  });

  return samples.sort((a, b) => a.timestamp - b.timestamp);
};

const addLiveSample = (scaffold, deviceKey, getIndex) => {
  const power = Number(liveData.value?.[deviceKey]?.power);
  if (!Number.isFinite(power)) return;

  const index = getIndex(Math.floor(Date.now() / 1000));
  if (index < 0 || index >= scaffold.length) return;

  scaffold[index]._sum += power;
  scaffold[index]._count++;
};

// Given a raw history snapshot, bucket every data point into the correct hour
// slot and return the 24-point series for ApexCharts.
const bucketHistoryInto24Hours = (historyData, deviceKey) => {
  const scaffold = buildHourlyScaffold();
  const firstHour = getLast24HoursStartTs();

  collectPowerSamples(historyData, deviceKey).forEach(({ timestamp, power }) => {
    const hourIndex = Math.floor((timestamp - firstHour) / 3600);
    if (hourIndex < 0 || hourIndex > 23) return;

    scaffold[hourIndex]._sum += power;
    scaffold[hourIndex]._count++;
  });

  addLiveSample(scaffold, deviceKey, (timestamp) => Math.floor((timestamp - firstHour) / 3600));

  scaffold.forEach((slot) => {
    if (slot._count > 0) {
      slot.y = parseFloat((slot._sum / slot._count).toFixed(1));
    } else {
      slot.y = 0;
    }
    delete slot._sum;
    delete slot._count;
  });

  return scaffold;
};

// ─── 7D DAILY UTILS ───────────────────────────────────────────────────────────
// Returns midnight (seconds) for a day that is `daysAgo` days before today.
const getDayMidnightTs = (daysAgo = 0) => {
  const d = new Date();
  d.setHours(0, 0, 0, 0);
  d.setDate(d.getDate() - daysAgo);
  return Math.floor(d.getTime() / 1000);
};

// Build a static array of 7 data points (one per day: 6 days ago → today).
const buildDailyScaffold = () => {
  return Array.from({ length: 7 }, (_, i) => ({
    x: getDayMidnightTs(6 - i) * 1000,
    y: null,
    _sum: 0,
    _count: 0
  }));
};

// Bucket all history readings into 7 daily slots (average power per day).
const bucketHistoryInto7Days = (historyData, deviceKey) => {
  const scaffold = buildDailyScaffold();
  const oldestMidnight = getDayMidnightTs(6);

  collectPowerSamples(historyData, deviceKey).forEach(({ timestamp, power }) => {
    const dayIndex = Math.floor((timestamp - oldestMidnight) / 86400);
    if (dayIndex < 0 || dayIndex > 6) return;

    scaffold[dayIndex]._sum += power;
    scaffold[dayIndex]._count++;
  });
  
  addLiveSample(scaffold, deviceKey, (timestamp) => Math.floor((timestamp - oldestMidnight) / 86400));

  scaffold.forEach(slot => {
    if (slot._count > 0) {
      slot.y = parseFloat((slot._sum / slot._count).toFixed(1));
    } else {
      slot.y = 0;
    }
    delete slot._sum;
    delete slot._count;
  });

  return scaffold;
};

// ─── 1M WEEKLY UTILS ──────────────────────────────────────────────────────────
// Returns midnight of the Monday at the start of the week `weeksAgo` weeks before this week.
const getWeekStartTs = (weeksAgo = 0) => {
  const d = new Date();
  d.setHours(0, 0, 0, 0);
  const day = d.getDay();
  const diffToMonday = (day === 0 ? 6 : day - 1);
  d.setDate(d.getDate() - diffToMonday - weeksAgo * 7);
  return Math.floor(d.getTime() / 1000);
};

// Build 4 static slots (one per week: 3 weeks ago → this week).
const buildWeeklyScaffold = () => {
  return Array.from({ length: 4 }, (_, i) => ({
    x: getWeekStartTs(3 - i) * 1000,
    y: null,
    _sum: 0,
    _count: 0
  }));
};

// Bucket history readings into 4 weekly slots (average power per week).
const bucketHistoryInto4Weeks = (historyData, deviceKey) => {
  const scaffold = buildWeeklyScaffold();
  const oldestWeekStart = getWeekStartTs(3);
  const WEEK_SECONDS = 7 * 86400;

  collectPowerSamples(historyData, deviceKey).forEach(({ timestamp, power }) => {
    const weekIndex = Math.floor((timestamp - oldestWeekStart) / WEEK_SECONDS);
    if (weekIndex < 0 || weekIndex > 3) return;

    scaffold[weekIndex]._sum += power;
    scaffold[weekIndex]._count++;
  });

  addLiveSample(scaffold, deviceKey, (timestamp) => Math.floor((timestamp - oldestWeekStart) / WEEK_SECONDS));


  scaffold.forEach(slot => {
    if (slot._count > 0) {
      slot.y = parseFloat((slot._sum / slot._count).toFixed(1));
    } else {
      slot.y = 0;
    }
    delete slot._sum;
    delete slot._count;
  });

  return scaffold;
};

const { isDark } = useTheme();

const props = defineProps({
  plugId: {
    type: Number,
    default: null
  },
  height: {
    type: [Number, String],
    default: 300
  }
});

const allPlugs = ref([
  { id: 1, name: 'Plug 1', seriesData: [], color: '#0891b2', stats: { max: 0, min: 0, avg: 0 }, peakX: null },
  { id: 2, name: 'Plug 2', seriesData: [], color: '#10b981', stats: { max: 0, min: 0, avg: 0 }, peakX: null }
]);

const displayedPlugs = computed(() => {
  return props.plugId ? allPlugs.value.filter(p => p.id === props.plugId) : allPlugs.value;
});

const timeFilter = ref('24h');
const loading = ref(false);
const liveData = ref({});
const latestHistoryData = ref({});

const getStartTimestamp = () => {
    const now = Math.floor(Date.now() / 1000);
    switch(timeFilter.value) {
        case '7d': return now - (7 * 24 * 3600);
        case '1m': return now - (30 * 24 * 3600);
        case '24h':
        default:
            return now - (24 * 3600);
    }
};

let activeCallback = null;

let liveCallback = null;
let dbRetryTimer = null;
let refreshFrame = null;
let parsedHistorySamples = [];

const refreshSeries = () => {
  const historyData = parsedHistorySamples;
    const is24h = timeFilter.value === '24h';
    const is1h = timeFilter.value === '1h';
    const is7d  = timeFilter.value === '7d';
    const is1m  = timeFilter.value === '1m';

    
    const plugsToRefresh = props.plugId
      ? allPlugs.value.filter((plug) => plug.id === props.plugId)
      : allPlugs.value;

    plugsToRefresh.forEach(plug => {
        const deviceKey = `PLUG${plug.id}`;
        const seriesData = is24h ? bucketHistoryInto24Hours(historyData, deviceKey)
                         : is1h ? buildOneHourSeries(historyData, deviceKey)
                         : is7d  ? bucketHistoryInto7Days(historyData, deviceKey)
                         : is1m  ? bucketHistoryInto4Weeks(historyData, deviceKey)
                         : [];

        plug.seriesData = seriesData;

        const values = seriesData.map(d => d.y).filter(v => v !== null);
        if (values.length > 0) {
            const max = Math.max(...values);
            const min = Math.min(...values);
            const avg = values.reduce((a, b) => a + b, 0) / values.length;
            const peakPoint = seriesData.find(d => d.y === max);
            plug.peakX = peakPoint ? peakPoint.x : null;
            plug.stats = {
                max: max.toFixed(0),
                min: min.toFixed(0),
                avg: avg.toFixed(0)
            };
        } else {
            plug.peakX = null;
            plug.stats = { max: 0, min: 0, avg: 0 };
        }
    });
};

const scheduleSeriesRefresh = () => {
  if (refreshFrame !== null) return;

  refreshFrame = window.requestAnimationFrame(() => {
    refreshFrame = null;
    refreshSeries();
  });
};

const subscribeToHistory = () => {
    if (!window.db) return;

  const deviceKey = `PLUG${props.plugId}`;
  const historyRef = window.db.ref('History').child(deviceKey);

    if (activeCallback) {
    historyRef.off('value', activeCallback);
        activeCallback = null;
    }

    const is24h = timeFilter.value === '24h';
    const is1h = timeFilter.value === '1h';
    const is7d  = timeFilter.value === '7d';
    const is1m  = timeFilter.value === '1m';

    const startAt = is24h
            ? getLast24HoursStartTs()
            : is1h
            ? getLastHourStartTs()
            : is7d
            ? getDayMidnightTs(6)
            : is1m
            ? getWeekStartTs(3)
            : getStartTimestamp();

    loading.value = true;

    activeCallback = (snapshot) => {
        loading.value = false;
        latestHistoryData.value = { [deviceKey]: snapshot.val() || {} };
      parsedHistorySamples = {
        [deviceKey]: collectPowerSamples(latestHistoryData.value, deviceKey),
      };
      scheduleSeriesRefresh();
    };
    historyRef.orderByKey().startAt(startAt.toString()).on('value', activeCallback);
};

const setTimeFilter = (filter) => {
    timeFilter.value = filter;
    subscribeToHistory();
};


const startFirebaseListeners = () => {
    if (!window.db) {
        dbRetryTimer = setTimeout(startFirebaseListeners, 250);
        return;
    }

    subscribeToHistory();

    const deviceKey = `PLUG${props.plugId}`;
    const liveRef = window.db.ref('Live').child(deviceKey);

    if (liveCallback) {
      liveRef.off('value', liveCallback);
    }

    liveCallback = (snapshot) => {
        liveData.value = { [deviceKey]: snapshot.val() || {} };
      scheduleSeriesRefresh();
    };

    liveRef.on('value', liveCallback);
};

onMounted(() => {
    allPlugs.value.forEach(plug => {
        const savedName = localStorage.getItem(`device_${plug.id}_name`);
        if (savedName) plug.name = savedName;
    });


    startFirebaseListeners();
});

onUnmounted(() => {

  if (refreshFrame !== null) {
    window.cancelAnimationFrame(refreshFrame);
    refreshFrame = null;
  }

    if (dbRetryTimer) {
        clearTimeout(dbRetryTimer);
        dbRetryTimer = null;
    }

    if (window.db && activeCallback) {
      window.db.ref('History').child(`PLUG${props.plugId}`).off('value', activeCallback);
        activeCallback = null;
    }

     if (window.db && liveCallback) {
        window.db.ref('Live').child(`PLUG${props.plugId}`).off('value', liveCallback);
        liveCallback = null;
    }
});

const getChartOptions = (plug) => {
  const is24h = timeFilter.value === '24h';
  const is1h = timeFilter.value === '1h';
  const is7d  = timeFilter.value === '7d';
  const is1m  = timeFilter.value === '1m';
  
  const options = {
    chart: { 
      id: `energy-${plug.id}`, 
      toolbar: {
        show: true,
        tools: {
          download: false,
          selection: false,
          zoom: false,
          zoomin: true,
          zoomout: true,
          pan: false,
          reset: true
        },
        offsetY: -4,
        autoSelected: 'zoom'
      }, 
      background: 'transparent',
      animations: {
        enabled: false
      },
      dropShadow: { enabled: false }
    },
    theme: { mode: isDark.value ? 'dark' : 'light' },
    colors: [plug.color],
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 3 },
    fill: { 
      type: 'gradient', 
      gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [0, 90, 100] } 
    },
    grid: {
      borderColor: isDark.value ? '#334155' : '#e2e8f0',
      strokeDashArray: 4,
      xaxis: { lines: { show: false } },
      yaxis: { lines: { show: true } },
      padding: { top: 10, right: 0, bottom: 0, left: 10 }
    },
    xaxis: {
      type: 'datetime',
      ...(is24h ? {
          min: getLast24HoursStartTs() * 1000,
          max: (getCurrentHourStartTs() + 3600 - 1) * 1000,
                             
        tickAmount: 24,
      } : {}),
      ...(is1h ? {
        min: getLastHourStartTs() * 1000,
        max: Date.now(),
        tickAmount: 6,
      } : {}),
      ...(is7d ? {
        min: getDayMidnightTs(6) * 1000,
        max: (getDayMidnightTs(0) + 86400 - 1) * 1000,
        tickAmount: 7,
      } : {}),
      ...(is1m ? {
        min: getWeekStartTs(3) * 1000,
        max: (getWeekStartTs(0) + 7 * 86400 - 1) * 1000,
        tickAmount: 4,
      } : {}),
      labels: {
        datetimeUTC: false,
        format: is1h || is24h ? 'HH:mm' : 'MMM dd',
        rotate: 0,
        style: { colors: isDark.value ? '#94a3b8' : '#64748b', fontSize: '11px', fontFamily: 'inherit' }
      },
      axisBorder: { show: false },
      axisTicks: { show: false },
      tooltip: { enabled: false }
    },
    yaxis: { 
      title: { 
        text: 'Power Consumption (Watts)',
        style: { color: isDark.value ? '#94a3b8' : '#64748b', fontWeight: 600, fontSize: '11px', fontFamily: 'inherit' },
        offsetX: -5
      },
      labels: { 
        formatter: (val) => val != null ? val.toFixed(0) : '',
        style: { colors: isDark.value ? '#94a3b8' : '#64748b', fontSize: '11px', fontFamily: 'inherit' },
        offsetX: -10
      }
    },
    tooltip: {
      theme: isDark.value ? 'dark' : 'light',
      y: {
        formatter: (val) => {
          if (val === null || val === undefined) return "No Data / OFF";
          return val.toFixed(0) + " W";
        }
      },
      x: { format: is24h ? 'HH:mm' : 'MMM dd' },
      style: { fontSize: '12px', fontFamily: 'inherit' }
    }
  };

  if (plug.peakX !== null && plug.stats.max > 0) {
    options.annotations = {
      points: [{
        x: plug.peakX,
        y: parseFloat(plug.stats.max),
        marker: { size: 5, fillColor: '#ffffff', strokeColor: plug.color, strokeWidth: 2, radius: 2 },
        label: {
          text: 'Peak',
          style: { background: plug.color, color: '#ffffff', fontSize: '10px', padding: { left: 4, right: 4, top: 2, bottom: 2 } }
        }
      }]
    };
  }

  return options;
};
</script>

<template>
  <div class="energy-graph w-full flex flex-col items-stretch pt-2">
    <div class="flex justify-end items-center mb-6 px-1">
      <div class="inline-flex bg-slate-100/80 dark:bg-slate-800/80 shadow-inner rounded-xl p-1 border border-slate-200/50 dark:border-slate-700/50">
        <button 
          @click="setTimeFilter('1h')" 
          :class="{'bg-white dark:bg-slate-700 shadow-sm text-indigo-600 dark:text-indigo-400': timeFilter === '1h', 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-slate-50/50 dark:hover:bg-slate-700/30': timeFilter !== '1h'}" 
          class="px-5 py-2 text-xs font-bold rounded-lg transition-all duration-300 ease-out"
        >1 HOUR</button>
        <button 
          @click="setTimeFilter('24h')" 
          :class="{'bg-white dark:bg-slate-700 shadow-sm text-indigo-600 dark:text-indigo-400': timeFilter === '24h', 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-slate-50/50 dark:hover:bg-slate-700/30': timeFilter !== '24h'}" 
          class="px-5 py-2 text-xs font-bold rounded-lg transition-all duration-300 ease-out"
        >24H</button>
       
        <button 
          @click="setTimeFilter('7d')" 
          :class="{'bg-white dark:bg-slate-700 shadow-sm text-indigo-600 dark:text-indigo-400': timeFilter === '7d', 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-slate-50/50 dark:hover:bg-slate-700/30': timeFilter !== '7d'}" 
          class="px-5 py-2 text-xs font-bold rounded-lg transition-all duration-300 ease-out"
        >7 DAYS</button>
        <button 
          @click="setTimeFilter('1m')" 
          :class="{'bg-white dark:bg-slate-700 shadow-sm text-indigo-600 dark:text-indigo-400': timeFilter === '1m', 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-slate-50/50 dark:hover:bg-slate-700/30': timeFilter !== '1m'}" 
          class="px-5 py-2 text-xs font-bold rounded-lg transition-all duration-300 ease-out"
        >1 MONTH</button>
      </div>
    </div>

    <div class="w-full relative min-h-[300px]">
      <div v-if="loading" class="absolute inset-0 flex items-center justify-center z-10 bg-white/40 dark:bg-gray-900/40 backdrop-blur-[2px] rounded-xl transition-all duration-300">
        <div class="animate-spin w-8 h-8 rounded-full border-4 border-indigo-500/30 border-t-indigo-500"></div>
      </div>
      
      <div v-for="plug in displayedPlugs" :key="plug.id" class="w-full transition-opacity duration-500 relative mb-4" :class="{'opacity-40': loading}">
        <div class="flex items-center gap-6 px-4 mb-2">
          <div class="flex flex-col">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Highest</span>
            <span class="text-base font-bold text-slate-800 dark:text-slate-100">{{ plug.stats.max }}<span class="text-xs text-slate-500 ml-0.5">W</span></span>
          </div>
          <div class="flex flex-col">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Lowest</span>
            <span class="text-base font-bold text-slate-800 dark:text-slate-100">{{ plug.stats.min }}<span class="text-xs text-slate-500 ml-0.5">W</span></span>
          </div>
          <div class="flex flex-col">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Average</span>
            <span class="text-base font-bold text-slate-800 dark:text-slate-100">{{ plug.stats.avg }}<span class="text-xs text-slate-500 ml-0.5">W</span></span>
          </div>
        </div>

        <VueApexCharts
          type="area"
          :height="height"
          :series="[{ name: plug.name, data: plug.seriesData }]"
          :options="getChartOptions(plug)"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
.energy-graph {
  contain: layout paint style;
}
</style>