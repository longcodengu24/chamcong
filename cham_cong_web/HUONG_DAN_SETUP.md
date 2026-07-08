# Hướng dẫn Setup Hệ thống Chấm Công IoT

## Kiến trúc hệ thống

```
[Thẻ RFID] → [ESP32] → [Supabase (Postgres + REST API)] ← [Laravel Web App]
                                                                  ↕
                                                          [Admin / User Browser]
```

Laravel kết nối thẳng vào Postgres của Supabase (Eloquent, không qua REST). ESP32 (thiết bị nhúng, không nói được giao thức Postgres) gọi vào Supabase qua REST API (PostgREST) có sẵn.

---

## BƯỚC 1: Tạo Supabase Project

1. Vào **https://supabase.com/dashboard** → **New project**
2. Đặt tên project (VD: `cham-cong-iot`), chọn vùng gần Việt Nam (VD: Singapore), đặt mật khẩu database → **Create new project**
3. Đợi vài phút để project khởi tạo xong

### 1.1 Lấy thông tin kết nối Postgres (cho Laravel)

1. Vào **Project Settings (⚙)** → **Database**
2. Mục **Connection string** → chọn tab **Session pooler** (khuyên dùng, tương thích IPv4) → copy các giá trị: Host, Port (thường `6543`), Database (`postgres`), User, Password (mật khẩu bạn đặt lúc tạo project)

### 1.2 Lấy Project URL và Service Role Key (cho ESP32)

1. Vào **Project Settings (⚙)** → **API**
2. **Project URL**: dạng `https://xxxxxxxxxxxx.supabase.co`
3. **Project API keys** → copy key **`service_role`** (bí mật, KHÔNG chia sẻ công khai)

---

## BƯỚC 2: Cấu hình Laravel (.env)

Mở file `.env` và điền:

```env
DB_CONNECTION=pgsql
DB_HOST=<host_từ_bước_1.1>
DB_PORT=6543
DB_DATABASE=postgres
DB_USERNAME=<user_từ_bước_1.1>
DB_PASSWORD=<password_database>
DB_SSLMODE=require

SUPABASE_URL=https://xxxxxxxxxxxx.supabase.co
SUPABASE_SERVICE_KEY=<service_role_key_từ_bước_1.2>
```

`SUPABASE_URL`/`SUPABASE_SERVICE_KEY` chỉ dùng làm tài liệu tham chiếu khi cấu hình ESP32 ở Bước 3 — Laravel không gọi REST API này (nó dùng `DB_*` để nói chuyện thẳng với Postgres qua Eloquent).

Sau khi điền xong, chạy migrate để tạo bảng trên Supabase:

```bash
cd cham_cong_web
php artisan migrate --force
php artisan db:seed --force   # tạo 2 tài khoản mặc định (xem cuối file)
```

---

## BƯỚC 3: Cấu hình ESP32

Mở `src/main.cpp` và điền (dòng 16–20):

```cpp
const char* WIFI_SSID = "Ten_WiFi_cua_ban";
const char* WIFI_PASS = "Mat_khau_WiFi";
const char* SUPABASE_URL = "https://xxxxxxxxxxxx.supabase.co";
const char* SUPABASE_SERVICE_KEY = "<service_role_key_từ_bước_1.2>";
```

Nạp code lên ESP32 qua PlatformIO (`pio run --target upload`).

> ESP32 dùng `service_role` key — key này có toàn quyền đọc/ghi mọi bảng trong Postgres, bỏ qua Row Level Security. Đơn giản để triển khai nhưng nếu ai trích xuất được firmware sẽ có full quyền vào database. Nếu cần an toàn hơn, có thể đổi sang `anon` key + bật RLS với policy chỉ cho phép `SELECT` trên `employees` và `INSERT` trên `attendance`.

---

## BƯỚC 4: Chạy Laravel

```bash
cd cham_cong_web

# Cài dependencies (đã cài rồi, bỏ qua nếu có vendor/)
composer install

# Chạy server
php artisan serve
```

Truy cập: **http://localhost:8000**

---

## Tài khoản mặc định

| Email | Mật khẩu | Vai trò |
|-------|----------|---------|
| admin@chamcong.vn | admin123 | Admin |
| nhanvien@chamcong.vn | user123 | User |

> **Đổi mật khẩu ngay sau khi đăng nhập lần đầu!**

---

## Cấu trúc dữ liệu trên Supabase (Postgres)

```sql
-- Bảng employees (được tạo qua migration của Laravel)
employees (
  rfid_uid    text primary key,   -- UID thẻ RFID
  name        text,
  department  text,
  phone       text,
  created_at  timestamp
)

-- Bảng attendance
attendance (
  id          bigserial primary key,
  rfid_uid    text,
  name        text,
  timestamp   text,               -- "2024-01-15 08:30:25"
  date        text,               -- "2024-01-15"
  created_at  timestamp
)
```

---

## Quy trình sử dụng

### Admin:
1. Đăng nhập → **admin@chamcong.vn**
2. Vào **Nhân viên & Thẻ** → Thêm nhân viên (nhập UID thẻ + tên)
3. Tạo tài khoản web cho nhân viên (để họ xem lịch sử của mình)
4. Xem **Lịch sử chấm công** → lọc theo ngày/tháng → xuất CSV

### Nhân viên:
1. Đăng nhập bằng tài khoản được admin tạo
2. Xem lịch sử chấm công của bản thân theo tháng

### ESP32:
1. Khi quẹt thẻ:
   - Tra cứu tên từ Supabase `GET /rest/v1/employees?rfid_uid=eq.{UID}&select=name`
   - Ghi bản ghi vào Supabase `POST /rest/v1/attendance`
   - Beep 1 tiếng (đã đăng ký) hoặc 3 tiếng (thẻ lạ)
   - Nếu mất mạng/lỗi ghi Supabase → ghi backup vào SPIFFS (`/log.csv`)
