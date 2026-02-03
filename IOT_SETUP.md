# IoT Integration Setup Guide

This guide explains how to connect your WattWise dashboard to your IoT devices.

## Overview

The system uses a service-based architecture where:
- **Frontend (Vue.js)**: Fetches data from Laravel API endpoints
- **Laravel API**: Acts as a middleware between frontend and IoT devices
- **IoTService**: Handles communication with your IoT devices
- **IoT Devices**: Your physical sensors/relays that monitor and control appliances

## Configuration

### 1. Set IoT Device URL

Add the following to your `.env` file:

```env
IOT_BASE_URL=http://192.168.1.100:8080
IOT_TIMEOUT=5
```

Replace `http://192.168.1.100:8080` with your actual IoT device IP address and port.

### 2. IoT Device API Requirements

Your IoT device should expose the following REST API endpoints:

#### Get All Ports
```
GET /api/ports
Response: [
  {
    "id": 1,
    "name": "Port 1",
    "is_on": true,
    "power_watts": 1500,
    "cost_per_hour": 18.75,
    "today_kwh": 12.5,
    "status": "Healthy"
  },
  ...
]
```

#### Toggle Port
```
POST /api/ports/{portId}/toggle
Body: { "state": "on" | "off" }
Response: {
  "id": 1,
  "is_on": true,
  ...
}
```

#### Get Current Power
```
GET /api/metrics/power
Response: { "watts": 3650 }
```

#### Get Today's Usage
```
GET /api/metrics/today
Response: { "kwh": 23.0 }
```

#### Get Alerts
```
GET /api/alerts
Response: {
  "heading": "Maintenance Alerts",
  "subheading": "1 appliance needs attention",
  "alerts": [
    {
      "title": "Refrigerator",
      "message": "Refrigerator may need maintenance soon.",
      "badge_text": "Warning"
    }
  ]
}
```

#### Get Monthly Records
```
GET /api/records/monthly
Response: { "total_kwh": 112.5 }
```

#### Get Thresholds
```
GET /api/thresholds
Response: [
  { "id": 1, "name": "Power Limit", ... },
  ...
]
```

## API Endpoints (Laravel → Frontend)

The Laravel API exposes these endpoints for the frontend:

- `GET /api/dashboard/data` - Get all dashboard data
- `GET /api/ports` - Get all ports
- `POST /api/ports/{id}/toggle` - Toggle port ON/OFF
- `GET /api/ports/{id}/status` - Get specific port status
- `GET /api/metrics/current-power` - Get current power
- `GET /api/metrics/today-usage` - Get today's usage
- `GET /api/alerts` - Get alerts
- `GET /api/records/monthly` - Get monthly records
- `GET /api/thresholds` - Get thresholds

## How It Works

1. **Frontend (Dashboard.vue)**:
   - Fetches data from Laravel API every 5 seconds (polling)
   - Sends toggle requests when user clicks port switches
   - Displays real-time data from IoT devices

2. **Laravel API (DashboardController)**:
   - Receives requests from frontend
   - Calls IoTService to communicate with IoT devices
   - Returns formatted data to frontend

3. **IoTService**:
   - Makes HTTP requests to your IoT device
   - Handles errors gracefully (falls back to mock data if IoT unavailable)
   - Formats data for frontend consumption

## Customizing for Your IoT Device

If your IoT device uses different endpoints or response formats:

1. **Update `app/Services/IoTService.php`**:
   - Modify the `formatPort()` method to match your device's response format
   - Update endpoint URLs in `config/iot.php`
   - Adjust data mapping in each method

2. **Example Customization**:
   ```php
   // In IoTService.php, update formatPort() method
   protected function formatPort(array $data): array
   {
       return [
           'id' => $data['device_id'], // Your device uses 'device_id'
           'name' => $data['label'],   // Your device uses 'label'
           'is_on' => $data['relay_state'] === 1, // Your device uses 0/1
           // ... adjust other fields
       ];
   }
   ```

## Testing Without IoT Device

The system includes fallback mock data. If your IoT device is unavailable:
- The service will log warnings
- Mock data will be displayed
- The dashboard will still function (with static data)

## Real-Time Updates

The dashboard polls the API every 5 seconds for updates. To change the interval:

```javascript
// In Dashboard.vue
const POLL_INTERVAL = 5000; // Change to your desired interval (milliseconds)
```

## Troubleshooting

1. **Check IoT device is accessible**:
   ```bash
   curl http://YOUR_IOT_IP:PORT/api/ports
   ```

2. **Check Laravel logs**:
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. **Verify .env configuration**:
   ```bash
   php artisan config:clear
   ```

4. **Test API endpoints**:
   ```bash
   # After logging in, test the API
   curl -X GET http://localhost:8000/api/dashboard/data \
     -H "Cookie: your_session_cookie"
   ```

## Next Steps

1. Configure your IoT device URL in `.env`
2. Ensure your IoT device exposes the required API endpoints
3. Test the connection using the Laravel API endpoints
4. The dashboard will automatically start fetching real-time data

