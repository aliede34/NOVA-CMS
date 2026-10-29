<?php
$adminTitle = "Yorum Yönetimi";
require_once __DIR__ . '/layout_header.php';

$db = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $subAction = $_POST['sub_action'] ?? '';
    $commentId = (int)($_POST['id'] ?? 0);

    if ($subAction === 'delete' && $commentId > 0) {
        $stmt = $db->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->execute([$commentId]);
        $msg = 'Yorum silindi.';
    } elseif ($subAction === 'toggle_status' && $commentId > 0) {
        $currStatus = $_POST['status'] ?? 'approved';
        $newStatus = ($currStatus === 'approved') ? 'pending' : 'approved';
        $stmt = $db->prepare("UPDATE comments SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $commentId]);
        $msg = 'Yorum durumu güncellendi.';
    }
}

$comments = $db->query("SELECT c.*, p.title as post_title, p.slug as post_slug 
                        FROM comments c 
                        LEFT JOIN posts p ON c.post_id = p.id 
                        ORDER BY c.id DESC")->fetchAll();
?>

<?php if ($msg): ?>
    <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-2">
        <i class="fa-solid fa-circle-check"></i> <?= sanitize($msg) ?>
    </div>
<?php endif; ?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Ziyaretçi Yorumları</h2>
        <p class="text-xs text-slate-500">Yazılara gelen tüm yorumlar ve moderasyon</p>
    </div>
</div>

<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50/50 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                <th class="py-3 px-6">Yazar</th>
                <th class="py-3 px-6">Yorum</th>
                <th class="py-3 px-6">İlgili Yazı</th>
                <th class="py-3 px-6">Durum</th>
                <th class="py-3 px-6">Tarih</th>
                <th class="py-3 px-6 text-right">İşlemler</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
            <?php if (empty($comments)): ?>
                <tr>
                    <td colspan="6" class="py-8 text-center text-slate-400">Henüz hiç yorum yapılmamış.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($comments as $c): ?>
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="py-4 px-6 font-semibold text-slate-800">
                            <?= sanitize($c['author_name']) ?>
                            <?php if ($c['author_email']): ?>
                                <div class="text-[11px] text-slate-400 font-normal"><?= sanitize($c['author_email']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-6 text-slate-600 max-w-xs break-words">
                            <?= sanitize($c['content']) ?>
                        </td>
                        <td class="py-4 px-6 text-xs text-indigo-600 font-medium">
                            <a href="../post.php?slug=<?= urlencode($c['post_slug']) ?>" target="_blank" class="hover:underline">
                                <?= sanitize($c['post_title'] ?? 'Yazı') ?>
                            </a>
                        </td>
                        <td class="py-4 px-6">
                            <span class="text-xs px-2.5 py-1 rounded-full font-medium <?= $c['status'] == 'approved' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' ?>">
                                <?= $c['status'] == 'approved' ? 'Onaylı' : 'Beklemede' ?>
                            </span>
                        </td>
                        <td class="py-4 px-6 text-xs text-slate-400">
                            <?= date('d.m.Y H:i', strtotime($c['created_at'])) ?>
                        </td>
                        <td class="py-4 px-6 text-right space-x-2">
                            <form method="POST" action="comments.php" class="inline-block">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="sub_action" value="toggle_status">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <input type="hidden" name="status" value="<?= $c['status'] ?>">
                                <button type="submit" class="p-2 text-slate-500 hover:text-indigo-600 rounded-lg transition" title="Durum Değiştir">
                                    <i class="fa-solid fa-arrows-rotate"></i>
                                </button>
                            </form>
                            <form method="POST" action="comments.php" class="inline-block" onsubmit="return confirm('Bu yorumu silmek istediğinizden emin misiniz?');">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="sub_action" value="delete">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <button type="submit" class="p-2 text-rose-500 hover:bg-rose-50 rounded-lg transition" title="Sil">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>

