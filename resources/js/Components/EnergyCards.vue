<!-- 
// Polling interval (5 seconds for real-time updates)
let pollingInterval = null;
const POLL_INTERVAL = 10000;

// Format value with unit
const formatValue = (value, unit) => {
    if (typeof value === 'number') {
        return `${value}${unit}`;
    }
    return value;
};

// Format port data from API
const formatPort = (port) => {
    return {
        id: port.id,
        name: port.name || `Port ${port.id}`,
        isOn: port.is_on ?? false,
        power: formatValue(port.power, 'W'),
        metricLabel: 'Cost/Hour',
        metricValue: `₱${(port.cost_per_hour || 0).toFixed(2)}`,
        todayKwh: formatValue(port.today_kwh, ' kWh'),
        status: port.status || 'Healthy',
        statusTone: port.status_tone || 'healthy',
    };
};

// Fetch dashboard data
const fetchDashboardData = async () => {
    try {
        loading.value = true;
        error.value = null;

        const response = await axios.get('/api/dashboard/data');

        if (response.data) {
            const data = response.data;

            // Update summary cards
            if (data.summary) {
                summaryCards.value[0].value = formatValue(data.summary.current_power?.value || 0, 'W');
                summaryCards.value[1].value = formatValue(data.summary.record?.value || 0, ' kWh');
                summaryCards.value[2].value = formatValue(data.summary.today_usage?.value || 0, ' kWh');
                summaryCards.value[3].value = String(data.summary.thresholds?.count || 0);
            }

            // Update ports
            if (data.ports && Array.isArray(data.ports)) {
                ports.value = data.ports.map(formatPort);
            }

            // Update alerts
            if (data.alerts) {
                const alerts = data.alerts.alerts || [];
                if (alerts.length > 0) {
                    const firstAlert = alerts[0];
                    alertData.value = {
                        heading: data.alerts.heading || 'Maintenance Alerts',
                        subheading: data.alerts.subheading || `${alerts.length} appliance(s) need attention`,
                        title: firstAlert.title || '',
                        message: firstAlert.message || '',
                        badgeText: firstAlert.badge_text || 'Warning',
                    };
                } else {
                    alertData.value = {
                        heading: 'Maintenance Alerts',
                        subheading: 'No alerts',
                        title: '',
                        message: '',
                        badgeText: '',
                    };
                }
            }
        }
    } catch (err) {
        console.error('Failed to fetch dashboard data:', err);
        error.value = 'Failed to load dashboard data. Please check your IoT connection.';
        // Keep mock data visible on error
    } finally {
        loading.value = false;
    }
};


// Toggle port ON/OFF
const togglePort = async (portId, currentState) => {
    try {
        const newState = !currentState;
        
        // Optimistic update
        const port = ports.value.find(p => p.id === portId);
        if (port) {
            port.isOn = newState;
        }

        const response = await axios.post(`/api/ports/${portId}/toggle`, {
            state: newState,
        });

        if (response.data && response.data.port) {
            // Update with server response
            const index = ports.value.findIndex(p => p.id === portId);
            if (index !== -1) {
                ports.value[index] = formatPort(response.data.port);
            }
        }
    } catch (err) {
        console.error('Failed to toggle port:', err);
        // Revert optimistic update
        const port = ports.value.find(p => p.id === portId);
        if (port) {
            port.isOn = currentState;
        }
        alert('Failed to toggle port. Please try again.');
    }
};

// Start polling for real-time updates
const startPolling = () => {
    pollingInterval = setInterval(() => {
        fetchDashboardData();
    }, POLL_INTERVAL);
};

// Stop polling
const stopPolling = () => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
};

// Lifecycle hooks
onMounted(() => {
    fetchDashboardData();
    startPolling();
});

onUnmounted(() => {
    stopPolling();
}); -->