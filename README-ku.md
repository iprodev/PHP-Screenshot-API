# PHP-Screenshot-API (کوردی)

REST API ـێکی سووک بە PHP بۆ وێنەگرتن لە هەر URL ـێک.

## ✨ تایبەتمەندییەکان
- 📸 وێنەگرتن لە هەر URL ـێک
- ⚙️ دیاریکردنی قەبارە (`width`, `height`)
- 🌐 **پەڕەی تەواو** (`full_page: true`)
- 🕒 **دواخستن** پێش وێنەگرتن (`delay` بە چرکە)
- 🧱 جۆری دەرچوون: **PNG** یان **JPG**
- 🧹 سڕینەوەی خۆکارانەی فایلە کۆنەکان (زیاتر لە ۱ کاتژمێر)
- 🔐 ناسنامەکردن بە `X-API-Key`
- 🔄 وەڵام: **JSON** یان **وێنەی binary**
- ⚖️ مۆڵەتی MIT

---

## 🧰 پێویستییەکان
```bash
sudo apt update
sudo apt install -y php-fpm php-cli php-gd nginx chromium wkhtmltopdf unzip curl jq
```
> `php-gd` بۆ گۆڕینی PNG→JPG پێویستە.

---

## 🚀 دەستپێکردنی خێرا (پەرەپێدانی)
```bash
sudo mkdir -p /var/www/php-screenshot-api
sudo chown -R $USER:www-data /var/www/php-screenshot-api
# فایلەکانی پڕۆژە بکۆپە
cd /var/www/php-screenshot-api
php -S 0.0.0.0:8080 -t .
# دەستگەیشتن: http://SERVER_IP:8080
```

### دامەزراندن بە Nginx + PHP-FPM
- فایلی `nginx.example.conf` بخە ئەم شوێنە: `/etc/nginx/sites-available/php-screenshot-api`
- بەستەرێک بۆ `sites-enabled` دروست بکە، پاشان:
```bash
sudo nginx -t && sudo systemctl reload nginx
```
> ئەگەر وەشانی PHP ـەکەت جیاوازە، ڕێڕەوی `fastcgi_pass` بگۆڕە (وەک `php8.2-fpm.sock`).

---

## 🔐 ناسنامەکردن
هەموو داواکارییەکان دەبێت ئەم هەدەرەی خوارەوە هەبێت:
```
X-API-Key: mysecretapikey123
```
> دەتوانیت نرخی ئەمە لە `config.php` بگۆڕیت.

---

## 📡 Endpoint ـەکان

### 1) `POST /api/screenshot.php`
**Headers:**
```
Content-Type: application/json
X-API-Key: mysecretapikey123
```

**Body ـی نموونە:**
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

**وەڵامی JSON ـی سەرکەوتوو:**
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

**نمونەی cURL (JSON):**
```bash
curl -s -X POST http://YOUR_SERVER/api/screenshot.php   -H 'Content-Type: application/json'   -H 'X-API-Key: mysecretapikey123'   -d '{"url":"https://example.com","response":"json"}' | jq .
```

**نمونەی cURL (Binary):**
```bash
curl -s -X POST http://YOUR_SERVER/api/screenshot.php   -H 'Content-Type: application/json'   -H 'X-API-Key: mysecretapikey123'   -d '{"url":"https://example.com","format":"png","response":"binary"}'   -o screenshot.png
```

### 2) `GET /api/image.php?name=...`
داگرتنی ڕاستەوخۆی فایلی هەڵگیراو:
```
GET /api/image.php?name=screenshot_*.png|jpg
```

---

## 🧹 سڕینەوەی خۆکار
پێش هەر وێنەگرتنێکی نوێ، ئەو فایلانەی لە `storage/` ـاندا کە زیاتر لە `CLEANUP_TTL` (بە بنەڕەت ۳۶۰۰ چرکە) لە کاتی دوایین گۆڕانکارییاندە تێپەڕیوە، سڕدرێنەوە.

---

## 🛡️ تێبینییەکانی ئاسایش
- کلیلی `API_KEY` لە نیشاندانی بنەڕەتی بگۆڕە و باشترە بەکارهێنانی متغیەری ژینگە/secret.
- سنووردانی نرخ (Rate limiting) و ڕیزکردنی دۆمەینە ڕێگەپێدراوەکان پێشنیار دەکرێت.
- جێبەجێکردنی فرمانە سیستەمییەکان دەبێت پارێزراو بێت (AppArmor/SELinux، سنووردانی دەستگەیشتن).

---

## 🧪 تێبینییە تەکنیکییەکان
- Chromium بە `--screenshot` زۆرجار تەنها بەشێکی دیاریکراوی پەڕەیەک دەگرێت؛ بۆ **full_page** بەرزی پەنجەرە زیاد دەکرێت (تا ۲۰۰۰۰px). لە پەڕەی زۆر درێژەکاندا ڕەنگە سنوورداری هەبێت.
- `wkhtmltoimage` وەک fallback بەکاردێت و گەورەکراوی دەرچوون بە پشتەوەی فایلەکە کاردەکات.
- فۆنتە پێویستەکان لەسەر ڕاژەکار دامەزرێنە بۆ ئەوەی رێندەرکردن باشتر بێت.

سەرکەوتوو بن ✨
