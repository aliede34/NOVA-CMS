<?php
// admin/messages.php - İletişim Mesajları Gelen Kutusu
$adminTitle = "Gelen Mesajlar";
require_once __DIR__ . '/layout_header.php';
requireRole('admin', 'editor');

$db = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $subAction = $_POST['sub_action'] ?? '';
    $msgId = (int)($_POST['id'] ?? 0);

    if ($subAction === 'delete' && $msgId > 0) {
        $stmt = $db->prepare("DELETE FROM messages WHERE id = ?");
        $stmt->execute([$msgId]);
        $msg = 'Mesaj silindi.';
    } elseif ($subAction === 'mark_read' && $msgId > 0) {
        $stmt = $db->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
        $stmt->execute([$msgId]);
        $msg = 'Mesaj okundu olarak işaretlendi.';
    }
}

$messages = $db->query("SELECT * FROM messages ORDER BY id DESC")->fetchAll();
?>

<?php if ($msg): ?>
    <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-2">
        <i class="fa-solid fa-circle-check"></i> <?= sanitize($msg) ?>
    </div>
<?php endif; ?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">İletişim Mesajları</h2>
        <p class="text-xs text-slate-500">Ziyaretçiler tarafından iletişim formundan gönderilen mesajlar</p>
    </div>
</div>

<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50/50 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                <th class="py-3 px-6">Durum</th>
                <th class="py-3 px-6">Gönderen</th>
                <th class="py-3 px-6">Konu & Mesaj</th>
                <th class="py-3 px-6">Tarih</th>
                <th class="py-3 px-6 text-right">İşlemler</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
            <?php if (empty($messages)): ?>
                <tr>
                    <td colspan="5" class="py-8 text-center text-slate-400">Henüz hiç mesaj gönderilmemiş.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($messages as $m): ?>
                    <tr class="hover:bg-slate-50/70 transition <?= $m['is_read'] ? '' : 'bg-indigo-50/30' ?>">
                        <td class="py-4 px-6">
                            <?php if (!$m['is_read']): ?>
                                <span class="px-2 py-0.5 rounded-full bg-rose-50 text-rose-600 text-xs font-bold inline-flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Yeni
                                </span>
                            <?php else: ?>
                                <span class="text-xs text-slate-400">Okundu</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-6 font-semibold text-slate-800">
                            <?= sanitize($m['name']) ?>
                            <div class="text-xs text-slate-400 font-normal"><?= sanitize($m['email']) ?></div>
                        </td>
                        <td class="py-4 px-6 max-w-md">
                            <div class="font-bold text-slate-800 text-xs mb-1"><?= sanitize($m['subject']) ?></div>
                            <p class="text-xs text-slate-600 whitespace-pre-line leading-relaxed"><?= sanitize($m['message']) ?></p>
                        </td>
                        <td class="py-4 px-6 text-xs text-slate-400 whitespace-nowrap">
                            <?= date('d.m.Y H:i', strtotime($m['created_at'])) ?>
                        </td>
                        <td class="py-4 px-6 text-right space-x-2 whitespace-nowrap">
                            <?php if (!$m['is_read']): ?>
                                <form method="POST" action="messages.php" class="inline-block">
                                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="sub_action" value="mark_read">
                                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                    <button type="submit" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg" title="Okundu Olarak İşaretle">
                                        <i class="fa-solid fa-envelope-open"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <a href="mailto:<?= urlencode($m['email']) ?>?subject=Re: <?= urlencode($m['subject']) ?>" class="p-2 text-slate-500 hover:text-indigo-600 rounded-lg inline-block" title="E-posta ile Yanıtla">
                                <i class="fa-solid fa-reply"></i>
                            </a>
                            <form method="POST" action="messages.php" class="inline-block" onsubmit="return confirm('Bu mesajı silmek istediğinizden emin misiniz?');">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="sub_action" value="delete">
                                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                <button type="submit" class="p-2 text-rose-500 hover:bg-rose-50 rounded-lg" title="Sil">
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

