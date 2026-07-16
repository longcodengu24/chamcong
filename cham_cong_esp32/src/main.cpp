
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
const char* SUPABASE_URL = "https://eeqilnevljkwqzctmywi.supabase.co";
// Lấy từ Supabase Dashboard > Project Settings > API > Project API keys > service_role (secret)
const char* SUPABASE_SERVICE_KEY = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImVlcWlsbmV2bGprd3F6Y3RteXdpIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc4NDE0MTU1NywiZXhwIjoyMDk5NzE3NTU3fQ.dBPBwBz-jmWA--qETLMy6XA9pGxDf0XSAS8bxjX2ddw";

#define SS_PIN     5
#define RST_PIN    22
#define BUZZER_PIN 4
#define LED_PIN    2
#define LED_BUSY_PIN 25 // Sáng liên tục ngay khi nhận được thẻ, tắt khi xử lý xong
#define LED_OK_PIN   26 // Nháy 2 cái khi check-in/check-out thành công

// Múi giờ Việt Nam GMT+7
const long GMT_OFFSET = 7 * 3600;
// ===========================================================

MFRC522 rfid(SS_PIN, RST_PIN);

unsigned long lastScanTime = 0;
String lastUID = "";

// Dùng chung 1 kết nối TLS cho mọi request tới Supabase (thay vì tạo mới mỗi
// lần) để tránh bắt tay TLS lại từ đầu ở từng request - đây là phần tốn thời
// gian nhất trên ESP32, thường 0.5-1.5s mỗi lần bắt tay.
WiFiClientSecure supabaseClient;

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

// Chớp 1 LED riêng để báo check-in/check-out thành công
void blinkLed(int pin, int times, int ms) {
  for (int i = 0; i < times; i++) {
    digitalWrite(pin, HIGH);
    delay(ms);
    digitalWrite(pin, LOW);
    if (i < times - 1) delay(80);
  }
}

// ---------- Tra cứu nhân viên theo UID thẻ + ca đang mở (nếu có) trong 1 request ----------
// Dùng PostgREST resource embedding để lấy đồng thời thông tin nhân viên và
// ca làm chưa check-out (nếu có), thay vì phải gọi 2 request tuần tự.
// Trả về true nếu tìm thấy nhân viên; outOpenSessionId = -1 nếu chưa có ca mở.
bool lookupUserAndOpenSession(const String& uid, long& outUserId, String& outName, long& outOpenSessionId) {
  if (WiFi.status() != WL_CONNECTED) return false;

  HTTPClient http;
  String url = String(SUPABASE_URL) +
               "/rest/v1/user?rfid_uid=eq." + uid +
               "&select=id,name,price(id,check_out)&price.check_out=is.null&price.order=id.desc&price.limit=1";

  http.begin(supabaseClient, url);
  http.setReuse(true); // giữ kết nối TLS để dùng lại cho request check-in/check-out kế tiếp
  http.addHeader("apikey", SUPABASE_SERVICE_KEY);
  http.addHeader("Authorization", String("Bearer ") + SUPABASE_SERVICE_KEY);
  int code = http.GET();

  bool found = false;
  outOpenSessionId = -1;
  if (code == 200) {
    String body = http.getString();
    JsonDocument doc;
    if (!deserializeJson(doc, body) && doc.size() > 0) {
      outUserId = doc[0]["id"].as<long>();
      outName   = doc[0]["name"].as<String>();
      found = true;

      JsonArray openSessions = doc[0]["price"].as<JsonArray>();
      if (openSessions.size() > 0) {
        outOpenSessionId = openSessions[0]["id"].as<long>();
      }
    }
  }
  http.end();
  return found;
}

// ---------- Tạo ca làm mới (check-in) ----------
bool createCheckIn(long userId) {
  if (WiFi.status() != WL_CONNECTED) return false;

  HTTPClient http;
  String url = String(SUPABASE_URL) + "/rest/v1/price";

  JsonDocument doc;
  doc["user_id"]  = userId;
  doc["check_in"] = getTimeString();

  String payload;
  serializeJson(doc, payload);

  http.begin(supabaseClient, url);
  http.setReuse(true);
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

  HTTPClient http;
  String url = String(SUPABASE_URL) + "/rest/v1/price?id=eq." + String(sessionId);

  JsonDocument doc;
  doc["check_out"] = getTimeString();

  String payload;
  serializeJson(doc, payload);

  http.begin(supabaseClient, url);
  http.setReuse(true);
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

  HTTPClient http;
  String url = String(SUPABASE_URL) + "/rest/v1/price";

  // Không gửi user_id -> cột nullable sẽ lưu NULL, đánh dấu đây là "thẻ chưa gán nhân viên"
  JsonDocument doc;
  doc["rfid_uid"] = uid;
  doc["check_in"] = getTimeString();

  String payload;
  serializeJson(doc, payload);

  http.begin(supabaseClient, url);
  http.setReuse(true);
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
  pinMode(LED_BUSY_PIN, OUTPUT);
  pinMode(LED_OK_PIN, OUTPUT);

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

  // Tắt chế độ tiết kiệm điện của WiFi: mặc định ESP32 ngủ đông modem giữa
  // các lần truyền, làm mỗi request chậm thêm (độ trễ đánh thức modem).
  // Đổi lại tốn điện hơn 1 chút, chấp nhận được vì máy chấm công luôn cắm điện.
  WiFi.setSleep(false);

  supabaseClient.setInsecure(); // Bỏ qua xác thực SSL để đơn giản, dùng chung cho mọi request

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

  digitalWrite(LED_BUSY_PIN, HIGH); // báo đang xử lý ngay khi nhận được thẻ

  Serial.printf("[%s] Quet the: %s\n", getTimeString().c_str(), uid.c_str());

  long userId;
  String name;
  long openSessionId;
  if (!lookupUserAndOpenSession(uid, userId, name, openSessionId)) {
    Serial.println("  The la, chua dang ky nhan vien nao");
    bool ok = recordUnknownScan(uid);
    if (!ok) logToSPIFFS(uid, "UNKNOWN_SCAN_FAILED");
    beep(3, 80); // thẻ lạ: 3 tiếng bíp ngắn
    digitalWrite(LED_BUSY_PIN, LOW); // thẻ lạ: không có ca hợp lệ nên không nháy đèn OK
    rfid.PICC_HaltA();
    rfid.PCD_StopCrypto1();
    return;
  }

  bool ok;
  if (openSessionId < 0) {
    // Chưa có ca mở -> đây là lượt check-in (vào ca)
    ok = createCheckIn(userId);
    Serial.printf("  [%s] Check-in %s\n", name.c_str(), ok ? "OK" : "LOI");
    if (!ok) logToSPIFFS(uid, "CHECKIN_FAILED");
    beep(1, 150); // 1 tiếng bíp: vào ca
  } else {
    ok = closeCheckOut(openSessionId);
    Serial.printf("  [%s] Check-out %s\n", name.c_str(), ok ? "OK" : "LOI");
    if (!ok) logToSPIFFS(uid, "CHECKOUT_FAILED");
    beep(2, 150); // 2 tiếng bíp: ra ca
  }

  if (ok) blinkLed(LED_OK_PIN, 2, 150); // check-in/check-out thành công: nháy đèn OK 2 cái
  digitalWrite(LED_BUSY_PIN, LOW);
  digitalWrite(LED_OK_PIN, LOW);

  rfid.PICC_HaltA();
  rfid.PCD_StopCrypto1();
}
