<?php
session_start();
require_once '../config.php';

// 检查是否已登录
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// 获取所有日记
$stmt = $conn->prepare("SELECT id, title FROM diaries");
$stmt->execute();
$result = $stmt->get_result();
$diaries = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $qq = $_POST['qq'];
    $diary_id = $_POST['diary_id'];
    $avatar_url = "https://q1.qlogo.cn/g?b=qq&nk={$qq}&s=640";
    
    $stmt = $conn->prepare("INSERT INTO celebrations (name, qq, avatar_url, diary_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $name, $qq, $avatar_url, $diary_id);
    
    if ($stmt->execute()) {
        header('Location: celebrations.php?success=1');
        exit;
    } else {
        $error = "保存庆祝记录失败: " . $stmt->error;
    }
    
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>小丑恋爱日记 - 添加庆祝记录</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3B82F6',
                        secondary: '#F97316',
                        neutral: '#F3F4F6',
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer utilities {
            .content-auto {
                content-visibility: auto;
            }
            .card-shadow {
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            }
            .sidebar-item {
                @apply flex items-center px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-lg transition-all duration-200;
            }
            .sidebar-item.active {
                @apply bg-primary/10 text-primary font-medium;
            }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <div class="flex-1 flex">
        <!-- 侧边栏 -->
        <aside class="hidden md:block w-64 bg-white shadow-lg">
            <div class="p-4 border-b">
                <h1 class="text-xl font-bold text-primary">小丑恋爱日记</h1>
            </div>
            <nav class="p-4 space-y-1">
                <a href="dashboard.php" class="sidebar-item">
                    <i class="fa fa-dashboard w-5 h-5 mr-3"></i>
                    <span>仪表盘</span>
                </a>
                <a href="diaries.php" class="sidebar-item">
                    <i class="fa fa-book w-5 h-5 mr-3"></i>
                    <span>日记管理</span>
                </a>
                <a href="celebrations.php" class="sidebar-item">
                    <i class="fa fa-users w-5 h-5 mr-3"></i>
                    <span>庆祝记录</span>
                </a>
                <a href="add_diary.php" class="sidebar-item">
                    <i class="fa fa-plus-circle w-5 h-5 mr-3"></i>
                    <span>添加日记</span>
                </a>
                <a href="add_celebration.php" class="sidebar-item">
                    <i class="fa fa-plus-circle w-5 h-5 mr-3"></i>
                    <span>添加庆祝记录</span>
                </a>
            </nav>
        </aside>

        <!-- 主内容区 -->
        <main class="flex-1 p-6">
            <!-- 顶部导航 -->
            <div class="mb-6 flex justify-between items-center">
                <div class="flex items-center">
                    <button class="md:hidden mr-4 text-gray-600" id="mobile-menu-button">
                        <i class="fa fa-bars text-xl"></i>
                    </button>
                    <h2 class="text-xl font-bold text-gray-800">添加庆祝记录</h2>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="celebrations.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-custom">
                        返回列表
                    </a>
                </div>
            </div>

            <!-- 庆祝记录表单 -->
            <div class="bg-white rounded-xl p-6 card-shadow">
                <form method="post">
                    <div class="space-y-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">姓名</label>
                            <input type="text" name="name" id="name" required class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary transition-custom" placeholder="请输入姓名">
                        </div>
                        
                        <div>
                            <label for="qq" class="block text-sm font-medium text-gray-700 mb-1">QQ号</label>
                            <input type="text" name="qq" id="qq" required class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary transition-custom" placeholder="请输入QQ号">
                        </div>
                        
                        <div>
                            <label for="diary_id" class="block text-sm font-medium text-gray-700 mb-1">关联日记</label>
                            <select name="diary_id" id="diary_id" required class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary transition-custom">
                                <?php foreach ($diaries as $diary): ?>
                                    <option value="<?php echo $diary['id']; ?>"><?php echo $diary['title']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="pt-4">
                        <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-custom">
                            保存庆祝记录
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <footer class="bg-gray-800 text-white py-6">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <p>&copy; 2023 小丑恋爱日记</p>
                </div>
                <div class="flex space-x-6">
                    <!-- 页脚链接 -->
                </div>
            </div>
        </div>
    </footer>
</body>
</html>