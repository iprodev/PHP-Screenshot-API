<?php
namespace Screenshot;

class Screenshot {
    private int $width = 1280;
    private int $height = 800;
    private int $timeout = 20000; // ms
    private int $delaySec = 0;    // seconds
    private string $format = 'png'; // png|jpeg
    private bool $fullPage = false;
    private bool $trimWhite = true;

    private ?array $proxy = null; // ['type'=>'http|socks5','host'=>'','port'=>0,'user'=>null,'pass'=>null]

    public function setViewport(int $w, int $h): void {
        $this->width = max(200, min(3840, $w));
        $this->height = max(200, min(20000, $h));
    }
    public function setTimeout(int $ms): void { $this->timeout = max(1000, $ms); }
    public function setDelay(int $sec): void { $this->delaySec = max(0, $sec); }
    public function setFormat(string $fmt): void {
        $fmt = strtolower($fmt);
        if ($fmt === 'jpg') $fmt = 'jpeg';
        if (in_array($fmt, ['png','jpeg'], true)) $this->format = $fmt;
    }
    public function setFullPage(bool $full): void { $this->fullPage = $full; }
    public function setTrimWhite(bool $t): void { $this->trimWhite = $t; }

    public function setProxy(?string $type, ?string $host, ?int $port, ?string $user=null, ?string $pass=null): void {
        if (!$type || !$host || !$port) { $this->proxy = null; return; }
        $type = strtolower($type);
        if (!in_array($type, ['http','socks5'], true)) { $this->proxy = null; return; }
        $this->proxy = [
            'type' => $type,
            'host' => $host,
            'port' => (int)$port,
            'user' => $user ?: null,
            'pass' => $pass ?: null,
        ];
    }

    private function buildProxyUrl(): ?string {
        if (!$this->proxy) return null;
        $scheme = $this->proxy['type'];
        $host = $this->proxy['host'];
        $port = (int)$this->proxy['port'];
        $auth = '';
        if (!empty($this->proxy['user']) && !empty($this->proxy['pass'])) {
            $auth = rawurlencode($this->proxy['user']) . ':' . rawurlencode($this->proxy['pass']) . '@';
        }
        return sprintf('%s://%s%s:%d', $scheme, $auth, $host, $port);
    }

    public function capture(string $url, string $outputPath): bool {
        $dir = dirname($outputPath);
        if (!is_dir($dir)) { mkdir($dir, 0775, true); }

        $escapedUrl = escapeshellarg($url);
        $escapedOut = escapeshellarg($outputPath);

        $proxyUrl = $this->buildProxyUrl();
        $envPrefix = '';
        if ($proxyUrl) {
            $e = escapeshellarg($proxyUrl);
            $envPrefix = "HTTP_PROXY=$e HTTPS_PROXY=$e ALL_PROXY=$e ";
        }

        // Prefer wkhtmltoimage when delay > 0 or full page requested (more predictable waits)
        $wk = trim(shell_exec('which wkhtmltoimage 2>/dev/null')) ?: null;
        $preferWkhtml = ($this->delaySec > 0 || $this->fullPage) && $wk;

        if ($preferWkhtml) {
            if ($this->runWkhtml($wk, $envPrefix, $proxyUrl, $escapedUrl, $escapedOut)) {
                $this->postProcess($outputPath);
                return true;
            }
            // fall back to chrome if wkhtml fails
        }

        $chrome = $this->findChrome();
        if ($chrome) {
            if ($this->runChrome($chrome, $envPrefix, $proxyUrl, $escapedUrl, $escapedOut)) {
                $this->postProcess($outputPath);
                return true;
            }
        }

        if (!$preferWkhtml && $wk) {
            if ($this->runWkhtml($wk, $envPrefix, $proxyUrl, $escapedUrl, $escapedOut)) {
                $this->postProcess($outputPath);
                return true;
            }
        }

        return false;
    }

