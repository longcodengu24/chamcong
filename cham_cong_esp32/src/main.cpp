
#include <Arduino.h>
#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <SPI.h>
#include <MFRC522.h>
#include <SPIFFS.h>
#include <ArduinoJson.h>
#include <time.h>

// ================== CẤU HÌNH - SỬA Ở ĐÂY ==================
const char* WIFI_SSID = "kittyfood";
const char* WIFI_PASS = "88888888";

// --- Supabase ---
// Lấy từ Supabase Dashboard > Project Settings > API > Project URL
const char* SUPABASE_URL = "https://wlyahxsteoatqpjldwrr.supabase.co";
// Lấy từ Supabase Dashboard > Project Settings > API > Project API keys > service_role (secret)
const char* SUPABASE_SERVICE_KEY = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6IndseWFoeHN0ZW9hdHFwamxkd3JyIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc4MzE0ODg1NCwiZXhwIjoyMDk4NzI0ODU0fQ.PEA2fPRREtSn-zFy2LtyzqbsQyV03K5Gzxfl48hyJzg";

#define SS_PIN     5
#define RST_PIN    22
#define BUZZER_PIN 4
#define LED_PIN    2

// Múi giờ Việt Nam GMT+7
const long GMT_OFFSET = 7 * 3600;
// ===========================================================

MFRC522 rfid(SS_PIN, RST_PIN);

unsigned long lastScanTime = 0;
String lastUID = "";

// ---------- Tiện ích ----------
String getUIDString(MFRC522::Uid* uid) {
  String s = "";
  for (byte i = 0; i < uid->size; i++) {
    if (uid->uidByte[i] < 0x10) s += "0";
    s += String(uid->uidByte[i], HEX);
  }
  s.toUpperCase();
  return s;
}

String getTimeString() {
  struct tm t;
  if (!getLocalTime(&t)) return "0000-00-00 00:00:00";
  char buf[25];
  strftime(buf, sizeof(buf), "%Y-%m-%d %H:%M:%S", &t);
  return String(buf);
}

void beep(int times, int ms) {
  for (int i = 0; i < times; i++) {
    digitalWrite(BUZZER_PIN, HIGH);
    digitalWrite(LED_PIN, HIGH);
    delay(ms);
    digitalWrite(BUZZER_PIN, LOW);
    digitalWrite(LED_PIN, LOW);
    if (i < times - 1) delay(80);
  }
}

// ---------- Tra cứu nhân viên theo UID thẻ ----------
// Trả về true nếu tìm thấy, gán outUserId + outName
bool lookupUserByUid(const String& uid, long& outUserId, String& outName) {
  if (WiFi.status() != WL_CONNECTED) return false;

  WiFiClientSecure client;
  client.setInsecure(); // Bỏ qua xác thực SSL để đơn giản

  HTTPClient http;
  String url = String(SUPABASE_URL) + "/rest/v1/users?rfid_uid=eq." + uid + "&select=id,name";

  http.begin(client, url);
  http.addHeader("apikey", SUPABASE_SERVICE_KEY);
  http.addHeader("Authorization", String("Bearer ") + SUPABASE_SERVICE_KEY);
  int code = http.GET();

  bool found = false;
  if (code == 200) {
    String body = http.getString();
    JsonDocument doc;
    if (!deserializeJson(doc, body) && doc.size() > 0) {
      outUserId = doc[0]["id"].as<long>();
      outName   = doc[0]["name"].as<String>();
      found = true;
    }
  }
  http.end();
  return found;
}

// ---------- Tìm ca làm đang mở (chưa check-out) của 1 nhân viên ----------
// Trả về id của bản ghi history nếu có, ngược lại -1
long findOpenSession(long userId) {
  if (WiFi.status() != WL_CONNECTED) return -1;

  WiFiClientSecure client;
  client.setInsecure();

  HTTPClient http;
  String url = String(SUPABASE_URL) + "/rest/v1/history?user_id=eq." + String(userId) +
               "&check_out=is.null&select=id&order=id.desc&limit=1";

  http.begin(client, url);
  http.addHeader("apikey", SUPABASE_SERVICE_KEY);
  http.addHeader("Authorization", String("Bearer ") + SUPABASE_SERVICE_KEY);
  int code = http.GET();

  long sessionId = -1;
  if (code == 200) {
    String body = http.getString();
    JsonDocument doc;
    if (!deserializeJson(doc, body) && doc.size() > 0) {
      sessionId = doc[0]["id"].as<long>();
    }
  }
  http.end();
  return sessionId;
}

// ---------- Tạo ca làm mới (check-in) ----------
bool createCheckIn(long userId) {
  if (WiFi.status() != WL_CONNECTED) return false;

  WiFiClientSecure client;
  client.setInsecure();

  HTTPClient http;
  String url = String(SUPABASE_URL) + "/rest/v1/history";

  JsonDocument doc;
  doc["user_id"]  = userId;
  doc["check_in"] = getTimeString();

  String payload;
  serializeJson(doc, payload);

  http.begin(client, url);
  http.addHeader("apikey", SUPABASE_SERVICE_KEY);
  http.addHeader("Authorization", String("Bearer ") + SUPABASE_SERVICE_KEY);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Prefer", "return=minimal");
  int code = http.POST(payload);

  Serial.printf("[Supabase] POST check-in -> %d\n", code);
  http.end();
  return code == 201;
}

