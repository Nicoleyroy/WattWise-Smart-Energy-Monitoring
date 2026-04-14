#include <PZEM004Tv30.h>
#include <WiFi.h>
#include <HardwareSerial.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <Firebase_ESP_Client.h>
#include <time.h>

// Firebase helpers
#include "addons/TokenHelper.h"
#include "addons/RTDBHelper.h"

// ================= WIFI =================
const char* ssid = "wattwise";
const char* password = "12345678";

// ================= FIREBASE =================
#define API_KEY "AIzaSyC7MPieor9OrzeAXgkMOAPmPJSlcdZ4ow0"
#define DATABASE_URL "https://wattwise-1d764-default-rtdb.firebaseio.com/"

// Firebase objects
FirebaseData fbdo;
FirebaseData fbdo_stream;
FirebaseAuth auth;
FirebaseConfig config;

// ================= SERIAL PORTS =================
HardwareSerial SerialPZEM1(1);
HardwareSerial SerialPZEM2(2);

// ================= PZEM =================
PZEM004Tv30 pzem1(SerialPZEM1, 16, 17);
PZEM004Tv30 pzem2(SerialPZEM2, 18, 19);

// ================= OLED =================
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, -1);

// ================= RELAYS =================
#define RELAY1 5
#define RELAY2 6

// ================= STATE VARIABLES (CACHED) =================
float threshold1 = 1000.0, threshold2 = 1000.0;
bool remote1 = true, remote2 = true;

unsigned long lastTelemetry = 0;
const int telemetryInterval = 1000; 

unsigned long lastHistory = 0;
const int historyInterval = 60000; 

// ==========================================================================
//  ENERGY ACCUMULATION (kWh) — NEW SECTION
//  -----------------------------------------------------------------------
//  How it works:
//    - Every loop iteration we measure how many milliseconds have elapsed
//      since the last accumulation tick (using millis(), non-blocking).
//    - We convert that to hours:  hours = elapsedMs / 3,600,000
//    - Energy in kWh:  kWh += (power_W * hours) / 1000
//    - This is equivalent to:  kWh += power_W * elapsedSeconds / 3,600,000
//    - We keep three running totals per plug: daily, weekly, monthly.
//    - At calendar boundaries (midnight, Monday, 1st of month) we reset
//      the corresponding accumulator to zero using NTP time.
// ==========================================================================

// --- Per-plug energy accumulators (kWh) ---
float dailyKwh1  = 0.0, dailyKwh2  = 0.0;   // resets at midnight
float weeklyKwh1 = 0.0, weeklyKwh2 = 0.0;   // resets every Monday at midnight
float monthlyKwh1 = 0.0, monthlyKwh2 = 0.0; // resets on the 1st of each month

// --- Timing for accumulation ---
unsigned long lastAccumMillis = 0;  // last time we ran the accumulation

// --- Calendar tracking for resets (stores last-seen day-of-year) ---
int lastDayOfYear = -1;   // -1 means "not initialised yet"

// --- Timing for energy sync to Firebase (every 5 seconds) ---
unsigned long lastEnergySync = 0;
const int energySyncInterval = 5000;  // sync energy values every 5 s

// ================= STREAM CALLBACK (INSTANT UPDATES) =================
void streamCallback(FirebaseStream data) {
  String path = data.dataPath();
  
  if (path == "/Control/PLUG1") remote1 = data.boolData();
  else if (path == "/Control/PLUG2") remote2 = data.boolData();
  else if (path == "/threshold/PLUG1") threshold1 = data.floatData();
  else if (path == "/threshold/PLUG2") threshold2 = data.floatData();
}

void streamTimeoutCallback(bool timeout) {
  if (timeout) Serial.println("Stream timeout, resuming...");
}

// ================= PZEM HELPER =================
float safeRead(float value) {
  return isnan(value) ? 0.0 : value;
}

// ==========================================================================
//  accumulateEnergy() — called every loop iteration
//  Adds the energy consumed since the last call to daily/weekly/monthly totals.
//  Uses millis() so it is completely non-blocking.
// ==========================================================================
void accumulateEnergy(float power1, float power2) {
  unsigned long now = millis();

  // First call after boot — just seed the timer, don't accumulate
  if (lastAccumMillis == 0) {
    lastAccumMillis = now;
    return;
  }

  // Calculate elapsed time in hours
  //   elapsedMs  → milliseconds since last tick
  //   elapsedHrs → same value converted to hours
  unsigned long elapsedMs = now - lastAccumMillis;
  lastAccumMillis = now;

  // Guard: if elapsed is unreasonably large (>10 s) clamp it.
  // This protects against millis() rollover or long WiFi reconnect stalls.
  if (elapsedMs > 10000) elapsedMs = 10000;

  float elapsedHrs = elapsedMs / 3600000.0;  // ms → hours

  // Energy (kWh) = Power (W) × Time (h) / 1000
  //   ─ dividing by 1000 converts Wh to kWh
  float kwhIncrement1 = (power1 * elapsedHrs) / 1000.0;
  float kwhIncrement2 = (power2 * elapsedHrs) / 1000.0;

  // Add to all three accumulators for each plug
  dailyKwh1   += kwhIncrement1;
  dailyKwh2   += kwhIncrement2;
  weeklyKwh1  += kwhIncrement1;
  weeklyKwh2  += kwhIncrement2;
  monthlyKwh1 += kwhIncrement1;
  monthlyKwh2 += kwhIncrement2;
}

