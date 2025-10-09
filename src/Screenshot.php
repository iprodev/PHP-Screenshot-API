<?php
namespace Screenshot;

class Screenshot {
    private int $width = 1280;
    private int $height = 800;
    private int $timeout = 20000; // ms
    private int $delaySec = 0;    // seconds
    private string $format = 'png'; // png|jpeg
    private bool $fullPage = false;

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

    public function capture(string $url, string $outputPath): bool {
        $dir = dirname($outputPath);
        if (!is_dir($dir)) { mkdir($dir, 0775, true); }

        $escapedUrl = escapeshellarg($url);
        $escapedOut = escapeshellarg($outputPath);

        // Prefer Chromium/Chrome
        $chrome = $this->findChrome();
        if ($chrome) {
            $winW = $this->width;
            $winH = $this->fullPage ? max($this->height, 20000) : $this->height;
            $windowSize = $winW . "," . $winH;
            $delayMs = $this->delaySec * 1000;
            $delayFlag = $delayMs > 0 ? (" --virtual-time-budget=" . (int)$delayMs) : "";
            $cmds = [
                sprintf("%s --headless=new --disable-gpu --hide-scrollbars --no-sandbox --window-size=%s%s --screenshot=%s %s 2>&1",
                        escapeshellcmd($chrome), $windowSize, $delayFlag, $escapedOut, $escapedUrl),
                sprintf("%s --headless --disable-gpu --hide-scrollbars --no-sandbox --window-size=%s%s --screenshot=%s %s 2>&1",
                        escapeshellcmd($chrome), $windowSize, $delayFlag, $escapedOut, $escapedUrl),
            ];
            foreach ($cmds as $cmd) {
                $out = []; $ret = 0;
                exec($cmd, $out, $ret);
                if ($ret === 0 && file_exists($outputPath)) {
                    $this->ensureFormat($outputPath, $this->format);
                    return true;
                }
            }
        }

        // Fallback: wkhtmltoimage
        $wk = trim(shell_exec('which wkhtmltoimage 2>/dev/null')) ?: null;
        if ($wk) {
            $args = [
                '--enable-javascript',
                '--javascript-delay ' . ($this->delaySec * 1000),
                '--width ' . $this->width,
            ];
            if (!$this->fullPage) {
                $args[] = '--height ' . $this->height;
            }
            $cmd = sprintf(
                "%s %s %s %s 2>&1",
                escapeshellcmd($wk),
                implode(' ', $args),
                $escapedUrl,
                $escapedOut
            );
            $out = []; $ret = 0;
            exec($cmd, $out, $ret);
            if ($ret === 0 && file_exists($outputPath)) {
                $this->ensureFormat($outputPath, $this->format);
                return true;
            }
        }

        return false;
    }

    private function ensureFormat(string $path, string $fmt): void {
        if (!function_exists('imagecreatefromstring')) return;
        $data = @file_get_contents($path);
        if ($data === false) return;
        $img = @imagecreatefromstring($data);
        if (!$img) return;
        if ($fmt === 'png') {
            @imagepng($img, $path);
        } else {
            @imagejpeg($img, $path, 85);
        }
        imagedestroy($img);
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
