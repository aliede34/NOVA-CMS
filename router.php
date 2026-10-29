<?php
// router.php - PHP Dahili Sunucusu İçin Güvenlik Router'ı
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// 1. Doğrudan erişilmemesi gereken dosya ve dizinler
$blockedPatterns = [
    '/\.sqlite$/i',
    '/\.cache$/i',
    '/\.env$/i',
    '/\.lock$/i',
    '/^\/cache\//i',
    '/^\/tests\//i',
    '/^\/uploads\/.*\.php$/i', // uploads içinde php çalıştırılamaz
];

foreach ($blockedPatterns as $pattern) {
    if (preg_match($pattern, $uri)) {
        http_response_code(403);
        die('403 Forbidden - Bu dosyaya veya dizine doğrudan web erişimi yasaktır.');
    }
}

// 2. Eğer dosya mevcutsa ve yasaklı değilse normal sun
$path = __DIR__ . $uri;
if ($uri !== '/' && file_exists($path) && !is_dir($path)) {
    return false; // PHP dahili sunucusunun dosyayı normal servis etmesini sağlar
}

// 3. Varsayılan index yönlendirmesi
if ($uri === '/' || is_dir($path)) {
    require_once __DIR__ . '/index.php';
    return true;
}

return false;

