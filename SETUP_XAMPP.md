# Hướng dẫn cài đặt LoyalteApp với XAMPP

> **Mục tiêu:** Chạy backend PHP + website khách hàng trên máy tính cá nhân bằng XAMPP,  
> sau đó có thể upload lên VPS bất kỳ khi nào sẵn sàng.

---

## Mục lục

1. [Cài đặt XAMPP](#1-cài-đặt-xampp)
2. [Import database MySQL](#2-import-database-mysql)
3. [Đặt file dự án vào XAMPP](#3-đặt-file-dự-án-vào-xampp)
4. [Cấu hình backend PHP](#4-cấu-hình-backend-php)
5. [Bật mod_rewrite](#5-bật-mod_rewrite)
6. [Kiểm tra API hoạt động](#6-kiểm-tra-api-hoạt-động)
7. [Chạy website khách hàng](#7-chạy-website-khách-hàng)
8. [Kết nối Android app](#8-kết-nối-android-app)
9. [Đổi mật khẩu admin](#9-đổi-mật-khẩu-admin)
10. [Upload lên VPS sau này](#10-upload-lên-vps-sau-này)
11. [Xử lý lỗi thường gặp](#11-xử-lý-lỗi-thường-gặp)

---

## 1. Cài đặt XAMPP

1. Tải XAMPP tại: https://www.apachefriends.org  
   _(chọn phiên bản PHP 8.x, Windows/Mac/Linux đều có)_

2. Cài đặt bình thường, chọn đường dẫn mặc định:
   - **Windows:** `C:\xampp`
   - **Mac:** `/Applications/XAMPP`
   - **Linux:** `/opt/lampp`

3. Mở **XAMPP Control Panel** → bật **Apache** và **MySQL**  
   _(hai dịch vụ này phải hiển thị nền xanh lá)_

---

## 2. Import database MySQL

### Cách A — phpMyAdmin (đơn giản nhất)

1. Mở trình duyệt → truy cập: `http://localhost/phpmyadmin`
2. Click **"New"** ở thanh trái → đặt tên database: `loyalteapp` → **Create**
3. Click vào database `loyalteapp` vừa tạo
4. Click tab **Import** → chọn file `backend/database.sql` trong thư mục dự án
5. Click **Go** — chờ vài giây là xong

### Cách B — Command line (nhanh hơn)

```bash
# Windows
"C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS loyalteapp;"
"C:\xampp\mysql\bin\mysql.exe" -u root loyalteapp < C:\xampp\htdocs\loyalteapp\backend\database.sql

# Mac/Linux
/opt/lampp/bin/mysql -u root -e "CREATE DATABASE IF NOT EXISTS loyalteapp;"
/opt/lampp/bin/mysql -u root loyalteapp < /opt/lampp/htdocs/loyalteapp/backend/database.sql
```

Sau khi import, database sẽ có:
- **12 khách hàng mẫu** (John Smith, Sarah Johnson, v.v.)
- **8 phần thưởng** (Free Coffee, 10% Discount, v.v.)
- **1 tài khoản staff mặc định:** `admin@loyalte.app` / `admin123`

---

## 3. Đặt file dự án vào XAMPP

Sao chép toàn bộ thư mục dự án vào `htdocs`:

```
Windows:  C:\xampp\htdocs\loyalteapp\
Mac:      /Applications/XAMPP/htdocs/loyalteapp/
Linux:    /opt/lampp/htdocs/loyalteapp/
```

Sau khi copy, cấu trúc thư mục sẽ là:

```
htdocs/
└── loyalteapp/
    ├── backend/
    │   ├── .htaccess
    │   ├── index.php          ← router chính
    │   ├── config.php
    │   ├── db.php
    │   ├── helpers.php
    │   ├── database.sql
    │   └── controllers/
    │       ├── auth.php
    │       ├── customers.php
    │       ├── transactions.php
    │       ├── rewards.php
    │       └── redemptions.php
    └── web/
        └── php/
            ├── index.php      ← trang đăng nhập khách hàng
            ├── dashboard.php
            ├── history.php
            ├── rewards.php
            ├── logout.php
            ├── auth.php
            └── config.php
```

---

## 4. Cấu hình backend PHP

Mở file **`backend/config.php`** bằng Notepad/VSCode:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'loyalteapp');
define('DB_USER', 'root');
define('DB_PASS', '');        // XAMPP mặc định: để trống
```

> **Trên VPS:** thay `''` bằng mật khẩu MySQL thật của bạn.

Mở file **`web/php/config.php`** và chỉnh tương tự (cùng thông số).

---

## 5. Bật mod_rewrite

Tính năng mod_rewrite cho phép URL đẹp như `/api/customers` thay vì `/index.php?resource=customers`.

### Windows (XAMPP)

1. Mở `C:\xampp\apache\conf\httpd.conf` bằng Notepad
2. Tìm dòng: `#LoadModule rewrite_module modules/mod_rewrite.so`
3. Xóa dấu `#` ở đầu dòng → Lưu file
4. Tìm đoạn `<Directory "C:/xampp/htdocs">` → trong đó tìm:
   ```
   AllowOverride None
   ```
   Đổi thành:
   ```
   AllowOverride All
   ```
5. Restart Apache trong XAMPP Control Panel

### Mac/Linux

```bash
# Mac (Homebrew XAMPP)
sudo /Applications/XAMPP/xamppfiles/bin/apachectl restart

# Hoặc edit file:
sudo nano /opt/lampp/etc/httpd.conf
# Uncomment: LoadModule rewrite_module modules/mod_rewrite.so
# Đổi AllowOverride None → AllowOverride All trong thư mục htdocs
sudo /opt/lampp/lampp restart
```

---

## 6. Kiểm tra API hoạt động

Mở trình duyệt và truy cập từng URL sau — nếu thấy JSON là thành công:

### Kiểm tra rewards (không cần đăng nhập)
```
http://localhost/loyalteapp/backend/api/rewards
```
Kết quả mong đợi:
```json
{"success":true,"rewards":[{"id":"rwd-00000001","name":"Free Coffee",...},...]}
```

### Kiểm tra đăng nhập staff

Dùng **Postman**, **Insomnia**, hoặc `curl`:

```bash
curl -X POST http://localhost/loyalteapp/backend/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@loyalte.app","password":"admin123"}'
```

Kết quả:
```json
{
  "success": true,
  "token": "abc123...",
  "staff": {"name": "Admin", "email": "admin@loyalte.app"}
}
```

### Kiểm tra danh sách khách hàng (cần token)

```bash
curl http://localhost/loyalteapp/backend/api/customers \
  -H "Authorization: Bearer abc123..."
```

---

## 7. Chạy website khách hàng

Mở trình duyệt → truy cập:

```
http://localhost/loyalteapp/web/php/index.php
```

Nhập số điện thoại của khách hàng mẫu (ví dụ: `+14155551001`) → **Xem điểm của tôi**

Sẽ thấy dashboard với:
- Điểm tích lũy và tier (Đồng/Bạc/Vàng/Bạch Kim)
- Mã QR thành viên
- 5 giao dịch gần nhất

**Số điện thoại khách hàng mẫu để test:**
| Tên | Số điện thoại | Điểm |
|-----|---------------|------|
| John Smith | +14155551001 | 450 (Đồng) |
| Sarah Johnson | +14155551002 | 750 (Bạc) |
| Michael Chen | +14155551003 | 1200 (Vàng) |
| Emily Davis | +14155551004 | 3000 (Bạch Kim) |
| Maria Rodriguez | +14155551010 | 4200 (Bạch Kim) |

---

## 8. Kết nối Android app

### Trên thiết bị thật (cùng mạng WiFi)

1. Tìm địa chỉ IP máy tính:
   - **Windows:** mở CMD → `ipconfig` → tìm `IPv4 Address` (vd: `192.168.1.100`)
   - **Mac/Linux:** `ifconfig | grep inet`

2. Mở file `app/src/main/java/com/loyalte/app/util/AppConfig.kt`:
   ```kotlin
   const val BASE_URL = "http://192.168.1.100/loyalteapp/backend/api/"
   //                           ^^ thay bằng IP máy tính của bạn
   ```

3. Đảm bảo thiết bị Android kết nối cùng mạng WiFi với máy tính

4. **Quan trọng:** Thêm Network Security Config vì HTTP (không phải HTTPS):

   Tạo file `app/src/main/res/xml/network_security_config.xml`:
   ```xml
   <?xml version="1.0" encoding="utf-8"?>
   <network-security-config>
       <domain-config cleartextTrafficPermitted="true">
           <domain includeSubdomains="true">192.168.1.100</domain>
       </domain-config>
   </network-security-config>
   ```

   Thêm vào `AndroidManifest.xml` trong thẻ `<application>`:
   ```xml
   android:networkSecurityConfig="@xml/network_security_config"
   ```

### Trên máy ảo Android (Emulator)

Emulator có thể dùng IP đặc biệt để kết nối với localhost:
```kotlin
const val BASE_URL = "http://10.0.2.2/loyalteapp/backend/api/"
```
_(10.0.2.2 là alias của localhost trong Android emulator — đã được đặt sẵn trong AppConfig.kt)_

### Đăng nhập staff trên Android

Tài khoản mặc định:
- **Email:** `admin@loyalte.app`
- **Password:** `admin123`

---

## 9. Đổi mật khẩu admin

### Cách 1 — phpMyAdmin

1. Tạo hash mật khẩu mới bằng PHP:
   ```php
   // Tạo file test.php trong htdocs, truy cập localhost/test.php
   <?php echo password_hash('MK_MOI_CUA_BAN', PASSWORD_BCRYPT); ?>
   ```
2. Copy chuỗi hash → vào phpMyAdmin → table `staff_accounts` → Edit → paste vào cột `password_hash`

### Cách 2 — MySQL command

```sql
UPDATE staff_accounts
SET password_hash = '$2y$10$...'   -- hash mới tạo bằng password_hash()
WHERE email = 'admin@loyalte.app';
```

### Thêm nhân viên mới

```sql
INSERT INTO staff_accounts (email, password_hash, name) VALUES
('nhanvien@loyalte.app',
 '$2y$10$...',   -- hash của mật khẩu
 'Nguyen Van A');
```

---

## 10. Upload lên VPS sau này

Khi bạn thuê VPS (khuyến nghị Ubuntu 22.04 LTS):

### Cài LAMP stack

```bash
sudo apt update
sudo apt install apache2 mysql-server php php-mysql php-curl -y
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Upload file

```bash
# Dùng FileZilla hoặc scp
scp -r loyalteapp/ user@YOUR_VPS_IP:/var/www/html/

# Phân quyền
sudo chown -R www-data:www-data /var/www/html/loyalteapp
```

### Import database

```bash
mysql -u root -p -e "CREATE DATABASE loyalteapp;"
mysql -u root -p loyalteapp < /var/www/html/loyalteapp/backend/database.sql
```

### Cập nhật config.php

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'loyalteapp');
define('DB_USER', 'loyalte_user');   // tạo user riêng, không dùng root
define('DB_PASS', 'MAT_KHAU_MANH');
```

### Cập nhật AppConfig.kt trong Android app

```kotlin
const val BASE_URL = "https://yourdomain.com/loyalteapp/backend/api/"
```

> **Lưu ý bảo mật VPS:**
> - Đổi mật khẩu admin ngay sau khi deploy
> - Dùng HTTPS (cài Let's Encrypt miễn phí)
> - Tạo MySQL user riêng với quyền hạn chế thay vì dùng root
> - Đặt `CORS_ORIGIN` trong config.php thành domain thật thay vì `*`

---

## 11. Xử lý lỗi thường gặp

### Lỗi: "404 Not Found" khi gọi API

**Nguyên nhân:** mod_rewrite chưa bật hoặc `.htaccess` chưa hoạt động.

**Giải pháp:**
- Kiểm tra lại Bước 5 (bật mod_rewrite)
- Thử truy cập trực tiếp: `http://localhost/loyalteapp/backend/index.php` (không qua rewrite)
- Kiểm tra file `.htaccess` có trong thư mục `backend/` chưa (file ẩn trên Mac/Linux)

---

### Lỗi: "Database connection failed"

**Nguyên nhân:** Sai thông tin database trong `config.php`.

**Giải pháp:**
- Kiểm tra MySQL đang chạy trong XAMPP Control Panel
- Mở `backend/config.php` → kiểm tra `DB_USER` và `DB_PASS`
- XAMPP mặc định: user = `root`, pass = `""` (rỗng)

---

### Lỗi: "Invalid email or password" khi đăng nhập Android

**Nguyên nhân:** Tài khoản admin chưa được tạo, hoặc password hash sai.

**Giải pháp:**
- Mở phpMyAdmin → table `staff_accounts` → kiểm tra xem có bản ghi `admin@loyalte.app` chưa
- Nếu chưa có, chạy lại lệnh SQL trong `database.sql` phần "Default staff account"

---

### Android app không kết nối được XAMPP

**Nguyên nhân:** Sai IP, khác mạng WiFi, hoặc thiếu Network Security Config.

**Giải pháp:**
1. Kiểm tra IP trong `AppConfig.kt` đúng chưa
2. Ping thử từ điện thoại: mở trình duyệt trên điện thoại → truy cập `http://192.168.x.x/loyalteapp/backend/api/rewards`
3. Thêm Network Security Config (Bước 8) — bắt buộc từ Android 9 trở lên

---

### Website khách hàng không hiển thị QR code

**Nguyên nhân:** `dashboard.php` dùng `api.qrserver.com` (dịch vụ online tạo QR).

**Giải pháp:**
- Cần có internet để hiển thị QR
- Hoặc cài thư viện PHP QR code để tạo cục bộ:  
  `composer require endroid/qr-code` rồi điều chỉnh `dashboard.php`

---

*Hướng dẫn này dành cho môi trường phát triển XAMPP. Khi deploy lên VPS thật, hãy đảm bảo dùng HTTPS và đổi tất cả mật khẩu mặc định.*
