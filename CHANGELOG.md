# Changelog

## 1.2.0
- Fix: `delay` now applied reliably (prefers wkhtmltoimage when `delay>0` or `full_page=true`; falls back to Chromium).
- Fix: removed bottom white strip using Chromium `--default-background-color=00000000` + post-process trim.
- Feat: `trim_white` (default: true) to auto-trim empty bottom area.
- Keep: Proxy support (HTTP/SOCKS5) with optional auth; JSON/Binary responses; auto-cleanup.
- Proxy support (HTTP & SOCKS5) with optional username/password.

## 1.0.1
- Fixed PHP 8 fatal (string + int).

## 1.0.0
- Initial release.
