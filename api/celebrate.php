<?php
require_once '../config.php';

// 设置响应头
header('Content-Type: application/json');

// 检查请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => '无效的请求方法'
    ]);
    exit;
}

// 获取请求数据
$requestData = json_decode(file_get_contents('php://input'), true);

if (!isset($requestData['diary_id']) || !isset($requestData['name']) || !isset($requestData['qq'])) {
    echo json_encode([
        'success' => false,
        'message' => '缺少必要参数'
    ]);
    exit;
}

$diary_id = intval($requestData['diary_id']);
$name = trim($requestData['name']);
$qq = trim($requestData['qq']);

// 验证数据
if ($diary_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => '无效的日记ID'
    ]);
    exit;
}

if (empty($name) || empty($qq)) {
    echo json_encode([
        'success' => false,
        'message' => '名字和QQ号不能为空'
    ]);
    exit;
}

// 构建QQ头像URL
$avatar_url = "https://q1.qlogo.cn/g?b=qq&nk={$qq}&s=640";

// 插入庆祝记录
$stmt = $conn->prepare("INSERT INTO celebrations (diary_id, name, qq, avatar_url) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $diary_id, $name, $qq, $avatar_url);

if ($stmt->execute()) {
    // 生成舔狗语录
    $quotes = [
        "你是我患得患失的梦，我是你可有可无的人。",
        "你一句话可以改变我一整天的心情，你一个解释可以原谅你所有的错误，你一个见面可以让我放弃所有。",
        "我从未放弃过爱你，只是从浓烈变得悄无声息。",
        "我不主动找你，不是因为你不重要，而是我不知道我重不重要。",
        "你永远也看不到我最寂寞时候的样子，因为只有你不在我身边的时候，我才最寂寞。",
        "你走之后，我得了一场大病，疼的我痛不欲生，后来我活过来了，却忘记了自己。",
        "你是我的不知所措，我却只是你的心不在焉。",
        "你是我眼都不眨就可以说喜欢的人，我却是你头都不抬就可以放手的人。",
        "你永远也不会知道，那个突然对你发了脾气的人，已经忍了你多久。",
        "你是我猜不到的不知所措，我是你想不到的无关痛痒。"
    ];
    
    $randomQuote = $quotes[array_rand($quotes)];
    
    echo json_encode([
        'success' => true,
        'quote' => $randomQuote
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => '保存庆祝记录失败: ' . $stmt->error
    ]);
}

$stmt->close();
?>
