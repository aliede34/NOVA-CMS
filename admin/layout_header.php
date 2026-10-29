<?php
// admin/layout_header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';
initDB();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$currentPage = basename($_SERVER['PHP_SELF']);
$userRole = $_SESSION['role'] ?? 'author';

// Okunmamış mesaj sayısı
$unreadMsgCount = 0;
if (hasRole('admin', 'editor')) {
    $unreadMsgCount = (int)getDB()->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($adminTitle ?? 'Admin Paneli') ?> - NovaCMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-100 flex min-h-screen text-slate-800">

    <!-- Sidebar -->
    <aside class="w-64 bg-slate-900 text-slate-300 flex flex-col shrink-0">
        <div class="h-16 flex items-center gap-3 px-6 border-b border-slate-800">
            <span class="p-2 bg-indigo-600 rounded-xl text-white text-sm"><i class="fa-solid fa-cube"></i></span>
            <div>
                <span class="font-extrabold text-white text-base tracking-wide block leading-none">NovaCMS</span>
                <span class="text-[10px] text-slate-400 font-mono">v1.3 Enterprise</span>
            </div>
        </div>

        <nav class="flex-grow p-4 space-y-1 text-sm font-medium">
            <a href="index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition <?= $currentPage == 'index.php' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' ?>">
                <i class="fa-solid fa-gauge-high w-5 text-center"></i> Başlangıç
            </a>
            <a href="posts.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition <?= $currentPage == 'posts.php' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' ?>">
                <i class="fa-solid fa-newspaper w-5 text-center"></i> Yazılar
            </a>
            <a href="media.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition <?= $currentPage == 'media.php' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' ?>">
                <i class="fa-solid fa-photo-film w-5 text-center"></i> Medya Galerisi
            </a>
            <a href="comments.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition <?= $currentPage == 'comments.php' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' ?>">
                <i class="fa-solid fa-comments w-5 text-center"></i> Yorumlar
            </a>

            <?php if (hasRole('admin', 'editor')): ?>
                <a href="analytics.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition <?= $currentPage == 'analytics.php' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' ?>">
                    <i class="fa-solid fa-chart-line w-5 text-center"></i> Ziyaretçi Analitiği
                </a>
                <a href="messages.php" class="flex items-center justify-between px-3 py-2.5 rounded-xl transition <?= $currentPage == 'messages.php' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' ?>">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-envelope w-5 text-center"></i> Mesajlar
                    </div>
                    <?php if ($unreadMsgCount > 0): ?>
                        <span class="px-2 py-0.5 rounded-full bg-rose-500 text-white text-xs font-bold"><?= $unreadMsgCount ?></span>
                    <?php endif; ?>
                </a>
                <a href="categories.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition <?= $currentPage == 'categories.php' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' ?>">
                    <i class="fa-solid fa-tags w-5 text-center"></i> Kategoriler
                </a>
                <a href="pages.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition <?= $currentPage == 'pages.php' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' ?>">
                    <i class="fa-solid fa-file-lines w-5 text-center"></i> Sayfalar
                </a>
            <?php endif; ?>

            <?php if (hasRole('admin')): ?>
                <a href="users.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition <?= $currentPage == 'users.php' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' ?>">
                    <i class="fa-solid fa-users-gear w-5 text-center"></i> Kullanıcılar & Roller
                </a>
                <a href="plugins.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition <?= in_array($currentPage, ['plugins.php', 'plugin_editor.php']) ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' ?>">
                    <i class="fa-solid fa-puzzle-piece w-5 text-center"></i> Eklentiler & Modüller
                </a>
                <a href="cache_manager.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition <?= $currentPage == 'cache_manager.php' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' ?>">
                    <i class="fa-solid fa-bolt w-5 text-center"></i> Önbellek & Hız
                </a>
                <a href="backup.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition <?= $currentPage == 'backup.php' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' ?>">
                    <i class="fa-solid fa-cloud-arrow-down w-5 text-center"></i> Yedekleme & Dışa Aktar
                </a>
                <a href="settings.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition <?= $currentPage == 'settings.php' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' ?>">
                    <i class="fa-solid fa-gear w-5 text-center"></i> Ayarlar
                </a>
            <?php endif; ?>
        </nav>

        <div class="p-4 border-t border-slate-800 space-y-2">
            <a href="../index.php" target="_blank" class="flex items-center justify-between px-3 py-2 rounded-xl text-xs bg-slate-800/80 hover:bg-slate-800 text-slate-300 transition">
                <span>Siteyi Görüntüle</span>
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
            </a>
            <a href="logout.php" class="flex items-center gap-2 px-3 py-2 rounded-xl text-xs text-rose-400 hover:bg-rose-500/10 transition">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Çıkış (<?= sanitize($_SESSION['username'] ?? 'User') ?>)
            </a>
        </div>
    </aside>

    <!-- Content Area -->
    <div class="flex-grow flex flex-col min-w-0 overflow-y-auto">
        <!-- Top bar -->
        <header class="h-16 bg-white border-b border-slate-200 px-8 flex items-center justify-between">
            <h1 class="text-lg font-bold text-slate-800"><?= sanitize($adminTitle ?? 'Panel') ?></h1>
            <div class="flex items-center gap-4">
                <a href="../contact.php" target="_blank" class="text-xs text-slate-500 hover:text-indigo-600 flex items-center gap-1.5 font-medium">
                    <i class="fa-solid fa-paper-plane text-indigo-500"></i> İletişim Sayfası
                </a>
                <a href="../feed.php" target="_blank" class="text-xs text-slate-500 hover:text-amber-600 flex items-center gap-1.5 font-medium">
                    <i class="fa-solid fa-rss text-amber-500"></i> RSS
                </a>
                <div class="h-4 w-px bg-slate-200"></div>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs uppercase">
                        <?= substr($_SESSION['username'] ?? 'A', 0, 1) ?>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-slate-800"><?= sanitize($_SESSION['username'] ?? '') ?></div>
                        <div class="text-[10px] text-indigo-600 uppercase font-semibold"><?= sanitize($userRole) ?></div>
                    </div>
                </div>
            </div>
        </header>

        <main class="p-8 flex-grow">
