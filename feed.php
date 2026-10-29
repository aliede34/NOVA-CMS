<?php
// feed.php - Otomatik RSS 2.0 Beslemesi
require_once __DIR__ . '/config.php';
initDB();

$db = getDB();
$siteName = getSetting('site_name', 'NovaCMS');
$siteDesc = getSetting('site_description', 'Hızlı, Taşınabilir ve Güvenli PHP CMS');

$posts = $db->query("SELECT p.*, c.name as category_name, u.username as author_name 
                     FROM posts p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     LEFT JOIN users u ON p.user_id = u.id 
                     WHERE p.status = 'published' 
                     ORDER BY p.created_at DESC LIMIT 20")->fetchAll();

header('Content-Type: application/rss+xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title><?= htmlspecialchars($siteName) ?></title>
    <link><?= SITE_URL ?></link>
    <description><?= htmlspecialchars($siteDesc) ?></description>
    <language>tr</language>
    <lastBuildDate><?= date(DATE_RSS) ?></lastBuildDate>
    <atom:link href="<?= SITE_URL ?>/feed.php" rel="self" type="application/rss+xml" />
    
    <?php foreach ($posts as $post): ?>
      <item>
        <title><?= htmlspecialchars($post['title']) ?></title>
        <link><?= SITE_URL ?>/post.php?slug=<?= urlencode($post['slug']) ?></link>
        <guid><?= SITE_URL ?>/post.php?slug=<?= urlencode($post['slug']) ?></guid>
        <pubDate><?= date(DATE_RSS, strtotime($post['created_at'])) ?></pubDate>
        <author><?= htmlspecialchars($post['author_name'] ?? 'Admin') ?></author>
        <category><?= htmlspecialchars($post['category_name'] ?? 'Genel') ?></category>
        <description><![CDATA[<?= strip_tags(mb_substr($post['content'], 0, 300)) ?>...]]></description>
      </item>
    <?php endforeach; ?>
  </channel>
</rss>

