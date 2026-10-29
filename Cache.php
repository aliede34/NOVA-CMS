<?php
// Cache.php - NovaCMS Ultra-fast File-based Cache Engine

class Cache {
    private static string $cacheDir = __DIR__ . '/cache/';
    private static int $defaultTtl = 600; // 10 dakika

    private static function init(): void {
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
    }

    private static function getFilePath(string $key): string {
        return self::$cacheDir . md5($key) . '.cache';
    }

    /**
     * Önbellekten veri oku
     */
    public static function get(string $key): mixed {
        self::init();
        $file = self::getFilePath($key);
        if (!file_exists($file)) {
            return null;
        }

        $content = file_get_contents($file);
        if (!$content) return null;

        $data = unserialize($content);
        if (!$data || !is_array($data) || !isset($data['expires']) || !isset($data['val'])) {
            return null;
        }

        if (time() > $data['expires']) {
            @unlink($file);
            return null;
        }

        return $data['val'];
    }

    /**
     * Önbelleğe veri yaz
     */
    public static function set(string $key, mixed $value, int $ttl = 0): void {
        self::init();
        if ($ttl <= 0) $ttl = self::$defaultTtl;

        $data = [
            'expires' => time() + $ttl,
            'val' => $value
        ];

        file_put_contents(self::getFilePath($key), serialize($data), LOCK_EX);
    }

    /**
     * Belirli bir anahtarı sil
     */
    public static function delete(string $key): void {
        $file = self::getFilePath($key);
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    /**
     * Tüm önbelleği temizle
     */
    public static function clear(): void {
        self::init();
        $files = glob(self::$cacheDir . '*.cache');
        if ($files) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }

    /**
     * Önbellek dosya sayısı ve toplam boyutunu döner
     */
    public static function stats(): array {
        self::init();
        $files = glob(self::$cacheDir . '*.cache') ?: [];
        $totalSize = 0;
        foreach ($files as $f) {
            $totalSize += filesize($f);
        }
        return [
            'count' => count($files),
            'size' => $totalSize
        ];
    }
}

