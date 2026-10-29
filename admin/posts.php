<?php
$adminTitle = "Yazı Yönetimi";
require_once __DIR__ . '/layout_header.php';

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$msg = '';
$err = '';

// POST İşlemleri (Ekleme / Güncelleme / Silme / Resim Yükleme)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $subAction = $_POST['sub_action'] ?? '';

    if ($subAction === 'save') {
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $content = $_POST['content'] ?? '';
        $featuredImage = trim($_POST['featured_image_url'] ?? '');
        $tagsInput = trim($_POST['tags'] ?? '');
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $status = in_array($_POST['status'] ?? '', ['published', 'draft']) ? $_POST['status'] : 'published';
        $postId = (int)($_POST['id'] ?? 0);

        // Görsel Yükleme (Dosya yüklendiyse öncelik ver)
        if (isset($_FILES['featured_image_file']) && $_FILES['featured_image_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['featured_image_file'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (in_array($mime, $allowedTypes)) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $newName = 'img_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (!is_dir(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0755, true);
                }
                if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $newName)) {
                    $featuredImage = UPLOAD_URL . $newName;
                }
            } else {
                $err = 'Yalnızca geçerli resim dosyaları (JPG, PNG, WebP, GIF) yüklenebilir.';
            }
        }

        if (empty($title)) {
            $err = 'Başlık alanı boş bırakılamaz.';
        } elseif (!$err) {
            $slug = !empty($slug) ? slugify($slug) : slugify($title);
            $readTime = calculateReadTime($content);

            if ($postId > 0) {
                // Güncelleme
                $stmt = $db->prepare("UPDATE posts SET title = ?, slug = ?, content = ?, featured_image = ?, category_id = ?, status = ?, read_time = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$title, $slug, $content, $featuredImage, $categoryId, $status, $readTime, $postId]);
                syncPostTags($postId, $tagsInput);
                $msg = 'Yazı başarıyla güncellendi.';
                $action = 'list';
            } else {
                // Yeni Kayıt
                $userId = $_SESSION['user_id'];
                $stmt = $db->prepare("INSERT INTO posts (title, slug, content, featured_image, category_id, user_id, status, read_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $slug, $content, $featuredImage, $categoryId, $userId, $status, $readTime]);
                $newPostId = (int)$db->lastInsertId();
                syncPostTags($newPostId, $tagsInput);
                $msg = 'Yeni yazı başarıyla yayınlandı.';
                $action = 'list';
            }
        }
    } elseif ($subAction === 'delete') {
        $delId = (int)($_POST['id'] ?? 0);
        if ($delId > 0) {
            $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
            $stmt->execute([$delId]);
            $msg = 'Yazı başarıyla silindi.';
        }
    }
}

$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>

