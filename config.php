<?php
// 数据库配置
define('DB_HOST', '45.192.111.232');
define('DB_USER', 'joker');
define('DB_PASSWORD', 'joker');
define('DB_NAME', 'joker');

// 创建数据库连接
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

// 检查连接
if ($conn->connect_error) {
    die("连接失败: " . $conn->connect_error);
}

// 设置字符集
$conn->set_charset("utf8mb4");
?>
