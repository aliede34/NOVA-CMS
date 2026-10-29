<?php
$adminTitle = "Genel Ayarlar";
require_once __DIR__ . '/layout_header.php';

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $siteName = trim($_POST['site_name'] ?? '');
    $siteDesc = trim($_POST['site_description'] ?? '');
    $newPass = $_POST['new_password'] ?? '';

    if (!empty($siteName)) {
        setSetting('site_name', $siteName);
        setSetting('site_description', $siteDesc);

        // Şifre güncelleme
        if (!empty($newPass)) {
            if (strlen($newPass) < 6) {
                $err = 'Şifre en az 6 karakter olmalıdır.';
            } else {
                $db = getDB();
                $hashed = password_hash($newPass, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed, $_SESSION['user_id']]);
                $msg = 'Site ayarları ve yönetici şifresi güncellendi.';
            }
        } else {
            $msg = 'Site ayarları başarıyla kaydedildi.';
        }
    } else {
        $err = 'Site adı boş bırakılamaz.';
    }
}

$siteName = getSetting('site_name', SITE_TITLE);
$siteDesc = getSetting('site_description', 'Basit, Hızlı ve Güçlü PHP CMS');
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

<div class="max-w-2xl bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
    <h2 class="text-lg font-bold text-slate-800 mb-6 pb-4 border-b border-slate-100">Site Yapılandırması</h2>

    <form method="POST" action="settings.php" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Site Başlığı / Adı</label>
            <input type="text" name="site_name" required value="<?= sanitize($siteName) ?>"
                   class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition">
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Site Sloganı / Açıklaması</label>
            <input type="text" name="site_description" value="<?= sanitize($siteDesc) ?>"
                   class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition">
        </div>

        <div class="pt-6 border-t border-slate-100">
            <h3 class="text-sm font-bold text-slate-800 mb-1">Yönetici Şifresini Değiştir</h3>
            <p class="text-xs text-slate-400 mb-4">Değiştirmek istemiyorsanız boş bırakın.</p>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Yeni Şifre</label>
                <input type="password" name="new_password" placeholder="••••••••"
                       class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition">
            </div>
        </div>

        <div class="flex justify-end pt-4">
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-md shadow-indigo-200 transition">
                Ayarları Kaydet
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>

