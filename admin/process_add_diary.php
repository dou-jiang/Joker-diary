<?php
session_start();
require_once '../config.php';

// 检查是否已登录
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $category = $_POST['category'];
    $publish_time = $_POST['publish_time'];
    $image_path = $_POST['image_path'];

    // 处理图片链接，将换行符分隔的链接转换为数组
    $image_links = explode("\n", $image_path);
    $image_links = array_filter(array_map('trim', $image_links));
    $image_path = json_encode($image_links);

    // 插入日记到数据库
    $stmt = $conn->prepare("INSERT INTO diaries (title, content, category, image_path, publish_time) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $title, $content, $category, $image_path, $publish_time);

    if ($stmt->execute()) {
        header('Location: diaries.php?success=1');
        exit;
    } else {
        $error = "保存日记失败: " . $stmt->error;
        header('Location: add_diary.php?error=' . urlencode($error));
        exit;
    }

    $stmt->close();
}
?>