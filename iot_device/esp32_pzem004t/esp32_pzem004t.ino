/*
 * WattWise - ESP32 Energy Monitor with PZEM-004T
 * 
 * This sketch reads energy data from PZEM-004T sensor and sends it to WattWise Laravel backend.
 * 
 * Hardware Connections:
 * PZEM-004T -> ESP32
 * TX -> GPIO 16 (RX2)
 * RX -> GPIO 17 (TX2)
 * VCC -> 5V
 * GND -> GND
 * 
 * Libraries Required:
 * - WiFi (built-in)
 * - HTTPClient (built-in)
 * - PZEM004Tv30 by Jakub Mandula (Install via Library Manager)
 */

#include <WiFi.h>
#include <HTTPClient.h>
#include <PZEM004Tv30.h>

// ============================================================================
// CONFIGURATION - Edit these values
// ============================================================================

// WiFi Credentials
const char* WIFI_SSID = "YOUR_WIFI_SSID";
const char* WIFI_PASSWORD = "YOUR_WIFI_PASSWORD";

// WattWise Server Configuration
const char* SERVER_URL = "http://192.168.8.37:8000/api/iot/energy";  // Change to your Laravel server IP
const char* DEVICE_ID = "ESP32_PZEM_001";  // Unique identifier for this device

// Data sending interval (milliseconds)
const unsigned long SEND_INTERVAL = 5000;  // Send data every 5 seconds

// ============================================================================
// Hardware Configuration
// ============================================================================

// PZEM-004T on Serial2 (GPIO 16=RX, GPIO 17=TX)
PZEM004Tv30 pzem(Serial2, 16, 17);

// ============================================================================
// Global Variables
// ============================================================================

unsigned long lastSendTime = 0;
bool wifiConnected = false;

// ============================================================================
// Setup Function
// ============================================================================

void setup() {
  // Initialize Serial Monitor
  Serial.begin(115200);
  delay(1000);
  
  Serial.println("\n\n================================");
  Serial.println("WattWise ESP32 Energy Monitor");
  Serial.println("PZEM-004T Sensor Integration");
  Serial.println("================================\n");
  Serial.print("Device ID: ");
  Serial.println(DEVICE_ID);
  
  // Connect to WiFi
  connectToWiFi();
  
  // Initialize PZEM sensor
  Serial.println("\nInitializing PZEM-004T sensor...");
  Serial.println("Waiting for sensor data...\n");
  
  delay(1000);
}

// ============================================================================
// Main Loop
// ============================================================================

void loop() {
  // Check WiFi connection
  if (WiFi.status() != WL_CONNECTED) {
    wifiConnected = false;
    Serial.println("WiFi disconnected! Reconnecting...");
    connectToWiFi();
  } else {
    wifiConnected = true;
  }
  
  // Read and send data at specified intervals
  if (millis() - lastSendTime >= SEND_INTERVAL) {
    lastSendTime = millis();
    
    if (wifiConnected) {
      readAndSendData();
    } else {
      Serial.println("Skipping data send - WiFi not connected");
    }
  }
  
  delay(100);  // Small delay to prevent watchdog issues
}

// ============================================================================
// WiFi Connection Function
// ============================================================================

void connectToWiFi() {
  Serial.print("Connecting to WiFi: ");
  Serial.println(WIFI_SSID);
  
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 30) {
    delay(500);
    Serial.print(".");
    attempts++;
  }
  
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\n✓ WiFi Connected!");
    Serial.print("IP Address: ");
    Serial.println(WiFi.localIP());
    Serial.print("Signal Strength: ");
    Serial.print(WiFi.RSSI());
    Serial.println(" dBm");
    wifiConnected = true;
  } else {
    Serial.println("\n✗ WiFi Connection Failed!");
    wifiConnected = false;
  }
}

// ============================================================================
// Read Sensor and Send Data Function
// ============================================================================

void readAndSendData() {
  Serial.println("\n--- Reading PZEM-004T Sensor ---");
  
  // Read values from PZEM sensor
  float voltage = pzem.voltage();
  float current = pzem.current();
  float power = pzem.power();
  float energy = pzem.energy();
  float frequency = pzem.frequency();
  float pf = pzem.pf();
  
  // Check if reading was successful
  if (isnan(voltage)) {
    Serial.println("✗ Error reading PZEM sensor!");
    Serial.println("Check connections:");
    Serial.println("  - PZEM TX -> ESP32 GPIO 16");
    Serial.println("  - PZEM RX -> ESP32 GPIO 17");
    Serial.println("  - PZEM VCC -> 5V");
    Serial.println("  - PZEM GND -> GND");
    return;
  }
  
  // Display readings on Serial Monitor
  Serial.println("Sensor Readings:");
  Serial.printf("  Voltage:    %.2f V\n", voltage);
  Serial.printf("  Current:    %.3f A\n", current);
  Serial.printf("  Power:      %.2f W\n", power);
  Serial.printf("  Energy:     %.4f kWh\n", energy);
  Serial.printf("  Frequency:  %.2f Hz\n", frequency);
  Serial.printf("  Power Factor: %.2f\n", pf);
  
  // Create JSON payload
  String jsonPayload = createJSONPayload(voltage, current, power, energy, frequency, pf);
  
  // Send to WattWise server
  sendToServer(jsonPayload);
}

// ============================================================================
// Create JSON Payload
// ============================================================================

String createJSONPayload(float voltage, float current, float power, 
                         float energy, float frequency, float pf) {
  String json = "{";
  json += "\"voltage\":" + String(voltage, 2) + ",";
  json += "\"current\":" + String(current, 3) + ",";
  json += "\"power\":" + String(power, 2) + ",";
  json += "\"energy\":" + String(energy, 4) + ",";
  json += "\"frequency\":" + String(frequency, 2) + ",";
  json += "\"pf\":" + String(pf, 2) + ",";
  json += "\"device_id\":\"" + String(DEVICE_ID) + "\"";
  json += "}";
  return json;
}

// ============================================================================
// Send Data to WattWise Server
// ============================================================================

void sendToServer(String jsonPayload) {
  Serial.println("\nSending to WattWise...");
  Serial.print("URL: ");
  Serial.println(SERVER_URL);
  Serial.print("Payload: ");
  Serial.println(jsonPayload);
  
  HTTPClient http;
  http.begin(SERVER_URL);
  http.addHeader("Content-Type", "application/json");
  
  int httpResponseCode = http.POST(jsonPayload);
  
  if (httpResponseCode > 0) {
    String response = http.getString();
    Serial.print("✓ Response Code: ");
    Serial.println(httpResponseCode);
    Serial.print("Response: ");
    Serial.println(response);
    
    if (httpResponseCode == 201) {
      Serial.println("✓ Data successfully sent to WattWise!");
    }
  } else {
    Serial.print("✗ Error sending data: ");
    Serial.println(httpResponseCode);
    Serial.println("Possible issues:");
    Serial.println("  - Check if WattWise server is running");
    Serial.println("  - Verify SERVER_URL is correct");
    Serial.println("  - Ensure ESP32 and server are on same network");
  }
  
  http.end();
  Serial.println("--------------------------------");
}
