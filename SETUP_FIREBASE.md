# Firebase Setup Guide — LoyalteApp

Hướng dẫn từng bước để kết nối Android app và website với Firebase.

---

## Tổng quan kiến trúc

```
┌─────────────────────────────────────────────────────┐
│                   Firebase Console                   │
│  ┌─────────────┐  ┌──────────────┐  ┌───────────┐  │
│  │  Firestore  │  │     Auth     │  │  Hosting  │  │
│  │  (Database) │  │  (Đăng nhập) │  │ (Website) │  │
│  └─────────────┘  └──────────────┘  └───────────┘  │
└─────────────────────────────────────────────────────┘
         ↑                   ↑                ↑
  Android (Staff)    Android + Website    Website
  Email/Password     Phone OTP           Deploy tại đây
```

---

## Phần 1 — Tạo Firebase Project

### Bước 1: Tạo project mới

1. Truy cập **https://console.firebase.google.com**
2. Nhấn **"Add project"** (Tạo dự án)
3. Nhập tên project: `LoyalteApp` (hoặc tên bạn muốn)
4. **Tắt Google Analytics** (không cần cho app này) → nhấn **Create project**
5. Đợi khoảng 30 giây → nhấn **Continue**

---

## Phần 2 — Cài đặt Firestore Database

### Bước 2: Tạo Firestore Database

1. Ở sidebar trái → nhấn **Build** → **Firestore Database**
2. Nhấn **Create database**
3. Chọn **"Start in production mode"** (bảo mật)
4. Chọn location gần nhất:
   - `asia-southeast1` (Singapore) — **khuyến nghị cho Việt Nam**
   - `asia-east2` (Hong Kong)
5. Nhấn **Enable**

### Bước 3: Deploy Firestore Security Rules

Bạn có 2 cách:

**Cách A — Dùng Firebase CLI (khuyến nghị):**
```bash
# Cài Firebase CLI
npm install -g firebase-tools

# Đăng nhập Firebase
firebase login

# Vào thư mục project
cd /path/to/LoyalteApp

# Cập nhật .firebaserc với project ID của bạn
# Mở .firebaserc và thay YOUR-FIREBASE-PROJECT-ID

# Deploy rules
firebase deploy --only firestore:rules,firestore:indexes
```

**Cách B — Copy thủ công:**
1. Firestore Console → tab **Rules**
2. Xóa toàn bộ nội dung cũ
3. Copy nội dung file `firestore.rules` trong project vào đây
4. Nhấn **Publish**

Sau đó tạo indexes:
1. Firestore Console → tab **Indexes**
2. Nhấn **Add index** cho từng index trong `firestore.indexes.json`

---

## Phần 3 — Cài đặt Authentication

### Bước 4: Bật Email/Password Auth (dành cho Staff Android)

1. Firebase Console → **Build** → **Authentication**
2. Nhấn **Get started**
3. Tab **Sign-in method** → nhấn **Email/Password**
4. **Bật** toggle đầu tiên (Email/Password)
5. Nhấn **Save**

### Bước 5: Bật Phone Auth (dành cho Khách hàng Website)

1. Vẫn trong tab **Sign-in method**
2. Nhấn **Phone**
3. **Bật** toggle
4. Nhấn **Save**

> **Lưu ý:** Phone Auth cần xác minh với Google reCAPTCHA. Firebase cung cấp miễn phí 10,000 SMS/tháng.

### Bước 6: Tạo tài khoản Staff

1. Authentication → tab **Users**
2. Nhấn **Add user**
3. Nhập email: `staff@yourbusiness.com` (hoặc tên bất kỳ)
4. Nhập password mạnh (ít nhất 8 ký tự)
5. Nhấn **Add user**

> Tạo bao nhiêu tài khoản tùy ý — mỗi nhân viên 1 tài khoản riêng.

---

## Phần 4 — Kết nối Android App

### Bước 7: Đăng ký Android App trong Firebase

