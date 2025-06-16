<?php
session_start();
require_once '../config.php';

// 检查是否已登录
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// 获取日记ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: diaries.php');
    exit;
}

$diary_id = $_GET['id'];
$error = '';
$uploaded_images = [];

// 获取日记信息
$stmt = $conn->prepare("SELECT * FROM diaries WHERE id = ?");
$stmt->bind_param("i", $diary_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: diaries.php');
    exit;
}

$diary = $result->fetch_assoc();
$stmt->close();

// 处理图片URL
$existing_image_links = [];
if (!empty($diary['image_path'])) {
    // 尝试JSON解析
    $existing_image_links = json_decode($diary['image_path'], true);
    
    // 如果JSON解析失败或结果不是数组
    if (!is_array($existing_image_links) || empty($existing_image_links)) {
        // 尝试使用逗号分隔
        $existing_image_links = explode(',', $diary['image_path']);
        $existing_image_links = array_map('trim', $existing_image_links);
        $existing_image_links = array_filter($existing_image_links);
    }
}

// 处理图片上传
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['images'])) {
    $upload_dir = '../uploads/';
    
    // 创建上传目录（如果不存在）
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $total = count($_FILES['images']['name']);
    
    for ($i = 0; $i < $total; $i++) {
        $tmpFilePath = $_FILES['images']['tmp_name'][$i];
        
        // 检查文件是否上传
        if ($tmpFilePath != "") {
            $fileName = basename($_FILES['images']['name'][$i]);
            $filePath = $upload_dir . $fileName;
            
            // 检查文件类型
            $fileType = pathinfo($filePath, PATHINFO_EXTENSION);
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array(strtolower($fileType), $allowedTypes)) {
                // 上传文件
                if (move_uploaded_file($tmpFilePath, $filePath)) {
                    // 获取完整URL
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                    $host = $_SERVER['HTTP_HOST'];
                    $url = $protocol . $host . '/uploads/' . $fileName;
                    
                    $uploaded_images[] = $url;
                } else {
                    $error = "上传文件失败";
                }
            } else {
                $error = "不支持的文件类型。支持的类型：jpg, jpeg, png, gif, webp";
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_diary'])) {
    $title = $_POST["title"];
    $content = $_POST["content"];
    $category = $_POST["category"];
    $publish_time = $_POST["publish_time"];
    
    // 获取已上传的图片URL
    $image_links = $_POST["image_path"];
    $image_links_array = explode("\n", $image_links);
    $image_links_array = array_filter(array_map('trim', $image_links_array));
    
    // 合并上传的图片URL
    if (!empty($uploaded_images)) {
        $image_links_array = array_merge($image_links_array, $uploaded_images);
    }
    
    // 转换为JSON字符串
    $image_links_json = json_encode($image_links_array);

    // 更新日记数据到数据库
    $stmt = $conn->prepare("UPDATE diaries SET title = ?, content = ?, category = ?, publish_time = ?, image_path = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $title, $content, $category, $publish_time, $image_links_json, $diary_id);

    if ($stmt->execute()) {
        header('Location: diaries.php?success=1');
        exit;
    } else {
        $error = "Error: " . $stmt->error;
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>编辑日记</title>
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
            .upload-area {
                @apply border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:bg-gray-50 transition-colors cursor-pointer;
            }
            .upload-area-drag {
                @apply border-primary bg-primary/5;
            }
            .image-preview {
                @apply relative inline-block m-2 rounded-md overflow-hidden;
            }
            .remove-image {
                @apply absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center cursor-pointer opacity-0 hover:opacity-100 transition-opacity;
            }
            .image-preview:hover .remove-image {
                @apply opacity-100;
            }
            /* 侧边栏折叠样式 */
            .sidebar-collapsed {
                @apply w-16 transition-all duration-300;
            }
            .sidebar-collapsed .sidebar-title {
                @apply hidden;
            }
            .sidebar-collapsed .sidebar-icon {
                @apply mr-0;
            }
            .sidebar-collapsed .sidebar-brand {
                @apply justify-center;
            }
            .sidebar-collapsed .sidebar-brand .title {
                @apply hidden;
            }
            .main-content {
                @apply md:ml-0 transition-all duration-300;
            }
            .main-content-expanded {
                @apply md:ml-64;
            }
            .main-content-collapsed {
                @apply md:ml-16;
            }
            .upload-status {
                @apply fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 transform translate-y-20 opacity-0 transition-all duration-300;
            }
            .upload-status.show {
                @apply translate-y-0 opacity-100;
            }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <div class="flex-1 flex">
        <!-- 侧边栏 -->
        <aside id="sidebar" class="fixed top-0 left-0 h-full bg-sidebar-default shadow-lg z-50 transition-all duration-300">
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
                <a href="diaries.php" class="sidebar-item">
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
                <a href="edit_diary.php?id=<?php echo $diary_id; ?>" class="sidebar-item active">
                    <i class="fa fa-pencil w-5 h-5 mr-3 sidebar-icon"></i>
                    <span class="sidebar-title">编辑日记</span>
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
        <main id="main-content" class="flex-1 p-6 main-content main-content-expanded">
            <!-- 顶部导航栏 -->
            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center">
                    <button id="open-sidebar" class="md:hidden text-gray-600 mr-4">
                        <i class="fa fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-2xl font-bold text-gray-800">编辑日记</h1>
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

            <!-- 日记表单 -->
            <div class="bg-white rounded-xl p-6 card-shadow">
                <form method="post" action="" enctype="multipart/form-data">
                    <div class="space-y-6">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">标题</label>
                            <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($diary['title']); ?>" required class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary transition-custom" placeholder="请输入日记标题">
                        </div>

                        <div>
                            <label for="content" class="block text-sm font-medium text-gray-700 mb-1">内容</label>
                            <textarea name="content" id="content" rows="10" required class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary transition-custom" placeholder="请输入日记内容"><?php echo htmlspecialchars($diary['content']); ?></textarea>
                        </div>

                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700 mb-1">分类</label>
                            <select name="category" id="category" required class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary transition-custom">
                                <option value="">请选择分类</option>
                                <option value="日常" <?php echo ($diary['category'] === '日常') ? 'selected' : ''; ?>>日常</option>
                                <option value="心情" <?php echo ($diary['category'] === '心情') ? 'selected' : ''; ?>>心情</option>
                                <option value="旅行" <?php echo ($diary['category'] === '旅行') ? 'selected' : ''; ?>>旅行</option>
                                <option value="美食" <?php echo ($diary['category'] === '美食') ? 'selected' : ''; ?>>美食</option>
                                <option value="其他" <?php echo ($diary['category'] === '其他') ? 'selected' : ''; ?>>其他</option>
                            </select>
                        </div>

                        <div>
                            <label for="publish_time" class="block text-sm font-medium text-gray-700 mb-1">发布时间</label>
                            <input type="datetime-local" name="publish_time" id="publish_time" value="<?php echo str_replace(' ', 'T', $diary['publish_time']); ?>" required class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary transition-custom">
                        </div>

                        <!-- 图片上传区域 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">图片上传</label>
                            
                            <!-- 拖拽上传区域 -->
                            <div id="upload-area" class="upload-area mb-4">
                                <input type="file" name="images[]" id="file-input" multiple class="hidden" accept="image/*">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="fa fa-cloud-upload text-gray-400 text-4xl mb-2"></i>
                                    <p class="text-gray-600">拖放图片到这里，或</p>
                                    <button type="button" id="browse-btn" class="mt-2 inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                        <i class="fa fa-folder-open-o mr-2"></i>
                                        浏览文件
                                    </button>
                                    <p class="mt-2 text-xs text-gray-500">支持 JPG, PNG, GIF, WEBP 格式</p>
                                </div>
                            </div>
                            
                            <!-- 图片预览区域 -->
                            <div id="image-previews" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 mb-4">
                                <?php if (!empty($existing_image_links)): ?>
                                    <?php foreach ($existing_image_links as $index => $image_url): ?>
                                        <div class="image-preview" data-url="<?php echo htmlspecialchars($image_url); ?>">
                                            <img src="<?php echo htmlspecialchars($image_url); ?>" alt="预览图" class="w-full h-24 object-cover rounded-md">
                                            <span class="remove-image" onclick="removeImage(this)">&times;</span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <?php if (!empty($uploaded_images)): ?>
                                    <?php foreach ($uploaded_images as $index => $image_url): ?>
                                        <div class="image-preview" data-url="<?php echo htmlspecialchars($image_url); ?>">
                                            <img src="<?php echo htmlspecialchars($image_url); ?>" alt="预览图" class="w-full h-24 object-cover rounded-md">
                                            <span class="remove-image" onclick="removeImage(this)">&times;</span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- 图片链接区域 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">图片直链 (每行一个链接)</label>
                            <textarea name="image_path" id="image_path" rows="5" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary transition-custom" placeholder="请输入图片直链，每行一个"><?php 
                                if (!empty($existing_image_links)) {
                                    echo implode("\n", $existing_image_links);
                                }
                            ?></textarea>
                            <p class="mt-1 text-xs text-gray-500">支持多张图片，每行输入一个图片链接</p>
                        </div>

                        <div class="pt-2 flex space-x-3">
                            <button type="button" onclick="window.location.href='diaries.php'" class="flex-1 justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-custom">
                                <i class="fa fa-times mr-2"></i>
                                取消
                            </button>
                            <button type="submit" name="update_diary" class="flex-1 justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-custom">
                                <i class="fa fa-save mr-2"></i>
                                保存更改
                            </button>
                        </div>
                    </div>
                </form>
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

    <!-- 上传状态提示 -->
    <div id="upload-status" class="upload-status">
        <i class="fa fa-check-circle mr-2"></i>
        <span id="upload-message">图片上传成功！</span>
    </div>

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
                    mainContent.classList.remove('main-content-expanded');
                    mainContent.classList.add('main-content-collapsed');
                    footer.classList.remove('md:ml-64');
                    footer.classList.add('md:ml-16');
                    collapseBtn.innerHTML = '<i class="fa fa-angle-double-right text-gray-500"></i>';
                } else {
                    sidebar.classList.remove('sidebar-collapsed');
                    mainContent.classList.remove('main-content-collapsed');
                    mainContent.classList.add('main-content-expanded');
                    footer.classList.remove('md:ml-16');
                    footer.classList.add('md:ml-64');
                    collapseBtn.innerHTML = '<i class="fa fa-angle-double-left text-gray-500"></i>';
                }
            });
            
            // 移动端侧边栏控制
            openSidebarBtn.addEventListener('click', function() {
                sidebar.classList.remove('-translate-x-full');
                sidebarBackdrop.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            });
            
            sidebarBackdrop.addEventListener('click', function() {
                sidebar.classList.add('-translate-x-full');
                this.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            });
            
            // 图片上传功能
            const uploadArea = document.getElementById('upload-area');
            const fileInput = document.getElementById('file-input');
            const browseBtn = document.getElementById('browse-btn');
            const imagePreviews = document.getElementById('image-previews');
            const imagePathTextarea = document.getElementById('image_path');
            const uploadStatus = document.getElementById('upload-status');
            const uploadMessage = document.getElementById('upload-message');
            
            // 浏览按钮点击事件
            browseBtn.addEventListener('click', () => {
                fileInput.click();
            });
            
            // 文件选择事件
            fileInput.addEventListener('change', (e) => {
                handleFiles(e.target.files);
            });
            
            // 拖拽上传功能
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.classList.add('upload-area-drag');
            });
            
            uploadArea.addEventListener('dragleave', () => {
                uploadArea.classList.remove('upload-area-drag');
            });
            
            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('upload-area-drag');
                
                if (e.dataTransfer.files.length) {
                    handleFiles(e.dataTransfer.files);
                }
            });
            
            // 显示上传状态
            function showUploadStatus(message, isSuccess = true) {
                uploadMessage.textContent = message;
                uploadStatus.className = `upload-status ${isSuccess ? 'bg-green-500' : 'bg-red-500'} show`;
                
                setTimeout(() => {
                    uploadStatus.classList.remove('show');
                }, 3000);
            }
            
            // 处理选择的文件
            function handleFiles(files) {
                if (files.length === 0) return;
                
                // 检查文件类型
                const validFiles = [];
                const invalidFiles = [];
                
                for (let i = 0; i < files.length; i++) {
                    if (files[i].type.startsWith('image/')) {
                        validFiles.push(files[i]);
                    } else {
                        invalidFiles.push(files[i].name);
                    }
                }
                
                // 显示无效文件提示
                if (invalidFiles.length > 0) {
                    showUploadStatus(`以下文件不是有效的图片: ${invalidFiles.join(', ')}`, false);
                }
                
                if (validFiles.length === 0) return;
                
                // 创建预览图
                const previewPromises = validFiles.map(file => {
                    return new Promise(resolve => {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const preview = document.createElement('div');
                            preview.className = 'image-preview';
                            preview.innerHTML = `
                                <img src="${e.target.result}" alt="预览图" class="w-full h-24 object-cover rounded-md">
                                <span class="remove-image" onclick="removeImage(this)">&times;</span>
                            `;
                            imagePreviews.appendChild(preview);
                            resolve();
                        };
                        reader.readAsDataURL(file);
                    });
                });
                
                // 等待所有预览图创建完成后上传文件
                Promise.all(previewPromises).then(() => {
                    uploadFiles(validFiles);
                });
            }
            
            // 上传文件到服务器
            function uploadFiles(files) {
                const formData = new FormData();
                
                for (let i = 0; i < files.length; i++) {
                    formData.append('images[]', files[i]);
                }
                
                // 显示上传中状态
                showUploadStatus('图片上传中...');
                
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'edit_diary.php?id=<?php echo $diary_id; ?>', true);
                
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        // 解析服务器响应
                        try {
                            // 这里需要解析PHP返回的HTML，提取上传的图片URL
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(xhr.responseText, 'text/html');
                            
                            // 查找上传的图片URL
                            const imageUrls = [];
                            const imagePreviews = doc.querySelectorAll('#image-previews .image-preview');
                            
                            imagePreviews.forEach(preview => {
                                const imageUrl = preview.getAttribute('data-url');
                                if (imageUrl) {
                                    imageUrls.push(imageUrl);
                                }
                            });
                            
                            if (imageUrls.length > 0) {
                                // 更新图片链接文本框
                                const currentLinks = imagePathTextarea.value.split('\n').filter(link => link.trim() !== '');
                                const newLinks = [...currentLinks, ...imageUrls];
                                imagePathTextarea.value = newLinks.join('\n');
                                
                                showUploadStatus(`成功上传 ${imageUrls.length} 张图片！`);
                            } else {
                                showUploadStatus('上传成功，但无法获取图片链接', false);
                            }
                        } catch (e) {
                            console.error('解析服务器响应失败:', e);
                            showUploadStatus('上传成功，但处理响应时出错', false);
                        }
                    } else {
                        console.error('上传失败:', xhr.statusText);
                        showUploadStatus('图片上传失败，请重试', false);
                    }
                };
                
                xhr.onerror = function() {
                    console.error('上传请求出错');
                    showUploadStatus('图片上传请求出错，请重试', false);
                };
                
                xhr.send(formData);
            }
            
            // 移除图片
            window.removeImage = function(element) {
                const preview = element.parentElement;
                const imageUrl = preview.getAttribute('data-url');
                
                // 从预览区域移除
                preview.remove();
                
                // 更新textarea中的链接
                let currentLinks = imagePathTextarea.value.split('\n');
                currentLinks = currentLinks.filter(link => link.trim() !== imageUrl);
                imagePathTextarea.value = currentLinks.join('\n');
            };
        });
    </script>
</body>
</html>
    