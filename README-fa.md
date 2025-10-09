# PHP-Screenshot-API — فارسی

یک REST API سبک با PHP برای گرفتن اسکرین‌شات از هر URL.

## ویژگی‌ها
- اسکرین‌شات از هر URL عمومی
- تعیین ابعاد دلخواه (عرض/ارتفاع)
- اسکرین‌شات تمام صفحه (`full_page: true`)
- تاخیر قابل تنظیم (`delay` بر حسب ثانیه) — با `wkhtmltoimage` قابل پیش‌بینی‌تر
- فرمت خروجی: **PNG** یا **JPG**
- نوع پاسخ: **`json`** یا **`binary`**
- احراز هویت با هدر `X-API-Key`
- **پشتیبانی پراکسی**: HTTP و SOCKS5 (با `username/password` اختیاری)
- پاکسازی خودکار فایل‌های قدیمی (`CLEANUP_TTL`، پیش‌فرض ۳۶۰۰ ثانیه)
- حذف نوار سفید پایین تصویر (`trim_white`، پیش‌فرض `true`)

## پیش‌نیازها (Debian 12)
```bash
sudo apt update
sudo apt install -y php-fpm php-cli php-gd nginx chromium wkhtmltopdf unzip curl jq
```

## راه‌اندازی (Development)
```bash
sudo mkdir -p /var/www/php-screenshot-api
sudo chown -R $USER:www-data /var/www/php-screenshot-api
cd /var/www/php-screenshot-api
php -S 0.0.0.0:8080 -t .
# http://SERVER_IP:8080
```

## تنظیمات (`config.php`)
```php
define('API_KEY', 'mysecretapikey123'); // در تولید تغییر دهید
define('ALLOW_ORIGIN', '*');            // CORS (مثلاً https://example.com)
define('CLEANUP_TTL', 3600);            // حذف فایل‌های قدیمی‌تر از این مقدار (ثانیه)
```

## Nginx (نمونه)
```nginx
server {
  listen 80;
  server_name _;
  root /var/www/php-screenshot-api;
  index index.php;
  location /api/ { try_files $uri $uri/ /api/screenshot.php; }
  location ~ \.php$ {
    include snippets/fastcgi-php.conf;
    fastcgi_pass unix:/run/php/php8.2-fpm.sock;
  }
}
```

## Endpointها

### `POST /api/screenshot.php`
هدرها:
```
Content-Type: application/json
X-API-Key: <YOUR_API_KEY>
```

پارامترهای بدنه (JSON):
- `url` *(الزامی)* — فقط http/https
- `width`, `height` *(اختیاری)*
- `full_page` *(bool)*
- `delay` *(ثانیه)*
- `format`: `png` یا `jpg` (پیش‌فرض `png`)
- `response`: `json` یا `binary` (پیش‌فرض `json`)
- `trim_white` *(bool، پیش‌فرض true)*
- `proxy` *(object)*: `{ type: "http"|"socks5", host, port, username?, password? }`

نمونه‌ها:
```bash
# پاسخ JSON
curl -s -X POST http://YOUR_SERVER/api/screenshot.php   -H 'Content-Type: application/json' -H 'X-API-Key: mysecretapikey123'   -d '{"url":"https://example.com","response":"json"}' | jq .

# پاسخ باینری -> ذخیره فایل
curl -s -X POST http://YOUR_SERVER/api/screenshot.php   -H 'Content-Type: application/json' -H 'X-API-Key: mysecretapikey123'   -d '{"url":"https://example.com","format":"png","response":"binary"}'   -o screenshot.png

# با پراکسی SOCKS5 + تاخیر
curl -s -X POST http://YOUR_SERVER/api/screenshot.php   -H 'Content-Type: application/json' -H 'X-API-Key: mysecretapikey123'   -d '{"url":"https://example.com","full_page":true,"delay":3,"proxy":{"type":"socks5","host":"127.0.0.1","port":1080}}' | jq .
```

### `GET /api/image.php?name=<FILENAME>`
دانلود تصویر ذخیره‌شده.

## امنیت
- `API_KEY` را در تولید تغییر دهید (ENV/Secrets).
- CORS (`ALLOW_ORIGIN`) را محدود کنید.
- Rate limiting و whitelist دامنه‌ها را مدنظر داشته باشید.
- دسترسی اجرای دستورهای سیستمی برای کاربر سرویس را محدود کنید.

## مجوز
MIT (فایل `LICENSE`).
