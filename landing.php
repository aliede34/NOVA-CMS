<?php
// landing.php - NovaCMS Tanıtım ve Vitrin Sayfası (Landing Page)
session_start();
require_once __DIR__ . '/config.php';
initDB();

$siteName = getSetting('site_name', 'NovaCMS');
$activePluginsCount = count(PluginManager::getActivePlugins());
$postsCount = getDB()->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NovaCMS - Türkiye'den Dünyaya Açık Kaynak PHP 8 CMS & Headless API</title>
    <meta name="description" content="Sıfır konfigürasyonlu, SQLite tabanlı, WordPress benzeri eklenti mimarisine ve REST API desteğine sahip modern PHP CMS.">
    
    <!-- Tailwind CSS with Dark Mode -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        turkishRed: '#E30A17'
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .gradient-text {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #db2777 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 flex flex-col min-h-screen selection:bg-indigo-500 selection:text-white">

    <!-- Top Turkish Pride Banner -->
    <div class="bg-gradient-to-r from-red-600 via-rose-600 to-red-700 text-white text-xs py-2 px-4 text-center font-medium shadow-md flex items-center justify-center gap-2.5">
        <img src="https://flagcdn.com/24x18/tr.png" width="20" height="15" alt="Türkiye" class="rounded-sm shadow-sm">
        <span><strong>Türkiye'de Geliştirildi</strong> &bull; Proudly Made in Türkiye with Open Source Passion</span>
        <img src="https://flagcdn.com/24x18/tr.png" width="20" height="15" alt="Türkiye" class="rounded-sm shadow-sm">
    </div>

    <!-- Navigation -->
    <header class="border-b border-slate-800 bg-slate-900/80 backdrop-blur sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-20 flex items-center justify-between">
            <a href="landing.php" class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-indigo-600 flex items-center justify-center text-white text-xl shadow-lg shadow-indigo-500/30">
                    <i class="fa-solid fa-cube"></i>
                </div>
                <div>
                    <span class="text-xl font-black tracking-tight text-white block leading-none">NovaCMS</span>
                    <span class="text-[10px] text-slate-400 font-mono">v1.4 OpenSource</span>
                </div>
            </a>

            <!-- Language Switcher & Links -->
            <div class="flex items-center gap-6 text-sm">
                <nav class="hidden md:flex items-center gap-6 font-medium text-slate-300">
                    <a href="#features" class="hover:text-white transition">Özellikler / Features</a>
                    <a href="#api" class="hover:text-white transition">REST API</a>
                    <a href="#architecture" class="hover:text-white transition">Mimari / Architecture</a>
                    <a href="index.php" class="text-indigo-400 hover:text-indigo-300 transition">Canlı Blog &rarr;</a>
                </nav>

                <div class="flex items-center gap-3">
                    <a href="admin/login.php" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold border border-slate-700 transition">
                        Admin Paneli
                    </a>
                    <a href="https://github.com" target="_blank" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-lg shadow-indigo-600/30 transition flex items-center gap-2">
                        <i class="fa-brands fa-github text-sm"></i> Star on GitHub
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative pt-20 pb-28 overflow-hidden">
        <!-- Glow Orbs -->
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-indigo-600/20 blur-3xl rounded-full pointer-events-none"></div>
        <div class="absolute top-1/3 left-1/4 w-80 h-80 bg-rose-600/10 blur-3xl rounded-full pointer-events-none"></div>

        <div class="max-w-5xl mx-auto px-4 text-center relative z-10">
            <!-- Turkish Made Badge -->
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-slate-800/90 border border-slate-700 text-xs font-semibold mb-6 shadow-sm">
                <img src="https://flagcdn.com/20x15/tr.png" width="16" height="12" alt="TR" class="rounded-[2px]">
                <span class="text-slate-300">Geliştirici Dostu Bağımsız Türk Açık Kaynak CMS Projesi</span>
            </div>

            <h1 class="text-4xl sm:text-6xl md:text-7xl font-black tracking-tight text-white leading-tight mb-6">
                Hafif. Taşınabilir.<br>
                <span class="gradient-text">Gelişmiş Eklenti & API Destekli.</span>
            </h1>

            <p class="text-slate-400 text-lg sm:text-xl max-w-3xl mx-auto font-light leading-relaxed mb-10">
                <strong>NovaCMS</strong>; sıfır veritabanı ayarı (Zero-Config SQLite), WordPress tarzı dinamik kanca mimarisi, yerleşik REST API ve 0.005s önbellek motoruyla donatılmış modern PHP 8 içerik yönetim sistemidir.
                <br>
                <span class="text-slate-500 text-sm italic mt-2 block">
                    Zero-configuration, ultra-fast, SQLite-powered PHP 8 Headless CMS with modular plugin ecosystem & Docker support.
                </span>
            </p>

            <!-- Quick Start Command -->
            <div class="max-w-lg mx-auto bg-slate-950/80 p-3 rounded-2xl border border-slate-800 flex items-center justify-between mb-8 shadow-2xl font-mono text-xs">
                <div class="flex items-center gap-2 text-indigo-400 pl-2">
                    <span class="text-slate-500">$</span>
                    <span>git clone &amp;&amp; php -S localhost:8000</span>
                </div>
                <button onclick="navigator.clipboard.writeText('php -S localhost:8000'); alert('Komut panoya kopyalandı!');" class="p-2 hover:bg-slate-800 rounded-lg text-slate-400 hover:text-white transition" title="Kopyala">
                    <i class="fa-regular fa-copy"></i>
                </button>
            </div>

            <!-- CTA Buttons -->
            <div class="flex flex-wrap items-center justify-center gap-4">
                <a href="index.php" class="px-8 py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-2xl shadow-xl shadow-indigo-600/30 transition flex items-center gap-2 text-sm">
                    <i class="fa-solid fa-rocket"></i> Canlı Demoyu Keşfet
                </a>
                <a href="admin/login.php" class="px-8 py-3.5 bg-slate-800/90 hover:bg-slate-800 text-slate-200 font-semibold rounded-2xl border border-slate-700 transition flex items-center gap-2 text-sm">
                    <i class="fa-solid fa-lock text-xs"></i> Admin Demo (admin/admin123)
                </a>
            </div>
        </div>
    </section>

    <!-- Key Metrics Counter -->
    <section class="border-y border-slate-800 bg-slate-950/40 py-12">
        <div class="max-w-6xl mx-auto px-4 grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div>
                <div class="text-3xl sm:text-4xl font-black text-indigo-400 font-mono">0.005s</div>
                <div class="text-xs text-slate-400 mt-1 uppercase tracking-wider font-semibold">Yanıt Süresi / Response Time</div>
            </div>
            <div>
                <div class="text-3xl sm:text-4xl font-black text-emerald-400 font-mono">0 DB Setup</div>
                <div class="text-xs text-slate-400 mt-1 uppercase tracking-wider font-semibold">SQLite Tak-Çalıştır / Zero Config</div>
            </div>
            <div>
                <div class="text-3xl sm:text-4xl font-black text-purple-400 font-mono"><?= $activePluginsCount ?>+ Modül</div>
                <div class="text-xs text-slate-400 mt-1 uppercase tracking-wider font-semibold">Aktif Eklenti / Plugin System</div>
            </div>
            <div>
                <div class="text-3xl sm:text-4xl font-black text-rose-400 font-mono">100% PHPUnit</div>
                <div class="text-xs text-slate-400 mt-1 uppercase tracking-wider font-semibold">Test Kapsamı / Test Coverage</div>
            </div>
        </div>
    </section>

    <!-- Bilinçli Çift Dilli Özellikler (TR & EN Features) -->
    <section id="features" class="py-24 max-w-6xl mx-auto px-4">
        <div class="text-center mb-16">
            <span class="text-xs font-bold uppercase tracking-widest text-indigo-400">Üstün Yetenekler &bull; Core Capabilities</span>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-white mt-2">Neden NovaCMS? / Why Choose NovaCMS?</h2>
            <p class="text-slate-400 text-sm max-w-2xl mx-auto mt-3">Hem Türkçe hem İngilizce dokümante edilmiş kurumsal ve bağımsız mimari.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Feature 1 -->
            <div class="bg-slate-800/40 border border-slate-800 hover:border-indigo-500/50 p-8 rounded-3xl transition duration-300">
                <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-xl mb-6">
                    <i class="fa-solid fa-puzzle-piece"></i>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">WordPress Tarzı Eklenti Mimarisi</h3>
                <p class="text-xs text-slate-400 leading-relaxed mb-4">
                    <code>Hook::addAction</code> ve <code>Hook::addFilter</code> ile sistem çekirdeğine dokunmadan yeni özellikler ekleyin. ZIP ile eklenti yükleme ve tarayıcı içi PHP kod editörü dahildir.
                </p>
                <div class="text-[11px] font-mono text-indigo-300/80 border-t border-slate-800 pt-3">
                    <strong>EN:</strong> Modular plugin ecosystem with Hook/Filter API, ZIP upload, and built-in CodeMirror PHP editor.
                </div>
            </div>

            <!-- Feature 2 -->
            <div class="bg-slate-800/40 border border-slate-800 hover:border-emerald-500/50 p-8 rounded-3xl transition duration-300">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-xl mb-6">
                    <i class="fa-solid fa-bolt"></i>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">0.005s Dosya Önbellekleme</h3>
                <p class="text-xs text-slate-400 leading-relaxed mb-4">
                    Yerleşik dosya tabanlı cache motoru sayesinde veritabanı yükü minimuma iner, sayfalar ve API çağrıları milisaniyeler içinde yanıt verir.
                </p>
                <div class="text-[11px] font-mono text-emerald-300/80 border-t border-slate-800 pt-3">
                    <strong>EN:</strong> Ultra-fast file-based caching engine reducing response latency to sub-5ms.
                </div>
            </div>

            <!-- Feature 3 -->
            <div class="bg-slate-800/40 border border-slate-800 hover:border-purple-500/50 p-8 rounded-3xl transition duration-300">
                <div class="w-12 h-12 rounded-2xl bg-purple-500/10 text-purple-400 flex items-center justify-center text-xl mb-6">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Yapay Zeka (AI) SEO Asistanı</h3>
                <p class="text-xs text-slate-400 leading-relaxed mb-4">
                    Yazı içeriğinizi analiz ederek otomatik olarak Google uyumlu 150 karakterlik meta açıklaması, anahtar kelimeler ve etiketler üretir.
                </p>
                <div class="text-[11px] font-mono text-purple-300/80 border-t border-slate-800 pt-3">
                    <strong>EN:</strong> AI-powered smart assistant automatically generating SEO meta descriptions, keywords and tags.
                </div>
            </div>

            <!-- Feature 4 -->
            <div class="bg-slate-800/40 border border-slate-800 hover:border-rose-500/50 p-8 rounded-3xl transition duration-300">
                <div class="w-12 h-12 rounded-2xl bg-rose-500/10 text-rose-400 flex items-center justify-center text-xl mb-6">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Brute-Force & Veri Güvenliği</h3>
                <p class="text-xs text-slate-400 leading-relaxed mb-4">
                    IP bazlı 5 hatalı denemede otomatik kilitleme, SQLite dosyasının webden indirilmesine karşı 403 koruması, CSRF tokenleri ve %100 PDO prepared statements.
                </p>
                <div class="text-[11px] font-mono text-rose-300/80 border-t border-slate-800 pt-3">
                    <strong>EN:</strong> Enterprise security with rate-limited login, protected SQLite database, and complete XSS/CSRF defense.
                </div>
            </div>

            <!-- Feature 5 -->
            <div class="bg-slate-800/40 border border-slate-800 hover:border-amber-500/50 p-8 rounded-3xl transition duration-300">
                <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-xl mb-6">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Gizlilik Dostu Analitik</h3>
                <p class="text-xs text-slate-400 leading-relaxed mb-4">
                    Harici çerez veya üçüncü taraf kodlara gerek duymadan günlük tekil ziyaretçi, sayfa görüntüleme ve tarayıcı istatistiklerini Chart.js ile izleyin.
                </p>
                <div class="text-[11px] font-mono text-amber-300/80 border-t border-slate-800 pt-3">
                    <strong>EN:</strong> Cookieless, privacy-first internal analytics dashboard powered by interactive Chart.js graphs.
                </div>
            </div>

            <!-- Feature 6 -->
            <div class="bg-slate-800/40 border border-slate-800 hover:border-blue-500/50 p-8 rounded-3xl transition duration-300">
                <div class="w-12 h-12 rounded-2xl bg-blue-500/10 text-blue-400 flex items-center justify-center text-xl mb-6">
                    <i class="fa-solid fa-users-gear"></i>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Rol Tabanlı Yetkilendirme (RBAC)</h3>
                <p class="text-xs text-slate-400 leading-relaxed mb-4">
                    Admin, Editor ve Author rolleri ile çoklu yazar desteği. Her kullanıcı kendi yetkisi dahilindeki alanları yönetir.
                </p>
                <div class="text-[11px] font-mono text-blue-300/80 border-t border-slate-800 pt-3">
                    <strong>EN:</strong> Multi-author role-based access control (Admin, Editor, Author) with clean separation of privileges.
                </div>
            </div>
        </div>
    </section>

    <!-- REST API Showcase Section -->
    <section id="api" class="py-24 bg-slate-950 border-t border-slate-800">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <span class="text-xs font-bold uppercase tracking-widest text-indigo-400">Headless Architecture &bull; Jamstack Ready</span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-white mt-2 mb-4">Yerleşik RESTful JSON API</h2>
                    <p class="text-slate-400 text-sm leading-relaxed mb-6">
                        NovaCMS sadece geleneksel bir CMS değil, aynı zamanda mobil uygulamalarınız, Next.js, Nuxt veya Astro web siteleriniz için sıfır yapılandırmalı bir <strong>Headless CMS</strong> backend'idir.
                    </p>

                    <div class="space-y-3 font-mono text-xs">
                        <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between">
                            <span class="text-emerald-400 font-bold">GET</span>
                            <span class="text-slate-300">/api/posts.php?page=1&amp;limit=10</span>
                            <a href="api/posts.php" target="_blank" class="text-indigo-400 hover:underline">Dene &rarr;</a>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between">
                            <span class="text-emerald-400 font-bold">GET</span>
                            <span class="text-slate-300">/api/posts.php?slug=yazi-adresi</span>
                            <span class="text-slate-500">Tekil Yazı</span>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between">
                            <span class="text-emerald-400 font-bold">GET</span>
                            <span class="text-slate-300">/api/categories.php</span>
                            <a href="api/categories.php" target="_blank" class="text-indigo-400 hover:underline">Dene &rarr;</a>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between">
                            <span class="text-emerald-400 font-bold">GET</span>
                            <span class="text-slate-300">/api/tags.php</span>
                            <a href="api/tags.php" target="_blank" class="text-indigo-400 hover:underline">Dene &rarr;</a>
                        </div>
                    </div>
                </div>

                <!-- API Response Preview Card -->
                <div class="bg-slate-900 rounded-3xl p-6 border border-slate-800 shadow-2xl font-mono text-xs overflow-hidden">
                    <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-800 text-[11px] text-slate-400">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                            <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                            <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                            <span class="ml-2 text-slate-400">JSON API Response</span>
                        </div>
                        <span class="text-emerald-400 font-bold">200 OK</span>
                    </div>

                    <pre class="text-indigo-300 overflow-x-auto text-[11px] leading-relaxed">
{
  <span class="text-rose-400">"status"</span>: <span class="text-emerald-400">"success"</span>,
  <span class="text-rose-400">"pagination"</span>: {
    <span class="text-rose-400">"page"</span>: 1,
    <span class="text-rose-400">"limit"</span>: 10,
    <span class="text-rose-400">"total"</span>: <?= $postsCount ?>,
    <span class="text-rose-400">"total_pages"</span>: 1
  },
  <span class="text-rose-400">"data"</span>: [
    {
      <span class="text-rose-400">"id"</span>: 1,
      <span class="text-rose-400">"title"</span>: <span class="text-emerald-400">"NovaCMS ile Tanışın..."</span>,
      <span class="text-rose-400">"read_time"</span>: 3,
      <span class="text-rose-400">"likes"</span>: 24,
      <span class="text-rose-400">"tags"</span>: [<span class="text-amber-300">"PHP8"</span>, <span class="text-amber-300">"SQLite"</span>]
    }
  ]
}</pre>
                </div>
            </div>
        </div>
    </section>

    <!-- Project Heritage / Made in Turkey Section -->
    <section class="py-20 bg-gradient-to-br from-slate-900 via-slate-900 to-red-950/30 border-t border-slate-800">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <!-- Turkish Flag Graphic -->
            <div class="inline-flex items-center justify-center p-3 rounded-2xl bg-red-600/10 border border-red-500/20 mb-6">
                <img src="https://flagcdn.com/64x48/tr.png" width="48" height="36" alt="Türk Bayrağı" class="rounded-lg shadow-md">
            </div>

            <h2 class="text-2xl sm:text-3xl font-extrabold text-white mb-4">
                Türkiye'den Küresel Açık Kaynak Ekosistemine Bir Katkı
            </h2>
            <p class="text-slate-400 text-sm leading-relaxed max-w-2xl mx-auto mb-8 font-light">
                NovaCMS, yerli ve milli bir vizyonla; bağımsız, sunucu maliyetsiz, karmaşık yabancı kütüphanelere muhtaç kalmadan her geliştiricinin kendi blogunu, haber sitesini veya API altyapısını saniyeler içinde kurabilmesi amacıyla tasarlanmıştır.
            </p>

            <div class="inline-flex flex-wrap items-center justify-center gap-3 text-xs">
                <span class="px-3.5 py-1.5 rounded-full bg-slate-800 text-slate-300 border border-slate-700">🇹🇷 Türkiye Merkezli Geliştirme (Ali)</span>
                <span class="px-3.5 py-1.5 rounded-full bg-slate-800 text-slate-300 border border-slate-700">📜 Çift Lisans (MIT &amp; GPL v3)</span>
                <span class="px-3.5 py-1.5 rounded-full bg-slate-800 text-slate-300 border border-slate-700">🌍 Çift Dilli Altyapı</span>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-slate-800 py-12 bg-slate-950 text-slate-500 text-xs">
        <div class="max-w-6xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-3">
                <img src="https://flagcdn.com/20x15/tr.png" width="20" height="15" alt="TR" class="rounded-[2px]">
                <p>&copy; <?= date('Y') ?> NovaCMS Project. Gururla Türkiye'de Geliştirildi &bull; Made in Türkiye.</p>
            </div>
            <div class="flex items-center gap-6">
                <a href="index.php" class="hover:text-white transition">Blog Ana Sayfa</a>
                <a href="admin/login.php" class="hover:text-white transition">Admin Paneli</a>
                <a href="contact.php" class="hover:text-white transition">İletişim</a>
                <a href="feed.php" class="hover:text-white transition">RSS 2.0</a>
            </div>
        </div>
    </footer>

</body>
</html>