// ==========================================================================
//  checkEnergyResets() — called every loop iteration
//  Uses NTP calendar time to reset accumulators at natural boundaries:
//    Daily  → midnight  (when tm_yday changes)
//    Weekly → Monday at midnight  (tm_wday == 1 AND day changed)
//    Monthly→ 1st of month at midnight  (tm_mday == 1 AND day changed)
// ==========================================================================
void checkEnergyResets() {
  time_t now = time(nullptr);

  // NTP may not be ready yet (returns epoch < year-2020 timestamp)
  if (now < 1577836800) return;  // 2020-01-01 00:00:00 UTC

  struct tm timeinfo;
  localtime_r(&now, &timeinfo);

  int currentDayOfYear = timeinfo.tm_yday;  // 0-365

  // First valid NTP reading — seed the tracker, no reset
  if (lastDayOfYear == -1) {
    lastDayOfYear = currentDayOfYear;
    return;
  }

  // Day hasn't changed — nothing to reset
  if (currentDayOfYear == lastDayOfYear) return;

  // ── A new day has begun (midnight crossed) ──

  // ➊ DAILY RESET — always reset when the day changes
  Serial.printf("[Energy] Daily reset — PLUG1: %.4f kWh, PLUG2: %.4f kWh\n",
                dailyKwh1, dailyKwh2);
  dailyKwh1 = 0.0;
  dailyKwh2 = 0.0;

  // ➋ WEEKLY RESET — if today is Monday (tm_wday == 1)
  if (timeinfo.tm_wday == 1) {
    Serial.printf("[Energy] Weekly reset — PLUG1: %.4f kWh, PLUG2: %.4f kWh\n",
                  weeklyKwh1, weeklyKwh2);
    weeklyKwh1 = 0.0;
    weeklyKwh2 = 0.0;
  }

  // ➌ MONTHLY RESET — if today is the 1st of the month (tm_mday == 1)
  if (timeinfo.tm_mday == 1) {
    Serial.printf("[Energy] Monthly reset — PLUG1: %.4f kWh, PLUG2: %.4f kWh\n",
                  monthlyKwh1, monthlyKwh2);
    monthlyKwh1 = 0.0;
    monthlyKwh2 = 0.0;
  }

  // Update the day tracker
  lastDayOfYear = currentDayOfYear;
}

// ==========================================================================
//  syncEnergyToFirebase() — pushes energy accumulators to Firebase
//  Writes to /Energy/PLUG1/ and /Energy/PLUG2/ every energySyncInterval ms.
//  Uses async call to avoid blocking the main loop.
// ==========================================================================
void syncEnergyToFirebase() {
  if (!Firebase.ready()) return;
  if (millis() - lastEnergySync < energySyncInterval) return;

  lastEnergySync = millis();

  FirebaseJson energyJson;

  // PLUG1 energy values
  energyJson.set("PLUG1/daily_kwh",   roundTo4(dailyKwh1));
  energyJson.set("PLUG1/weekly_kwh",  roundTo4(weeklyKwh1));
  energyJson.set("PLUG1/monthly_kwh", roundTo4(monthlyKwh1));

  // PLUG2 energy values
  energyJson.set("PLUG2/daily_kwh",   roundTo4(dailyKwh2));
  energyJson.set("PLUG2/weekly_kwh",  roundTo4(weeklyKwh2));
  energyJson.set("PLUG2/monthly_kwh", roundTo4(monthlyKwh2));

  // Async write — non-blocking
  Firebase.RTDB.updateNodeAsync(&fbdo, "/Energy", &energyJson);
}

// ==========================================================================
//  roundTo4() — utility to round a float to 4 decimal places
//  Keeps Firebase values clean without excessive floating-point noise.
// ==========================================================================
float roundTo4(float value) {
  return (int)(value * 10000 + 0.5) / 10000.0;
}

// ================= WIFI =================
void connectWiFi() {
  if (WiFi.status() == WL_CONNECTED) return;
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nWiFi Connected!");
}

