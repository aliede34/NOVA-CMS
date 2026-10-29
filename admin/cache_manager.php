<?php
// admin/cache_manager.php - Önbellek Yönetimi & Performans
$adminTitle = "Önbellek & Performans";
require_once __DIR__ . '/layout_header.php';
requireRole('admin');

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    Cache::clear();
    $msg = 'Tüm önbellek başarıyla temizlendi!';
}

$cacheStats = Cache::stats();
?>

<?php if ($msg): ?>
    <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-2">
        <i class="fa-solid fa-circle-check"></i> <?= sanitize($msg) ?>
    </div>
<?php endif; ?>

<div class="max-w-2xl bg-white rounded-2xl border border-slate-200/80 shadow-sm p-8 space-y-6">
    <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
            <i class="fa-solid fa-bolt"></i>
        </div>
        <div>
            <h2 class="text-lg font-bold text-slate-800">Dahili Dosya Tabanlı Önbellek Motoru</h2>
            <p class="text-xs text-slate-500">Sayfa yanıt sürelerini 0.005 saniyeye (5ms) düşürür</p>
        </div>
    </div>

    <!-- Özet Kartları -->
    <div class="grid grid-cols-2 gap-4">
        <div class="p-4 bg-slate-50 rounded-xl">
            <div class="text-xs text-slate-400 font-medium">Önbelleklenen Öğe</div>
            <div class="text-2xl font-bold text-slate-800 mt-1"><?= $cacheStats['count'] ?> dosya</div>
        </div>
        <div class="p-4 bg-slate-50 rounded-xl">
            <div class="text-xs text-slate-400 font-medium">Toplam Cache Boyutu</div>
            <div class="text-2xl font-bold text-slate-800 mt-1"><?= round($cacheStats['size'] / 1024, 2) ?> KB</div>
        </div>
    </div>

    <p class="text-xs text-slate-600 leading-relaxed">
        NovaCMS, API yanıtlarını ve sık çağrılan veritabanı sorgularını geçici olarak <code>/cache</code> dizininde saklar. Yeni yazı eklediğinizde veya ayarları güncellediğinizde dilerseniz aşağıdaki butona basarak önbelleği anında temizleyebilirsiniz.
    </p>

    <form method="POST" action="cache_manager.php">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <button type="submit" class="w-full py-3 px-4 bg-rose-600 hover:bg-rose-700 text-white font-semibold rounded-xl text-sm shadow transition flex items-center justify-center gap-2">
            <i class="fa-solid fa-trash-can"></i> Tüm Önbelleği Temizle (Purge Cache)
        </button>
    </form>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>

