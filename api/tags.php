<?php
// api/tags.php - RESTful Tags API
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../config.php';
initDB();

$db = getDB();

$tags = $db->query("SELECT t.id, t.name, t.slug, COUNT(pt.post_id) as post_count 
                    FROM tags t 
                    LEFT JOIN post_tags pt ON pt.tag_id = t.id 
                    GROUP BY t.id 
                    ORDER BY post_count DESC, t.name ASC")->fetchAll();

echo json_encode([
    'status' => 'success',
    'total' => count($tags),
    'data' => $tags
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

