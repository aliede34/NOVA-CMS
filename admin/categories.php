<?php
$adminTitle = "Kategori Yönetimi";
require_once __DIR__ . '/layout_header.php';

$db = getDB();
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $subAction = $_POST['sub_action'] ?? '';

    if ($subAction === 'save') {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $catId = (int)($_POST['id'] ?? 0);

        if (empty($name)) {
            $err = 'Kategori adı gereklidir.';
        } else {
            $slug = !empty($slug) ? slugify($slug) : slugify($name);
            try {
                if ($catId > 0) {
                    $stmt = $db->prepare("UPDATE categories SET name = ?, slug = ? WHERE id = ?");
                    $stmt->execute([$name, $slug, $catId]);
                    $msg = 'Kategori güncellendi.';
                } else {
                    $stmt = $db->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
                    $stmt->execute([$name, $slug]);
                    $msg = 'Kategori başarıyla eklendi.';
                }
            } catch (PDOException $e) {
                $err = 'Bu kategori veya kalıcı bağlantı (slug) zaten mevcut.';
            }
        }
    } elseif ($subAction === 'delete') {
        $delId = (int)($_POST['id'] ?? 0);
        if ($delId > 0) {
            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$delId]);
            $msg = 'Kategori silindi.';
        }
    }
}

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editCat = null;
if ($editId > 0) {
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$editId]);
    $editCat = $stmt->fetch();
}

$categories = $db->query("SELECT c.*, COUNT(p.id) as post_count FROM categories c LEFT JOIN posts p ON p.category_id = c.id GROUP BY c.id ORDER BY c.name ASC")->fetchAll();
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

<div class="grid grid-cols-1 md:grid-cols-3 gap-8">
    <!-- Form Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
        <h3 class="font-bold text-slate-800 mb-4 pb-3 border-b border-slate-100">
            <?= $editCat ? 'Kategoriyi Düzenle' : 'Yeni Kategori Ekle' ?>
        </h3>

        <form method="POST" action="categories.php" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="sub_action" value="save">
            <input type="hidden" name="id" value="<?= $editCat['id'] ?? 0 ?>">

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Kategori Adı</label>
                <input type="text" name="name" required value="<?= sanitize($editCat['name'] ?? '') ?>" placeholder="Örn: Yapay Zeka"
                       class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Kalıcı Bağlantı (Slug)</label>
                <input type="text" name="slug" value="<?= sanitize($editCat['slug'] ?? '') ?>" placeholder="Örn: yapay-zeka"
                       class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition">
            </div>

            <div class="flex items-center gap-2 pt-2">
                <button type="submit" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-sm shadow-md shadow-indigo-200 transition">
                    <?= $editCat ? 'Güncelle' : 'Ekle' ?>
                </button>
                <?php if ($editCat): ?>
                    <a href="categories.php" class="py-2.5 px-3 bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold rounded-xl text-sm transition">
                        İptal
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Categories List -->
    <div class="md:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100">
            <h3 class="font-bold text-slate-800">Mevcut Kategoriler</h3>
        </div>
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50/50 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                    <th class="py-3 px-6">Ad</th>
                    <th class="py-3 px-6">Slug</th>
                    <th class="py-3 px-6">Yazı Sayısı</th>
                    <th class="py-3 px-6 text-right">İşlemler</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="4" class="py-6 text-center text-slate-400">Kategori bulunamadı.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories as $c): ?>
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-6 font-semibold text-slate-800"><?= sanitize($c['name']) ?></td>
                            <td class="py-3.5 px-6 font-mono text-xs text-slate-500"><?= sanitize($c['slug']) ?></td>
                            <td class="py-3.5 px-6">
                                <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 text-xs font-semibold">
                                    <?= $c['post_count'] ?> yazı
                                </span>
                            </td>
                            <td class="py-3.5 px-6 text-right space-x-2">
                                <a href="categories.php?edit=<?= $c['id'] ?>" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg inline-block">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <form method="POST" action="categories.php" class="inline-block" onsubmit="return confirm('Bu kategoriyi silmek istediğinizden emin misiniz? (İçindeki yazıların kategorisi boşaltılır)');">
                                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="sub_action" value="delete">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
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
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>

