# Firebase Real-time Backend Configuration

## Environment Variables

Add these variables to your `.env` file:

```env
# Firebase Realtime Database Configuration
FIREBASE_DATABASE_URL=https://wattwise-1d764-default-rtdb.firebaseio.com
FIREBASE_API_KEY=your-firebase-api-key-here

# Electricity Rate (PHP per kWh)
ELECTRICITY_RATE=12.5
```

## Firebase Database Structure (Optimized)

Your ESP32 devices communicate with Firebase using this optimized structure:

```
firebase-realtime-db/
├── Live/
│   ├── PLUG1/
│   │   ├── voltage: 220.5
│   │   ├── current: 5.2
│   │   ├── power: 1147.6
│   │   └── energy: 12.5
│   ├── PLUG2/
│   └── PLUG3/
├── Control/
│   ├── PLUG1: true (ON) or false (OFF)
│   ├── PLUG2: boolean
│   └── PLUG3: boolean
├── threshold/
│   ├── PLUG1: 1500 (watts)
│   ├── PLUG2: 2000
│   └── PLUG3: 3000
├── Status/
│   ├── PLUG1: "ON" or "OFF" (actual relay state)
│   └── PLUG2: "ON"
├── Energy/
│   ├── PLUG1/
│   │   ├── daily_kwh: 2.45
│   │   ├── weekly_kwh: 14.30
│   │   └── monthly_kwh: 58.75
│   └── PLUG2/
│       ├── daily_kwh: 1.80
│       ├── weekly_kwh: 10.20
│       └── monthly_kwh: 42.10
├── Uptime/
│   ├── online_since: 1771772060000
│   ├── uptime_seconds: 3600
│   ├── formatted: "1h 0m"
│   └── last_updated: 1771775660000
└── History/
    ├── PLUG1/
    │   └── [timestamp]: { "power": 1100 }
    └── PLUG2/
```

## API Endpoints

### Real-time Device Control

**Toggle Device ON/OFF**
```http
POST /api/ports/{deviceId}/toggle
Content-Type: application/json

{
  "state": true
}
```
This writes to `Control/PLUG{deviceId}` in Firebase, and the ESP32 reads it instantly via a Stream Listener.

### Threshold Management

**Set Threshold**
```http
POST /api/devices/{deviceId}/threshold
Content-Type: application/json

{
  "threshold": 1500
}
```
This writes to `threshold/PLUG{deviceId}` in Firebase. The ESP32 reads this and automatically turns off the relay when power exceeds this value.

### Live Data

**Get All Ports/Devices**
```http
GET /api/ports
```
Returns real-time data from Firebase `Live/` path.

## How Real-time Works

1. **ESP32 → Firebase (Push)**: Your ESP32 pushes live sensor data to Firebase every 1 second using asynchronous batch updates to the `Live/` path.
2. **Laravel Backend → Firebase (Write)**: When you toggle a switch or set a threshold, the backend writes to `Control/` or `threshold/`.
3. **Firebase → ESP32 (Stream)**: The ESP32 uses a persistent **Stream Listener**. It receives changes to `Control/` or `threshold/` in under 200ms without polling.
4. **Safety Logic**: The ESP32 performs threshold checks locally. If power > threshold, it turns off the relay immediately and updates the `Status/` node.
