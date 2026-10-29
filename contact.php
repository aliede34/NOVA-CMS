<?php
// contact.php - Ziyaretçi İletişim Formu
session_start();
require_once __DIR__ . '/config.php';
initDB();

$db = getDB();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        $error = 'Lütfen zorunlu alanları (Ad, E-posta, Mesaj) doldurunuz.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Lütfen geçerli bir e-posta adresi giriniz.';
    } else {
        $stmt = $db->prepare("INSERT INTO messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, empty($subject) ? 'Genel İletişim' : $subject, $message]);
        $success = 'Mesajınız başarıyla iletildi! En kısa sürede geri dönüş yapılacaktır.';
    }
}

$pageTitle = "İletişim - " . getSetting('site_name', 'NovaCMS');
require_once __DIR__ . '/header.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-8 sm:p-12 shadow-sm transition-colors">
        <div class="text-center mb-8">
            <div class="inline-flex p-3 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 mb-3 text-2xl">
                <i class="fa-solid fa-paper-plane"></i>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white">Bizimle İletişime Geçin</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-2">Soru, görüş veya iş birliği önerileriniz için formu doldurabilirsiniz.</p>
        </div>

        <?php if ($success): ?>
            <div class="mb-6 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-sm flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-lg"></i>
                <span><?= sanitize($success) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-6 p-4 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-sm flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                <span><?= sanitize($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="contact.php" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Adınız Soyadınız *</label>
                    <input type="text" name="name" required placeholder="Ad Soyad"
                           class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">E-posta Adresiniz *</label>
                    <input type="email" name="email" required placeholder="ornek@mail.com"
                           class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Konu</label>
                <input type="text" name="subject" placeholder="Mesajınızın konusu"
                       class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Mesajınız *</label>
                <textarea name="message" rows="5" required placeholder="Mesajınızı buraya yazınız..."
                          class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"></textarea>
            </div>

            <button type="submit" class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-sm shadow-md shadow-indigo-200 dark:shadow-none transition">
                Mesajı Gönder
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>

