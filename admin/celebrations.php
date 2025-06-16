<?php
session_start();
require_once '../config.php';

// 检查是否已登录
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// 获取所有庆祝记录
$stmt = $conn->prepare("SELECT c.id, c.name, c.qq, c.avatar_url, d.title, c.created_at 
                        FROM celebrations c 
                        JOIN diaries d ON c.diary_id = d.id 
                        ORDER BY c.created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
$celebrations = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 处理删除请求
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $stmt = $conn->prepare("DELETE FROM celebrations WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $success = "庆祝记录删除成功";
    } else {
        $error = "删除失败: " . $stmt->error;
    }
    
    $stmt->close();
    
    // 重定向到当前页面
    header('Location: celebrations.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <!-- 头部信息保持不变 -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>小丑恋爱日记 - 管理后台</title>
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
            .dropdown-item {
                @apply flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md transition-all duration-150;
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
                <a href="celebrations.php" class="sidebar-item active">
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
                    <h2 class="text-xl font-bold text-gray-800">庆祝记录管理</h2>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="add_celebration.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-custom">
                        <i class="fa fa-plus mr-2"></i>
                        添加庆祝记录
                    </a>
                </div>
            </div>

            <!-- 消息提示 -->
            <?php if (isset($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">成功！</strong>
                    <span class="block sm:inline"><?php echo $success; ?></span>
                </div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">错误！</strong>
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <!-- 庆祝记录列表 -->
            <div class="bg-white rounded-xl p-6 card-shadow">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-gray-800">所有庆祝记录</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">姓名</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">QQ号</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">关联日记</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">创建时间</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($celebrations as $celebration): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo $celebration['name']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo $celebration['qq']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo $celebration['title']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo date('Y-m-d H:i', strtotime($celebration['created_at'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="edit_celebration.php?id=<?php echo $celebration['id']; ?>" class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-custom">
                                            <i class="fa fa-pencil mr-1"></i>
                                            编辑
                                        </a>
                                        <a href="celebrations.php?action=delete&id=<?php echo $celebration['id']; ?>" onclick="return confirm('确定要删除这条庆祝记录吗？')" class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-custom">
                                            <i class="fa fa-trash mr-1"></i>
                                            删除
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>