<?php
// admin/plugin_editor.php - Eklenti Kod Düzenleyicisi (Built-in Code Editor)
session_start();
require_once __DIR__ . '/../config.php';
initDB();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
requireRole('admin');

$msg = '';
$err = '';
$pluginSlug = trim($_GET['plugin'] ?? $_POST['plugin'] ?? '');

$pluginsDir = realpath(__DIR__ . '/../plugins');
$allPlugins = PluginManager::getAllPlugins();

// Seçili eklentinin dosya yolu ve güvenliği
$currentPlugin = null;
$filePath = null;
$fileContent = '';

if (!empty($pluginSlug) && isset($allPlugins[$pluginSlug])) {
    $currentPlugin = $allPlugins[$pluginSlug];
    $filePath = realpath($currentPlugin['file']);

    // Path Traversal güvenlik kontrolü: dosya mutlaka plugins dizini altında olmalı
    if (!$filePath || !str_starts_with($filePath, $pluginsDir)) {
        $err = 'Geçersiz veya yetkisiz dosya yolu!';
        $currentPlugin = null;
    }
}

// Kod Kaydetme İşlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $currentPlugin && $filePath) {
    checkCsrf();
    $newCode = $_POST['code'] ?? '';

    // Basit PHP Sözdizimi (Syntax) Ön Kontrolü
    $tempFile = tempnam(sys_get_temp_dir(), 'novacms_lint_');
    file_put_contents($tempFile, $newCode);
    $lintOutput = shell_exec("php -l " . escapeshellarg($tempFile) . " 2>&1");
    @unlink($tempFile);

    if ($lintOutput && !str_contains($lintOutput, 'No syntax errors detected')) {
        $err = 'PHP Sözdizimi (Syntax) Hatası tespit edildi! Kod kaydedilmedi: ' . htmlspecialchars($lintOutput);
        $fileContent = $newCode; // Kullanıcının yazdığı kod kaybolmasın
    } else {
        if (file_put_contents($filePath, $newCode) !== false) {
            $msg = "'{$currentPlugin['name']}' kodları başarıyla kaydedildi!";
            $fileContent = $newCode;
        } else {
            $err = 'Dosya yazılamadı. Lütfen dosya izinlerini kontrol ediniz.';
            $fileContent = file_get_contents($filePath);
        }
    }
} elseif ($filePath && file_exists($filePath)) {
    $fileContent = file_get_contents($filePath);
}

$adminTitle = "Eklenti Kod Düzenleyici";
require_once __DIR__ . '/layout_header.php';
?>

<!-- CodeMirror Editor Assets CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/theme/dracula.min.css">
<style>
    .CodeMirror {
        height: 520px;
        border-radius: 0.75rem;
        font-family: 'JetBrains Mono', 'Fira Code', monospace;
        font-size: 13px;
        line-height: 1.5;
    }
</style>

<div class="space-y-6">
    <!-- Üst Başlık ve Eklenti Seçici -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="plugins.php" class="text-xs text-slate-400 hover:text-indigo-600 transition">&larr; Eklentilere Dön</a>
            </div>
            <h2 class="text-lg font-bold text-slate-800 mt-1">Eklenti Dosya Düzenleyicisi</h2>
            <p class="text-xs text-slate-500">Eklenti PHP kodlarını doğrudan tarayıcı üzerinden canlı olarak düzenleyin</p>
        </div>

        <form method="GET" action="plugin_editor.php" class="flex items-center gap-2">
            <label class="text-xs font-semibold text-slate-500 whitespace-nowrap">Düzenlenecek Eklenti:</label>
            <select name="plugin" onchange="this.form.submit()" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-indigo-500">
                <option value="">-- Eklenti Seçiniz --</option>
                <?php foreach ($allPlugins as $slug => $p): ?>
                    <option value="<?= sanitize($slug) ?>" <?= $pluginSlug === $slug ? 'selected' : '' ?>>
                        <?= sanitize($p['name']) ?> (<?= sanitize($slug) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if ($msg): ?>
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-2">
            <i class="fa-solid fa-circle-check"></i> <?= sanitize($msg) ?>
        </div>
    <?php endif; ?>

    <?php if ($err): ?>
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center gap-2">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= sanitize($err) ?>
        </div>
    <?php endif; ?>

    <?php if ($currentPlugin && $filePath): ?>
        <form method="POST" action="plugin_editor.php" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="plugin" value="<?= sanitize($pluginSlug) ?>">

            <div class="bg-slate-900 rounded-2xl p-4 border border-slate-800 shadow-md">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-800 text-xs text-slate-400">
                    <span class="font-mono text-indigo-400 font-bold flex items-center gap-2">
                        <i class="fa-brands fa-php text-sm"></i>
                        plugins/<?= sanitize($pluginSlug) ?>/plugin.php
                    </span>
                    <span class="text-[11px] bg-slate-800 px-2.5 py-1 rounded-md text-emerald-400">
                        Sözdizimi Korumalı (Auto-Lint)
                    </span>
                </div>

                <textarea name="code" id="codeEditor"><?= htmlspecialchars($fileContent) ?></textarea>
            </div>

            <div class="flex items-center justify-between">
                <div class="text-xs text-slate-400">
                    <i class="fa-solid fa-shield-halved text-emerald-500 mr-1"></i>
                    Kaydetmeden önce otomatik PHP syntax kontrolü yapılır. Hatalı kod sitenizi bozmaz.
                </div>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-sm shadow-md shadow-indigo-200 transition flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i> Değişiklikleri Kaydet
                </button>
            </div>
        </form>
    <?php else: ?>
        <div class="bg-white rounded-2xl border border-slate-200/80 p-12 text-center text-slate-400">
            <i class="fa-solid fa-code text-4xl mb-3 text-slate-300"></i>
            <p class="text-sm font-semibold">Lütfen yukarıdaki açılır menüden düzenlemek istediğiniz bir eklenti seçin.</p>
        </div>
    <?php endif; ?>
</div>

<!-- CodeMirror Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/css/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/clike/clike.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/php/php.min.js"></script>

<script>
    const textarea = document.getElementById('codeEditor');
    if (textarea) {
        const editor = CodeMirror.fromTextArea(textarea, {
            lineNumbers: true,
            mode: "application/x-httpd-php",
            theme: "dracula",
            indentUnit: 4,
            tabSize: 4,
            indentWithTabs: false,
            matchBrackets: true,
            autoCloseBrackets: true
        });
        editor.setSize("100%", "520px");
    }
</script>

<?php require_once __DIR__ . '/layout_footer.php'; ?>

