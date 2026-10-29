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

// Yazıyı çek
$stmt = $db->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug, u.username as author_name 
                      FROM posts p 
                      LEFT JOIN categories c ON p.category_id = c.id 
                      LEFT JOIN users u ON p.user_id = u.id 
                      WHERE p.slug = ? AND p.status = 'published'");
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    $pageTitle = "Yazı Bulunamadı";
    require_once __DIR__ . '/header.php';
    echo '<div class="text-center py-20"><h1 class="text-2xl font-bold text-slate-800 dark:text-white">404 - Yazı Bulunamadı</h1><p class="text-slate-500 dark:text-slate-400 mt-2">Aradığınız içerik silinmiş veya yayından kaldırılmış olabilir.</p><a href="index.php" class="inline-block mt-4 px-4 py-2 bg-indigo-600 text-white rounded-xl">Ana Sayfaya Dön</a></div>';
    require_once __DIR__ . '/footer.php';
    exit;
}

// Görüntülenme sayacını artır
if (!isset($_SESSION['viewed_post_' . $post['id']])) {
    $db->prepare("UPDATE posts SET views_count = views_count + 1 WHERE id = ?")->execute([$post['id']]);
    $_SESSION['viewed_post_' . $post['id']] = true;
    $post['views_count']++;
}

// Yazının Etiketlerini Çek
$postTags = getPostTags((int)$post['id']);