1. Firebase Console → nhấn icon ⚙️ (Project settings)
2. Tab **General** → phần **"Your apps"**
3. Nhấn icon **Android** (dấu `</>`)
4. Điền:
   - **Android package name:** `com.loyalte.app`
   - **App nickname:** `LoyalteApp Android`
   - **Debug signing certificate SHA-1:** (xem bước 8)
5. Nhấn **Register app**

### Bước 8: Lấy SHA-1 để dùng Phone Auth trên Android

Chạy lệnh này trong terminal (cần có Java/Android SDK):
```bash
# Windows (trong thư mục project Android)
./gradlew signingReport

# Hoặc dùng keytool
keytool -list -v -keystore ~/.android/debug.keystore -alias androiddebugkey -storepass android -keypass android
```

Tìm dòng `SHA1:` và copy giá trị đó vào Firebase.

> **Tại sao cần SHA-1?** Firebase Phone Auth trên Android yêu cầu SHA-1 để xác minh app hợp lệ.

### Bước 9: Tải google-services.json

1. Sau khi register app → nhấn **"Download google-services.json"**
2. **Xóa** file `app/google-services.json` placeholder trong project
3. **Copy** file vừa tải vào thư mục `app/` của project Android
4. File nằm đúng vị trí: `LoyalteApp/app/google-services.json`

### Bước 10: Build và test Android app

```bash
# Trong Android Studio: File → Sync Project with Gradle Files
# Sau đó Run app trên device/emulator
```

Khi app mở lần đầu sẽ thấy màn hình **"Sign In"** cho staff.

---

## Phần 5 — Kết nối Customer Website

### Bước 11: Đăng ký Web App trong Firebase

1. Firebase Console → ⚙️ Project settings → **Your apps**
2. Nhấn icon **Web** (`</>`)
3. App nickname: `LoyalteApp Web`
4. ✅ **Bật** "Also set up Firebase Hosting for this app"
5. Nhấn **Register app**
6. Bạn sẽ thấy đoạn config như này — **copy lại**:

```javascript
const firebaseConfig = {
  apiKey: "AIzaSy...",
  authDomain: "your-project.firebaseapp.com",
  projectId: "your-project-id",
  storageBucket: "your-project.appspot.com",
  messagingSenderId: "123456789",
  appId: "1:123456789:web:abc123"
};
```

### Bước 12: Tạo file .env.local

1. Vào thư mục `web/`
2. Copy file `.env.local.example` thành `.env.local`
3. Điền các giá trị từ bước 11:

```bash
# web/.env.local
NEXT_PUBLIC_FIREBASE_API_KEY=AIzaSy...
NEXT_PUBLIC_FIREBASE_AUTH_DOMAIN=your-project.firebaseapp.com
NEXT_PUBLIC_FIREBASE_PROJECT_ID=your-project-id
NEXT_PUBLIC_FIREBASE_STORAGE_BUCKET=your-project.appspot.com
NEXT_PUBLIC_FIREBASE_MESSAGING_SENDER_ID=123456789
NEXT_PUBLIC_FIREBASE_APP_ID=1:123456789:web:abc123
```

### Bước 13: Cài dependencies và test local

```bash
# Cần Node.js 18+ (tải tại nodejs.org)

cd web
npm install
npm run dev
```

Mở trình duyệt: **http://localhost:3000**

---

## Phần 6 — Seed dữ liệu mẫu

### Bước 14: Chạy Android app để seed data

1. Mở Android Studio → Run app
2. Đăng nhập bằng tài khoản staff (email/password tạo ở Bước 6)
3. Sau khi đăng nhập, `SeedDataUtil` sẽ tự động ghi **12 khách hàng + 8 phần thưởng** vào Firestore
4. Kiểm tra trong Firebase Console → Firestore → xem collection `customers`

> **Nếu muốn reset dữ liệu:** Vào Firestore Console → xóa collection `customers` và `rewards` → mở lại app.

### Kiểm tra Firestore có dữ liệu không

