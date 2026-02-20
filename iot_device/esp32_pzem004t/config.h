/*
 * WattWise ESP32 Configuration File
 * 
 * Copy this file and modify the values according to your setup.
 * This file allows you to change settings without modifying the main code.
 */

#ifndef CONFIG_H
#define CONFIG_H

// ============================================================================
// WiFi Configuration
// ============================================================================

// Your WiFi network name (SSID)
#define WIFI_SSID "YOUR_WIFI_SSID"

// Your WiFi password
#define WIFI_PASSWORD "YOUR_WIFI_PASSWORD"

// ============================================================================
// WattWise Server Configuration
// ============================================================================

// IP address of your WattWise Laravel server
// Find this by running 'ipconfig' (Windows) or 'ifconfig' (Mac/Linux)
// Example: "http://192.168.1.100:8000/api/iot/energy"
#define SERVER_URL "http://192.168.1.100:8000/api/iot/energy"

// Optional: Change server port if you're using a different port
// Default Laravel development server uses port 8000
#define SERVER_PORT 8000

// ============================================================================
// Device Configuration
// ============================================================================

// Unique identifier for this ESP32 device
// If you have multiple devices, give each a unique ID
// Examples: "ESP32_LIVING_ROOM", "ESP32_KITCHEN", "ESP32_001"
#define DEVICE_ID "ESP32_PZEM_001"

// How often to send data to server (in milliseconds)
// 5000 = 5 seconds, 10000 = 10 seconds, 60000 = 1 minute
#define SEND_INTERVAL 5000

// ============================================================================
// PZEM-004T Hardware Configuration
// ============================================================================

// ESP32 GPIO pins for PZEM-004T connection
// Default: RX GPIO 16, TX GPIO 17 (Serial2)
// Change these if you wire differently
#define PZEM_RX_PIN 16
#define PZEM_TX_PIN 17

// ============================================================================
// Debug Configuration
// ============================================================================

// Enable/disable serial debug output
// Set to 'true' for debugging, 'false' for production
#define DEBUG_MODE true

// Serial baud rate for debug output
#define SERIAL_BAUD 115200

#endif // CONFIG_H