// İlgili Yazıları Çek (Aynı kategori veya ortak etiketleri olan yazılar)
$tagIds = array_column($postTags, 'id');
$relatedPosts = [];
if (!empty($tagIds)) {
    $inClause = implode(',', array_map('intval', $tagIds));
    $relStmt = $db->query("SELECT DISTINCT p.id, p.title, p.slug, p.featured_image, p.created_at, p.read_time
                           FROM posts p 
                           INNER JOIN post_tags pt ON pt.post_id = p.id
                           WHERE pt.tag_id IN ($inClause) AND p.id != {$post['id']} AND p.status = 'published'
                           LIMIT 3");
    $relatedPosts = $relStmt->fetchAll();
}

// Eğer etiketle bulunamadıysa aynı kategoriden çek
if (empty($relatedPosts) && $post['category_id']) {
    $relStmt = $db->prepare("SELECT id, title, slug, featured_image, created_at, read_time 
                            FROM posts 
                            WHERE category_id = ? AND id != ? AND status = 'published' 
                            ORDER BY created_at DESC LIMIT 3");
    $relStmt->execute([$post['category_id'], $post['id']]);
    $relatedPosts = $relStmt->fetchAll();
}

// Yorum Gönderme İşlemi
$commentSuccess = '';
$commentError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    checkCsrf();
    $authorName = trim($_POST['author_name'] ?? '');
    $authorEmail = trim($_POST['author_email'] ?? '');
    $commentContent = trim($_POST['comment_content'] ?? '');

    if (empty($authorName) || empty($commentContent)) {
        $commentError = 'Lütfen adınızı ve yorumunuzu doldurunuz.';
    } else {
        $stmt = $db->prepare("INSERT INTO comments (post_id, author_name, author_email, content, status) VALUES (?, ?, ?, ?, 'approved')");
        $stmt->execute([$post['id'], $authorName, $authorEmail, $commentContent]);
        $commentSuccess = 'Yorumunuz başarıyla paylaşıldı!';
    }
}

// Onaylı Yorumları çek
$cStmt = $db->prepare("SELECT * FROM comments WHERE post_id = ? AND status = 'approved' ORDER BY created_at DESC");
$cStmt->execute([$post['id']]);
$comments = $cStmt->fetchAll();

$pageTitle = $post['title'];
$pageDesc = mb_substr(strip_tags($post['content']), 0, 150) . '...';
require_once __DIR__ . '/header.php';
?>

<div class="max-w-4xl mx-auto">
    <!-- Geri Dön Butonu -->
    <div class="mb-6">
        <a href="index.php" class="text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 inline-flex items-center gap-2 transition">
            <i class="fa-solid fa-arrow-left text-xs"></i> Tüm Yazılara Geri Dön
        </a>
    </div>

    <article class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-6 sm:p-10 md:p-12 shadow-sm transition-colors">
        <!-- Başlık Bölümü -->
        <header class="mb-8 border-b border-slate-100 dark:border-slate-800 pb-8">
            <div class="flex flex-wrap items-center gap-3 text-xs mb-4">
                <?php if ($post['category_name']): ?>
                    <a href="index.php?category=<?= urlencode($post['category_slug']) ?>" 
                       class="font-semibold px-3 py-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-300 hover:bg-indigo-100 transition">
                        <?= sanitize($post['category_name']) ?>
                    </a>
                <?php endif; ?>
                <span class="text-slate-400 flex items-center gap-1">
                    <i class="fa-regular fa-clock"></i> <?= date('d F Y', strtotime($post['created_at'])) ?>
                </span>
                <span class="text-slate-400 flex items-center gap-1">
                    <i class="fa-regular fa-book-open"></i> <?= $post['read_time'] ?> dk okuma
                </span>
                <span class="text-slate-400 flex items-center gap-1">
                    <i class="fa-regular fa-eye"></i> <?= $post['views_count'] ?> okunma
                </span>
            </div>

            <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-slate-900 dark:text-white leading-tight mb-4">
                <?= sanitize($post['title']) ?>
            </h1>

            <!-- Etiket Rozetleri -->
            <?php if (!empty($postTags)): ?>
                <div class="flex flex-wrap gap-1.5 mt-4">
                    <?php foreach ($postTags as $t): ?>
                        <a href="index.php?tag=<?= urlencode($t['slug']) ?>" class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs hover:bg-indigo-50 hover:text-indigo-600 transition">
                            #<?= sanitize($t['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </header>

        <!-- Öne Çıkan Görsel -->
        <?php if (!empty($post['featured_image'])): ?>
            <div class="mb-8 rounded-2xl overflow-hidden max-h-[460px] border border-slate-100 dark:border-slate-800">
                <img src="<?= sanitize(str_starts_with($post['featured_image'], 'http') ? $post['featured_image'] : $post['featured_image']) ?>" alt="<?= sanitize($post['title']) ?>" class="w-full h-full object-cover">
            </div>
        <?php endif; ?>

        <!-- İçerik -->
        <div class="prose dark:prose-invert max-w-none text-slate-700 dark:text-slate-300 leading-relaxed text-base space-y-4">
            <?= Hook::applyFilters('the_content', $post['content'], $post); ?>
        </div>

        <!-- Beğeni (Like) Butonu Bölümü -->
        <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-800 flex justify-center">
            <button id="likeBtn" data-post-id="<?= $post['id'] ?>" class="inline-flex items-center gap-2.5 px-6 py-3 rounded-full bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 font-bold text-sm hover:scale-105 active:scale-95 border border-rose-200 dark:border-rose-900/40 transition shadow-sm">
                <i class="fa-solid fa-heart text-base" id="likeIcon"></i>
                <span>Bu Yazıyı Beğen</span>
                <span class="px-2 py-0.5 rounded-full bg-white dark:bg-slate-800 text-xs shadow-sm" id="likesCounter"><?= $post['likes_count'] ?? 0 ?></span>
            </button>
        </div>

        <!-- Yazar ve Düzenleme -->
        <div class="mt-12 pt-6 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/60 text-indigo-600 dark:text-indigo-300 font-bold flex items-center justify-center">
                    <?= substr($post['author_name'] ?? 'A', 0, 1) ?>
                </div>
                <div>
                    <div class="text-xs text-slate-400">Yazar</div>
                    <div class="text-sm font-semibold text-slate-800 dark:text-white"><?= sanitize($post['author_name'] ?? 'Admin') ?></div>
                </div>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="admin/posts.php?action=edit&id=<?= $post['id'] ?>" class="text-xs font-medium text-slate-600 dark:text-slate-300 hover:text-indigo-600 bg-slate-100 dark:bg-slate-800 px-3.5 py-2 rounded-xl transition">
                    <i class="fa-solid fa-pen-to-square mr-1"></i> Bu Yazıyı Düzenle
                </a>
            <?php endif; ?>
        </div>
    </article>

    <!-- İlgili Yazılar (Related Posts) -->
    <?php if (!empty($relatedPosts)): ?>
        <section class="mt-10">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="fa-solid fa-layer-group text-indigo-600"></i> İlgili Diğer Yazılar
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <?php foreach ($relatedPosts as $rp): ?>
                    <a href="post.php?slug=<?= urlencode($rp['slug']) ?>" class="group block p-4 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 hover:border-indigo-400 dark:hover:border-indigo-500 transition">
                        <h4 class="font-bold text-sm text-slate-800 dark:text-white group-hover:text-indigo-600 line-clamp-2 mb-2">
                            <?= sanitize($rp['title']) ?>
                        </h4>
                        <div class="text-[11px] text-slate-400 flex items-center gap-2">
                            <span><?= date('d.m.Y', strtotime($rp['created_at'])) ?></span>
                            <span>&bull;</span>
                            <span><?= $rp['read_time'] ?> dk okuma</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Yorumlar Bölümü -->
    <section class="mt-10 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-6 sm:p-10 shadow-sm transition-colors">
        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
            <i class="fa-regular fa-comments text-indigo-600"></i>
            Yorumlar (<?= count($comments) ?>)
        </h3>

        <?php if ($commentSuccess): ?>
            <div class="mb-6 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-sm flex items-center gap-2">
                <i class="fa-solid fa-circle-check"></i> <?= sanitize($commentSuccess) ?>
            </div>
        <?php endif; ?>

        <?php if ($commentError): ?>
            <div class="mb-6 p-4 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-sm flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation"></i> <?= sanitize($commentError) ?>
            </div>
        <?php endif; ?>

        <!-- Yorum Formu -->
        <form method="POST" action="post.php?slug=<?= urlencode($post['slug']) ?>#comment-form" id="comment-form" class="mb-10 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="submit_comment" value="1">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">İsminiz *</label>
                    <input type="text" name="author_name" required placeholder="Ad Soyad"
                           class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">E-posta (Yayınlanmaz)</label>
                    <input type="email" name="author_email" placeholder="ornek@mail.com"
                           class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Yorumunuz *</label>
                <textarea name="comment_content" rows="4" required placeholder="Düşüncelerinizi paylaşın..."
                          class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"></textarea>
            </div>

            <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-md shadow-indigo-200 dark:shadow-none transition">
                Yorum Gönder
            </button>
        </form>

        <!-- Yorum Listesi -->
        <div class="space-y-4">
            <?php if (empty($comments)): ?>
                <p class="text-sm text-slate-400 italic">Bu yazı için henüz bir yorum yapılmamış. İlk yorumu siz yapın!</p>
            <?php else: ?>
                <?php foreach ($comments as $c): ?>
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-sm text-slate-800 dark:text-white"><?= sanitize($c['author_name']) ?></span>
                            <span class="text-xs text-slate-400"><?= date('d.m.Y H:i', strtotime($c['created_at'])) ?></span>
                        </div>
                        <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed"><?= nl2br(sanitize($c['content'])) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<!-- AJAX Like Script -->
<script>
    const likeBtn = document.getElementById('likeBtn');
    if (likeBtn) {
        likeBtn.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            const counter = document.getElementById('likesCounter');
            const icon = document.getElementById('likeIcon');

            fetch('api/like.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'post_id=' + encodeURIComponent(postId)
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' || data.status === 'already_liked') {
                    counter.innerText = data.likes;
                    icon.classList.add('animate-bounce');
                    likeBtn.classList.add('bg-rose-600', 'text-white');
                    likeBtn.classList.remove('bg-rose-50', 'text-rose-600');
                    setTimeout(() => icon.classList.remove('animate-bounce'), 1000);
                }
            })
            .catch(err => console.error(err));
        });
    }
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
