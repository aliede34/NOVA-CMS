<?php
// admin/users.php - Kullanıcı ve Rol Yönetimi (Yalnızca Admin)
$adminTitle = "Kullanıcı & Rol Yönetimi";
require_once __DIR__ . '/layout_header.php';
requireRole('admin');

$db = getDB();
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $subAction = $_POST['sub_action'] ?? '';

    if ($subAction === 'save') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = in_array($_POST['role'] ?? '', ['admin', 'editor', 'author']) ? $_POST['role'] : 'author';
        $userId = (int)($_POST['id'] ?? 0);

        if (empty($username)) {
            $err = 'Kullanıcı adı boş bırakılamaz.';
        } else {
            try {
                if ($userId > 0) {
                    if (!empty($password)) {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, password = ?, role = ? WHERE id = ?");
                        $stmt->execute([$username, $email, $hash, $role, $userId]);
                    } else {
                        $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
                        $stmt->execute([$username, $email, $role, $userId]);
                    }
                    $msg = 'Kullanıcı başarıyla güncellendi.';
                } else {
                    if (empty($password)) {
                        $err = 'Yeni kullanıcı için şifre zorunludur.';
                    } else {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $db->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$username, $email, $hash, $role]);
                        $msg = 'Yeni kullanıcı başarıyla eklendi.';
                    }
                }
            } catch (PDOException $e) {
                $err = 'Bu kullanıcı adı veya e-posta zaten kayıtlı.';
            }
        }
    } elseif ($subAction === 'delete') {
        $delId = (int)($_POST['id'] ?? 0);
        if ($delId === (int)$_SESSION['user_id']) {
            $err = 'Kendi hesabınızı silemezsiniz.';
        } elseif ($delId > 0) {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$delId]);
            $msg = 'Kullanıcı hesabı silindi.';
        }
    }
}

$editId = (int)($_GET['edit'] ?? 0);
$editUser = null;
if ($editId > 0) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$editId]);
    $editUser = $stmt->fetch();
}

$users = $db->query("SELECT u.*, (SELECT COUNT(*) FROM posts WHERE user_id = u.id) as post_count FROM users u ORDER BY u.id ASC")->fetchAll();
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
    <!-- Form -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
        <h3 class="font-bold text-slate-800 mb-4 pb-3 border-b border-slate-100">
            <?= $editUser ? 'Kullanıcıyı Düzenle' : 'Yeni Kullanıcı Oluştur' ?>
        </h3>

        <form method="POST" action="users.php" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="sub_action" value="save">
            <input type="hidden" name="id" value="<?= $editUser['id'] ?? 0 ?>">

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Kullanıcı Adı *</label>
                <input type="text" name="username" required value="<?= sanitize($editUser['username'] ?? '') ?>" placeholder="ornek_kullanici"
                       class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">E-posta</label>
                <input type="email" name="email" value="<?= sanitize($editUser['email'] ?? '') ?>" placeholder="user@example.com"
                       class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">
                    <?= $editUser ? 'Şifre (Değiştirmek istemiyorsanız boş bırakın)' : 'Şifre *' ?>
                </label>
                <input type="password" name="password" <?= $editUser ? '' : 'required' ?> placeholder="••••••••"
                       class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Kullanıcı Rolü</label>
                <select name="role" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="admin" <?= ($editUser['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin (Tam Yetki)</option>
                    <option value="editor" <?= ($editUser['role'] ?? '') === 'editor' ? 'selected' : '' ?>>Editor (Yazı, Kategori, Mesaj)</option>
                    <option value="author" <?= ($editUser['role'] ?? 'author') === 'author' ? 'selected' : '' ?>>Author (Yalnızca Yazı Yazma)</option>
                </select>
            </div>

            <div class="flex items-center gap-2 pt-2">
                <button type="submit" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-sm shadow transition">
                    <?= $editUser ? 'Güncelle' : 'Kullanıcı Ekle' ?>
                </button>
                <?php if ($editUser): ?>
                    <a href="users.php" class="py-2.5 px-3 bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold rounded-xl text-sm transition">
                        İptal
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100">
            <h3 class="font-bold text-slate-800">Sistem Kullanıcıları & Yetkileri</h3>
        </div>
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50/50 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                    <th class="py-3 px-6">Kullanıcı</th>
                    <th class="py-3 px-6">Rol</th>
                    <th class="py-3 px-6">Yazı Sayısı</th>
                    <th class="py-3 px-6 text-right">İşlemler</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                <?php foreach ($users as $u): ?>
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="py-3.5 px-6 font-semibold text-slate-800">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full bg-slate-100 text-slate-700 font-bold flex items-center justify-center text-xs uppercase">
                                    <?= substr($u['username'], 0, 1) ?>
                                </div>
                                <div>
                                    <span><?= sanitize($u['username']) ?></span>
                                    <div class="text-[11px] text-slate-400 font-normal"><?= sanitize($u['email'] ?? 'E-posta yok') ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="py-3.5 px-6">
                            <?php
                            $badge = match($u['role']) {
                                'admin' => 'bg-rose-50 text-rose-700',
                                'editor' => 'bg-indigo-50 text-indigo-700',
                                default => 'bg-slate-100 text-slate-600'
                            };
                            ?>
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $badge ?>">
                                <?= strtoupper($u['role'] ?? 'AUTHOR') ?>
                            </span>
                        </td>
                        <td class="py-3.5 px-6 text-xs text-slate-500">
                            <?= $u['post_count'] ?> yazı
                        </td>
                        <td class="py-3.5 px-6 text-right space-x-2">
                            <a href="users.php?edit=<?= $u['id'] ?>" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg inline-block">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                                <form method="POST" action="users.php" class="inline-block" onsubmit="return confirm('Bu kullanıcı hesabını silmek istediğinizden emin misiniz?');">
                                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="sub_action" value="delete">
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>

