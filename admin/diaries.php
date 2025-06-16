<?php
session_start();
require_once '../config.php';

// 检查是否已登录
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// 获取日记列表
$stmt = $conn->prepare("SELECT * FROM diaries ORDER BY publish_time DESC");
$stmt->execute();
$result = $stmt->get_result();
$diaries = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 错误信息变量
$error = '';
$success = '';

// 处理删除请求
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    $stmt = $conn->prepare("DELETE FROM diaries WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $success = "日记已成功删除";
    } else {
        $error = "删除日记失败: " . $stmt->error;
    }
    
    $stmt->close();
}

// 处理成功消息
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success = "操作成功";
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>日记管理</title>
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
                        sidebar: {
                            default: '#FFFFFF',
                            hover: '#F5F7FA',
                            active: '#E8F3FF',
                        }
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
                @apply flex items-center px-4 py-3 text-gray-600 hover:bg-sidebar-hover rounded-lg transition-all duration-200;
            }
            .sidebar-item.active {
                @apply bg-sidebar-active text-primary font-medium;
            }
            /* 多图缩略图网格 */
            .thumbnail-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                grid-template-rows: repeat(2, 1fr);
                gap: 2px;
                width: 100%;
                height: 100%;
                overflow: hidden;
            }
            .thumbnail-grid img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .thumbnail-grid-1 {
                grid-template-columns: 1fr;
                grid-template-rows: 1fr;
            }
            .thumbnail-grid-2 {
                grid-template-columns: 1fr 1fr;
                grid-template-rows: 1fr;
            }
            .thumbnail-grid-3 .item-1 {
                grid-column: 1 / 3;
                grid-row: 1 / 2;
            }
            .thumbnail-grid-more .item-1 {
                grid-column: 1 / 3;
                grid-row: 1 / 3;
            }
            .thumbnail-grid-more .item-overlay {
                grid-column: 2 / 3;
                grid-row: 2 / 3;
                background-color: rgba(0, 0, 0, 0.5);
                color: white;
                display: flex;
                justify-center: center;
                align-items: center;
                font-weight: bold;
            }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <div class="flex-1 flex">
        <!-- 侧边栏 -->
        <aside id="sidebar" class="fixed top-0 left-0 h-full bg-sidebar-default shadow-lg z-50 transition-all duration-300 w-64">
            <div class="p-4 border-b sidebar-brand flex items-center">
                <i class="fa fa-heart-o text-xl text-primary sidebar-icon mr-3"></i>
                <h1 class="text-xl font-bold text-primary title">小丑恋爱日记</h1>
            </div>
            
            <!-- 折叠按钮 -->
            <div class="p-4 border-b flex justify-center">
                <button id="collapse-btn" class="p-2 rounded-full hover:bg-gray-100 transition-colors">
                    <i class="fa fa-angle-double-left text-gray-500"></i>
                </button>
            </div>

            <nav class="p-4 space-y-1">
                <a href="dashboard.php" class="sidebar-item">
                    <i class="fa fa-dashboard w-5 h-5 mr-3 sidebar-icon"></i>
                    <span class="sidebar-title">仪表盘</span>
                </a>
                <a href="diaries.php" class="sidebar-item active">
                    <i class="fa fa-book w-5 h-5 mr-3 sidebar-icon"></i>
                    <span class="sidebar-title">日记管理</span>
                </a>
                <a href="celebrations.php" class="sidebar-item">
                    <i class="fa fa-users w-5 h-5 mr-3 sidebar-icon"></i>
                    <span class="sidebar-title">庆祝记录</span>
                </a>
                <a href="add_diary.php" class="sidebar-item">
                    <i class="fa fa-plus-circle w-5 h-5 mr-3 sidebar-icon"></i>
                    <span class="sidebar-title">添加日记</span>
                </a>
                <a href="add_celebration.php" class="sidebar-item">
                    <i class="fa fa-plus-circle w-5 h-5 mr-3 sidebar-icon"></i>
                    <span class="sidebar-title">添加庆祝记录</span>
                </a>
                <a href="profile.php" class="sidebar-item">
                    <i class="fa fa-user w-5 h-5 mr-3 sidebar-icon"></i>
                    <span class="sidebar-title">个人资料</span>
                </a>
                <a href="logout.php" class="sidebar-item">
                    <i class="fa fa-sign-out w-5 h-5 mr-3 sidebar-icon"></i>
                    <span class="sidebar-title">退出登录</span>
                </a>
            </nav>
        </aside>

        <!-- 侧边栏遮罩层 -->
        <div id="sidebar-backdrop" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-40 hidden md:hidden" onclick="document.getElementById('sidebar').classList.add('-translate-x-full'); this.classList.add('hidden'); document.body.classList.remove('overflow-hidden');"></div>

        <!-- 主内容区 -->
        <main id="main-content" class="flex-1 p-6 md:ml-64 transition-all duration-300">
            <!-- 顶部导航栏 -->
            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center">
                    <button id="open-sidebar" class="md:hidden text-gray-600 mr-4">
                        <i class="fa fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-2xl font-bold text-gray-800">日记管理</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button class="flex items-center text-gray-600 hover:text-primary transition-custom">
                            <i class="fa fa-bell-o text-xl"></i>
                            <span class="absolute top-0 right-0 h-4 w-4 rounded-full bg-primary text-white text-xs flex items-center justify-center">3</span>
                        </button>
                    </div>
                    <div class="flex items-center">
                        <img class="h-8 w-8 rounded-full" src="https://picsum.photos/200" alt="用户头像">
                        <span class="ml-2 text-sm font-medium text-gray-700">管理员</span>
                    </div>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">错误!</strong>
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">成功!</strong>
                    <span class="block sm:inline"><?php echo $success; ?></span>
                </div>
            <?php endif; ?>

            <!-- 日记列表 -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (empty($diaries)): ?>
                    <div class="col-span-full bg-white rounded-xl p-6 card-shadow text-center">
                        <i class="fa fa-book text-5xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-medium text-gray-500">暂无日记记录</h3>
                        <p class="text-gray-400 mt-2">点击"添加日记"按钮创建你的第一篇日记</p>
                        <a href="add_diary.php" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            <i class="fa fa-plus-circle mr-2"></i>
                            添加日记
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($diaries as $diary): ?>
                        <div class="bg-white rounded-xl overflow-hidden card-shadow transition-all duration-300 hover:shadow-lg group">
                            <!-- 日记封面图片 -->
                            <div class="relative h-48 overflow-hidden">
                                <?php
                                $image_links = [];
                                if (!empty($diary['image_path'])) {
                                    // 尝试JSON解析
                                    $image_links = json_decode($diary['image_path'], true);
                                    
                                    // 如果JSON解析失败或结果不是数组
                                    if (!is_array($image_links) || empty($image_links)) {
                                        // 尝试使用逗号分隔
                                        $image_links = explode(',', $diary['image_path']);
                                        $image_links = array_map('trim', $image_links);
                                        $image_links = array_filter($image_links);
                                    }
                                }
                                
                                $image_count = count($image_links);
                                ?>
                                
                                <?php if ($image_count > 0): ?>
                                    <div class="thumbnail-grid <?php 
                                        echo $image_count == 1 ? 'thumbnail-grid-1' : 
                                             ($image_count == 2 ? 'thumbnail-grid-2' : 
                                             ($image_count == 3 ? 'thumbnail-grid-3' : 'thumbnail-grid-more')); 
                                    ?>">
                                        <?php for ($i = 0; $i < min($image_count, 4); $i++): ?>
                                            <div class="item-<?php echo $i+1; ?>">
                                                <img src="<?php echo htmlspecialchars($image_links[$i]); ?>" alt="日记图片" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                                            </div>
                                        <?php endfor; ?>
                                        
                                        <?php if ($image_count > 4): ?>
                                            <div class="item-overlay flex items-center justify-center">
                                                <span>+<?php echo $image_count - 4; ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <!-- 没有图片时的默认封面 -->
                                    <div class="w-full h-full bg-gray-100 flex items-center justify-center">
                                        <i class="fa fa-image text-4xl text-gray-300"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- 分类标签 -->
                                <div class="absolute top-3 left-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary">
                                        <?php echo htmlspecialchars($diary['category']); ?>
                                    </span>
                                </div>
                                
                                <!-- 日期 -->
                                <div class="absolute top-3 right-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                        <i class="fa fa-calendar-o mr-1"></i>
                                        <?php echo date('Y-m-d', strtotime($diary['publish_time'])); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- 日记内容 -->
                            <div class="p-5">
                                <h3 class="text-lg font-semibold text-gray-800 mb-2 line-clamp-1"><?php echo htmlspecialchars($diary['title']); ?></h3>
                                <p class="text-gray-600 mb-4 line-clamp-2"><?php echo htmlspecialchars($diary['content']); ?></p>
                                
                                <!-- 操作按钮 -->
                                <div class="flex justify-between items-center">
                                    <div class="flex space-x-2">
                                        <a href="edit_diary.php?id=<?php echo $diary['id']; ?>" class="text-sm text-primary hover:text-primary/80 transition-colors">
                                            <i class="fa fa-pencil mr-1"></i> 编辑
                                        </a>
                                        <a href="diaries.php?delete=<?php echo $diary['id']; ?>" class="text-sm text-red-500 hover:text-red-700 transition-colors" onclick="return confirm('确定要删除这篇日记吗？')">
                                            <i class="fa fa-trash-o mr-1"></i> 删除
                                        </a>
                                    </div>
                                    <span class="text-xs text-gray-500">
                                        <i class="fa fa-picture-o mr-1"></i> <?php echo $image_count; ?>张图片
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <footer class="bg-gray-800 text-white py-6 md:ml-64 transition-all duration-300" id="footer">
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
        // 侧边栏折叠功能
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            const footer = document.getElementById('footer');
            const collapseBtn = document.getElementById('collapse-btn');
            const openSidebarBtn = document.getElementById('open-sidebar');
            const sidebarBackdrop = document.getElementById('sidebar-backdrop');
            
            // 初始化折叠状态
            let isCollapsed = false;
            
            // 折叠/展开侧边栏
            collapseBtn.addEventListener('click', function() {
                isCollapsed = !isCollapsed;
                
                if (isCollapsed) {
                    sidebar.classList.add('sidebar-collapsed');
                    sidebar.classList.remove('w-64');
                    sidebar.classList.add('w-16');
                    mainContent.classList.remove('md:ml-64');
                    mainContent.classList.add('md:ml-16');
                    footer.classList.remove('md:ml-64');
                    footer.classList.add('md:ml-16');
                    collapseBtn.innerHTML = '<i class="fa fa-angle-double-right text-gray-500"></i>';
                    document.querySelectorAll('.sidebar-title').forEach(title => {
                        title.classList.add('hidden');
                    });
                    document.querySelectorAll('.sidebar-icon').forEach(icon => {
                        icon.classList.remove('mr-3');
                    });
                } else {
                    sidebar.classList.remove('sidebar-collapsed');
                    sidebar.classList.remove('w-16');
                    sidebar.classList.add('w-64');
                    mainContent.classList.remove('md:ml-16');
                    mainContent.classList.add('md:ml-64');
                    footer.classList.remove('md:ml-16');
                    footer.classList.add('md:ml-64');
                    collapseBtn.innerHTML = '<i class="fa fa-angle-double-left text-gray-500"></i>';
                    document.querySelectorAll('.sidebar-title').forEach(title => {
                        title.classList.remove('hidden');
                    });
                    document.querySelectorAll('.sidebar-icon').forEach(icon => {
                        icon.classList.add('mr-3');
                    });
                }
            });
            
            // 移动端侧边栏控制
            openSidebarBtn.addEventListener('click', function() {
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('w-64');
                sidebar.classList.remove('w-16');
                sidebarBackdrop.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
                document.querySelectorAll('.sidebar-title').forEach(title => {
                    title.classList.remove('hidden');
                });
                document.querySelectorAll('.sidebar-icon').forEach(icon => {
                    icon.classList.add('mr-3');
                });
            });
            
            sidebarBackdrop.addEventListener('click', function() {
                sidebar.classList.add('-translate-x-full');
                this.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            });
        });
    </script>
</body>
</html>
    