# WattWise ESP32 + PZEM-004T Setup Guide

Complete guide to connect your ESP32 with PZEM-004T energy monitor to WattWise.

## 📋 What You Need

### Hardware
- **ESP32 Development Board** (any variant with WiFi)
- **PZEM-004T** Energy Monitoring Module
- **Jumper Wires** (4 wires minimum)
- **USB Cable** (for programming ESP32)
- **5V Power Supply** (for PZEM-004T)

### Software
- **Arduino IDE** (version 1.8.x or 2.x) - [Download here](https://www.arduino.cc/en/software)
- **PZEM004Tv30 Library** by Jakub Mandula
- **ESP32 Board Support** in Arduino IDE

## 🔌 Hardware Wiring

Connect PZEM-004T to ESP32:

```
PZEM-004T          ESP32
─────────────────────────
VCC (5V)    ──────► 5V or VIN
GND         ──────► GND
TX          ──────► GPIO 16 (RX2)
RX          ──────► GPIO 17 (TX2)
```

### Wiring Diagram

```
     ┌─────────────────┐
     │    PZEM-004T    │
     │  Energy Monitor │
     └─────────────────┘
      │   │    │    │
      │   │    │    │
     VCC GND  TX   RX
      │   │    │    │
      │   │    │    └─────┐
      │   │    └──────┐   │
      │   └───┐       │   │
      └───┐   │       │   │
          │   │       │   │
     ┌────▼───▼───────▼───▼──┐
     │  5V GND  GPIO16 GPIO17 │
     │        ESP32            │
     │    Development Board    │
     └─────────────────────────┘
```

**⚠️ Important Notes:**
- PZEM-004T requires **5V power** (not 3.3V)
- The TX/RX are **not** 5V tolerant on all ESP32 boards
- Some boards may need level shifters for safety
- Double-check your connections before powering on

## 💻 Software Installation

### Step 1: Install Arduino IDE

1. Download and install [Arduino IDE](https://www.arduino.cc/en/software)
2. Open Arduino IDE

### Step 2: Add ESP32 Board Support

1. Open **File → Preferences**
2. In "Additional Board Manager URLs", add:
   ```
   https://raw.githubusercontent.com/espressif/arduino-esp32/gh-pages/package_esp32_index.json
   ```
3. Click **OK**
4. Go to **Tools → Board → Boards Manager**
5. Search for **"esp32"**
6. Install **"esp32 by Espressif Systems"**
7. Wait for installation to complete

### Step 3: Install PZEM Library

1. Go to **Sketch → Include Library → Manage Libraries**
2. Search for **"PZEM004Tv30"**
3. Install **"PZEM004Tv30 by Jakub Mandula"**
4. Close Library Manager

### Step 4: Configure the Sketch

1. Open `esp32_pzem004t.ino` in Arduino IDE
2. Edit these lines at the top of the file:

```cpp
// WiFi Credentials
const char* WIFI_SSID = "YOUR_WIFI_SSID";      // Your WiFi name
const char* WIFI_PASSWORD = "YOUR_WIFI_PASSWORD";  // Your WiFi password

// WattWise Server Configuration
const char* SERVER_URL = "http://192.168.1.100:8000/api/iot/energy";  // Your computer's IP
const char* DEVICE_ID = "ESP32_PZEM_001";      // Unique name for this device
```

#### How to Find Your Computer's IP Address:

**Windows:**
```cmd
ipconfig
```
Look for "IPv4 Address" under your active network adapter (e.g., `192.168.1.100`)

**Mac/Linux:**
```bash
ifconfig
```
or
```bash
ip addr show
```

**Update SERVER_URL:**
If your IP is `192.168.1.100` and Laravel runs on port `8000`:
```cpp
const char* SERVER_URL = "http://192.168.1.100:8000/api/iot/energy";
```

### Step 5: Upload to ESP32

1. Connect ESP32 to your computer via USB
2. In Arduino IDE:
   - **Tools → Board** → Select your ESP32 board (e.g., "ESP32 Dev Module")
   - **Tools → Port** → Select the COM port (e.g., COM3 on Windows, /dev/ttyUSB0 on Linux)
3. Click **Upload** button (→ arrow icon)
4. Wait for "Done uploading" message

### Step 6: Monitor Serial Output

1. Open **Tools → Serial Monitor**
2. Set baud rate to **115200**
3. You should see:
   ```
   ================================
   WattWise ESP32 Energy Monitor
   PZEM-004T Sensor Integration
   ================================
   
   Connecting to WiFi: YourWiFiName
   .....
   ✓ WiFi Connected!
   IP Address: 192.168.1.105
   
   --- Reading PZEM-004T Sensor ---
   Sensor Readings:
     Voltage:    220.50 V
     Current:    1.234 A
     Power:      272.05 W
     Energy:     0.0735 kWh
     Frequency:  50.00 Hz
     Power Factor: 0.85
   
   Sending to WattWise...
   ✓ Response Code: 201
   ✓ Data successfully sent to WattWise!
   ```

## 🖥️ Laravel Server Setup

### Step 1: Update .env File

On your Laravel WattWise project, ensure your `.env` has:

```env
# Allow connections from ESP32
# Your computer's IP address
APP_URL=http://192.168.1.100:8000

# Database connection (make sure it's set up)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wattwise
DB_USERNAME=root
DB_PASSWORD=your_password
```

### Step 2: Run Database Migrations

```bash
php artisan migrate
```

This creates the `energy_readings` table.

### Step 3: Start Laravel Server

**IMPORTANT: Use your local IP, not localhost!**

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Or specify your IP:
```bash
php artisan serve --host=192.168.1.100 --port=8000
```

The server should show:
```
INFO  Server running on [http://192.168.1.100:8000]
```

### Step 4: Test API Endpoint

Open: `http://192.168.1.100:8000/api/iot/energy` in your browser or Postman

You should see Laravel's API endpoint active.

## 🧪 Testing the Connection

### Method 1: Using Serial Monitor

1. Open Arduino IDE Serial Monitor (Tools → Serial Monitor)
2. Set baud to **115200**
3. Watch for sensor readings and server responses
4. Look for "✓ Data successfully sent to WattWise!"

### Method 2: Using Laravel Logs

In your Laravel project:
```bash
tail -f storage/logs/laravel.log
```

You should see entries like:
```
[2026-02-14 10:30:15] local.INFO: ESP32 Energy Reading Received
[2026-02-14 10:30:15] local.INFO: ESP32 Energy Reading Stored
```

### Method 3: Check Database

```bash
php artisan tinker
```

Then run:
```php
App\Models\EnergyReading::latest()->first();
```

You should see the latest reading from your ESP32.

## 🐛 Troubleshooting

### ESP32 Can't Connect to WiFi
- ✅ Double-check WIFI_SSID and WIFI_PASSWORD
- ✅ Make sure WiFi is 2.4GHz (ESP32 doesn't support 5GHz)
- ✅ Check if WiFi has MAC address filtering

### PZEM Sensor Not Reading
- ✅ Verify wiring: TX→GPIO16, RX→GPIO17
- ✅ Ensure PZEM has 5V power
- ✅ Check if appliance is plugged in and powered
- ✅ Try swapping TX/RX if still not working

### Data Not Reaching Laravel
- ✅ Ensure Laravel is running with `--host=0.0.0.0` or your IP
- ✅ Check firewall isn't blocking port 8000
- ✅ Verify ESP32 and computer are on same network
- ✅ Test URL in browser: `http://YOUR_IP:8000/api/iot/energy`
- ✅ Check SERVER_URL in ESP32 code matches Laravel host

### HTTP Error 500
- ✅ Run `php artisan migrate` to create database tables
- ✅ Check Laravel logs: `storage/logs/laravel.log`
- ✅ Verify database connection in `.env`

### HTTP Error 422 (Validation Error)
- ✅ Check Serial Monitor for validation errors
- ✅ Sensor data might be NaN or out of range
- ✅ Ensure PZEM is properly connected and reading

## 📊 Viewing Data in WattWise

1. Log in to WattWise dashboard
2. Navigate to Dashboard page
3. You should see live energy readings
4. Data updates every 5 seconds (configurable)

## 🔧 Advanced Configuration

### Change Data Send Interval

In `esp32_pzem004t.ino`:
```cpp
const unsigned long SEND_INTERVAL = 5000;  // milliseconds
// 5000 = 5 seconds
// 10000 = 10 seconds
// 60000 = 1 minute
```

### Multiple ESP32 Devices

Give each device a unique ID:
```cpp
const char* DEVICE_ID = "ESP32_LIVING_ROOM";
const char* DEVICE_ID = "ESP32_BEDROOM";
const char* DEVICE_ID = "ESP32_GARAGE";
```

### Use Different GPIO Pins

In the code, find:
```cpp
PZEM004Tv30 pzem(Serial2, 16, 17);
```

Change to your preferred pins:
```cpp
PZEM004Tv30 pzem(Serial2, YOUR_RX_PIN, YOUR_TX_PIN);
```

## 📁 Project Structure

```
iot_device/
└── esp32_pzem004t/
    ├── esp32_pzem004t.ino  (Main sketch)
    ├── config.h             (Configuration file)
    └── README.md            (This file)
```

## 🆘 Need Help?

Check these resources:
- ESP32 Documentation: https://docs.espressif.com/
- PZEM-004T Library: https://github.com/mandulaj/PZEM-004T-v30
- WattWise IOT_SETUP.md in project root

## ✅ Quick Checklist

Before asking for help, verify:

- [ ] ESP32 connects to WiFi (check Serial Monitor)
- [ ] PZEM sensor reads values (not NaN)
- [ ] Laravel server running on correct IP and port
- [ ] Firewall allows port 8000
- [ ] ESP32 and computer on same network
- [ ] Database migrated (`php artisan migrate`)
- [ ] SERVER_URL in ESP32 code is correct
- [ ] Serial Monitor shows "✓ Data successfully sent!"

---

**Last Updated:** February 14, 2026
