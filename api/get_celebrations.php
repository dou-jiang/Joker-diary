<?php
require_once '../config.php';

// 设置响应头
header('Content-Type: application/json');

// 获取日记ID
$diary_id = isset($_GET['diary_id']) ? intval($_GET['diary_id']) : 0;

if ($diary_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => '无效的日记ID'
    ]);
    exit;
}

// 查询庆祝记录
$stmt = $conn->prepare("SELECT name, qq, avatar_url FROM celebrations WHERE diary_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $diary_id);
$stmt->execute();
$result = $stmt->get_result();
$celebrations = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode([
    'success' => true,
    'celebrations' => $celebrations
]);
?>
