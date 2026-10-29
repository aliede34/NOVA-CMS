<?php
// admin/media.php - Medya Kütüphanesi & Dosya Yöneticisi
$adminTitle = "Medya Kütüphanesi";
require_once __DIR__ . '/layout_header.php';

$msg = '';
$err = '';

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Yeni Dosya Yükleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $subAction = $_POST['sub_action'] ?? '';

    if ($subAction === 'upload' && isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['file'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (in_array($mime, $allowed)) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
            $newName = $cleanName . '_' . time() . '.' . $ext;

            if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $newName)) {
                $msg = 'Dosya başarıyla yüklendi: ' . $newName;
            } else {
                $err = 'Dosya yükleme sırasında hata oluştu.';
            }
        } else {
            $err = 'Desteklenmeyen dosya türü (yalnızca JPG, PNG, WebP, GIF, PDF).';
        }
    } elseif ($subAction === 'delete') {
        $filename = basename($_POST['filename'] ?? '');
        $filepath = UPLOAD_DIR . $filename;
        if (file_exists($filepath) && !is_dir($filepath)) {
            @unlink($filepath);
            $msg = 'Dosya başarıyla silindi.';
        }
    }
}

// Uploads klasöründeki dosyaları tara
$files = [];
$rawItems = scandir(UPLOAD_DIR);
foreach ($rawItems as $item) {
    if ($item === '.' || $item === '..' || $item === '.gitkeep') continue;
    $filePath = UPLOAD_DIR . $item;
    if (is_file($filePath)) {
        $files[] = [
            'name' => $item,
            'url' => UPLOAD_URL . $item,
            'size' => filesize($filePath),
            'time' => filemtime($filePath)
        ];
    }
}
usort($files, fn($a, $b) => $b['time'] <=> $a['time']);
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

<div class="space-y-6">
    <!-- Üst Yükleme Kutusu -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-bold text-slate-800">Medya Kütüphanesi & Galeri</h2>
            <p class="text-xs text-slate-500">Yazılarınızda kullanabileceğiniz görseller ve belgeler (Toplam: <?= count($files) ?> dosya)</p>
        </div>

        <form method="POST" action="media.php" enctype="multipart/form-data" class="flex items-center gap-2 w-full sm:w-auto">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="sub_action" value="upload">
            <input type="file" name="file" required accept="image/*,application/pdf" class="text-xs text-slate-500 file:mr-2 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow transition shrink-0">
                <i class="fa-solid fa-arrow-up-from-bracket mr-1"></i> Yükle
            </button>
        </form>
    </div>

    <!-- Medya Izgarası (Grid) -->
    <?php if (empty($files)): ?>
        <div class="bg-white rounded-2xl border border-slate-200/80 p-12 text-center text-slate-400">
            <i class="fa-regular fa-images text-4xl mb-3 text-slate-300"></i>
            <p class="text-sm">Henüz yüklenmiş bir medya dosyası bulunmuyor.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <?php foreach ($files as $f): ?>
                <?php
                $isImg = preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $f['name']);
                ?>
                <div class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-sm group hover:shadow-md transition flex flex-col justify-between">
                    <div class="h-32 bg-slate-100 flex items-center justify-center overflow-hidden relative">
                        <?php if ($isImg): ?>
                            <img src="../<?= sanitize($f['url']) ?>" alt="<?= sanitize($f['name']) ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <i class="fa-regular fa-file-pdf text-4xl text-rose-500"></i>
                        <?php endif; ?>
                        
                        <!-- Kopyala & Sil Aksiyon Butonları -->
                        <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-2 p-2">
                            <button onclick="navigator.clipboard.writeText('<?= $f['url'] ?>'); alert('Görsel yolu panoya kopyalandı:\n<?= $f['url'] ?>');" class="p-2 bg-white text-slate-800 rounded-lg text-xs font-semibold hover:bg-indigo-50 hover:text-indigo-600" title="URL'yi Kopyala">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                            <form method="POST" action="media.php" onsubmit="return confirm('Bu dosyayı silmek istediğinizden emin misiniz?');">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="sub_action" value="delete">
                                <input type="hidden" name="filename" value="<?= sanitize($f['name']) ?>">
                                <button type="submit" class="p-2 bg-rose-600 text-white rounded-lg text-xs hover:bg-rose-700" title="Sil">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="p-2.5 bg-white border-t border-slate-100">
                        <div class="text-[11px] font-bold text-slate-800 truncate" title="<?= sanitize($f['name']) ?>"><?= sanitize($f['name']) ?></div>
                        <div class="text-[10px] text-slate-400 mt-0.5"><?= round($f['size'] / 1024, 1) ?> KB &bull; <?= date('d.m.Y', $f['time']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>

