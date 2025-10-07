# PHP-Screenshot-API (English)

A lightweight PHP REST API that captures website screenshots.

## ✨ Features
- Capture any public URL
- Custom width & height
- Full-page screenshots (`full_page: true`)
- Adjustable delay before capture (`delay`, seconds)
- Output formats: **PNG** and **JPG**
- Auto-delete old files (> 1 hour)
- Simple API key auth (`X-API-Key`)
- Select response type: **JSON** or **binary image**
- MIT License

---

## Requirements
```bash
sudo apt update
sudo apt install -y php-fpm php-cli php-gd nginx chromium wkhtmltopdf unzip curl jq
```

---

## Quick Start (Development)
```bash
sudo mkdir -p /var/www/php-screenshot-api
sudo chown -R $USER:www-data /var/www/php-screenshot-api
# copy the project here
cd /var/www/php-screenshot-api
php -S 0.0.0.0:8080 -t .
# access at http://SERVER_IP:8080
```

### Deploy with Nginx + PHP-FPM
- See `nginx.example.conf`. Copy it to `/etc/nginx/sites-available/php-screenshot-api`
- Symlink to `sites-enabled`, then:
```bash
sudo nginx -t && sudo systemctl reload nginx
```

---

## Authentication
Send the API key via header:
```
X-API-Key: mysecretapikey123
```
Update the key in `config.php` for production.

---

## Endpoints

### 1) `POST /api/screenshot.php`
**Headers:**
```
Content-Type: application/json
X-API-Key: mysecretapikey123
```

**Body (example):**
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

**Sample (JSON):**
```bash
curl -s -X POST http://YOUR_SERVER/api/screenshot.php   -H 'Content-Type: application/json'   -H 'X-API-Key: mysecretapikey123'   -d '{"url":"https://example.com","response":"json"}' | jq .
```

**Sample (Binary):**
```bash
curl -s -X POST http://YOUR_SERVER/api/screenshot.php   -H 'Content-Type: application/json'   -H 'X-API-Key: mysecretapikey123'   -d '{"url":"https://example.com","format":"png","response":"binary"}'   -o screenshot.png
```

**Success (JSON):**
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

### 2) `GET /api/image.php?name=...`
Download a stored screenshot:
```
GET /api/image.php?name=screenshot_*.png|jpg
```

---

## Auto Cleanup
Before each capture, files older than `CLEANUP_TTL` (default 3600s) in `storage/` are deleted.

---

## Security Notes
- Change `API_KEY` and use environment secrets in production.
- Add rate limiting, origin/domain whitelisting as needed.
- Restrict system command execution permissions.

---

## Technical Notes
- Chromium `--screenshot` grabs the visible viewport. To approximate **full page**, the window height is increased (up to 20,000px). Extremely tall pages may still be clipped.
- `wkhtmltoimage` is used as a fallback and respects the output extension.
- Install necessary fonts on the server for best rendering.
