<?php
session_start();
require_once '../config.php';

// 检查是否已登录
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// 获取当前管理员信息
$stmt = $conn->prepare("SELECT username FROM admins WHERE id = ?");
$stmt->bind_param("i", $_SESSION['admin_id']);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newUsername = $_POST['username'];
    $newPassword = $_POST['password'];

    if (!empty($newPassword)) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admins SET username = ?, password = ? WHERE id = ?");
        $stmt->bind_param("ssi", $newUsername, $hashedPassword, $_SESSION['admin_id']);
    } else {
        $stmt = $conn->prepare("UPDATE admins SET username = ? WHERE id = ?");
        $stmt->bind_param("si", $newUsername, $_SESSION['admin_id']);
    }

    if ($stmt->execute()) {
        $success = "信息更新成功";
    } else {
        $error = "更新失败: " . $stmt->error;
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>小丑恋爱日记 - 个人信息</title>
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
                <!-- <a href="profile.php" class="sidebar-item active">
                    <i class="fa fa-user w-5 h-5 mr-3"></i>
                    <span>个人信息</span>
                </a>
                <a href="logout.php" class="sidebar-item">
                    <i class="fa fa-sign-out w-5 h-5 mr-3"></i>
                    <span>退出登录</span>
                </a> -->
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
                    <h2 class="text-xl font-bold text-gray-800">个人信息</h2>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button class="flex items-center text-gray-600 hover:text-primary transition-custom">
                            <i class="fa fa-bell-o text-xl"></i>
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center">3</span>
                        </button>
                    </div>
                    <div class="flex items-center">
                        <img src="https://picsum.photos/200/200" alt="管理员头像" class="w-8 h-8 rounded-full mr-2">
                        <span class="text-sm font-medium"><?php echo $admin['username']; ?></span>
                        <i class="fa fa-caret-down ml-1 text-gray-500"></i>
                    </div>
                </div>
            </div>

            <!-- 个人信息表单 -->
            <div class="bg-white rounded-xl p-6 card-shadow">
                <?php if (isset($success)): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                        <strong class="font-bold">成功!</strong>
                        <span class="block sm:inline"><?php echo $success; ?></span>
                    </div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                        <strong class="font-bold">错误!</strong>
                        <span class="block sm:inline"><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>
                <form method="post" class="space-y-4">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">用户名</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fa fa-user text-gray-400"></i>
                            </div>
                            <input type="text" name="username" id="username" value="<?php echo $admin['username']; ?>" required class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary transition-custom" placeholder="请输入用户名">
                        </div>
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">新密码（留空则不修改）</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fa fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" name="password" id="password" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary transition-custom" placeholder="请输入新密码">
                        </div>
                    </div>
                    <div class="pt-2">
                        <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-custom">
                            更新信息
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <!-- JavaScript -->
    <script>
        // 移动端菜单切换
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const sidebar = document.querySelector('aside');
            sidebar.classList.toggle('hidden');
        });
    </script>
</body>
</html>