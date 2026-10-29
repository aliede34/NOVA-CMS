<?php
// api/ai_assistant.php - Akıllı SEO & Başlık/Etiket Üretici
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

session_start();
require_once __DIR__ . '/../config.php';
initDB();

// Yalnızca giriş yapmış panel kullanıcıları kullanabilir
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Oturum açmanız gerekmektedir.']);
    exit;
}

$title = trim($_POST['title'] ?? '');
$content = strip_tags($_POST['content'] ?? '');

if (empty($title) && empty($content)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Lütfen en azından bir başlık veya içerik giriniz.']);
    exit;
}

// 1. Akıllı Özet Çıkarma (Meta Description)
$cleanContent = preg_replace('/\s+/', ' ', trim($content));
if (mb_strlen($cleanContent) > 20) {
    // İlk 150 karakteri alıp son kelimeden kes
    $metaDesc = mb_substr($cleanContent, 0, 155);
    $lastSpace = mb_strrpos($metaDesc, ' ');
    if ($lastSpace !== false) {
        $metaDesc = mb_substr($metaDesc, 0, $lastSpace) . '...';
    }
} else {
    $metaDesc = $title . ' hakkında detaylı bilgiler, güncel ipuçları ve kapsamlı rehber.';
}

// 2. Akıllı Anahtar Kelime & Etiket Analizi (Frekans ve uzunluk bazlı)
$stopWords = ['ve', 'ile', 'bir', 'bu', 'da', 'de', 'için', 'çok', 'en', 'gibi', 'kadar', 'daha', 'ama', 'fakat', 'ancak', 'the', 'and', 'with', 'for'];
$words = preg_split('/[\s,\.\?!;:\"\(\)\[\]{}<>]+/', mb_strtolower($title . ' ' . $cleanContent));
$frequency = [];

foreach ($words as $w) {
    $w = trim($w);
    if (mb_strlen($w) >= 4 && !in_array($w, $stopWords) && !is_numeric($w)) {
        $frequency[$w] = ($frequency[$w] ?? 0) + 1;
    }
}
arsort($frequency);
$topWords = array_slice(array_keys($frequency), 0, 5);

// Etiket formatına dönüştür
$suggestedTags = array_map(fn($w) => mb_convert_case($w, MB_CASE_TITLE, 'UTF-8'), $topWords);

echo json_encode([
    'status' => 'success',
    'meta_description' => $metaDesc,
    'meta_keywords' => implode(', ', $topWords),
    'suggested_tags' => implode(', ', $suggestedTags)
], JSON_UNESCAPED_UNICODE);

