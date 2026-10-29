<?php
session_start();
require_once __DIR__ . '/config.php';
initDB();
PluginManager::loadActivePlugins();

$db = getDB();

// Filtreler: Kategori, Etiket veya Arama
$catSlug = $_GET['category'] ?? '';
$tagSlug = $_GET['tag'] ?? '';
$search = trim($_GET['search'] ?? '');

// Sayfalama (Pagination) Parametreleri
$perPage = 6;
$currentPage = max(1, (int)($_GET['p'] ?? 1));
$offset = ($currentPage - 1) * $perPage;

$query = "SELECT p.*, c.name as category_name, c.slug as category_slug, u.username as author_name 
          FROM posts p 
          LEFT JOIN categories c ON p.category_id = c.id 
          LEFT JOIN users u ON p.user_id = u.id";

$whereClauses = ["p.status = 'published'"];
$params = [];

if ($catSlug) {
    $whereClauses[] = "c.slug = ?";
    $params[] = $catSlug;
}

if ($tagSlug) {
    $query .= " INNER JOIN post_tags pt ON pt.post_id = p.id INNER JOIN tags t ON t.id = pt.tag_id";
    $whereClauses[] = "t.slug = ?";
    $params[] = $tagSlug;
}

if ($search) {
    $whereClauses[] = "(p.title LIKE ? OR p.content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " WHERE " . implode(" AND ", $whereClauses);

// Toplam Yazı Sayısını Hesapla
$countQuery = "SELECT COUNT(*) FROM (" . $query . ")";
$stmtCount = $db->prepare($countQuery);
$stmtCount->execute($params);
$totalPosts = (int)$stmtCount->fetchColumn();
$totalPages = ceil($totalPosts / $perPage);

// Sayfalı Verileri Çek
$query .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;

$stmt = $db->prepare($query);
$stmt->execute($params);
$posts = $stmt->fetchAll();

// Kategorileri listele
$categories = $db->query("SELECT c.*, COUNT(p.id) as post_count FROM categories c LEFT JOIN posts p ON p.category_id = c.id AND p.status='published' GROUP BY c.id ORDER BY c.name ASC")->fetchAll();

// Popüler Etiketler (Tag Cloud)
$popularTags = $db->query("SELECT t.*, COUNT(pt.post_id) as post_count 
                           FROM tags t 
                           INNER JOIN post_tags pt ON pt.tag_id = t.id 
                           GROUP BY t.id 
                           ORDER BY post_count DESC LIMIT 12")->fetchAll();

$pageTitle = getSetting('site_name', 'NovaCMS') . " - Açık Kaynak İçerik Sistemi";
require_once __DIR__ . '/header.php';
?>

<!-- Hero Banner -->
<div class="relative overflow-hidden bg-gradient-to-br from-indigo-700 via-indigo-600 to-purple-800 rounded-3xl p-8 md:p-14 text-white shadow-xl mb-12">
    <div class="max-w-2xl">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur border border-white/20 text-xs font-semibold mb-4 text-indigo-100">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> Açık Kaynak PHP CMS &bull; REST API Hazır
        </div>
        <h1 class="text-3xl sm:text-4xl md:text-5xl font-black tracking-tight mb-4 leading-tight">
            <?= sanitize(getSetting('site_name', 'NovaCMS')) ?>
        </h1>
        <p class="text-indigo-100 text-base md:text-lg font-light mb-8 leading-relaxed">
            <?= sanitize(getSetting('site_description', 'Hızlı, Taşınabilir ve Güvenli PHP İçerik Yönetim Sistemi')) ?>
        </p>
        <div class="flex flex-wrap gap-3">
            <a href="#articles" class="px-5 py-2.5 bg-white text-indigo-700 font-bold rounded-xl shadow hover:bg-slate-100 transition">
                Yazıları İncele
            </a>
            <a href="api/posts.php" target="_blank" class="px-4 py-2.5 bg-indigo-500/30 border border-white/20 text-white font-medium rounded-xl hover:bg-indigo-500/50 transition flex items-center gap-2 text-xs">
                <i class="fa-solid fa-code"></i> JSON API
            </a>
            <a href="feed.php" target="_blank" class="px-4 py-2.5 bg-indigo-500/30 border border-white/20 text-white font-medium rounded-xl hover:bg-indigo-500/50 transition flex items-center gap-2 text-xs">
                <i class="fa-solid fa-rss text-amber-300"></i> RSS
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-8" id="articles">
    <!-- Main Content: Posts -->
    <div class="lg:col-span-3 space-y-6">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
            <h2 class="text-xl font-bold text-slate-800 dark:text-white">
                <?php
                if ($catSlug) echo 'Kategori: ' . sanitize($catSlug);
                elseif ($tagSlug) echo 'Etiket: #' . sanitize($tagSlug);
                elseif ($search) echo 'Arama: "' . sanitize($search) . '"';
                else echo 'Son Gönderiler';
                ?>
                <span class="text-xs text-slate-400 font-normal ml-2">(<?= $totalPosts ?> yazı)</span>
            </h2>
            <?php if ($catSlug || $tagSlug || $search): ?>
                <a href="index.php" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">Tümünü Göster &rarr;</a>
            <?php endif; ?>
        </div>

        <?php if (empty($posts)): ?>
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-12 text-center border border-slate-200/80 dark:border-slate-800">
                <i class="fa-regular fa-folder-open text-4xl text-slate-400 mb-3"></i>
                <h3 class="text-lg font-semibold text-slate-700 dark:text-slate-300">İçerik bulunamadı</h3>
                <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Bu kritere uygun yazı bulunmuyor.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($posts as $post): ?>
                    <?php $pTags = getPostTags((int)$post['id']); ?>
                    <article class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition flex flex-col overflow-hidden">
                        <?php if (!empty($post['featured_image'])): ?>
                            <a href="post.php?slug=<?= urlencode($post['slug']) ?>" class="block h-48 overflow-hidden bg-slate-100 dark:bg-slate-800">
                                <img src="<?= sanitize(str_starts_with($post['featured_image'], 'http') ? $post['featured_image'] : $post['featured_image']) ?>" alt="<?= sanitize($post['title']) ?>" class="w-full h-full object-cover hover:scale-105 transition duration-300">
                            </a>
                        <?php endif; ?>

                        <div class="p-6 flex-grow flex flex-col justify-between">
                            <div>
                                <div class="flex items-center gap-2 mb-3 text-xs">
                                    <?php if ($post['category_name']): ?>
                                        <a href="index.php?category=<?= urlencode($post['category_slug'] ?? slugify($post['category_name'])) ?>" 
                                           class="font-semibold px-2.5 py-1 rounded-md bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 transition">
                                            <?= sanitize($post['category_name']) ?>
                                        </a>
                                    <?php endif; ?>
                                    <span class="text-slate-400">
                                        <?= date('d.m.Y', strtotime($post['created_at'])) ?>
                                    </span>
                                    <span class="text-slate-400">&bull; <?= $post['read_time'] ?> dk okuma</span>
                                </div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition mb-2">
                                    <a href="post.php?slug=<?= urlencode($post['slug']) ?>">
                                        <?= sanitize($post['title']) ?>
                                    </a>
                                </h3>
                                <p class="text-slate-600 dark:text-slate-400 text-sm line-clamp-3 leading-relaxed mb-4">
                                    <?= sanitize(strip_tags($post['content'])) ?>
                                </p>

                                <?php if (!empty($pTags)): ?>
                                    <div class="flex flex-wrap gap-1 mb-2">
                                        <?php foreach ($pTags as $pt): ?>
                                            <a href="index.php?tag=<?= urlencode($pt['slug']) ?>" class="text-[11px] px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-indigo-600">
                                                #<?= sanitize($pt['name']) ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                                <span class="flex items-center gap-1.5">
                                    <i class="fa-regular fa-eye"></i>
                                    <?= $post['views_count'] ?> okunma
                                </span>
                                <a href="post.php?slug=<?= urlencode($post['slug']) ?>" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 flex items-center gap-1">
                                    Devamını Oku <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Sayfalama (Pagination Bar) -->
            <?php if ($totalPages > 1): ?>
                <div class="pt-8 flex items-center justify-center gap-2">
                    <?php if ($currentPage > 1): ?>
                        <a href="?p=<?= $currentPage - 1 ?><?= $catSlug ? '&category=' . urlencode($catSlug) : '' ?><?= $tagSlug ? '&tag=' . urlencode($tagSlug) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                           class="px-4 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 transition">
                            &larr; Önceki
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?p=<?= $i ?><?= $catSlug ? '&category=' . urlencode($catSlug) : '' ?><?= $tagSlug ? '&tag=' . urlencode($tagSlug) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                           class="w-10 h-10 flex items-center justify-center rounded-xl text-sm font-semibold transition <?= $i === $currentPage ? 'bg-indigo-600 text-white shadow' : 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-50' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <a href="?p=<?= $currentPage + 1 ?><?= $catSlug ? '&category=' . urlencode($catSlug) : '' ?><?= $tagSlug ? '&tag=' . urlencode($tagSlug) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                           class="px-4 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 transition">
                            Sonraki &rarr;
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <aside class="space-y-6">
        <!-- Canlı Ajax Arama Kutusu -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm transition-colors relative">
            <h3 class="font-bold text-slate-800 dark:text-white mb-3 flex items-center gap-2 text-xs uppercase tracking-wider text-slate-400">
                <i class="fa-solid fa-magnifying-glass"></i> Canlı Arama
            </h3>
            <div class="relative">
                <input type="text" id="liveSearchInput" placeholder="Yazmaya başlayın..." 
                       class="w-full px-4 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                <span class="absolute right-3 top-3 text-slate-400 text-xs" id="searchSpinner" style="display: none;">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                </span>
            </div>
            <!-- Ajax Canlı Sonuç Kutusu -->
            <div id="liveSearchResults" class="hidden absolute left-0 right-0 top-full mt-2 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xl overflow-hidden z-50"></div>
        </div>

        <!-- Categories Widget -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm transition-colors">
            <h3 class="font-bold text-slate-800 dark:text-white mb-3 flex items-center gap-2 text-xs uppercase tracking-wider text-slate-400">
                <i class="fa-solid fa-folder"></i> Kategoriler
            </h3>
            <div class="space-y-1">
                <a href="index.php" class="flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium <?= empty($catSlug) && empty($tagSlug) && empty($search) ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800' ?> transition">
                    <span>Tüm Yazılar</span>
                </a>
                <?php foreach ($categories as $cat): ?>
                    <a href="index.php?category=<?= urlencode($cat['slug']) ?>" 
                       class="flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium <?= $catSlug === $cat['slug'] ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800' ?> transition">
                        <span><?= sanitize($cat['name']) ?></span>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-normal"><?= $cat['post_count'] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Popüler Etiketler (Tag Cloud) -->
        <?php if (!empty($popularTags)): ?>
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm transition-colors">
                <h3 class="font-bold text-slate-800 dark:text-white mb-3 flex items-center gap-2 text-xs uppercase tracking-wider text-slate-400">
                    <i class="fa-solid fa-tags"></i> Etiket Bulutu
                </h3>
                <div class="flex flex-wrap gap-1.5">
                    <?php foreach ($popularTags as $tag): ?>
                        <a href="index.php?tag=<?= urlencode($tag['slug']) ?>" 
                           class="px-2.5 py-1 text-xs rounded-lg transition <?= $tagSlug === $tag['slug'] ? 'bg-indigo-600 text-white font-semibold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-indigo-50 hover:text-indigo-600' ?>">
                            #<?= sanitize($tag['name']) ?>
                            <span class="text-[10px] opacity-60">(<?= $tag['post_count'] ?>)</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- REST API Kartı -->
        <div class="bg-gradient-to-br from-slate-900 to-indigo-950 text-white rounded-3xl p-6 border border-slate-700 shadow-sm">
            <div class="flex items-center gap-2 text-indigo-400 text-sm font-bold mb-2">
                <i class="fa-solid fa-bolt text-amber-400"></i> RESTful JSON API
            </div>
            <p class="text-xs text-slate-300 leading-relaxed mb-3">
                Mobil uygulama veya Jamstack siteleriniz için yerleşik REST API uç noktalarını hemen kullanın.
            </p>
            <div class="text-[11px] bg-black/40 p-2.5 rounded-xl border border-slate-700 font-mono text-emerald-400">
                GET /api/posts.php
            </div>
        </div>
    </aside>
</div>

<!-- Canlı Ajax Arama Scripti -->
<script>
    const searchInput = document.getElementById('liveSearchInput');
    const searchResults = document.getElementById('liveSearchResults');
    const spinner = document.getElementById('searchSpinner');
    let debounceTimer;

    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim();

        if (query.length < 2) {
            searchResults.classList.add('hidden');
            searchResults.innerHTML = '';
            spinner.style.display = 'none';
            return;
        }

        spinner.style.display = 'block';

        debounceTimer = setTimeout(() => {
            fetch(`api/posts.php?search=${encodeURIComponent(query)}&limit=5`)
                .then(res => res.json())
                .then(data => {
                    spinner.style.display = 'none';
                    if (data.status === 'success' && data.data.length > 0) {
                        let html = '<div class="divide-y divide-slate-100 dark:divide-slate-700">';
                        data.data.forEach(p => {
                            html += `
                                <a href="post.php?slug=${encodeURIComponent(p.slug)}" class="block p-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                                    <div class="text-xs font-bold text-slate-800 dark:text-white">${p.title}</div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">${p.category_name || 'Genel'} &bull; ${p.read_time} dk okuma</div>
                                </a>
                            `;
                        });
                        html += '</div>';
                        searchResults.innerHTML = html;
                        searchResults.classList.remove('hidden');
                    } else {
                        searchResults.innerHTML = '<div class="p-4 text-xs text-slate-400 text-center">Sonuç bulunamadı</div>';
                        searchResults.classList.remove('hidden');
                    }
                })
                .catch(() => {
                    spinner.style.display = 'none';
                });
        }, 300);
    });

    // Dışarı tıklandığında canlı arama kutusunu kapat
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.classList.add('hidden');
        }
    });
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
