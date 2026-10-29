<?php
// Hook.php - NovaCMS Event / Hook / Plugin Architecture

class Hook {
    private static array $actions = [];
    private static array $filters = [];

    /**
     * Bir aksiyona (action hook) fonksiyon bağlar
     */
    public static function addAction(string $tag, callable $callback, int $priority = 10): void {
        self::$actions[$tag][$priority][] = $callback;
    }

    /**
     * Aksiyonu tetikler
     */
    public static function doAction(string $tag, ...$args): void {
        if (!isset(self::$actions[$tag])) {
            return;
        }

        ksort(self::$actions[$tag]);
        foreach (self::$actions[$tag] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                call_user_func_array($callback, $args);
            }
        }
    }

    /**
     * Bir filtreye (filter hook) fonksiyon bağlar
     */
    public static function addFilter(string $tag, callable $callback, int $priority = 10): void {
        self::$filters[$tag][$priority][] = $callback;
    }

    /**
     * Veriyi filtrelerden geçirir
     */
    public static function applyFilters(string $tag, mixed $value, ...$args): mixed {
        if (!isset(self::$filters[$tag])) {
            return $value;
        }

        ksort(self::$filters[$tag]);
        foreach (self::$filters[$tag] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                $value = call_user_func_array($callback, array_merge([$value], $args));
            }
        }
        return $value;
    }
}

/**
 * Eklenti Yöneticisi (Plugin Manager)
 */
class PluginManager {
    private static string $pluginsDir = __DIR__ . '/plugins';

    /**
     * Yüklü tüm eklentileri tarar ve metadata bilgilerini döner
     */
    public static function getAllPlugins(): array {
        $plugins = [];
        if (!is_dir(self::$pluginsDir)) {
            mkdir(self::$pluginsDir, 0755, true);
        }

        $items = scandir(self::$pluginsDir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $pluginPath = self::$pluginsDir . '/' . $item;
            $mainFile = $pluginPath . '/plugin.php';

            if (is_dir($pluginPath) && file_exists($mainFile)) {
                $meta = self::parsePluginHeaders($mainFile);
                $plugins[$item] = array_merge([
                    'slug' => $item,
                    'dir' => $pluginPath,
                    'file' => $mainFile,
                    'name' => $item,
                    'version' => '1.0.0',
                    'author' => 'Bilinmiyor',
                    'description' => 'Açıklama belirtilmemiş.'
                ], $meta);
            }
        }
        return $plugins;
    }

    /**
     * plugin.php başındaki yorum satırından eklenti meta bilgilerini ayrıştırır
     */
    private static function parsePluginHeaders(string $file): array {
        $headers = [];
        $content = file_get_contents($file, false, null, 0, 8192); // ilk 8KB

        if (preg_match('/Plugin Name:\s*(.*)$/mi', $content, $match)) $headers['name'] = trim($match[1]);
        if (preg_match('/Description:\s*(.*)$/mi', $content, $match)) $headers['description'] = trim($match[1]);
        if (preg_match('/Version:\s*(.*)$/mi', $content, $match)) $headers['version'] = trim($match[1]);
        if (preg_match('/Author:\s*(.*)$/mi', $content, $match)) $headers['author'] = trim($match[1]);

        return $headers;
    }

    /**
     * Aktif eklentilerin listesini döner
     */
    public static function getActivePlugins(): array {
        $activeJson = getSetting('active_plugins', '[]');
        $list = json_decode($activeJson, true);
        return is_array($list) ? $list : [];
    }

    /**
     * Eklentiyi etkinleştirir
     */
    public static function activatePlugin(string $slug): void {
        $active = self::getActivePlugins();
        if (!in_array($slug, $active)) {
            $active[] = $slug;
            setSetting('active_plugins', json_encode(array_values($active)));
        }
    }

    /**
     * Eklentiyi devre dışı bırakır
     */
    public static function deactivatePlugin(string $slug): void {
        $active = self::getActivePlugins();
        $active = array_filter($active, fn($item) => $item !== $slug);
        setSetting('active_plugins', json_encode(array_values($active)));
    }

    /**
     * Aktif olan tüm eklentileri yükler (boot eder)
     */
    public static function loadActivePlugins(): void {
        $active = self::getActivePlugins();
        foreach ($active as $slug) {
            $file = self::$pluginsDir . '/' . $slug . '/plugin.php';
            if (file_exists($file)) {
                try {
                    require_once $file;
                } catch (\Throwable $e) {
                    error_log("Eklenti yüklenirken hata ($slug): " . $e->getMessage());
                }
            }
        }
    }
}

