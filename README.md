# PHP-Screenshot-API (English)

A lightweight PHP REST API to capture website screenshots.

## Features
- Capture from any public URL
- Custom width & height
- Full‑page capture (`full_page: true`)
- Adjustable `delay` (seconds) — predictable with wkhtmltoimage
- Output formats: **PNG** / **JPG**
- Response type: **`json`** or **`binary`**
- API key auth via `X-API-Key`
- **Proxy support**: HTTP & SOCKS5 (optional `username/password`)
- Auto‑cleanup of old files (`CLEANUP_TTL`, default 3600s)
- Trim bottom white strip (`trim_white`, default `true`)

## Requirements (Debian 12)
```bash
sudo apt update
sudo apt install -y php-fpm php-cli php-gd nginx chromium wkhtmltopdf unzip curl jq
```

## Setup (Development)
```bash
sudo mkdir -p /var/www/php-screenshot-api
sudo chown -R $USER:www-data /var/www/php-screenshot-api
cd /var/www/php-screenshot-api
php -S 0.0.0.0:8080 -t .
# http://SERVER_IP:8080
```

## Configuration (`config.php`)
```php
define('API_KEY', 'mysecretapikey123'); // change in production
define('ALLOW_ORIGIN', '*');            // CORS origin (e.g., https://example.com)
define('CLEANUP_TTL', 3600);            // delete files older than this (seconds)
```

## Nginx (snippet)
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

## Endpoints

### `POST /api/screenshot.php`
Headers:
```
Content-Type: application/json
X-API-Key: <YOUR_API_KEY>
```

Body parameters:
- `url` *(required)* — http/https only
- `width`, `height` *(optional)*
- `full_page` *(bool)*
- `delay` *(seconds)*
- `format`: `png` or `jpg` (default `png`)
- `response`: `json` or `binary` (default `json`)
- `trim_white` *(bool, default true)*
- `proxy` *(object)*: `{ type: "http"|"socks5", host, port, username?, password? }`

Examples:
```bash
# JSON response
curl -s -X POST http://YOUR_SERVER/api/screenshot.php   -H 'Content-Type: application/json' -H 'X-API-Key: mysecretapikey123'   -d '{"url":"https://example.com","response":"json"}' | jq .

# Binary response -> file
curl -s -X POST http://YOUR_SERVER/api/screenshot.php   -H 'Content-Type: application/json' -H 'X-API-Key: mysecretapikey123'   -d '{"url":"https://example.com","format":"png","response":"binary"}'   -o screenshot.png

# With SOCKS5 proxy + delay
curl -s -X POST http://YOUR_SERVER/api/screenshot.php   -H 'Content-Type: application/json' -H 'X-API-Key: mysecretapikey123'   -d '{"url":"https://example.com","full_page":true,"delay":3,"proxy":{"type":"socks5","host":"127.0.0.1","port":1080}}' | jq .
```

### `GET /api/image.php?name=<FILENAME>`
Downloads a stored image.

## Security
- Change `API_KEY` and keep it secret (ENV/secrets).
- Restrict CORS (`ALLOW_ORIGIN`).
- Consider rate limiting & domain whitelists.
- Limit system command permissions for the service user.

## License
MIT (see `LICENSE`).