    private function runChrome(string $chrome, string $envPrefix, ?string $proxyUrl, string $escapedUrl, string $escapedOut): bool {
        $winW = $this->width;
        $winH = $this->fullPage ? max($this->height, 20000) : $this->height;
        $windowSize = $winW . "," . $winH;
        $delayMs = $this->delaySec * 1000;
        $delayFlag = $delayMs > 0 ? (" --virtual-time-budget=" . (int)$delayMs) : "";
        $proxyFlag = $proxyUrl ? (" --proxy-server=" . escapeshellarg($proxyUrl) . " --proxy-bypass-list=" . escapeshellarg("<-loopback>")) : "";
        $common = " --disable-gpu --hide-scrollbars --no-sandbox --no-first-run --no-default-browser-check --force-device-scale-factor=1 --default-background-color=00000000 ";
        $cmds = [
            $envPrefix . sprintf("%s --headless=new %s --window-size=%s%s%s --screenshot=%s %s 2>&1",
                    escapeshellcmd($chrome), $common, $windowSize, $delayFlag, $proxyFlag, $escapedOut, $escapedUrl),
            $envPrefix . sprintf("%s --headless %s --window-size=%s%s%s --screenshot=%s %s 2>&1",
                    escapeshellcmd($chrome), $common, $windowSize, $delayFlag, $proxyFlag, $escapedOut, $escapedUrl),
        ];
        foreach ($cmds as $cmd) {
            $out = []; $ret = 0;
            exec($cmd, $out, $ret);
            if ($ret === 0 && file_exists(trim($escapedOut, "'"))) {
                return true;
            }
        }
        return false;
    }

    private function runWkhtml(string $wk, string $envPrefix, ?string $proxyUrl, string $escapedUrl, string $escapedOut): bool {
        $args = [
            '--enable-javascript',
            '--javascript-delay ' . ($this->delaySec * 1000),
            '--width ' . $this->width,
            '--quality 90'
        ];
        if (!$this->fullPage) {
            $args[] = '--height ' . $this->height;
        }
        if ($proxyUrl) {
            $args[] = '--proxy ' . escapeshellarg($proxyUrl);
        }
        $cmd = $envPrefix . sprintf(
            "%s %s %s %s 2>&1",
            escapeshellcmd($wk),
            implode(' ', $args),
            $escapedUrl,
            $escapedOut
        );
        $out = []; $ret = 0;
        exec($cmd, $out, $ret);
        return ($ret === 0 && file_exists(trim($escapedOut, "'")));
    }

    private function postProcess(string $outputPath): void {
        if ($this->trimWhite) {
            $this->trimBottomWhitespace($outputPath);
        }
        if ($this->format === 'jpeg') {
            $this->convertToJpeg($outputPath);
        }
    }

    private function trimBottomWhitespace(string $path): void {
        if (!function_exists('imagecreatefromstring')) return;
        $data = @file_get_contents($path);
        if ($data === false) return;
        $img = @imagecreatefromstring($data);
        if (!$img) return;
        $w = imagesx($img);
        $h = imagesy($img);
        if ($w <= 0 || $h <= 0) { imagedestroy($img); return; }

        $threshold = 248; // near white
        $step = max(1, (int)($w / 200));
        $newH = $h;
        for ($y = $h - 1; $y >= 0; $y--) {
            $rowEmpty = true;
            for ($x = 0; $x < $w; $x += $step) {
                $rgb = imagecolorat($img, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                if (!($r >= $threshold && $g >= $threshold && $b >= $threshold)) {
                    $rowEmpty = false; break;
                }
            }
            if (!$rowEmpty) { $newH = $y + 1; break; }
        }

        if ($newH < $h - 1 && $newH > 10) {
            $cropped = imagecrop($img, ['x' => 0, 'y' => 0, 'width' => $w, 'height' => $newH]);
            if ($cropped !== false) {
                imagealphablending($cropped, false);
                imagesavealpha($cropped, true);
                imagepng($cropped, $path);
                imagedestroy($cropped);
            }
        }
        imagedestroy($img);
    }

    private function convertToJpeg(string $path): void {
        if (!function_exists('imagecreatefromstring')) return;
        $data = @file_get_contents($path);
        if ($data === false) return;
        $img = @imagecreatefromstring($data);
        if (!$img) return;
        @imagejpeg($img, $path, 85);
        imagedestroy($img);
        if (substr($path, -4) !== '.jpg') {
            $new = preg_replace('/\.(png|jpeg)$/i', '.jpg', $path);
            if ($new && $new != $path) @rename($path, $new);
        }
    }

    private function findChrome(): ?string {
        $bins = ['chromium', 'chromium-browser', 'google-chrome', 'google-chrome-stable', '/usr/bin/chromium'];
        foreach ($bins as $b) {
            $which = trim(shell_exec('which ' . escapeshellarg($b) . ' 2>/dev/null'));
            if ($which) return $which;
        }
        return null;
    }
}
