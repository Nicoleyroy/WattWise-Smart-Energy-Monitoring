// import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

// const DEVICE_CONFIG = [
//     { id: 1, key: 'PLUG1', fallbackName: 'Device 1', color: '#0891b2' },
//     { id: 2, key: 'PLUG2', fallbackName: 'Device 2', color: '#f97316' },
// ];

// const FILTER_CONFIG = {
//     hourly: { points: 24, unit: 'hour' },
//     daily: { points: 7, unit: 'day' },
//     weekly: { points: 4, unit: 'week' },
//     monthly: { points: 12, unit: 'month' },
// };

// const toSeconds = (timestamp) => {
//     const value = Number(timestamp);
//     if (!Number.isFinite(value)) return null;

//     return value > 9999999999 ? Math.floor(value / 1000) : Math.floor(value);
// };

// const startOfHour = (date) => {
//     const bucket = new Date(date);
//     bucket.setMinutes(0, 0, 0);
//     return bucket;
// };

// const startOfDay = (date) => {
//     const bucket = new Date(date);
//     bucket.setHours(0, 0, 0, 0);
//     return bucket;
// };

// const startOfWeek = (date) => {
//     const bucket = startOfDay(date);
//     const day = bucket.getDay();
//     const diffToMonday = day === 0 ? 6 : day - 1;
//     bucket.setDate(bucket.getDate() - diffToMonday);
//     return bucket;
// };

// const startOfMonth = (date) => {
//     const bucket = startOfDay(date);
//     bucket.setDate(1);
//     return bucket;
// };

// const moveBucket = (date, unit, amount) => {
//     const moved = new Date(date);

//     if (unit === 'hour') moved.setHours(moved.getHours() + amount);
//     if (unit === 'day') moved.setDate(moved.getDate() + amount);
//     if (unit === 'week') moved.setDate(moved.getDate() + amount * 7);
//     if (unit === 'month') moved.setMonth(moved.getMonth() + amount);

//     return moved;
// };

// const getBucketStart = (date, unit) => {
//     if (unit === 'hour') return startOfHour(date);
//     if (unit === 'day') return startOfDay(date);
//     if (unit === 'week') return startOfWeek(date);

//     return startOfMonth(date);
// };

// const buildBuckets = (filter) => {
//     const config = FILTER_CONFIG[filter] || FILTER_CONFIG.hourly;
//     const currentBucket = getBucketStart(new Date(), config.unit);
//     const firstBucket = moveBucket(currentBucket, config.unit, -(config.points - 1));

//     return Array.from({ length: config.points }, (_, index) => {
//         const bucketDate = moveBucket(firstBucket, config.unit, index);
//         return {
//             x: bucketDate.getTime(),
//             y: null,
//             sum: 0,
//             count: 0,
//         };
//     });
// };

// const extractReading = (entry, deviceKey, fallbackTimestamp) => {
//     const payload = entry?.[deviceKey];
//     if (!payload || payload.power === undefined || payload.power === null) return null;

//     const timestamp = toSeconds(payload.timestamp ?? entry.timestamp ?? fallbackTimestamp);
//     const power = Number(payload.power);

//     if (!Number.isFinite(timestamp) || !Number.isFinite(power)) return null;

//     return {
//         timestamp,
//         power,
//     };
// };

// const aggregateAveragePower = (historyData, deviceKey, filter) => {
//     const config = FILTER_CONFIG[filter] || FILTER_CONFIG.hourly;
//     const buckets = buildBuckets(filter);
//     const firstBucketMs = buckets[0]?.x ?? 0;
//     const lastBucketMs = moveBucket(new Date(buckets[buckets.length - 1]?.x ?? Date.now()), config.unit, 1).getTime();

//     Object.entries(historyData || {}).forEach(([key, entry]) => {
//         const reading = extractReading(entry, deviceKey, key);
//         if (!reading) return;

//         const readingMs = reading.timestamp * 1000;
//         if (readingMs < firstBucketMs || readingMs >= lastBucketMs) return;

//         const bucketStart = getBucketStart(new Date(readingMs), config.unit).getTime();
//         const bucket = buckets.find((candidate) => candidate.x === bucketStart);
//         if (!bucket) return;

//         bucket.sum += reading.power;
//         bucket.count += 1;
//     });

//     return buckets.map((bucket) => ({
//         x: bucket.x,
//         y: bucket.count > 0 ? Number((bucket.sum / bucket.count).toFixed(1)) : 0,
//     }));
// };

