<?php
// sitemap.php - Arama Motorları İçin Dinamik XML Site Haritası
require_once __DIR__ . '/config.php';
initDB();

$db = getDB();

$posts = $db->query("SELECT slug, updated_at FROM posts WHERE status = 'published' ORDER BY updated_at DESC")->fetchAll();
$pages = $db->query("SELECT slug, created_at FROM pages ORDER BY id ASC")->fetchAll();

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <!-- Ana Sayfa -->
  <url>
    <loc><?= SITE_URL ?>/</loc>
    <changefreq>daily</changefreq>
    <priority>1.0</priority>
  </url>

  <!-- Yazılar -->
  <?php foreach ($posts as $post): ?>
    <url>
      <loc><?= SITE_URL ?>/post.php?slug=<?= urlencode($post['slug']) ?></loc>
      <lastmod><?= date('Y-m-d', strtotime($post['updated_at'])) ?></lastmod>
      <changefreq>weekly</changefreq>
      <priority>0.8</priority>
    </url>
  <?php endforeach; ?>

  <!-- Sayfalar -->
  <?php foreach ($pages as $page): ?>
    <url>
      <loc><?= SITE_URL ?>/page.php?slug=<?= urlencode($page['slug']) ?></loc>
      <changefreq>monthly</changefreq>
      <priority>0.5</priority>
    </url>
  <?php endforeach; ?>
</urlset>