Firestore Console → **Data** tab. Bạn sẽ thấy:
```
customers/
  {uuid}/
    name: "John Smith"
    phone: "+14155551001"
    points: 450
    tier: "BRONZE"
    memberId: "LYL-000001"
    qrCode: "LYL-000001"
    transactions/ (subcollection)
    redemptions/ (subcollection)

rewards/
  {uuid}/
    name: "Free Coffee"
    pointsRequired: 100
    ...
```

---

## Phần 7 — Deploy Website lên Firebase Hosting

### Bước 15: Cài Firebase CLI và đăng nhập

```bash
npm install -g firebase-tools
firebase login
```

### Bước 16: Cập nhật .firebaserc

Mở file `.firebaserc` trong thư mục gốc:
```json
{
  "projects": {
    "default": "your-actual-project-id"
  }
}
```
Thay `your-actual-project-id` bằng Project ID của bạn (thấy trong Firebase Console → Project settings).

### Bước 17: Build và Deploy

```bash
# Từ thư mục gốc LoyalteApp/
cd web

# Build production
npm run build

# Deploy lên Firebase Hosting
cd ..
firebase deploy --only hosting
```

Sau khi deploy xong, bạn sẽ thấy:
```
✔  Deploy complete!
Hosting URL: https://your-project-id.web.app
```

Khách hàng truy cập: **https://your-project-id.web.app**

---

## Phần 8 — Bật reCAPTCHA cho Phone Auth (bắt buộc)

### Bước 18: Thêm Authorized Domains

1. Firebase Console → Authentication → tab **Settings**
2. Phần **Authorized domains** → nhấn **Add domain**
3. Thêm:
   - `localhost` (cho dev)
   - `your-project-id.web.app` (production)
   - Domain riêng của bạn nếu có

---

## Phần 9 — Cài đặt domain riêng (tùy chọn)

Nếu bạn muốn dùng domain như `loyalty.yourbusiness.com`:

1. Firebase Hosting → **Custom domain**
2. Nhấn **Add custom domain**
3. Nhập domain của bạn
4. Làm theo hướng dẫn thêm DNS record vào DNS provider
5. Đợi 24-48 giờ để SSL kích hoạt

---

## Phần 10 — Kiểm tra toàn bộ hệ thống

### Checklist sau khi setup

**Android app (Staff):**
- [ ] App mở ra và hiện màn hình đăng nhập
- [ ] Đăng nhập thành công với email/password đã tạo
- [ ] Tìm kiếm khách hàng bằng số điện thoại `+14155551001` → hiện thông tin
- [ ] Quét QR code `LYL-000001` → hiện thông tin khách hàng
- [ ] Đổi thưởng và thấy điểm giảm real-time

**Website (Khách hàng):**
- [ ] Mở website → hiện màn hình nhập số điện thoại
- [ ] Nhập SĐT của 1 khách hàng mẫu → nhận OTP
- [ ] Đăng nhập thành công → thấy điểm và QR code
- [ ] Điểm thay đổi real-time khi staff xử lý trên Android

---

## Phần 11 — Giám sát và Quản lý

### Xem usage trong Firebase Console

| Mục | Đường dẫn |
|-----|-----------|
| Số document reads/writes | Firestore → Usage |
| Người dùng đã đăng ký | Authentication → Users |
| Lỗi app | (Thêm Firebase Crashlytics sau) |
| Tốc độ database | Firestore → Usage → Operations |

### Firebase Free Tier — đủ cho shop nhỏ

| Tính năng | Free Limit | Tương đương |
|-----------|-----------|-------------|
| Firestore reads | 50,000/ngày | ~250 khách/ngày tra điểm |
| Firestore writes | 20,000/ngày | ~200 giao dịch/ngày |
| Firestore storage | 1 GB | ~10 triệu document |
| Hosting | 10 GB/tháng | Đủ cho website loyalty |
| Phone Auth SMS | 10,000/tháng | Miễn phí |
| Auth users | Không giới hạn | ✓ |

---

## Phần 12 — Thêm nhân viên mới

### Cách tạo thêm tài khoản staff

**Cách 1 — Firebase Console:**
1. Authentication → Users → **Add user**
2. Nhập email + password
3. Gửi thông tin cho nhân viên

