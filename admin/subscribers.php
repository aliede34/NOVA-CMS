<?php
// admin/subscribers.php - Bülten Aboneleri Yönetimi & CSV İndirme
session_start();
require_once __DIR__ . '/../config.php';
initDB();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
requireRole('admin', 'editor');

$db = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $subAction = $_POST['sub_action'] ?? '';
    
    if ($subAction === 'delete') {
        $delId = (int)($_POST['id'] ?? 0);
        if ($delId > 0) {
            $stmt = $db->prepare("DELETE FROM subscribers WHERE id = ?");
            $stmt->execute([$delId]);
            $msg = 'Abone silindi.';
        }
    } elseif ($subAction === 'export_csv') {
        $subscribers = $db->query("SELECT id, email, created_at FROM subscribers ORDER BY id ASC")->fetchAll();
        $filename = 'subscribers_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'E-posta', 'Kayıt Tarihi']);
        foreach ($subscribers as $row) {
            fputcsv($output, [$row['id'], $row['email'], $row['created_at']]);
        }
        fclose($output);
        exit;
    }
}

$adminTitle = "E-Bülten Aboneleri";
require_once __DIR__ . '/layout_header.php';
$subscribers = $db->query("SELECT * FROM subscribers ORDER BY id DESC")->fetchAll();
?>

<?php if ($msg): ?>
    <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-2">
        <i class="fa-solid fa-circle-check"></i> <?= sanitize($msg) ?>
    </div>
<?php endif; ?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Bülten Aboneleri</h2>
        <p class="text-xs text-slate-500">Sitenizden e-bültene kaydolan ziyaretçilerin listesi (<?= count($subscribers) ?> abone)</p>
    </div>
    <?php if (!empty($subscribers)): ?>
        <form method="POST" action="subscribers.php">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="sub_action" value="export_csv">
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow transition flex items-center gap-2">
                <i class="fa-solid fa-file-csv text-sm"></i> CSV Olarak İndir
            </button>
        </form>
    <?php endif; ?>
</div>

<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50/50 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                <th class="py-3 px-6">ID</th>
                <th class="py-3 px-6">E-posta Adresi</th>
                <th class="py-3 px-6">Kayıt Tarihi</th>
                <th class="py-3 px-6 text-right">İşlemler</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
            <?php if (empty($subscribers)): ?>
                <tr>
                    <td colspan="4" class="py-8 text-center text-slate-400">Henüz hiç e-bülten abonesi bulunmuyor.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($subscribers as $s): ?>
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="py-3.5 px-6 font-mono text-xs text-slate-400">#<?= $s['id'] ?></td>
                        <td class="py-3.5 px-6 font-semibold text-slate-800 flex items-center gap-2">
                            <i class="fa-regular fa-envelope text-indigo-500"></i>
                            <?= sanitize($s['email']) ?>
                        </td>
                        <td class="py-3.5 px-6 text-xs text-slate-400"><?= date('d.m.Y H:i', strtotime($s['created_at'])) ?></td>
                        <td class="py-3.5 px-6 text-right">
                            <form method="POST" action="subscribers.php" class="inline-block" onsubmit="return confirm('Bu aboneyi silmek istediğinizden emin misiniz?');">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="sub_action" value="delete">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                <button type="submit" class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg">
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

