<?php
$adminTitle = "Genel Bakış";
require_once __DIR__ . '/layout_header.php';

$db = getDB();
$postCount = $db->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$publishedCount = $db->query("SELECT COUNT(*) FROM posts WHERE status='published'")->fetchColumn();
$categoryCount = $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$pageCount = $db->query("SELECT COUNT(*) FROM pages")->fetchColumn();
$commentCount = $db->query("SELECT COUNT(*) FROM comments")->fetchColumn();
$totalViews = $db->query("SELECT SUM(views_count) FROM posts")->fetchColumn() ?: 0;

// Son 5 yazı
$recentPosts = $db->query("SELECT p.*, c.name as category_name FROM posts p LEFT JOIN categories c ON p.category_id=c.id ORDER BY p.id DESC LIMIT 5")->fetchAll();
// Son 4 yorum
$recentComments = $db->query("SELECT c.*, p.title as post_title FROM comments c LEFT JOIN posts p ON c.post_id = p.id ORDER BY c.id DESC LIMIT 4")->fetchAll();
?>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
            <i class="fa-solid fa-newspaper"></i>
        </div>
        <div>
            <div class="text-2xl font-bold text-slate-800"><?= $postCount ?></div>
            <div class="text-xs text-slate-500 font-medium">Toplam Yazı (<?= $publishedCount ?> Yayında)</div>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
            <i class="fa-solid fa-eye"></i>
        </div>
        <div>
            <div class="text-2xl font-bold text-slate-800"><?= number_format($totalViews) ?></div>
            <div class="text-xs text-slate-500 font-medium">Toplam Okunma</div>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
            <i class="fa-solid fa-comments"></i>
        </div>
        <div>
            <div class="text-2xl font-bold text-slate-800"><?= $commentCount ?></div>
            <div class="text-xs text-slate-500 font-medium">Ziyaretçi Yorumu</div>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
            <i class="fa-solid fa-tags"></i>
        </div>
        <div>
            <div class="text-2xl font-bold text-slate-800"><?= $categoryCount ?></div>
            <div class="text-xs text-slate-500 font-medium">Kategori</div>
        </div>
    </div>
</div>

<!-- Layout: Posts + Comments -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-100">
            <h2 class="font-bold text-slate-800 text-base">Son Eklenen Yazılar</h2>
            <a href="posts.php?action=create" class="text-xs font-semibold px-3 py-1.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                + Yeni Yazı Ekle
            </a>
        </div>

        <?php if (empty($recentPosts)): ?>
            <p class="text-sm text-slate-500 text-center py-6">Henüz bir yazı eklenmedi.</p>
        <?php else: ?>
            <div class="divide-y divide-slate-100">
                <?php foreach ($recentPosts as $p): ?>
                    <div class="py-3.5 flex items-center justify-between">
                        <div>
                            <a href="posts.php?action=edit&id=<?= $p['id'] ?>" class="text-sm font-semibold text-slate-800 hover:text-indigo-600 transition">
                                <?= sanitize($p['title']) ?>
                            </a>
                            <div class="text-xs text-slate-400 mt-0.5 flex items-center gap-2">
                                <span><?= $p['category_name'] ? sanitize($p['category_name']) : 'Genel' ?></span>
                                <span>&bull;</span>
                                <span><i class="fa-regular fa-eye mr-1"></i><?= $p['views_count'] ?></span>
                                <span>&bull;</span>
                                <span><?= date('d.m.Y H:i', strtotime($p['created_at'])) ?></span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs px-2.5 py-1 rounded-full font-medium <?= $p['status'] == 'published' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>">
                                <?= $p['status'] == 'published' ? 'Yayında' : 'Taslak' ?>
                            </span>
                            <a href="posts.php?action=edit&id=<?= $p['id'] ?>" class="p-1.5 text-slate-400 hover:text-indigo-600">
                                <i class="fa-solid fa-pen-to-square text-sm"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Son Yorumlar -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <h3 class="font-bold text-slate-800 text-base">Son Yorumlar</h3>
            <a href="comments.php" class="text-xs font-semibold text-indigo-600 hover:underline">Tümü &rarr;</a>
        </div>

        <?php if (empty($recentComments)): ?>
            <p class="text-xs text-slate-400 py-6 text-center">Henüz yorum bulunmuyor.</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($recentComments as $rc): ?>
                    <div class="p-3 bg-slate-50 rounded-xl text-xs">
                        <div class="flex justify-between items-center font-bold text-slate-800 mb-1">
                            <span><?= sanitize($rc['author_name']) ?></span>
                            <span class="font-normal text-slate-400"><?= date('d.m', strtotime($rc['created_at'])) ?></span>
                        </div>
                        <p class="text-slate-600 line-clamp-2"><?= sanitize($rc['content']) ?></p>
                        <div class="mt-1 text-[11px] text-indigo-600 truncate">
                            <i class="fa-solid fa-arrow-turn-up rotate-90 mr-1"></i><?= sanitize($rc['post_title'] ?? 'Yazı') ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