// ---------- Đóng ca làm (check-out) ----------
bool closeCheckOut(long sessionId) {
  if (WiFi.status() != WL_CONNECTED) return false;

  WiFiClientSecure client;
  client.setInsecure();

  HTTPClient http;
  String url = String(SUPABASE_URL) + "/rest/v1/history?id=eq." + String(sessionId);

  JsonDocument doc;
  doc["check_out"] = getTimeString();

  String payload;
  serializeJson(doc, payload);

  http.begin(client, url);
  http.addHeader("apikey", SUPABASE_SERVICE_KEY);
  http.addHeader("Authorization", String("Bearer ") + SUPABASE_SERVICE_KEY);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Prefer", "return=minimal");
  int code = http.PATCH(payload);

  Serial.printf("[Supabase] PATCH check-out -> %d\n", code);
  http.end();
  return code >= 200 && code < 300;
}

// ---------- Ghi lại UID của thẻ lạ (chưa gán cho ai) để trang "Thêm nhân viên" đọc được ----------
bool recordUnknownScan(const String& uid) {
  if (WiFi.status() != WL_CONNECTED) return false;

  WiFiClientSecure client;
  client.setInsecure();

  HTTPClient http;
  String url = String(SUPABASE_URL) + "/rest/v1/history";

  // Không gửi user_id -> cột nullable sẽ lưu NULL, đánh dấu đây là "thẻ chưa gán nhân viên"
  JsonDocument doc;
  doc["rfid_uid"] = uid;
  doc["check_in"] = getTimeString();

  String payload;
  serializeJson(doc, payload);

  http.begin(client, url);
  http.addHeader("apikey", SUPABASE_SERVICE_KEY);
  http.addHeader("Authorization", String("Bearer ") + SUPABASE_SERVICE_KEY);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Prefer", "return=minimal");
  int code = http.POST(payload);

  Serial.printf("[Supabase] POST unknown scan -> %d\n", code);
  http.end();
  return code == 201;
}

// ---------- Backup: Ghi log vào SPIFFS khi mất kết nối Supabase ----------
void logToSPIFFS(const String& uid, const String& event) {
  File f = SPIFFS.open("/log.csv", "a");
  if (!f) return;
  f.printf("%s,%s,%s\n", getTimeString().c_str(), uid.c_str(), event.c_str());
  f.close();
}

// ---------- Setup / Loop ----------
void setup() {
  Serial.begin(115200);
  pinMode(BUZZER_PIN, OUTPUT);
  pinMode(LED_PIN, OUTPUT);

  SPI.begin();
  rfid.PCD_Init();
  rfid.PCD_DumpVersionToSerial();

  if (!SPIFFS.begin(true)) Serial.println("Loi SPIFFS!");

  WiFi.begin(WIFI_SSID, WIFI_PASS);
  Serial.print("Dang ket noi WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println();
  Serial.println("Da ket noi! IP: " + WiFi.localIP().toString());

  // Đồng bộ giờ qua NTP
  configTime(GMT_OFFSET, 0, "pool.ntp.org", "time.google.com");
  Serial.print("Dong bo NTP");
  struct tm t;
  while (!getLocalTime(&t)) { delay(500); Serial.print("."); }
  Serial.println(" OK - " + getTimeString());

  beep(2, 100); // báo khởi động xong
  Serial.println("He thong san sang. Quet the RFID...");
}

void loop() {
  if (!rfid.PICC_IsNewCardPresent() || !rfid.PICC_ReadCardSerial()) return;

  String uid = getUIDString(&rfid.uid);

  // Chống quẹt trùng: cùng 1 thẻ trong vòng 5 giây thì bỏ qua
  if (uid == lastUID && millis() - lastScanTime < 5000) {
    rfid.PICC_HaltA();
    return;
  }
  lastUID = uid;
  lastScanTime = millis();

  Serial.printf("[%s] Quet the: %s\n", getTimeString().c_str(), uid.c_str());

  long userId;
  String name;
  if (!lookupUserByUid(uid, userId, name)) {
    Serial.println("  The la, chua dang ky nhan vien nao");
    bool ok = recordUnknownScan(uid);
    if (!ok) logToSPIFFS(uid, "UNKNOWN_SCAN_FAILED");
    beep(3, 80); // thẻ lạ: 3 tiếng bíp ngắn
    rfid.PICC_HaltA();
    rfid.PCD_StopCrypto1();
    return;
  }

  long openSessionId = findOpenSession(userId);

  if (openSessionId < 0) {
    // Chưa có ca mở -> đây là lượt check-in (vào ca)
    bool ok = createCheckIn(userId);
    Serial.printf("  [%s] Check-in %s\n", name.c_str(), ok ? "OK" : "LOI");
    if (!ok) logToSPIFFS(uid, "CHECKIN_FAILED");
    beep(1, 150); // 1 tiếng bíp: vào ca
  } else {
    bool ok = closeCheckOut(openSessionId);
    Serial.printf("  [%s] Check-out %s\n", name.c_str(), ok ? "OK" : "LOI");
    if (!ok) logToSPIFFS(uid, "CHECKOUT_FAILED");
    beep(2, 150); // 2 tiếng bíp: ra ca
  }

  rfid.PICC_HaltA();
  rfid.PCD_StopCrypto1();
}
