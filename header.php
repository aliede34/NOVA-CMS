<?php
// header.php - Genel Başlık ve Menü
require_once __DIR__ . '/config.php';
recordVisit();
$siteName = getSetting('site_name', 'NovaCMS');
$siteDesc = getSetting('site_description', 'Hızlı, Taşınabilir ve Güvenli PHP CMS');

$db = getDB();
$pagesNav = $db->query("SELECT title, slug FROM pages ORDER BY id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle ?? $siteName) ?></title>
    <meta name="description" content="<?= sanitize($pageDesc ?? $siteDesc) ?>">
    
    <!-- OpenGraph SEO Tags -->
    <meta property="og:title" content="<?= sanitize($pageTitle ?? $siteName) ?>">
    <meta property="og:description" content="<?= sanitize($pageDesc ?? $siteDesc) ?>">
    <meta property="og:type" content="website">

    <!-- RSS & Sitemap links -->
    <link rel="alternate" type="application/rss+xml" title="<?= sanitize($siteName) ?> RSS Feed" href="feed.php">
    <link rel="sitemap" type="application/xml" title="Sitemap" href="sitemap.php">

    <!-- Tailwind CSS with Dark Mode Class Strategy -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3'
                        }
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
    <!-- Script to set dark mode on initial load -->
    <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 flex flex-col min-h-screen transition-colors duration-200">
    <?php Hook::doAction('after_body_start'); ?>
    <!-- Navbar -->
    <header class="bg-white/90 dark:bg-slate-900/90 backdrop-blur border-b border-slate-200 dark:border-slate-800 sticky top-0 z-50 transition-colors">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-2.5 text-xl font-extrabold text-indigo-600 dark:text-indigo-400 tracking-tight">
                <span class="p-2 bg-indigo-50 dark:bg-indigo-950/60 rounded-xl text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800/40">
                    <i class="fa-solid fa-cube"></i>
                </span>
                <span><?= sanitize($siteName) ?></span>
            </a>

            <!-- Nav Links -->
            <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-slate-600 dark:text-slate-300">
                <a href="landing.php" class="text-indigo-600 dark:text-indigo-400 font-bold hover:underline flex items-center gap-1.5">
                    <img src="https://flagcdn.com/16x12/tr.png" width="16" height="12" alt="TR" class="rounded-[2px]"> Tanıtım / About
                </a>
                <a href="index.php" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition">Ana Sayfa</a>
                <?php foreach ($pagesNav as $p): ?>
                    <a href="page.php?slug=<?= urlencode($p['slug']) ?>" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                        <?= sanitize($p['title']) ?>
                    </a>
                <?php endforeach; ?>
                <a href="contact.php" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition">İletişim</a>
                <a href="feed.php" target="_blank" class="hover:text-amber-500 transition" title="RSS Beslemesi">
                    <i class="fa-solid fa-rss"></i>
                </a>
            </nav>

            <!-- Actions / Theme Toggle / Admin Link -->
            <div class="flex items-center gap-3">
                <!-- Dark Mode Toggle Button -->
                <button id="theme-toggle" type="button" class="p-2 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl text-sm transition focus:outline-none" title="Tema Değiştir">
                    <i id="theme-toggle-dark-icon" class="fa-solid fa-moon hidden"></i>
                    <i id="theme-toggle-light-icon" class="fa-solid fa-sun hidden text-amber-400"></i>
                </button>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="admin/index.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 text-sm font-semibold hover:bg-indigo-100 dark:hover:bg-indigo-900/60 transition">
                        <i class="fa-solid fa-gauge text-xs"></i> Admin
                    </a>
                    <a href="admin/logout.php" class="text-sm text-slate-500 dark:text-slate-400 hover:text-rose-600 transition" title="Çıkış Yap">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </a>
                <?php else: ?>
                    <a href="admin/login.php" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium shadow-sm transition">
                        <i class="fa-solid fa-lock text-xs"></i> Giriş
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="flex-grow max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
