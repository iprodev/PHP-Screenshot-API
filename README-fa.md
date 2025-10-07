# PHP-Screenshot-API (فارسی)

یک REST API سبک با PHP برای گرفتن اسکرین‌شات از هر URL.

## ✨ ویژگی‌ها
- 📸 اسکرین‌شات از هر URL
- ⚙️ تعیین ابعاد (`width`, `height`)
- 🌐 **تمام صفحه** (`full_page: true`)
- 🕒 **تاخیر** قبل از عکس‌گرفتن (`delay` بر حسب ثانیه)
- 🧱 فرمت خروجی: **PNG** یا **JPG**
- 🧹 حذف خودکار فایل‌های قدیمی (بیش از ۱ ساعت)
- 🔐 احراز هویت با `X-API-Key`
- 🔄 پاسخ **JSON** یا **باینری تصویر**
- ⚖️ مجوز MIT

---

## 🧰 پیش‌نیازها
```bash
sudo apt update
sudo apt install -y php-fpm php-cli php-gd nginx chromium wkhtmltopdf unzip curl jq
```
> `php-gd` برای تبدیل PNG→JPG لازم است.

---

## 🚀 راه‌اندازی سریع (Development)
```bash
sudo mkdir -p /var/www/php-screenshot-api
sudo chown -R $USER:www-data /var/www/php-screenshot-api
# فایل‌های پروژه را کپی کنید
cd /var/www/php-screenshot-api
php -S 0.0.0.0:8080 -t .
# دسترسی: http://SERVER_IP:8080
```

### استقرار با Nginx + PHP-FPM
- فایل `nginx.example.conf` را در `/etc/nginx/sites-available/php-screenshot-api` قرار دهید.
- لینک به `sites-enabled` بسازید، سپس:
```bash
sudo nginx -t && sudo systemctl reload nginx
```
> اگر نسخه PHP شما متفاوت است، مسیر `fastcgi_pass` را ویرایش کنید (مثلاً `php8.2-fpm.sock`).

---

## 🔐 احراز هویت
تمام درخواست‌ها باید هدر زیر را داشته باشند:
```
X-API-Key: mysecretapikey123
```
> می‌توانید مقدار آن را در `config.php` تغییر دهید.

---

## 📡 Endpoint ها

### 1) `POST /api/screenshot.php`
**Headers:**
```
Content-Type: application/json
X-API-Key: mysecretapikey123
```

**Body مثال:**
```json
{
  "url": "https://example.com",
  "width": 1366,
  "height": 900,
  "full_page": true,
  "delay": 2,
  "format": "jpg",
  "response": "json"
}
```

**پاسخ JSON موفق:**
```json
{
  "success": true,
  "filename": "screenshot_1730900000_ab12cd34ef.jpg",
  "deleted_old_files": 3,
  "meta": {
    "width": 1366,
    "height": 900,
    "full_page": true,
    "delay": 2,
    "format": "jpg"
  },
  "base64": "iVBORw0KGgoAAAANS..."
}
```

**نمونه cURL (JSON):**
```bash
curl -s -X POST http://YOUR_SERVER/api/screenshot.php   -H 'Content-Type: application/json'   -H 'X-API-Key: mysecretapikey123'   -d '{"url":"https://example.com","response":"json"}' | jq .
```

**نمونه cURL (Binary):**
```bash
curl -s -X POST http://YOUR_SERVER/api/screenshot.php   -H 'Content-Type: application/json'   -H 'X-API-Key: mysecretapikey123'   -d '{"url":"https://example.com","format":"png","response":"binary"}'   -o screenshot.png
```

### 2) `GET /api/image.php?name=...`
دانلود مستقیم فایل ذخیره‌شده:
```
GET /api/image.php?name=screenshot_*.png|jpg
```

---

## 🧹 حذف خودکار
قبل از هر اسکرین‌شات جدید، فایل‌های داخل `storage/` که بیش از مقدار `CLEANUP_TTL` (پیش‌فرض ۳۶۰۰ ثانیه) از آخرین تغییرشان گذشته باشد حذف می‌شوند.

---

## 🛡️ نکات امنیتی
- کلید `API_KEY` را در تولید تغییر دهید و ترجیحاً از متغیر محیطی/secret استفاده کنید.
- Rate limiting و whitelist دامنه‌ها توصیه می‌شود.
- اجرای فرمان‌های سیستمی امن‌سازی شود (AppArmor/SELinux، محدودیت دسترسی).

---

## 🧪 نکات فنی
- Chromium با `--screenshot` معمولاً نمای قابل‌مشاهده را ذخیره می‌کند؛ برای **full_page** ارتفاع پنجره بزرگ تنظیم می‌شود (تا ۲۰۰۰۰px). در صفحات بسیار بلند ممکن است محدودیت وجود داشته باشد.
- `wkhtmltoimage` به‌عنوان fallback استفاده می‌شود و با پسوند خروجی کار می‌کند.
- فونت‌های لازم را روی سرور نصب کنید تا رندر بهتری بگیرید.

موفق باشید ✨
