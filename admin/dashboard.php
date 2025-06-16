<?php
session_start();
require_once '../config.php';

// 检查是否已登录
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// 获取管理员信息
$stmt = $conn->prepare("SELECT username FROM admins WHERE id = ?");
$stmt->bind_param("i", $_SESSION['admin_id']);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

// 获取日记总数
$diaryCount = $conn->query("SELECT COUNT(*) as count FROM diaries")->fetch_assoc()['count'];

// 获取庆祝记录总数
$celebrationCount = $conn->query("SELECT COUNT(*) as count FROM celebrations")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>小丑恋爱日记 - 管理面板</title>
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
                <a href="dashboard.php" class="sidebar-item active">
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
                    <h2 class="text-xl font-bold text-gray-800">仪表盘</h2>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button class="flex items-center text-gray-600 hover:text-primary transition-custom">
                            <i class="fa fa-bell-o text-xl"></i>
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center">3</span>
                        </button>
                    </div>
                    <!-- 用户下拉菜单 -->
                    <div class="relative" id="user-menu-container">
                        <button class="flex items-center text-sm font-medium text-gray-700 hover:text-primary transition-custom" id="user-menu-button">
                            <img class="h-8 w-8 rounded-full" src="https://picsum.photos/200/200" alt="管理员头像">
                            <span class="ml-2 hidden md:inline-block"><?php echo $admin['username']; ?></span>
                            <i class="fa fa-caret-down ml-1"></i>
                        </button>
                        
                        <!-- 下拉菜单内容 -->
                        <div class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10" id="user-menu">
                            <a href="profile.php" class="dropdown-item">
                                <i class="fa fa-user mr-2 text-gray-500"></i>
                                <span>个人信息</span>
                            </a>
                            <a href="logout.php" class="dropdown-item text-red-500 hover:text-red-700">
                                <i class="fa fa-sign-out mr-2 text-red-500"></i>
                                <span>退出登录</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 数据卡片 -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white rounded-xl p-6 card-shadow">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">日记总数</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $diaryCount; ?></h3>
                        </div>
                        <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center">
                            <i class="fa fa-book text-primary text-xl"></i>
                        </div>
                    </div>
                    <div class="flex items-center text-sm">
                        <span class="text-green-500 flex items-center">
                            <i class="fa fa-arrow-up mr-1"></i> 12%
                        </span>
                        <span class="text-gray-500 ml-2">较上月</span>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-6 card-shadow">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">庆祝记录</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $celebrationCount; ?></h3>
                        </div>
                        <div class="w-12 h-12 rounded-full bg-secondary/10 flex items-center justify-center">
                            <i class="fa fa-users text-secondary text-xl"></i>
                        </div>
                    </div>
                    <div class="flex items-center text-sm">
                        <span class="text-green-500 flex items-center">
                            <i class="fa fa-arrow-up mr-1"></i> 8%
                        </span>
                        <span class="text-gray-500 ml-2">较上月</span>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-6 card-shadow">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">平均每天</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo round($celebrationCount / 30, 1); ?></h3>
                        </div>
                        <div class="w-12 h-12 rounded-full bg-green-500/10 flex items-center justify-center">
                            <i class="fa fa-line-chart text-green-500 text-xl"></i>
                        </div>
                    </div>
                    <div class="flex items-center text-sm">
                        <span class="text-green-500 flex items-center">
                            <i class="fa fa-arrow-up mr-1"></i> 5%
                        </span>
                        <span class="text-gray-500 ml-2">较上周</span>
                    </div>
                </div>
            </div>

            <!-- 最近日记 -->
            <div class="bg-white rounded-xl p-6 card-shadow mb-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-gray-800">最近日记</h3>
                    <a href="diaries.php" class="text-primary hover:text-primary/80 text-sm font-medium">查看全部</a>
                </div>
                <div class="space-y-4">
                    <?php
                    $stmt = $conn->prepare("SELECT id, title, category, created_at FROM diaries ORDER BY created_at DESC LIMIT 5");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $date = date('Y-m-d H:i', strtotime($row['created_at']));
                            ?>
                            <div class="border-l-4 border-primary pl-4 py-2">
                                <h4 class="font-medium text-gray-800"><?php echo $row['title']; ?></h4>
                                <div class="flex items-center mt-1">
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-primary/10 text-primary"><?php echo $row['category']; ?></span>
                                    <span class="text-xs text-gray-500 ml-3"><?php echo $date; ?></span>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<p class="text-gray-500">暂无日记</p>';
                    }
                    ?>
                </div>
            </div>

            <!-- 最近庆祝记录 -->
            <div class="bg-white rounded-xl p-6 card-shadow">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-gray-800">最近庆祝记录</h3>
                    <a href="celebrations.php" class="text-primary hover:text-primary/80 text-sm font-medium">查看全部</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">用户</th>
                                <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">QQ</th>
                                <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">日记</th>
                                <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">时间</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            $stmt = $conn->prepare("SELECT c.id, c.name, c.qq, d.title, c.created_at 
                                                   FROM celebrations c 
                                                   JOIN diaries d ON c.diary_id = d.id 
                                                   ORDER BY c.created_at DESC LIMIT 5");
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $date = date('Y-m-d H:i', strtotime($row['created_at']));
                                    ?>
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <img class="h-10 w-10 rounded-full" src="https://q1.qlogo.cn/g?b=qq&nk=<?php echo $row['qq']; ?>&s=640" alt="<?php echo $row['name']; ?>">
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo $row['name']; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo $row['qq']; ?></div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo $row['title']; ?></div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo $date; ?>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr><td colspan="4" class="px-4 py-3 text-center text-gray-500">暂无庆祝记录</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
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
                    <a href="#" class="text-gray-400 hover:text-white transition-custom">
                        <i class="fa fa-weibo"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-custom">
                        <i class="fa fa-wechat"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-custom">
                        <i class="fa fa-github"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // 用户菜单切换
        document.getElementById('user-menu-button').addEventListener('click', function() {
            const userMenu = document.getElementById('user-menu');
            userMenu.classList.toggle('hidden');
        });
        
        // 点击其他区域关闭菜单
        document.addEventListener('click', function(event) {
            const userMenuContainer = document.getElementById('user-menu-container');
            const userMenu = document.getElementById('user-menu');
            
            if (!userMenuContainer.contains(event.target)) {
                userMenu.classList.add('hidden');
            }
        });
        
        // 移动端菜单切换
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const sidebar = document.querySelector('aside');
            sidebar.classList.toggle('hidden');
            sidebar.classList.toggle('fixed');
            sidebar.classList.toggle('inset-0');
            sidebar.classList.toggle('z-50');
            sidebar.classList.toggle('w-full');
        });
    </script>
</body>
</html>