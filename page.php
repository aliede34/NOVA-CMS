<?php
session_start();
require_once __DIR__ . '/config.php';
initDB();
PluginManager::loadActivePlugins();

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: index.php');
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM pages WHERE slug = ?");
$stmt->execute([$slug]);
$page = $stmt->fetch();

if (!$page) {
    http_response_code(404);
    $pageTitle = "Sayfa Bulunamadı";
    require_once __DIR__ . '/header.php';
    echo '<div class="text-center py-20"><h1 class="text-2xl font-bold text-slate-800 dark:text-white">404 - Sayfa Bulunamadı</h1><a href="index.php" class="inline-block mt-4 px-4 py-2 bg-indigo-600 text-white rounded-xl">Ana Sayfaya Dön</a></div>';
    require_once __DIR__ . '/footer.php';
    exit;
}

$pageTitle = $page['title'];
require_once __DIR__ . '/header.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-8 md:p-12 shadow-sm transition-colors">
        <h1 class="text-3xl font-black text-slate-900 dark:text-white mb-6 pb-6 border-b border-slate-100 dark:border-slate-800">
            <?= sanitize($page['title']) ?>
        </h1>
        <div class="prose dark:prose-invert max-w-none text-slate-700 dark:text-slate-300 leading-relaxed space-y-4">
            <?= $page['content'] ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
