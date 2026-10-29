<?php
// api/like.php - Beğeni / Reaksiyon Artırma API'si
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

session_start();
require_once __DIR__ . '/../config.php';
initDB();

$db = getDB();
$postId = (int)($_POST['post_id'] ?? 0);

if ($postId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz yazı ID']);
    exit;
}

// Oturum bazlı beğeni kontrolü
$sessionKey = 'liked_post_' . $postId;
if (isset($_SESSION[$sessionKey])) {
    // Zaten beğenilmişse mevcut sayıyı dön
    $stmt = $db->prepare("SELECT likes_count FROM posts WHERE id = ?");
    $stmt->execute([$postId]);
    $currentLikes = (int)$stmt->fetchColumn();

    echo json_encode(['status' => 'already_liked', 'likes' => $currentLikes, 'message' => 'Bu yazıyı zaten beğendiniz.']);
    exit;
}

// Beğeniyi artır
$stmt = $db->prepare("UPDATE posts SET likes_count = likes_count + 1 WHERE id = ?");
$stmt->execute([$postId]);
$_SESSION[$sessionKey] = true;

// Yeni sayıyı çek
$stmt = $db->prepare("SELECT likes_count FROM posts WHERE id = ?");
$stmt->execute([$postId]);
$newLikes = (int)$stmt->fetchColumn();

echo json_encode([
    'status' => 'success',
    'likes' => $newLikes,
    'message' => 'Beğeniniz kaydedildi!'
]);