**Cách 2 — Trong Android app (tương lai):**
Có thể thêm màn hình "Admin" để tạo tài khoản nhân viên ngay trong app.

---

## Phần 13 — Thêm khách hàng mới (thực tế)

Hiện tại app dùng dữ liệu mẫu (seed). Để thêm khách hàng thật:

### Option A: Staff tạo trực tiếp trong app
Cần thêm màn hình "Tạo khách hàng" trong Android app — có thể làm sau.

### Option B: Tạo trực tiếp trong Firestore Console
1. Firestore → `customers` → **Add document**
2. Document ID: UUID mới (nhấn Auto-ID)
3. Thêm các fields:
```
memberId:  "LYL-000013"     (string)
name:      "Nguyễn Văn A"   (string)
phone:     "+84901234567"   (string)
points:    0                (number)
tier:      "BRONZE"         (string)
qrCode:    "LYL-000013"     (string)
email:     ""               (string)
createdAt: [timestamp hiện tại] (number — milliseconds)
updatedAt: [timestamp hiện tại] (number — milliseconds)
```

---

## Troubleshooting — Lỗi thường gặp

### ❌ Android: "Default FirebaseApp is not initialized"
**Nguyên nhân:** `google-services.json` sai hoặc không đúng vị trí  
**Fix:** Đảm bảo file nằm tại `app/google-services.json` (không phải thư mục gốc)

### ❌ Android: Đăng nhập báo "network error"
**Nguyên nhân:** Thiếu internet hoặc cấu hình sai  
**Fix:** Kiểm tra internet. Đảm bảo Email/Password Auth đã bật trong Console.

### ❌ Website: "auth/unauthorized-domain"
**Nguyên nhân:** Domain chưa được thêm vào Authorized domains  
**Fix:** Authentication → Settings → Authorized domains → thêm domain của bạn

### ❌ Website: SMS OTP không nhận được
**Nguyên nhân 1:** Số điện thoại không đúng format (+84...)  
**Nguyên nhân 2:** Vượt quota SMS miễn phí  
**Fix:** Kiểm tra format số. Trong dev, dùng test phone numbers:
1. Firebase Console → Authentication → Sign-in method → Phone
2. Phần **"Phone numbers for testing"** → thêm số test
3. Ví dụ: `+15555555555` với code `123456` — không tốn SMS

### ❌ Firestore: "Missing or insufficient permissions"
**Nguyên nhân:** Security rules chưa deploy hoặc sai  
**Fix:** Chạy lại `firebase deploy --only firestore:rules`  
**Fix tạm (development only):** Đổi rules thành `allow read, write: if true;` để test, nhớ đổi lại sau.

### ❌ Android: Dữ liệu không hiện sau khi đăng nhập
**Nguyên nhân:** Seed data chưa chạy  
**Fix:** 
1. Kiểm tra Firestore Console xem có dữ liệu không
2. Nếu không có → trong `LoyalteApplication.kt`, hàm `seedDatabase()` sẽ tự chạy khi app khởi động lần đầu
3. Đảm bảo Firestore đã bật và rules cho phép write

---

## Tóm tắt các bước theo thứ tự

```
1.  Tạo Firebase Project
2.  Bật Firestore (chọn asia-southeast1)
3.  Deploy firestore.rules
4.  Bật Email/Password Auth
5.  Bật Phone Auth
6.  Tạo tài khoản staff (email/password)
7.  Đăng ký Android app → tải google-services.json
8.  Lấy SHA-1, thêm vào Firebase
9.  Chạy Android app → seed data tự động
10. Đăng ký Web app → lấy config
11. Tạo web/.env.local với config
12. npm install && npm run dev (test local)
13. npm run build → firebase deploy (production)
14. Thêm domain vào Authorized domains
15. Test toàn bộ flow
```

---

## Liên hệ hỗ trợ Firebase

- **Docs:** https://firebase.google.com/docs
- **Status:** https://status.firebase.google.com
- **Pricing:** https://firebase.google.com/pricing (Spark = free tier)