// export function useEnergyData(selectedFilter) {
//     const historyData = ref({});
//     const liveData = ref({});
//     const energyData = ref({});
//     const thresholds = ref({});
//     const loading = ref(true);
//     const deviceNames = ref(
//         DEVICE_CONFIG.reduce((names, device) => {
//             names[device.key] = localStorage.getItem(`device_${device.id}_name`) || device.fallbackName;
//             return names;
//         }, {}),
//     );

//     let historyCallback = null;
//     let liveCallback = null;
//     let energyCallback = null;
//     let thresholdCallback = null;

//     const refreshDeviceNames = () => {
//         DEVICE_CONFIG.forEach((device) => {
//             deviceNames.value[device.key] = localStorage.getItem(`device_${device.id}_name`) || device.fallbackName;
//         });
//     };

//     const currentUsage = computed(() => {
//         return DEVICE_CONFIG.reduce((total, device) => {
//             const power = Number(liveData.value?.[device.key]?.power || 0);
//             return total + power;
//         }, 0);
//     });

//     const totalConsumption = computed(() => {
//         return DEVICE_CONFIG.reduce((total, device) => {
//             const dailyKwh = Number(energyData.value?.[device.key]?.daily_kwh || 0);
//             return total + dailyKwh;
//         }, 0);
//     });

//     const powerLimit = computed(() => {
//         const activeThresholds = DEVICE_CONFIG.map((device) => Number(thresholds.value?.[device.key] || 0)).filter(
//             (value) => value > 0,
//         );

//         return activeThresholds.length > 0 ? Math.min(...activeThresholds) : 0;
//     });

//     const chartSeries = computed(() => {
//         return DEVICE_CONFIG.map((device) => ({
//             name: deviceNames.value[device.key],
//             color: device.color,
//             data: aggregateAveragePower(historyData.value, device.key, selectedFilter.value),
//         }));
//     });

//     const spikes = computed(() => {
//         if (powerLimit.value <= 0) return [];

//         return chartSeries.value.flatMap((device) =>
//             device.data
//                 .filter((point) => Number(point.y) > powerLimit.value)
//                 .map((point) => ({
//                     ...point,
//                     deviceName: device.name,
//                 })),
//         );
//     });

//     const subscribeToHistory = () => {
//         if (!window.db) {
//             loading.value = false;
//             return;
//         }

//         if (historyCallback) {
//             window.db.ref('History').off('value', historyCallback);
//             historyCallback = null;
//         }

//         const firstBucket = buildBuckets(selectedFilter.value)[0];
//         const startAtSeconds = Math.floor((firstBucket?.x || Date.now()) / 1000);
//         loading.value = true;

//         historyCallback = (snapshot) => {
//             historyData.value = snapshot.val() || {};
//             loading.value = false;
//         };

//         window.db.ref('History').orderByKey().startAt(startAtSeconds.toString()).on('value', historyCallback);
//     };

//     onMounted(() => {
//         if (!window.db) {
//             loading.value = false;
//             return;
//         }

//         subscribeToHistory();

//         liveCallback = (snapshot) => {
//             liveData.value = snapshot.val() || {};
//         };

//         energyCallback = (snapshot) => {
//             energyData.value = snapshot.val() || {};
//         };

//         thresholdCallback = (snapshot) => {
//             thresholds.value = snapshot.val() || {};
//         };

//         window.db.ref('Live').on('value', liveCallback);
//         window.db.ref('Energy').on('value', energyCallback);
//         window.db.ref('threshold').on('value', thresholdCallback);
//         window.addEventListener('storage', refreshDeviceNames);
//         window.addEventListener('focus', refreshDeviceNames);
//     });

//     watch(selectedFilter, subscribeToHistory);

//     onUnmounted(() => {
//         if (window.db) {
//             if (historyCallback) window.db.ref('History').off('value', historyCallback);
//             if (liveCallback) window.db.ref('Live').off('value', liveCallback);
//             if (energyCallback) window.db.ref('Energy').off('value', energyCallback);
//             if (thresholdCallback) window.db.ref('threshold').off('value', thresholdCallback);
//         }

//         window.removeEventListener('storage', refreshDeviceNames);
//         window.removeEventListener('focus', refreshDeviceNames);
//     });

//     return {
//         chartSeries,
//         currentUsage,
//         totalConsumption,
//         powerLimit,
//         spikes,
//         loading,
//     };
// }