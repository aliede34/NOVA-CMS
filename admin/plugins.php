<?php
// admin/plugins.php - Eklenti Yönetimi & ZIP ile Eklenti Yükleme
$adminTitle = "Eklenti & Modül Yönetimi";
require_once __DIR__ . '/layout_header.php';
requireRole('admin');

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $subAction = $_POST['sub_action'] ?? '';
    $pluginSlug = trim($_POST['plugin_slug'] ?? '');

    if ($subAction === 'toggle') {
        $activePlugins = PluginManager::getActivePlugins();
        if (in_array($pluginSlug, $activePlugins)) {
            PluginManager::deactivatePlugin($pluginSlug);
            $msg = "'$pluginSlug' eklentisi devre dışı bırakıldı.";
        } else {
            PluginManager::activatePlugin($pluginSlug);
            $msg = "'$pluginSlug' eklentisi başarıyla etkinleştirildi!";
        }
    } elseif ($subAction === 'upload_zip' && isset($_FILES['plugin_zip']) && $_FILES['plugin_zip']['error'] === UPLOAD_ERR_OK) {
        $zipFile = $_FILES['plugin_zip']['tmp_name'];
        $zipName = $_FILES['plugin_zip']['name'];

        if (pathinfo($zipName, PATHINFO_EXTENSION) !== 'zip') {
            $err = 'Lütfen yalnızca .zip uzantılı bir eklenti paketi yükleyiniz.';
        } elseif (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($zipFile) === TRUE) {
                $pluginsDir = __DIR__ . '/../plugins/';
                $zip->extractTo($pluginsDir);
                $zip->close();
                $msg = "Eklenti paketi başarıyla yüklendi ve açıldı!";
            } else {
                $err = 'ZIP dosyası açılamadı.';
            }
        } else {
            $err = 'Sunucunuzda PHP ZipArchive eklentisi yüklü değil.';
        }
    } elseif ($subAction === 'create') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $author = trim($_POST['author'] ?? $_SESSION['username']);
        $slug = slugify($name);

        if (empty($name)) {
            $err = 'Eklenti adı zorunludur.';
        } else {
            $targetDir = __DIR__ . '/../plugins/' . $slug;
            if (is_dir($targetDir)) {
                $err = 'Bu isimde veya dizinde bir eklenti zaten mevcut.';
            } else {
                mkdir($targetDir, 0755, true);
                $templateCode = <<<PHP
<?php
/**
 * Plugin Name: $name
 * Description: $description
 * Version: 1.0.0
 * Author: $author
 */

// Örnek Hook Kullanımları:
// Hook::addAction('after_body_start', function() { echo "<!-- Sayfa Başı -->"; });
// Hook::addFilter('the_content', function(\$content, \$post) { return \$content; });

PHP;
                file_put_contents($targetDir . '/plugin.php', $templateCode);
                PluginManager::activatePlugin($slug);
                $msg = "'$name' eklentisi oluşturuldu ve etkinleştirildi! plugins/$slug/plugin.php dosyasından dilediğiniz kodu yazabilirsiniz.";
            }
        }
    }
}

