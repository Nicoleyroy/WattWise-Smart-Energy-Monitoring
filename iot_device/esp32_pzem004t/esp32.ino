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
const char *ssid = "wattwise";
const char *password = "12345678";

// ================= FIREBASE =================
#define API_KEY "AIzaSyC7MPieor9OrzeAXgkMOAPmPJSlcdZ4ow0"
#define DATABASE_URL "https://wattwise-1d764-default-rtdb.firebaseio.com/"

// Firebase objects
FirebaseData fbdo;
FirebaseAuth auth;
FirebaseConfig config;

// ================= SERIAL PORTS =================
HardwareSerial SerialPZEM1(1);
HardwareSerial SerialPZEM2(2);

// ================= PZEM =================
PZEM004Tv30 pzem1(SerialPZEM1, 18, 19);
PZEM004Tv30 pzem2(SerialPZEM2, 16, 17);

// ================= 2 I2C BUSES) =================
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64

TwoWire I2C_1 = TwoWire(0);
TwoWire I2C_2 = TwoWire(1);

Adafruit_SSD1306 display1(SCREEN_WIDTH, SCREEN_HEIGHT, &I2C_1, -1);
Adafruit_SSD1306 display2(SCREEN_WIDTH, SCREEN_HEIGHT, &I2C_2, -1);

// ================= RELAYS =================
#define RELAY1 5
#define RELAY2 4

// ================= VARIABLES =================
bool remote1 = false;
bool remote2 = false;
unsigned long plug1OnTime = 0;
unsigned long plug2OnTime = 0;

unsigned long lastLog = 0;
const int logInterval = 60000;
time_t deviceBootTime = 0;

// ================= FORMAT UPTIME =================
String formatUptimeStr(unsigned long seconds)
{
  if (seconds < 60) return String(seconds) + "s";
  unsigned long totalMinutes = seconds / 60;
  unsigned long days = totalMinutes / 1440;
  unsigned long hours = (totalMinutes % 1440) / 60;
  unsigned long minutes = totalMinutes % 60;
  if (days > 0) return String(days) + "d " + String(hours) + "h";
  if (hours > 0) return String(hours) + "h " + String(minutes) + "m";
  return String(minutes) + "m";
}

String twoDigit(unsigned long value)
{
  return value < 10 ? String("0") + value : String(value);
}

String formatDateShort(time_t timestamp)
{
  struct tm timeInfo;
  localtime_r(&timestamp, &timeInfo);
  return String(timeInfo.tm_mon + 1) + "/" + String(timeInfo.tm_mday) + "/" + String((timeInfo.tm_year + 1900) % 100);
}

String formatDateRange(time_t startTime, time_t endTime)
{
  return formatDateShort(startTime) + " - " + formatDateShort(endTime);
}

String formatUptimeLong(unsigned long seconds)
{
  const unsigned long days = seconds / 86400;
  const unsigned long hours = (seconds % 86400) / 3600;
  const unsigned long minutes = (seconds % 3600) / 60;
  return String(days) + " days " + twoDigit(hours) + ":" + twoDigit(minutes) + " hrs";
}

// ================= SAFE READ =================
float safeRead(float value)
{
  if (isnan(value))
    return 0.0;
  return value;
}

// ================= WIFI =================
void connectWiFi()
{
  WiFi.begin(ssid, password);
  Serial.print("Connecting WiFi");

  while (WiFi.status() != WL_CONNECTED)
  {
    delay(500);
    Serial.print(".");
  }

  Serial.println("\nWiFi Connected!");
}

// ================= SETUP =================
void setup()
{
  Serial.begin(115200);

  // ================= I2C SETUP =================
  I2C_1.begin(8, 9); // OLED 1
  I2C_2.begin(6, 7); // OLED 2

  // ================= PZEM =================
  SerialPZEM1.begin(9600, SERIAL_8N1, 18, 19);
  SerialPZEM2.begin(9600, SERIAL_8N1, 16, 17);

  // ================= RELAYS =================
  pinMode(RELAY1, OUTPUT);
  pinMode(RELAY2, OUTPUT);
  digitalWrite(RELAY1, HIGH);
  digitalWrite(RELAY2, HIGH);

  // ================= OLED INIT =================
  if (!display1.begin(SSD1306_SWITCHCAPVCC, 0x3C))
  {
    Serial.println("OLED1 failed");
    while (true)
      ;
  }

  if (!display2.begin(SSD1306_SWITCHCAPVCC, 0x3C))
  {
    Serial.println("OLED2 failed");
    while (true)
      ;
  }

  display1.clearDisplay();
  display2.clearDisplay();

  display1.setTextSize(1);
  display2.setTextSize(1);

  display1.setTextColor(WHITE);
  display2.setTextColor(WHITE);

  // ================= WIFI =================
  connectWiFi();

  // ================= FIREBASE =================
  config.api_key = API_KEY;
  config.database_url = DATABASE_URL;

  config.signer.tokens.legacy_token = "WooQcXvmaEMzu0koU8gMsu8B1sQeb40BEQJ1Hh2q";
  config.token_status_callback = tokenStatusCallback;

  Firebase.begin(&config, &auth);
  Firebase.reconnectWiFi(true);

  // ================= TIME =================
  configTime(8 * 3600, 0, "pool.ntp.org", "time.nist.gov");

  Serial.print("Syncing time");
  while (time(nullptr) < 100000)
  {
    delay(500);
    Serial.print(".");
  }
  deviceBootTime = time(nullptr) - static_cast<time_t>(millis() / 1000);
  Serial.println(" Time synced!");
}

