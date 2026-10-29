<?php
// config.php - Veritabanı ve genel ayarlar

define('DB_FILE', __DIR__ . '/database.sqlite');
define('SITE_TITLE', 'NovaCMS - Modern PHP Content Management System');
define('SITE_URL', 'http://localhost:8000');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', 'uploads/');

require_once __DIR__ . '/Hook.php';
require_once __DIR__ . '/Cache.php';

function getDB(): PDO {
    static $db = null;
    if ($db === null) {
        $db = new PDO('sqlite:' . DB_FILE);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
    return $db;
}

function initDB(): void {
    $db = getDB();
    
    // Kullanıcılar
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        email TEXT UNIQUE,
        role TEXT DEFAULT 'author',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Kategoriler
    $db->exec("CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        slug TEXT UNIQUE NOT NULL
    )");

    // Etiketler
    $db->exec("CREATE TABLE IF NOT EXISTS tags (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        slug TEXT UNIQUE NOT NULL
    )");

    // Yazı - Etiket İlişkisi
    $db->exec("CREATE TABLE IF NOT EXISTS post_tags (
        post_id INTEGER NOT NULL,
        tag_id INTEGER NOT NULL,
        PRIMARY KEY (post_id, tag_id),
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
        FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
    )");

    // Yazılar
    $db->exec("CREATE TABLE IF NOT EXISTS posts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        slug TEXT UNIQUE NOT NULL,
        content TEXT NOT NULL,
        featured_image TEXT,
        category_id INTEGER,
        user_id INTEGER,
        status TEXT DEFAULT 'published',
        views_count INTEGER DEFAULT 0,
        likes_count INTEGER DEFAULT 0,
        read_time INTEGER DEFAULT 1,
        meta_description TEXT,
        meta_keywords TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Sayfalar
    $db->exec("CREATE TABLE IF NOT EXISTS pages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        slug TEXT UNIQUE NOT NULL,
        content TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Yorumlar
    $db->exec("CREATE TABLE IF NOT EXISTS comments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        post_id INTEGER NOT NULL,
        author_name TEXT NOT NULL,
        author_email TEXT NOT NULL,
        content TEXT NOT NULL,
        status TEXT DEFAULT 'approved',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
    )");

    // Gelen İletişim Mesajları
    $db->exec("CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL,
        subject TEXT NOT NULL,
        message TEXT NOT NULL,
        is_read INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // E-Bülten Aboneleri
    $db->exec("CREATE TABLE IF NOT EXISTS subscribers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Giriş Denemeleri (Rate Limiting)
    $db->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip_address TEXT NOT NULL,
        attempt_time INTEGER NOT NULL
    )");

    // Ziyaretçi Analitiği (Gizlilik Dostu, Çerezsiz)
    $db->exec("CREATE TABLE IF NOT EXISTS analytics (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        page_url TEXT NOT NULL,
        referrer TEXT,
        browser TEXT,
        ip_hash TEXT NOT NULL,
        visit_date DATE DEFAULT (DATE('now')),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Ayarlar
    $db->exec("CREATE TABLE IF NOT EXISTS settings (
        key TEXT PRIMARY KEY,
        value TEXT
    )");

    // Sütun göçleri
    $postCols = $db->query("PRAGMA table_info(posts)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('meta_description', $postCols)) {
        $db->exec("ALTER TABLE posts ADD COLUMN meta_description TEXT");
    }
    if (!in_array('meta_keywords', $postCols)) {
        $db->exec("ALTER TABLE posts ADD COLUMN meta_keywords TEXT");
    }

    // Varsayılan Admin
    $stmt = $db->prepare("SELECT COUNT(*) FROM users");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', $adminPass, 'admin@example.com', 'admin']);
    }

    // Varsayılan Kategoriler
    $stmt = $db->prepare("SELECT COUNT(*) FROM categories");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $db->exec("INSERT INTO categories (name, slug) VALUES 
            ('Web Geliştirme', 'web-gelistirme'), 
            ('PHP & Mimari', 'php-mimari'), 
            ('Açık Kaynak', 'acik-kaynak')");
    }

    // Varsayılan Eklentiler
    if (getSetting('active_plugins') === '') {
        setSetting('active_plugins', json_encode(['announcement-bar', 'reading-progress']));
    }
}

// Ziyaretçi İstatistiği Kaydedici (Bot ve Admin hariç)
function recordVisit(): void {
    if (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/admin')) {
        return;
    }

    $db = getDB();
    $pageUrl = sanitize($_SERVER['REQUEST_URI'] ?? '/');
    $referrer = sanitize($_SERVER['HTTP_REFERER'] ?? '');
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Basit tarayıcı tespiti
    $browser = 'Diğer';
    if (str_contains($userAgent, 'Chrome') && !str_contains($userAgent, 'Edg')) $browser = 'Chrome';
    elseif (str_contains($userAgent, 'Firefox')) $browser = 'Firefox';
    elseif (str_contains($userAgent, 'Safari') && !str_contains($userAgent, 'Chrome')) $browser = 'Safari';
    elseif (str_contains($userAgent, 'Edg')) $browser = 'Edge';

    $ipHash = hash('sha256', getClientIp() . date('Y-m-d')); // Günlük anonim hash

    // Aynı gün içinde aynı sayfaya tekil ziyaret kontrolü
    $stmt = $db->prepare("SELECT id FROM analytics WHERE ip_hash = ? AND page_url = ? AND visit_date = DATE('now')");
    $stmt->execute([$ipHash, $pageUrl]);
    if (!$stmt->fetchColumn()) {
        $ins = $db->prepare("INSERT INTO analytics (page_url, referrer, browser, ip_hash) VALUES (?, ?, ?, ?)");
        $ins->execute([$pageUrl, $referrer, $browser, $ipHash]);
    }
}

// Brute-force Koruması
function checkLoginRateLimit(string $ip): bool {
    $db = getDB();
    $fifteenMinsAgo = time() - 900;
    $db->prepare("DELETE FROM login_attempts WHERE attempt_time < ?")->execute([$fifteenMinsAgo]);
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempt_time >= ?");
    $stmt->execute([$ip, $fifteenMinsAgo]);
    return ((int)$stmt->fetchColumn()) < 5;
}

function recordLoginAttempt(string $ip): void {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO login_attempts (ip_address, attempt_time) VALUES (?, ?)");
    $stmt->execute([$ip, time()]);
}

function clearLoginAttempts(string $ip): void {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
    $stmt->execute([$ip]);
}

function getClientIp(): string {
    return $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

function hasRole(string ...$roles): bool {
    $currentRole = $_SESSION['role'] ?? 'author';
    return in_array($currentRole, $roles, true);
}

function requireRole(string ...$roles): void {
    if (!hasRole(...$roles)) {
        http_response_code(403);
        die('Bu sayfaya erişim yetkiniz bulunmamaktadır.');
    }
}

function getPostTags(int $postId): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT t.* FROM tags t INNER JOIN post_tags pt ON pt.tag_id = t.id WHERE pt.post_id = ? ORDER BY t.name ASC");
    $stmt->execute([$postId]);
    return $stmt->fetchAll();
}

function syncPostTags(int $postId, string $tagsCommaSeparated): void {
    $db = getDB();
    $db->prepare("DELETE FROM post_tags WHERE post_id = ?")->execute([$postId]);

    $tagNames = array_filter(array_map('trim', explode(',', $tagsCommaSeparated)));
    foreach ($tagNames as $name) {
        if (empty($name)) continue;
        $slug = slugify($name);
        
        $stmt = $db->prepare("SELECT id FROM tags WHERE slug = ?");
        $stmt->execute([$slug]);
        $tagId = $stmt->fetchColumn();

        if (!$tagId) {
            $ins = $db->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
            $ins->execute([$name, $slug]);
            $tagId = $db->lastInsertId();
        }

        $rel = $db->prepare("INSERT OR IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)");
        $rel->execute([$postId, $tagId]);
    }
}

function calculateReadTime(string $content): int {
    $wordCount = str_word_count(strip_tags($content));
    $minutes = (int)ceil($wordCount / 200);
    return max(1, $minutes);
}

function slugify(string $text): string {
    $turkish = ['ı', 'ğ', 'ü', 'ş', 'ö', 'ç', 'İ', 'Ğ', 'Ü', 'Ş', 'Ö', 'Ç'];
    $english = ['i', 'g', 'u', 's', 'o', 'c', 'i', 'g', 'u', 's', 'o', 'c'];
    $text = str_replace($turkish, $english, $text);
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}

function sanitize(string $data): string {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function getSetting(string $key, string $default = ''): string {
    $db = getDB();
    $stmt = $db->prepare("SELECT value FROM settings WHERE key = ?");
    $stmt->execute([$key]);
    $res = $stmt->fetchColumn();
    return $res !== false ? $res : $default;
}

function setSetting(string $key, string $value): void {
    $db = getDB();
    $stmt = $db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)");
    $stmt->execute([$key, $value]);
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function checkCsrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            die('Geçersiz veya süresi dolmuş CSRF isteği.');
        }
    }
}