// ================= SETUP =================
void setup() {
  Serial.begin(115200);

  SerialPZEM1.begin(9600, SERIAL_8N1, 16, 17);
  SerialPZEM2.begin(9600, SERIAL_8N1, 18, 19);

  pinMode(RELAY1, OUTPUT);
  pinMode(RELAY2, OUTPUT);
  digitalWrite(RELAY1, LOW); 
  digitalWrite(RELAY2, LOW);

  if (!display.begin(SSD1306_SWITCHCAPVCC, 0x3C)) {
    Serial.println("OLED failed");
  }

  display.clearDisplay();
  display.setTextColor(WHITE);
  display.setTextSize(1);

  connectWiFi();

  config.api_key = API_KEY;
  config.database_url = DATABASE_URL;
  config.signer.tokens.legacy_token = "WooQcXvmaEMzu0koU8gMsu8B1sQeb40BEQJ1Hh2q";
  
  Firebase.begin(&config, &auth);
  Firebase.reconnectWiFi(true);

  // Start the Real-time Stream at root
  Firebase.RTDB.setStreamCallback(&fbdo_stream, streamCallback, streamTimeoutCallback);
  Firebase.RTDB.beginStream(&fbdo_stream, "/");

  // Configure NTP for Philippine Standard Time (UTC+8)
  configTime(8 * 3600, 0, "pool.ntp.org", "time.nist.gov");

  Serial.println("[Energy] Energy accumulation module initialised.");
}

// ================= MAIN LOOP =================
void loop() {
  if (WiFi.status() != WL_CONNECTED) connectWiFi();

  // ── Read PZEM sensor values ──
  float v1 = safeRead(pzem1.voltage());
  float c1 = safeRead(pzem1.current());
  float p1 = safeRead(pzem1.power());
  float e1 = safeRead(pzem1.energy());

  float v2 = safeRead(pzem2.voltage());
  float c2 = safeRead(pzem2.current());
  float p2 = safeRead(pzem2.power());
  float e2 = safeRead(pzem2.energy());
  float f1 = safeRead(pzem1.frequency());
  float pf1 = safeRead(pzem1.pf());
  float f2 = safeRead(pzem2.frequency());
  float pf2 = safeRead(pzem2.pf());

  // ── Relay logic (unchanged) ──
  bool r1State = (remote1 && p1 <= threshold1);
  bool r2State = (remote2 && p2 <= threshold2);

  digitalWrite(RELAY1, r1State ? HIGH : LOW);
  digitalWrite(RELAY2, r2State ? HIGH : LOW);

  // ══════════════════════════════════════════════════════════════════════
  //  ENERGY ACCUMULATION — runs every loop iteration (non-blocking)
  //  1. accumulateEnergy() — adds power×time to daily/weekly/monthly kWh
  //  2. checkEnergyResets() — resets accumulators at calendar boundaries
  //  3. syncEnergyToFirebase() — pushes values to /Energy/ every 5 s
  // ══════════════════════════════════════════════════════════════════════
  accumulateEnergy(p1, p2);
  checkEnergyResets();
  syncEnergyToFirebase();

  // ── Live telemetry to Firebase (every 1 s) ──
  if (Firebase.ready() && (millis() - lastTelemetry > telemetryInterval)) {
    lastTelemetry = millis();

    // If safety logic forces a relay OFF while remote command is still true,
    // mirror the actual enforced state back into Control to avoid drift.
    if (remote1 && !r1State) {
      remote1 = false;
      Firebase.RTDB.setBoolAsync(&fbdo, "/Control/PLUG1", false);
    }
    if (remote2 && !r2State) {
      remote2 = false;
      Firebase.RTDB.setBoolAsync(&fbdo, "/Control/PLUG2", false);
    }
    
    FirebaseJson json;
    json.set("PLUG1/voltage", v1).set("PLUG1/current", c1).set("PLUG1/power", p1).set("PLUG1/energy", e1);
    json.set("PLUG1/frequency", f1).set("PLUG1/pf", pf1).set("PLUG1/optimal", pf1 * 100.0);
    
    json.set("PLUG2/voltage", v2).set("PLUG2/current", c2).set("PLUG2/power", p2).set("PLUG2/energy", e2);
    json.set("PLUG2/frequency", f2).set("PLUG2/pf", pf2).set("PLUG2/optimal", pf2 * 100.0);
    
    Firebase.RTDB.updateNodeAsync(&fbdo, "/Live", &json);
    Firebase.RTDB.setStringAsync(&fbdo, "/Status/PLUG1", r1State ? "ON" : "OFF");
    Firebase.RTDB.setStringAsync(&fbdo, "/Status/PLUG2", r2State ? "ON" : "OFF");
  }

  // ── History logging (every 60 s, unchanged) ──
  if (Firebase.ready() && (millis() - lastHistory > historyInterval)) {
    lastHistory = millis();
    String ts = String(time(nullptr));
    FirebaseJson hJson;
    hJson.set("PLUG1/power", p1); hJson.set("PLUG1/voltage", v1); hJson.set("PLUG1/current", c1);
    hJson.set("PLUG2/power", p2); hJson.set("PLUG2/voltage", v2); hJson.set("PLUG2/current", c2);
    Firebase.RTDB.updateNodeAsync(&fbdo, "/History/" + ts, &hJson);
  }

  // ── OLED display (updated to show daily kWh) ──
  display.clearDisplay();
  display.setCursor(0, 0);
  display.println("WattWise REAL-TIME");
  display.printf("\nP1: %.1fW  %.3fkWh", p1, dailyKwh1);
  display.printf("\nP2: %.1fW  %.3fkWh", p2, dailyKwh2);
  display.display();
}