// ================= LOOP =================
void loop()
{

  if (WiFi.status() != WL_CONNECTED)
  {
    connectWiFi();
  }

  // ================= READ PZEM =================
  float voltage1 = safeRead(pzem1.voltage());
  float current1 = safeRead(pzem1.current());
  float power1 = safeRead(pzem1.power());
  float energy1 = safeRead(pzem1.energy());

  float voltage2 = safeRead(pzem2.voltage());
  float current2 = safeRead(pzem2.current());
  float power2 = safeRead(pzem2.power());
  float energy2 = safeRead(pzem2.energy());

  // ================= GET CONTROL =================
  if (Firebase.ready())
  {
    if (Firebase.RTDB.getBool(&fbdo, "/Control/PLUG1"))
      remote1 = fbdo.boolData();

    if (Firebase.RTDB.getBool(&fbdo, "/Control/PLUG2"))
      remote2 = fbdo.boolData();
  }

  // ================= RELAY LOGIC & UPTIME =================
  if (remote1) {
    if (plug1OnTime == 0) plug1OnTime = millis();
  } else {
    plug1OnTime = 0;
  }

  if (remote2) {
    if (plug2OnTime == 0) plug2OnTime = millis();
  } else {
    plug2OnTime = 0;
  }

  digitalWrite(RELAY1, remote1 ? HIGH : LOW);
  digitalWrite(RELAY2, remote2 ? HIGH : LOW);

  // ================= FIREBASE LIVE & UPTIME =================
  if (Firebase.ready())
  {

    Firebase.RTDB.setFloat(&fbdo, "/Live/PLUG1/voltage", voltage1);
    Firebase.RTDB.setFloat(&fbdo, "/Live/PLUG1/current", current1);
    Firebase.RTDB.setFloat(&fbdo, "/Live/PLUG1/power", power1);
    Firebase.RTDB.setFloat(&fbdo, "/Live/PLUG1/energy", energy1);

    Firebase.RTDB.setFloat(&fbdo, "/Live/PLUG2/voltage", voltage2);
    Firebase.RTDB.setFloat(&fbdo, "/Live/PLUG2/current", current2);
    Firebase.RTDB.setFloat(&fbdo, "/Live/PLUG2/power", power2);
    Firebase.RTDB.setFloat(&fbdo, "/Live/PLUG2/energy", energy2);

    Firebase.RTDB.setString(&fbdo, "/Status/PLUG1", remote1 ? "ON" : "OFF");
    Firebase.RTDB.setString(&fbdo, "/Status/PLUG2", remote2 ? "ON" : "OFF");

    // ================= TOTAL DEVICE UPTIME =================
    time_t nowTime = time(nullptr);
    unsigned long totalDeviceUptimeSec = nowTime >= deviceBootTime
      ? static_cast<unsigned long>(nowTime - deviceBootTime)
      : 0;
    String formattedUptime = formatUptimeStr(totalDeviceUptimeSec);
    String readableDate = formatDateRange(deviceBootTime, nowTime);
    String readableTime = formatUptimeLong(totalDeviceUptimeSec);

    Firebase.RTDB.setInt(&fbdo, "/Uptime/uptime_seconds", totalDeviceUptimeSec);
    Firebase.RTDB.setString(&fbdo, "/Uptime/formatted", formattedUptime);
    Firebase.RTDB.setString(&fbdo, "/Uptime/date", readableDate);
    Firebase.RTDB.setString(&fbdo, "/Uptime/time", readableTime);
    if (nowTime > 100000) {
      Firebase.RTDB.setDouble(&fbdo, "/Uptime/last_updated", (double)nowTime * 1000);
    }
  }

  // ================= HISTORY =================
  if (Firebase.ready() && millis() - lastLog > logInterval)
  {
    lastLog = millis();

    time_t now = time(nullptr);
    String timestamp = String(now);

    Firebase.RTDB.setFloat(&fbdo, "/History/" + timestamp + "/PLUG1/voltage", voltage1);
    Firebase.RTDB.setFloat(&fbdo, "/History/" + timestamp + "/PLUG1/current", current1);
    Firebase.RTDB.setFloat(&fbdo, "/History/" + timestamp + "/PLUG1/power", power1);
    Firebase.RTDB.setFloat(&fbdo, "/History/" + timestamp + "/PLUG1/energy", energy1);
    Firebase.RTDB.setString(&fbdo, "/History/" + timestamp + "/PLUG1/device_id", "PLUG1");

    Firebase.RTDB.setFloat(&fbdo, "/History/" + timestamp + "/PLUG2/voltage", voltage2);
    Firebase.RTDB.setFloat(&fbdo, "/History/" + timestamp + "/PLUG2/current", current2);
    Firebase.RTDB.setFloat(&fbdo, "/History/" + timestamp + "/PLUG2/power", power2);
    Firebase.RTDB.setFloat(&fbdo, "/History/" + timestamp + "/PLUG2/energy", energy2);
    Firebase.RTDB.setString(&fbdo, "/History/" + timestamp + "/PLUG2/device_id", "PLUG2");
  }

  // ================= OLED 1 (PLUG 1) =================
  display1.clearDisplay();
  display1.setCursor(0, 0);
  display1.println("Plug 1");

  display1.setCursor(0, 20);
  display1.printf("Power: %.0fW", power1);

  display1.setCursor(0, 40);
  display1.print(remote1 ? "ON" : "OFF");

  display1.display();

  // ================= OLED 2 (PLUG 2) =================
  display2.clearDisplay();
  display2.setCursor(0, 0);
  display2.println("Plug 2");

  display2.setCursor(0, 20);
  display2.printf("Power: %.0fW", power2);

  display2.setCursor(0, 40);
  display2.print(remote2 ? "ON" : "OFF");

  display2.display();

  // ================= SERIAL =================
  Serial.printf("P1: %.2fW | P2: %.2fW\n", power1, power2);

  delay(2000);
}