$allPlugins = PluginManager::getAllPlugins();
$activePlugins = PluginManager::getActivePlugins();
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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Sol Kolon: ZIP Yükle & Yeni Oluştur -->
    <div class="space-y-6">
        <!-- ZIP ile Eklenti Yükle -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
            <div class="flex items-center gap-2.5 mb-3 pb-3 border-b border-slate-100">
                <span class="p-2 bg-indigo-50 text-indigo-600 rounded-xl text-sm"><i class="fa-solid fa-file-zipper"></i></span>
                <h3 class="font-bold text-slate-800 text-base">ZIP ile Eklenti Yükle</h3>
            </div>
            <p class="text-xs text-slate-500 mb-4">İndirdiğiniz bir eklenti paketini (.zip) doğrudan sisteme kurun.</p>

            <form method="POST" action="plugins.php" enctype="multipart/form-data" class="space-y-3">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="sub_action" value="upload_zip">

                <input type="file" name="plugin_zip" required accept=".zip" class="text-xs text-slate-500 file:mr-2 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer w-full">
                <button type="submit" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-xs shadow transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Eklentiyi Yükle ve Kur
                </button>
            </form>
        </div>

        <!-- Yeni Eklenti Oluştur Formu -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
            <div class="flex items-center gap-2.5 mb-4 pb-3 border-b border-slate-100">
                <span class="p-2 bg-purple-50 text-purple-600 rounded-xl text-sm"><i class="fa-solid fa-puzzle-piece"></i></span>
                <h3 class="font-bold text-slate-800 text-base">Yeni Modül Yaz</h3>
            </div>

            <form method="POST" action="plugins.php" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="sub_action" value="create">

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Eklenti Adı *</label>
                    <input type="text" name="name" required placeholder="Örn: WhatsApp Canlı Destek"
                           class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 transition">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Açıklama</label>
                    <textarea name="description" rows="2" placeholder="Eklentinin işlevi..."
                              class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 transition"></textarea>
                </div>

                <button type="submit" class="w-full py-2.5 px-4 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-xl text-sm shadow transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-plus text-xs"></i> Eklenti İskeleti Oluştur
                </button>
            </form>
        </div>
    </div>

    <!-- Eklenti Listesi -->
    <div class="lg:col-span-2 space-y-4">
        <div class="flex items-center justify-between mb-2">
            <div>
                <h3 class="font-bold text-slate-800 text-lg">Yüklü Eklentiler (<?= count($allPlugins) ?>)</h3>
                <p class="text-xs text-slate-500">Etkinleştirmek veya kapatmak için anahtarı kullanın</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700">
                <?= count($activePlugins) ?> Aktif
            </span>
        </div>

        <?php if (empty($allPlugins)): ?>
            <div class="bg-white rounded-2xl border border-slate-200/80 p-12 text-center text-slate-400">
                Henüz hiç eklenti yüklenmemiş.
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 gap-4">
                <?php foreach ($allPlugins as $slug => $p): ?>
                    <?php $isActive = in_array($slug, $activePlugins); ?>
                    <div class="bg-white rounded-2xl border <?= $isActive ? 'border-indigo-300 ring-1 ring-indigo-200' : 'border-slate-200/80' ?> p-6 shadow-sm transition flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-3">
                                <h4 class="font-bold text-base text-slate-900"><?= sanitize($p['name']) ?></h4>
                                <span class="text-[11px] font-mono px-2 py-0.5 rounded bg-slate-100 text-slate-600">v<?= sanitize($p['version']) ?></span>
                                <?php if ($isActive): ?>
                                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                                    </span>
                                <?php else: ?>
                                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">
                                        Pasif
                                    </span>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs text-slate-500 leading-relaxed"><?= sanitize($p['description']) ?></p>
                            <div class="text-[11px] text-slate-400 flex items-center gap-3 pt-1">
                                <span><i class="fa-regular fa-user mr-1"></i><?= sanitize($p['author']) ?></span>
                                <span>&bull;</span>
                                <span class="font-mono text-slate-400">plugins/<?= sanitize($slug) ?>/plugin.php</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <a href="plugin_editor.php?plugin=<?= urlencode($slug) ?>" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center gap-1.5" title="Kodu Düzenle">
                                <i class="fa-solid fa-code text-[11px] text-indigo-600"></i> Kodu Düzenle
                            </a>
                            <form method="POST" action="plugins.php" class="inline-block">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="sub_action" value="toggle">
                                <input type="hidden" name="plugin_slug" value="<?= sanitize($slug) ?>">
                                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold transition <?= $isActive ? 'bg-rose-50 text-rose-600 hover:bg-rose-100' : 'bg-indigo-600 text-white hover:bg-indigo-700' ?>">
                                    <?= $isActive ? 'Devre Dışı Bırak' : 'Etkinleştir' ?>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
