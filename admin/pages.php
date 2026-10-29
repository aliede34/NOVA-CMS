<?php
$adminTitle = "Sayfa Yönetimi";
require_once __DIR__ . '/layout_header.php';

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $subAction = $_POST['sub_action'] ?? '';

    if ($subAction === 'save') {
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $content = $_POST['content'] ?? '';
        $pageId = (int)($_POST['id'] ?? 0);

        if (empty($title)) {
            $err = 'Başlık zorunludur.';
        } else {
            $slug = !empty($slug) ? slugify($slug) : slugify($title);
            try {
                if ($pageId > 0) {
                    $stmt = $db->prepare("UPDATE pages SET title = ?, slug = ?, content = ? WHERE id = ?");
                    $stmt->execute([$title, $slug, $content, $pageId]);
                    $msg = 'Sayfa güncellendi.';
                    $action = 'list';
                } else {
                    $stmt = $db->prepare("INSERT INTO pages (title, slug, content) VALUES (?, ?, ?)");
                    $stmt->execute([$title, $slug, $content]);
                    $msg = 'Yeni sayfa oluşturuldu.';
                    $action = 'list';
                }
            } catch (PDOException $e) {
                $err = 'Bu sayfa adresi (slug) zaten mevcut.';
            }
        }
    } elseif ($subAction === 'delete') {
        $delId = (int)($_POST['id'] ?? 0);
        if ($delId > 0) {
            $stmt = $db->prepare("DELETE FROM pages WHERE id = ?");
            $stmt->execute([$delId]);
            $msg = 'Sayfa silindi.';
        }
    }
}
?>

<?php if ($msg): ?>
    <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-2">
        <i class="fa-solid fa-circle-check"></i> <?= sanitize($msg) ?>
    </div>
<?php endif; ?>

<?php if ($err): ?>
    <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center gap-2">
        <i class="fa-solid fa-triangle-exclamation"></i> <?= sanitize($err) ?>
    </div>
<?php endif; ?>

<?php if ($action === 'create' || $action === 'edit'): ?>
    <?php
    $page = ['id' => 0, 'title' => '', 'slug' => '', 'content' => ''];
    if ($action === 'edit' && $id > 0) {
        $stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
        $stmt->execute([$id]);
        $page = $stmt->fetch() ?: $page;
    }
    ?>
    <div class="max-w-4xl bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
        <div class="flex items-center justify-between pb-4 mb-6 border-b border-slate-100">
            <h2 class="text-lg font-bold text-slate-800">
                <?= $action === 'edit' ? 'Sayfayı Düzenle' : 'Yeni Sayfa Ekle' ?>
            </h2>
            <a href="pages.php" class="text-sm text-slate-500 hover:text-slate-800">&larr; Vazgeç ve Geri Dön</a>
        </div>

        <form method="POST" action="pages.php" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="sub_action" value="save">
            <input type="hidden" name="id" value="<?= $page['id'] ?>">

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Sayfa Başlığı</label>
                <input type="text" name="title" required value="<?= sanitize($page['title']) ?>" placeholder="Örn: Hakkımızda, İletişim..." 
                       class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Kalıcı Bağlantı (Slug)</label>
                <input type="text" name="slug" value="<?= sanitize($page['slug']) ?>" placeholder="Otomatik üretilir veya özelleştirin..." 
                       class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Sayfa İçeriği (HTML destekli)</label>
                <textarea name="content" rows="12" placeholder="Sayfa içeriğini HTML formatında yazabilirsiniz..." 
                          class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition"><?= htmlspecialchars($page['content']) ?></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="pages.php" class="px-5 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">Vazgeç</a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-md shadow-indigo-200 transition">
                    Kaydet
                </button>
            </div>
        </form>
    </div>

<?php else: ?>
    <?php
    $pages = $db->query("SELECT * FROM pages ORDER BY id ASC")->fetchAll();
    ?>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Statik Sayfalar</h2>
            <p class="text-xs text-slate-500">Üst menüde gösterilen statik içerik sayfaları</p>
        </div>
        <a href="pages.php?action=create" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-md shadow-indigo-200 transition inline-flex items-center gap-2">
            <i class="fa-solid fa-plus text-xs"></i> Yeni Sayfa
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50/50 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                    <th class="py-3 px-6">Başlık</th>
                    <th class="py-3 px-6">Slug (URL)</th>
                    <th class="py-3 px-6">Eklenme Tarihi</th>
                    <th class="py-3 px-6 text-right">İşlemler</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                <?php if (empty($pages)): ?>
                    <tr>
                        <td colspan="4" class="py-6 text-center text-slate-400">Henüz sayfa oluşturulmamış.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pages as $p): ?>
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-6 font-semibold text-slate-800">
                                <a href="../page.php?slug=<?= urlencode($p['slug']) ?>" target="_blank" class="hover:text-indigo-600">
                                    <?= sanitize($p['title']) ?>
                                    <i class="fa-solid fa-arrow-up-right-from-square text-[10px] text-slate-400 ml-1"></i>
                                </a>
                            </td>
                            <td class="py-3.5 px-6 font-mono text-xs text-slate-500">/page.php?slug=<?= sanitize($p['slug']) ?></td>
                            <td class="py-3.5 px-6 text-xs text-slate-400"><?= date('d.m.Y', strtotime($p['created_at'])) ?></td>
                            <td class="py-3.5 px-6 text-right space-x-2">
                                <a href="pages.php?action=edit&id=<?= $p['id'] ?>" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg inline-block">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <form method="POST" action="pages.php" class="inline-block" onsubmit="return confirm('Bu sayfayı silmek istediğinizden emin misiniz?');">
                                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="sub_action" value="delete">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
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
<?php endif; ?>

<?php require_once __DIR__ . '/layout_footer.php'; ?>

