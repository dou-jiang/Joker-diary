<?php
require_once 'config.php';

// 获取日记ID
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}
$id = $_GET['id'];

// 获取日记信息
$stmt = $conn->prepare("SELECT id, title, content, category, image_path, publish_time FROM diaries WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$diary = $result->fetch_assoc();
$stmt->close();

// 处理图片链接
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
    
    // 处理每个图片URL
    $processed_links = [];
    foreach ($image_links as $link) {
        // 跳过空链接
        if (empty($link)) continue;
        
        // 如果不是完整URL，添加域名前缀
        if (!preg_match('/^(http|https):\/\//', $link)) {
            // 确定图片路径前缀 - 修改为直接指向网站根目录下的uploads文件夹
            $base_path = '/uploads/';
            
            // 确保路径以斜杠开头
            if (strpos($link, '/') !== 0) {
                $link = '/' . $link;
            }
            
            // 构建完整URL
            $link = 'http://localhost:81' . $base_path . ltrim($link, '/');
        }
        
        $processed_links[] = $link;
    }
    
    $image_links = $processed_links;
}

// 调试信息 - 开发完成后可移除
if (isset($_GET['debug'])) {
    echo "<div class=\"bg-yellow-50 p-4 rounded-lg mb-4 border border-yellow-200\">";
    echo "<h3 class=\"font-bold text-yellow-800 mb-2\">调试信息</h3>";
    echo "<p class=\"text-sm text-gray-700\">数据库中的图片路径: " . htmlspecialchars($diary['image_path']) . "</p>";
    echo "<p class=\"text-sm text-gray-700\">解析后的图片链接数量: " . count($image_links) . "</p>";
    
    if (!empty($image_links)) {
        echo "<p class=\"text-sm text-gray-700 mt-2\">第一个图片链接: <a href=\"" . htmlspecialchars($image_links[0]) . "\" target=\"_blank\">" . htmlspecialchars($image_links[0]) . "</a></p>";
    }
    
    echo "</div>";
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>小丑恋爱日记 - <?php echo htmlspecialchars($diary['title']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#165DFF',
                        secondary: '#FF7D00',
                        dark: '#1D2129',
                        light: '#F2F3F5',
                        gray: {
                            100: '#F2F3F5',
                            200: '#E5E6EB',
                            300: '#C9CDD4',
                            400: '#86909C',
                            500: '#4E5969',
                            600: '#272E3B',
                            700: '#1D2129',
                        }
                    },
                    fontFamily: {
                        inter: ['Inter', 'sans-serif'],
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.3s ease-in-out',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        }
                    }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer utilities {
            .content-auto {
                content-visibility: auto;
            }
            .article-content p {
                @apply mb-4 text-gray-700 leading-relaxed;
            }
            .article-content h2 {
                @apply text-xl font-bold text-gray-900 mt-8 mb-4 border-b border-gray-200 pb-2;
            }
            .article-content h3 {
                @apply text-lg font-semibold text-gray-800 mt-6 mb-3;
            }
            .article-content ul {
                @apply list-disc pl-5 mb-4;
            }
            .article-content ol {
                @apply list-decimal pl-5 mb-4;
            }
            .article-content blockquote {
                @apply border-l-4 border-primary pl-4 italic my-6 text-gray-600 bg-gray-50 p-4 rounded-r;
            }
            .article-content a {
                @apply text-primary hover:underline;
            }
            .article-content img {
                @apply rounded-lg shadow-md my-6 mx-auto max-w-full h-auto;
            }
            .article-meta {
                @apply text-gray-500 text-sm flex items-center flex-wrap gap-4;
            }
            .article-meta-item {
                @apply flex items-center;
            }
            .bg-gradient-header {
                background: linear-gradient(135deg, #165DFF 0%, #367CFE 100%);
            }
            .nav-link {
                @apply px-4 py-2 text-gray-600 hover:text-primary transition-colors duration-200;
            }
            .nav-link.active {
                @apply text-primary font-medium border-b-2 border-primary;
            }
            .article-footer {
                @apply flex flex-wrap justify-between items-center mt-8 pt-6 border-t border-gray-200;
            }
            .comment-item {
                @apply border-b border-gray-100 py-6 last:border-0;
            }
            .btn-primary {
                @apply bg-primary hover:bg-primary/90 text-white font-medium py-2 px-4 rounded-lg shadow-sm transition-all duration-200;
            }
            .btn-secondary {
                @apply bg-white border border-gray-300 text-gray-700 font-medium py-2 px-4 rounded-lg shadow-sm hover:bg-gray-50 transition-all duration-200;
            }
        }
    </style>
</head>
<body class="font-inter bg-gray-50 text-gray-800 min-h-screen">
    <!-- 导航栏 -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-40">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-3">
                <div class="flex items-center">
                    <a href="index.php" class="text-xl font-bold text-primary flex items-center">
                        <i class="fa fa-heart-o mr-2"></i>
                        小丑恋爱日记
                    </a>
                    <nav class="hidden md:flex ml-8 space-x-1">
                        <a href="index.php" class="nav-link active">首页</a>
                        <a href="#" class="nav-link">分类</a>
                        <a href="#" class="nav-link">关于</a>
                        <a href="#" class="nav-link">联系</a>
                    </nav>
                </div>
                <div class="flex items-center space-x-4">
                    <button class="md:hidden text-gray-600" id="mobile-menu-button">
                        <i class="fa fa-bars text-xl"></i>
                    </button>
                    <a href="admin/login.php" class="hidden md:inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors duration-200">
                        <i class="fa fa-lock mr-2"></i>
                        管理员登录
                    </a>
                </div>
            </div>
        </div>
        
        <!-- 移动端菜单 -->
        <div class="md:hidden hidden bg-white border-t border-gray-200" id="mobile-menu">
            <div class="px-4 py-3 space-y-1">
                <a href="index.php" class="block px-3 py-2 rounded-md text-base font-medium text-primary bg-primary/5">首页</a>
                <a href="#" class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:text-primary hover:bg-primary/5">分类</a>
                <a href="#" class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:text-primary hover:bg-primary/5">关于</a>
                <a href="#" class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:text-primary hover:bg-primary/5">联系</a>
                <a href="admin/login.php" class="block px-3 py-2 rounded-md text-base font-medium text-primary hover:text-primary/80">
                    <i class="fa fa-lock mr-2"></i>
                    管理员登录
                </a>
            </div>
        </div>
    </header>

    <!-- 主内容区 -->
    <main class="container mx-auto px-4 py-8 md:py-12">
        <div class="max-w-4xl mx-auto">
            <!-- 面包屑导航 -->
            <div class="mb-6 text-sm text-gray-500">
                <a href="index.php" class="hover:text-primary">首页</a>
                <span class="mx-2">/</span>
                <span class="text-gray-700"><?php echo htmlspecialchars($diary['title']); ?></span>
            </div>
            
            <!-- 文章标题 -->
            <article class="bg-white rounded-lg shadow-sm p-6 md:p-8 animate-fade-in">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4">
                    <?php echo htmlspecialchars($diary['title']); ?>
                </h1>
                
                <!-- 文章元数据 -->
                <div class="article-meta mb-6">
                    <div class="article-meta-item">
                        <i class="fa fa-calendar-o mr-1"></i>
                        <span>发布于 <?php echo date('Y年m月d日 H:i', strtotime($diary['publish_time'])); ?></span>
                    </div>
                    <div class="article-meta-item">
                        <i class="fa fa-folder-o mr-1"></i>
                        <a href="#" class="hover:text-primary"><?php echo htmlspecialchars($diary['category']); ?></a>
                    </div>
                    <div class="article-meta-item">
                        <i class="fa fa-eye mr-1"></i>
                        <span>阅读 248</span>
                    </div>
                    <div class="article-meta-item">
                        <i class="fa fa-comment-o mr-1"></i>
                        <span>评论 5</span>
                    </div>
                </div>
                
                <!-- 文章内容 -->
                <div class="article-content">
                    <?php echo nl2br(htmlspecialchars($diary['content'])); ?>
                    
                    <!-- 图片展示 -->
                    <?php if (!empty($image_links)): ?>
                        <div class="my-8">
                            <?php foreach ($image_links as $key => $image_link): ?>
                                <div class="mb-6">
                                    <img 
                                        src="<?php echo htmlspecialchars($image_link); ?>" 
                                        alt="<?php echo htmlspecialchars($diary['title']); ?> - 图片 <?php echo $key + 1; ?>" 
                                        class="block mx-auto rounded-lg shadow-md max-w-full h-auto"
                                        onerror="this.style.display='none'; this.parentElement.innerHTML = '<div class=\'text-center py-8 border border-gray-200 rounded-lg bg-gray-50\'><i class=\'fa fa-image text-4xl text-gray-300 mb-2\'></i><p class=\'text-gray-500\'>图片加载失败</p></div>';"
                                    >
                                    <!-- 调试信息 - 开发完成后可移除 -->
                                    <?php if (isset($_GET['debug'])): ?>
                                        <div class="mt-2 text-xs text-gray-500">
                                            <p>图片链接: <a href="<?php echo htmlspecialchars($image_link); ?>" target="_blank"><?php echo htmlspecialchars($image_link); ?></a></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="my-8 text-center py-8 border border-gray-200 rounded-lg bg-gray-50">
                            <i class="fa fa-image text-4xl text-gray-300 mb-2"></i>
                            <p class="text-gray-500">暂无图片</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- 文章底部 -->
                <div class="article-footer">
                    <div class="flex space-x-3">
                        <button class="flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors duration-200">
                            <i class="fa fa-thumbs-o-up mr-1"></i>
                            <span>点赞 (24)</span>
                        </button>
                        <button class="flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors duration-200">
                            <i class="fa fa-bookmark-o mr-1"></i>
                            <span>收藏</span>
                        </button>
                    </div>
                    <div class="flex space-x-3">
                        <a href="#" class="flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors duration-200">
                            <i class="fa fa-share-alt mr-1"></i>
                            <span>分享</span>
                        </a>
                    </div>
                </div>
            </article>

            <!-- 评论区 -->
            <div class="mt-8 bg-white rounded-lg shadow-sm p-6 md:p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6">评论 (5)</h2>
                
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">发表评论</h3>
                    <form>
                        <div class="mb-4">
                            <textarea 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors duration-200" 
                                rows="4" 
                                placeholder="写下你的评论..."
                            ></textarea>
                        </div>
                        <div class="flex justify-end">
                            <button type="button" class="btn-primary">
                                <i class="fa fa-paper-plane mr-2"></i>
                                发表评论
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="space-y-6">
                    <!-- 评论项1 -->
                    <div class="comment-item">
                        <div class="flex items-start">
                            <img src="https://picsum.photos/id/1/40/40" alt="用户头像" class="w-10 h-10 rounded-full mr-4">
                            <div class="flex-1">
                                <div class="flex items-center mb-2">
                                    <h4 class="font-medium text-gray-900">用户A</h4>
                                    <span class="ml-2 text-xs text-gray-500">2023-05-15 14:30</span>
                                </div>
                                <p class="text-gray-700 mb-2">这篇日记写得真好，非常感人！</p>
                                <div class="flex items-center text-sm text-gray-500">
                                    <button class="flex items-center hover:text-primary transition-colors duration-200">
                                        <i class="fa fa-thumbs-o-up mr-1"></i>
                                        <span>点赞 (12)</span>
                                    </button>
                                    <span class="mx-2">|</span>
                                    <button class="flex items-center hover:text-primary transition-colors duration-200">
                                        <i class="fa fa-reply mr-1"></i>
                                        <span>回复</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 评论项2 -->
                    <div class="comment-item">
                        <div class="flex items-start">
                            <img src="https://picsum.photos/id/2/40/40" alt="用户头像" class="w-10 h-10 rounded-full mr-4">
                            <div class="flex-1">
                                <div class="flex items-center mb-2">
                                    <h4 class="font-medium text-gray-900">用户B</h4>
                                    <span class="ml-2 text-xs text-gray-500">2023-05-14 09:15</span>
                                </div>
                                <p class="text-gray-700 mb-2">请问这是在哪里拍的照片？景色真美！</p>
                                <div class="flex items-center text-sm text-gray-500">
                                    <button class="flex items-center hover:text-primary transition-colors duration-200">
                                        <i class="fa fa-thumbs-o-up mr-1"></i>
                                        <span>点赞 (8)</span>
                                    </button>
                                    <span class="mx-2">|</span>
                                    <button class="flex items-center hover:text-primary transition-colors duration-200">
                                        <i class="fa fa-reply mr-1"></i>
                                        <span>回复</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 更多评论... -->
                    <div class="text-center">
                        <button class="btn-secondary">
                            <i class="fa fa-refresh mr-2"></i>
                            加载更多评论
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- 页脚 -->
    <footer class="bg-gray-900 text-white py-12 mt-16">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-6 md:mb-0">
                    <p class="text-gray-400">&copy; 2023 小丑恋爱日记. 保留所有权利.</p>
                </div>
                <div class="flex space-x-6">
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">
                        <i class="fa fa-weibo"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">
                        <i class="fa fa-wechat"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">
                        <i class="fa fa-github"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // 移动端菜单切换
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
        
        // 平滑滚动
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
        
        // 评论回复按钮
        document.querySelectorAll('.fa-reply').forEach(replyBtn => {
            replyBtn.parentElement.addEventListener('click', function() {
                // 这里可以添加回复功能的逻辑
                alert('回复功能将在后续版本中实现');
            });
        });
        
        // 图片加载失败处理
        document.querySelectorAll('img').forEach(img => {
            img.addEventListener('error', function() {
                console.error('图片加载失败:', this.src);
            });
        });
    </script>
</body>
</html>
    