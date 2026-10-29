<?php
// admin/login.php
session_start();
require_once __DIR__ . '/../config.php';
initDB();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'] ?? 'author';
            header('Location: index.php');
            exit;
        } else {
            $error = 'Kullanıcı adı veya şifre hatalı!';
        }
    } else {
        $error = 'Lütfen tüm alanları doldurun.';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - NovaCMS Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-100 flex items-center justify-center min-h-screen p-4">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-xl border border-slate-200/80 p-8 sm:p-10">
        <div class="text-center mb-8">
            <div class="inline-flex p-3 rounded-2xl bg-indigo-50 text-indigo-600 mb-3 text-2xl">
                <i class="fa-solid fa-cube"></i>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">NovaCMS Yönetim Paneli</h1>
            <p class="text-slate-500 text-sm mt-1">İçeriklerinizi yönetmek için oturum açın</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span><?= sanitize($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Kullanıcı Adı</label>
                <div class="relative">
                    <span class="absolute left-3.5 top-3 text-slate-400 text-sm"><i class="fa-regular fa-user"></i></span>
                    <input type="text" name="username" required value="admin"
                           class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Şifre</label>
                <div class="relative">
                    <span class="absolute left-3.5 top-3 text-slate-400 text-sm"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" required value="admin123"
                           class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition">
                </div>
            </div>

            <button type="submit" 
                    class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl shadow-md shadow-indigo-200 transition duration-150">
                Giriş Yap
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-slate-100 text-center">
            <a href="../index.php" class="text-sm font-medium text-slate-500 hover:text-indigo-600 transition">
                &larr; Web Sitesine Dön
            </a>
        </div>
    </div>
</body>
</html>