<!-- Quill.js WYSIWYG Editor Styles -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

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
    $post = ['id' => 0, 'title' => '', 'slug' => '', 'content' => '', 'featured_image' => '', 'category_id' => '', 'status' => 'published'];
    $currentTags = '';
    if ($action === 'edit' && $id > 0) {
        $stmt = $db->prepare("SELECT * FROM posts WHERE id = ?");
        $stmt->execute([$id]);
        $post = $stmt->fetch() ?: $post;
        $tagObjects = getPostTags($id);
        $currentTags = implode(', ', array_column($tagObjects, 'name'));
    }
    ?>
    <div class="max-w-4xl bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
        <div class="flex items-center justify-between pb-4 mb-6 border-b border-slate-100">
            <h2 class="text-lg font-bold text-slate-800">
                <?= $action === 'edit' ? 'Yazıyı Düzenle' : 'Yeni Yazı Ekle' ?>
            </h2>
            <a href="posts.php" class="text-sm text-slate-500 hover:text-slate-800">&larr; Vazgeç ve Geri Dön</a>
        </div>

        <form method="POST" action="posts.php" enctype="multipart/form-data" id="postForm" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="sub_action" value="save">
            <input type="hidden" name="id" value="<?= $post['id'] ?>">
            <input type="hidden" name="content" id="hiddenContent">

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Başlık *</label>
                <input type="text" name="title" required value="<?= sanitize($post['title']) ?>" placeholder="Yazının başlığı..." 
                       class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Kalıcı Bağlantı (Slug)</label>
                    <input type="text" name="slug" value="<?= sanitize($post['slug']) ?>" placeholder="Boş bırakılırsa başlıktan üretilir" 
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Kategori</label>
                    <select name="category_id" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition">
                        <option value="">-- Kategori Seçin --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $post['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                <?= sanitize($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Etiketler (Tags) Input -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Etiketler (Virgülle ayırın)</label>
                <div class="relative">
                    <span class="absolute left-3.5 top-2.5 text-slate-400 text-xs"><i class="fa-solid fa-tags"></i></span>
                    <input type="text" name="tags" value="<?= sanitize($currentTags) ?>" placeholder="Örn: PHP8, SQLite, Web Geliştirme, API"
                           class="w-full pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition">
                </div>
                <span class="text-[11px] text-slate-400 mt-1 block">Etiketler otomatik oluşturulur ve ilgili yazı eşleşmesinde kullanılır.</span>
            </div>

            <!-- Kapak Görseli -->
            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Öne Çıkan Kapak Görseli</label>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <span class="block text-xs text-slate-400 mb-1">Bilgisayardan Yükle:</span>
                        <input type="file" name="featured_image_file" accept="image/*" class="text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                    </div>
                    <div>
                        <span class="block text-xs text-slate-400 mb-1">Veya Doğrudan Görsel URL'si:</span>
                        <input type="url" name="featured_image_url" value="<?= sanitize($post['featured_image']) ?>" placeholder="https://..." class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <?php if (!empty($post['featured_image'])): ?>
                    <div class="mt-2 flex items-center gap-3">
                        <img src="<?= sanitize(str_starts_with($post['featured_image'], 'http') ? $post['featured_image'] : '../' . $post['featured_image']) ?>" class="w-16 h-12 rounded-lg object-cover border border-slate-200">
                        <span class="text-xs text-slate-500">Mevcut görsel tanımlı.</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- SEO Optimizasyon Kutusu (AI Destekli) -->
            <div class="p-4 bg-indigo-50/50 rounded-2xl border border-indigo-100 space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-900 flex items-center gap-1.5">
                        <i class="fa-solid fa-chart-line text-indigo-600"></i> Arama Motoru Optimizasyonu (SEO)
                    </h4>
                    <button type="button" id="btnAiAssist" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white text-[11px] font-semibold rounded-lg shadow-sm transition flex items-center gap-1.5">
                        <i class="fa-solid fa-wand-magic-sparkles text-amber-300"></i> AI ile Otomatik Doldur
                    </button>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Özel Meta Açıklaması (Meta Description)</label>
                    <textarea name="meta_description" id="fieldMetaDesc" rows="2" placeholder="Google arama sonuçlarında görünecek 150-160 karakterlik özet..."
                              class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500"><?= sanitize($post['meta_description'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">SEO Anahtar Kelimeleri (Virgülle ayırın)</label>
                    <input type="text" name="meta_keywords" id="fieldMetaKeywords" value="<?= sanitize($post['meta_keywords'] ?? '') ?>" placeholder="php, sqlite, açık kaynak cms, web geliştirme"
                           class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Yayın Durumu</label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 text-sm font-medium text-slate-700 cursor-pointer">
                        <input type="radio" name="status" value="published" <?= $post['status'] === 'published' ? 'checked' : '' ?> class="text-indigo-600 focus:ring-indigo-500">
                        Yayında
                    </label>
                    <label class="flex items-center gap-2 text-sm font-medium text-slate-700 cursor-pointer">
                        <input type="radio" name="status" value="draft" <?= $post['status'] === 'draft' ? 'checked' : '' ?> class="text-indigo-600 focus:ring-indigo-500">
                        Taslak
                    </label>
                </div>
            </div>

            <!-- WYSIWYG Quill Editor Container -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">İçerik (Zengin Metin Editörü)</label>
                <div id="editor-container" class="bg-white rounded-b-xl border-slate-200 text-sm min-h-[300px]">
                    <?= $post['content'] ?>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="posts.php" class="px-5 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">Vazgeç</a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-md shadow-indigo-200 transition">
                    <?= $action === 'edit' ? 'Değişiklikleri Kaydet' : 'Yazıyı Yayınla' ?>
                </button>
            </div>
        </form>
    </div>

    <!-- Quill.js Entegrasyonu -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <script>
        const quill = new Quill('#editor-container', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    ['blockquote', 'code-block'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['link', 'clean']
                ]
            }
        });

        // Form submit edilmeden önce quill HTML'ini gizli alana aktar
        document.getElementById('postForm').onsubmit = function() {
            document.getElementById('hiddenContent').value = quill.root.innerHTML;
        };

        // AI Otomatik Doldurma Butonu
        const btnAi = document.getElementById('btnAiAssist');
        if (btnAi) {
            btnAi.addEventListener('click', function() {
                const title = document.querySelector('input[name="title"]').value;
                const content = quill.root.innerText;

                btnAi.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Analiz ediliyor...';

                fetch('../api/ai_assistant.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'title=' + encodeURIComponent(title) + '&content=' + encodeURIComponent(content)
                })
                .then(res => res.json())
                .then(data => {
                    btnAi.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles text-amber-300"></i> AI ile Otomatik Doldur';
                    if (data.status === 'success') {
                        document.getElementById('fieldMetaDesc').value = data.meta_description;
                        document.getElementById('fieldMetaKeywords').value = data.meta_keywords;
                        const tagsInput = document.querySelector('input[name="tags"]');
                        if (tagsInput && !tagsInput.value) {
                            tagsInput.value = data.suggested_tags;
                        }
                    }
                })
                .catch(() => {
                    btnAi.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles text-amber-300"></i> AI ile Otomatik Doldur';
                });
            });
        }
    </script>

