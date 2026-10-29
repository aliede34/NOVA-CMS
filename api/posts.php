<?php
// api/posts.php - RESTful Posts API
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../config.php';
initDB();

$db = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$slug = trim($_GET['slug'] ?? '');
$category = trim($_GET['category'] ?? '');
$tag = trim($_GET['tag'] ?? '');
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
$offset = ($page - 1) * $limit;

if ($id > 0 || !empty($slug)) {
    // Tek yazı getirme
    $where = $id > 0 ? "p.id = ?" : "p.slug = ?";
    $param = $id > 0 ? $id : $slug;

    $stmt = $db->prepare("SELECT p.id, p.title, p.slug, p.content, p.featured_image, p.status, p.views_count, p.read_time, p.created_at, p.updated_at,
                                 c.id as category_id, c.name as category_name, c.slug as category_slug,
                                 u.username as author_name
                          FROM posts p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          LEFT JOIN users u ON p.user_id = u.id 
                          WHERE $where AND p.status = 'published'");
    $stmt->execute([$param]);
    $post = $stmt->fetch();

    if ($post) {
        $post['tags'] = getPostTags((int)$post['id']);
        echo json_encode(['status' => 'success', 'data' => $post], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Yazı bulunamadı.'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// Liste getirme
$query = "SELECT p.id, p.title, p.slug, p.featured_image, p.status, p.views_count, p.read_time, p.created_at,
                 c.name as category_name, c.slug as category_slug,
                 u.username as author_name,
                 SUBSTR(REPLACE(p.content, '<', ' <'), 1, 160) as excerpt
          FROM posts p 
          LEFT JOIN categories c ON p.category_id = c.id 
          LEFT JOIN users u ON p.user_id = u.id";

$whereClauses = ["p.status = 'published'"];
$params = [];

if ($category) {
    $whereClauses[] = "c.slug = ?";
    $params[] = $category;
}

if ($tag) {
    $query .= " INNER JOIN post_tags pt ON pt.post_id = p.id INNER JOIN tags t ON t.id = pt.tag_id";
    $whereClauses[] = "t.slug = ?";
    $params[] = $tag;
}

if ($search) {
    $whereClauses[] = "(p.title LIKE ? OR p.content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " WHERE " . implode(" AND ", $whereClauses);

// Toplam sayı
$countQuery = "SELECT COUNT(*) FROM (" . $query . ")";
$stmtCount = $db->prepare($countQuery);
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();

// Sayfalama ve sıralama
$query .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$stmt = $db->prepare($query);
$stmt->execute($params);
$posts = $stmt->fetchAll();

foreach ($posts as &$post) {
    $post['excerpt'] = strip_tags($post['excerpt']) . '...';
    $post['tags'] = getPostTags((int)$post['id']);
}

echo json_encode([
    'status' => 'success',
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'total_pages' => ceil($total / $limit)
    ],
    'data' => $posts
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

