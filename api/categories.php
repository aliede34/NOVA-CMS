<?php
// api/categories.php - RESTful Categories API
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../config.php';
initDB();

$db = getDB();

$categories = $db->query("SELECT c.id, c.name, c.slug, COUNT(p.id) as post_count 
                          FROM categories c 
                          LEFT JOIN posts p ON p.category_id = c.id AND p.status = 'published' 
                          GROUP BY c.id 
                          ORDER BY c.name ASC")->fetchAll();

echo json_encode([
    'status' => 'success',
    'total' => count($categories),
    'data' => $categories
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