<?php else: ?>
    <!-- Yazı Listesi -->
    <?php
    $posts = $db->query("SELECT p.*, c.name as category_name, u.username as author_name, 
                         (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
                         FROM posts p 
                         LEFT JOIN categories c ON p.category_id = c.id 
                         LEFT JOIN users u ON p.user_id = u.id 
                         ORDER BY p.id DESC")->fetchAll();
    ?>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Tüm Yazılar</h2>
            <p class="text-xs text-slate-500">Sitenizdeki tüm blog ve makale içerikleri</p>
        </div>
        <a href="posts.php?action=create" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-md shadow-indigo-200 transition inline-flex items-center gap-2">
            <i class="fa-solid fa-plus text-xs"></i> Yeni Yazı Ekle
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50/50 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                    <th class="py-3 px-6">Yazı</th>
                    <th class="py-3 px-6">Kategori</th>
                    <th class="py-3 px-6">Etiketler</th>
                    <th class="py-3 px-6">Okunma / Yorum</th>
                    <th class="py-3 px-6">Durum</th>
                    <th class="py-3 px-6">Tarih</th>
                    <th class="py-3 px-6 text-right">İşlemler</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                <?php if (empty($posts)): ?>
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">Henüz hiç yazı eklenmemiş.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($posts as $p): ?>
                        <?php $pTags = getPostTags((int)$p['id']); ?>
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-4 px-6 font-semibold text-slate-800">
                                <div class="flex items-center gap-3">
                                    <?php if (!empty($p['featured_image'])): ?>
                                        <img src="<?= sanitize(str_starts_with($p['featured_image'], 'http') ? $p['featured_image'] : '../' . $p['featured_image']) ?>" class="w-10 h-10 rounded-lg object-cover border border-slate-200">
                                    <?php endif; ?>
                                    <div>
                                        <a href="../post.php?slug=<?= urlencode($p['slug']) ?>" target="_blank" class="hover:text-indigo-600">
                                            <?= sanitize($p['title']) ?>
                                            <i class="fa-solid fa-arrow-up-right-from-square text-[10px] text-slate-400 ml-1"></i>
                                        </a>
                                        <div class="text-[11px] text-slate-400 font-normal"><?= $p['read_time'] ?> dk okuma</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-slate-500 text-xs">
                                <?= $p['category_name'] ? '<span class="px-2.5 py-1 bg-slate-100 rounded-md font-medium text-slate-600">' . sanitize($p['category_name']) . '</span>' : '-' ?>
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-400">
                                <div class="flex flex-wrap gap-1 max-w-[160px]">
                                    <?php foreach ($pTags as $t): ?>
                                        <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px] font-medium">#<?= sanitize($t['name']) ?></span>
                                    <?php endforeach; ?>
                                    <?php if (empty($pTags)): ?>-<?php endif; ?>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-500">
                                <span class="mr-3"><i class="fa-regular fa-eye mr-1"></i><?= $p['views_count'] ?></span>
                                <span><i class="fa-regular fa-comments mr-1"></i><?= $p['comment_count'] ?></span>
                            </td>
                            <td class="py-4 px-6">
                                <span class="text-xs px-2.5 py-1 rounded-full font-medium <?= $p['status'] == 'published' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' ?>">
                                    <?= $p['status'] == 'published' ? 'Yayında' : 'Taslak' ?>
                                </span>
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-400">
                                <?= date('d.m.Y', strtotime($p['created_at'])) ?>
                            </td>
                            <td class="py-4 px-6 text-right space-x-2">
                                <a href="posts.php?action=edit&id=<?= $p['id'] ?>" class="inline-flex p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="Düzenle">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <form method="POST" action="posts.php" class="inline-block" onsubmit="return confirm('Bu yazıyı silmek istediğinizden emin misiniz?');">
                                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="sub_action" value="delete">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="p-2 text-rose-500 hover:bg-rose-50 rounded-lg transition" title="Sil">
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
