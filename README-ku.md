# php-screenshot-api (v1.3.0) — کوردیی سۆرانی

سرویسێکی REST بە PHP بۆ وێنەگرتن (screenshot) لە ماڵپەڕەکان لەسەر Debian 12.

## تایبەتمەندییەکان
- وێنەگرتن لە هەر URL ـێکی گشتی
- دیاری‌کردنی قەبارە (`width`/`height`)
- وێنەی پەڕەی تەواو (`full_page: true`)
- چاوەڕوانی (`delay` چرکە) — لەگەڵ wkhtmltoimage بەهێزترە
- شێوە: **PNG** یان **JPG**
- جۆری وەڵام: **`json`** یان **`binary`**
- پاراستن بە `X-API-Key`
- **پڕۆکسی**: HTTP & SOCKS5 (هاوکاری `username/password`)
- سڕینەوەی خۆکارانەی پەڕگەکانی کۆن (`CLEANUP_TTL` = ٣٦٠٠ چرکە)
- سڕینەوەی سپیارەی خوارەوەی وێنە (`trim_white`, بنەڕەتی `true`)

## پێویستەکان (Debian 12)
```bash
sudo apt update
sudo apt install -y php-fpm php-cli php-gd nginx chromium wkhtmltopdf unzip curl jq
```

## دامەزراندن (Development)
```bash
sudo mkdir -p /var/www/php-screenshot-api
sudo chown -R $USER:www-data /var/www/php-screenshot-api
cd /var/www/php-screenshot-api
php -S 0.0.0.0:8080 -t .
# http://SERVER_IP:8080
```

## ڕێکخستن (`config.php`)
```php
define('API_KEY', 'mysecretapikey123'); // لە پڕۆداکشن بگۆڕە
define('ALLOW_ORIGIN', '*');            // بۆ CORS
define('CLEANUP_TTL', 3600);            // سڕینەوەی پەڕگە کۆنەکان (چرکە)
```

## Nginx (کورتە)
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

## ئەندپۆینتەکان

### `POST /api/screenshot.php`
هێدرەکان:
```
Content-Type: application/json
X-API-Key: <YOUR_API_KEY>
```

پارامێتەرەکان:
- `url` *(پێویستە)* — تەنیا http/https
- `width`, `height`
- `full_page` *(bool)*
- `delay` *(چرکە)*
- `format`: `png` یان `jpg`
- `response`: `json` یان `binary`
- `trim_white` *(bool, بنەڕەتی true)*
- `proxy`: `{ type: "http"|"socks5", host, port, username?, password? }`

نمونەکان:
```bash
# وەڵامی JSON
curl -s -X POST http://YOUR_SERVER/api/screenshot.php   -H 'Content-Type: application/json' -H 'X-API-Key: mysecretapikey123'   -d '{"url":"https://example.com","response":"json"}' | jq .

# وەڵامی Binary -> پاشەکەوت
curl -s -X POST http://YOUR_SERVER/api/screenshot.php   -H 'Content-Type: application/json' -H 'X-API-Key: mysecretapikey123'   -d '{"url":"https://example.com","format":"png","response":"binary"}'   -o screenshot.png

# بە پڕۆکسی SOCKS5 + delay
curl -s -X POST http://YOUR_SERVER/api/screenshot.php   -H 'Content-Type: application/json' -H 'X-API-Key: mysecretapikey123'   -d '{"url":"https://example.com","full_page":true,"delay":3,"proxy":{"type":"socks5","host":"127.0.0.1","port":1080}}' | jq .
```

### `GET /api/image.php?name=<FILENAME>`
داگرتنی وێنەی خەزنکراو.

## ئاسایش
- `API_KEY` لە پڕۆداکشن بگۆڕە و بە secret بەکاربەرە.
- CORS سنووردار بکە (`ALLOW_ORIGIN`).
- Rate limiting و whitelist بەکاربەرەکان لەویستە.
- دەستپێگەیشتنی فرمانە سیستەمەکان سنووردار بکە.

## مۆڵەت
MIT (سەیری `LICENSE` بکە).
