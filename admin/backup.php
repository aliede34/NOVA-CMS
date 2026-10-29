<?php
// admin/backup.php - Veritabanı Yedekleme & Dışa Aktarma
session_start();
require_once __DIR__ . '/../config.php';
initDB();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
requireRole('admin');

$err = '';

// POST İşlemleri Header gönderilmeden önce (HTML'den önce) işlenmeli
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $type = $_POST['type'] ?? '';

    if ($type === 'backup_db') {
        if (file_exists(DB_FILE)) {
            $filename = 'novacms_backup_' . date('Y-m-d_H-i-s') . '.sqlite';
            header('Content-Type: application/x-sqlite3');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize(DB_FILE));
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            readfile(DB_FILE);
            exit;
        } else {
            $err = 'Veritabanı dosyası bulunamadı.';
        }
    } elseif ($type === 'export_json') {
        $db = getDB();
        $exportData = [
            'exported_at' => date('c'),
            'posts' => $db->query("SELECT * FROM posts")->fetchAll(),
            'categories' => $db->query("SELECT * FROM categories")->fetchAll(),
            'pages' => $db->query("SELECT * FROM pages")->fetchAll(),
            'comments' => $db->query("SELECT * FROM comments")->fetchAll(),
            'settings' => $db->query("SELECT * FROM settings")->fetchAll(),
        ];
        $json = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filename = 'novacms_export_' . date('Y-m-d') . '.json';
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo $json;
        exit;
    }
}

// İndirme yapılmıyorsa HTML layout'unu yükle
$adminTitle = "Yedekleme & Dışa Aktarma";
require_once __DIR__ . '/layout_header.php';
?>

<?php if ($err): ?>
    <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center gap-2">
        <i class="fa-solid fa-triangle-exclamation"></i> <?= sanitize($err) ?>
    </div>
<?php endif; ?>

<div class="max-w-4xl space-y-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Veritabanı Yedekleme & Dışa Aktarma</h2>
        <p class="text-xs text-slate-500">Tüm sistem verilerinizi güvenle tek tıkla cihazınıza indirebilirsiniz.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- SQLite Full DB Backup -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 flex flex-col justify-between">
            <div>
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl mb-4">
                    <i class="fa-solid fa-database"></i>
                </div>
                <h3 class="font-bold text-slate-900 text-base mb-1">SQLite Veritabanı Yedeği (.sqlite)</h3>
                <p class="text-xs text-slate-500 leading-relaxed mb-6">
                    Mevcut SQLite veritabanı dosyasının birebir tam kopyasını indirir. Başka bir sunucuya taşımak veya tam kurtarma sağlamak için idealdir.
                </p>
            </div>
            <form method="POST" action="backup.php">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="type" value="backup_db">
                <button type="submit" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-sm transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-download"></i> SQLite Veritabanını İndir
                </button>
            </form>
        </div>

        <!-- JSON Format Export -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 flex flex-col justify-between">
            <div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl mb-4">
                    <i class="fa-solid fa-file-code"></i>
                </div>
                <h3 class="font-bold text-slate-900 text-base mb-1">JSON Formatında Dışa Aktar (.json)</h3>
                <p class="text-xs text-slate-500 leading-relaxed mb-6">
                    Yazıları, kategorileri, sayfaları ve yorumları yapılandırılmış JSON dosyası olarak dışa aktarır. API entegrasyonu ve analizler için uygundur.
                </p>
            </div>
            <form method="POST" action="backup.php">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="type" value="export_json">
                <button type="submit" class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl text-sm transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-file-export"></i> JSON Olarak Dışa Aktar
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
