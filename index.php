<?php
session_start();
require_once './config.php';

// 检查数据库连接是否成功
if (!$conn) {
    die("数据库连接失败: " . mysqli_connect_error());
}

// 获取日记列表
$diaries = [];
$stmt = $conn->prepare("SELECT * FROM diaries ORDER BY publish_time DESC LIMIT 5");
if (!$stmt) {
    // 记录错误并继续执行
    error_log("SQL准备失败: " . $conn->error);
    $error = "系统错误: 无法获取日记列表";
} else {
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $diaries = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        error_log("SQL执行失败: " . $stmt->error);
        $error = "系统错误: 无法获取日记列表";
    }
    $stmt->close();
}

// 获取庆祝记录
$celebrations = [];
$stmt = $conn->prepare("SELECT c.id, c.name, c.qq, d.title as diary_title, c.created_at 
                        FROM celebrations c
                        JOIN diaries d ON c.diary_id = d.id
                        ORDER BY c.created_at DESC LIMIT 5");
if (!$stmt) {
    // 记录错误并继续执行
    error_log("SQL准备失败: " . $conn->error);
    $error = "系统错误: 无法获取庆祝记录";
} else {
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $celebrations = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        error_log("SQL执行失败: " . $stmt->error);
        $error = "系统错误: 无法获取庆祝记录";
    }
    $stmt->close();
}

// 检查是否已登录
$isLoggedIn = isset($_SESSION['admin_id']);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>小丑恋爱日记 - 记录张泽鑫的小丑恋爱日记</title>
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
            .text-shadow {
                text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }
            .animate-float {
                animation: float 6s ease-in-out infinite;
            }
            @keyframes float {
                0% { transform: translateY(0px); }
                50% { transform: translateY(-10px); }
                100% { transform: translateY(0px); }
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
    
   <!--添加优化后的弹窗-->
<!-- 访问确认弹窗 -->
<div id="access-modal">
    <div class="modal-content">
        <div class="modal-title">主播主播</div>
        <div class="modal-message">你是来看小丑的吗？🤣👉🤡</div>
        
        <!-- 表情包容器 -->
        <div class="emoticon-container">
            <img src="joker.jpg" alt="小丑表情包" id="clown-emoticon">
        </div>
        
        <!-- 提示文本 -->
        <div class="hint-text">
            这是一个记录欢笑与泪水的地方<br>
            只有真正理解小丑的人才能进入
        </div>
        
        <div class="input-container">
            <input type="text" id="access-input" class="access-input" placeholder="请输入正确的答案 “是的”">
            <div id="error-message" class="error-message">
                请输入正确的答案哦~
            </div>
            <!-- 倒计时提示 -->
            <div id="countdown-message" class="error-message" style="display: none; color: #333;">
                不是小丑爱好者？页面将在 <span id="countdown-number">3</span> 秒后关闭...
            </div>
        </div>
        <button id="confirm-btn" class="confirm-btn">确认进入</button>
    </div>
</div>

<!-- 音频播放器 -->
<div id="audio-player">
    <svg class="audio-icon" viewBox="0 0 24 24">
        <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/>
    </svg>
    <div class="audio-controls">
        <div class="audio-progress">
            <div class="audio-progress-bar" id="progress-bar"></div>
        </div>
        <div class="audio-time" id="current-time">0:00</div>
        <div class="audio-time">/</div>
        <div class="audio-time" id="total-time">0:00</div>
    </div>
</div>

<!-- 音频资源 -->
<audio id="joker-audio" preload="auto">
    <source src="https://pan.xiaoqiyuan.cn/f/BDOCa/joker.mp3" type="audio/mpeg">
    您的浏览器不支持音频播放
</audio>

<style>
    /* 访问确认弹窗样式 */
    #access-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.75);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        backdrop-filter: blur(8px);
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    
    #access-modal.active {
        opacity: 1;
        pointer-events: all;
    }
    
    .modal-content {
        background: linear-gradient(145deg, #f8f9fa, #e9ecef);
        border-radius: 20px;
        padding: 2.5rem 2rem;
        width: 90%;
        max-width: 500px;
        text-align: center;
        box-shadow: 0 20px 30px rgba(0, 0, 0, 0.15);
        transform: translateY(30px);
        transition: transform 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        position: relative;
        overflow: hidden;
    }
    
    #access-modal.active .modal-content {
        transform: translateY(0);
    }
    
    /* 装饰元素 */
    .modal-content::before, .modal-content::after {
        content: "";
        position: absolute;
        border-radius: 50%;
        opacity: 0.3;
        z-index: 0;
    }
    
    .modal-content::before {
        top: -50px;
        right: -50px;
        width: 150px;
        height: 150px;
        background-color: #ffcccc;
    }
    
    .modal-content::after {
        bottom: -30px;
        left: -30px;
        width: 100px;
        height: 100px;
        background-color: #ccffcc;
    }
    
    .modal-title {
        font-size: 2.2rem;
        font-weight: 800;
        margin-bottom: 1rem;
        color: #ff5e5e;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        position: relative;
        display: inline-block;
        z-index: 1;
    }
    
    .modal-title::after {
        content: "";
        position: absolute;
        bottom: -8px;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 3px;
        background-color: #ff5e5e;
        border-radius: 3px;
    }
    
    .modal-message {
        font-size: 1.3rem;
        margin-bottom: 2rem;
        color: #333;
        line-height: 1.5;
        z-index: 1;
    }
    
    /* 表情包容器 */
    .emoticon-container {
        width: 160px;
        height: 160px;
        margin: 0 auto 2rem;
        border-radius: 50%;
        overflow: hidden;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(145deg, #f0f0f0, #e0e0e0);
        position: relative;
        z-index: 1;
        animation: float 3s ease-in-out infinite;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }
    
    .emoticon-container img {
        max-width: 100%;
        max-height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    
    /* 提示文本 */
    .hint-text {
        font-size: 1rem;
        color: #555;
        margin-bottom: 2rem;
        line-height: 1.6;
        max-width: 80%;
        margin-left: auto;
        margin-right: auto;
        z-index: 1;
    }
    
    .input-container {
        position: relative;
        margin-bottom: 2rem;
        max-width: 80%;
        margin-left: auto;
        margin-right: auto;
        z-index: 1;
    }
    
    .access-input {
        width: 100%;
        padding: 1rem 1.5rem;
        border: 2px solid #ddd;
        border-radius: 30px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background-color: transparent;
        box-shadow: inset 2px 2px 5px rgba(0, 0, 0, 0.05),
                    inset -2px -2px 5px rgba(255, 255, 255, 0.5);
    }
    
    .access-input:focus {
        outline: none;
        border-color: #ff5e5e;
        box-shadow: inset 2px 2px 5px rgba(0, 0, 0, 0.05),
                    inset -2px -2px 5px rgba(255, 255, 255, 0.5),
                    0 0 0 3px rgba(255, 94, 94, 0.3);
    }
    
    .error-message {
        color: #ff5e5e;
        font-size: 0.9rem;
        margin-top: 0.5rem;
        display: none;
        animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .error-message.show {
        display: block;
    }
    
    /* 倒计时消息样式 */
    #countdown-message {
        color: #333;
        margin-top: 1rem;
        padding: 0.5rem;
        background-color: rgba(255, 255, 255, 0.8);
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        display: none;
    }
    
    #countdown-number {
        font-weight: bold;
        color: #ff5e5e;
        font-size: 1.2rem;
        animation: pulse 1s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.2); }
    }
    
    .confirm-btn {
        width: 80%;
        padding: 1rem;
        background: linear-gradient(135deg, #ff7e7e, #ff4d4d);
        color: white;
        border: none;
        border-radius: 30px;
        font-size: 1.1rem;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 5px 15px rgba(255, 94, 94, 0.3);
        display: block;
        margin: 0 auto;
        position: relative;
        overflow: hidden;
        z-index: 1;
    }
    
    .confirm-btn::before {
        content: "";
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
        transform: scale(0);
        opacity: 0;
        transition: all 0.5s ease;
    }
    
    .confirm-btn:hover {
        background: linear-gradient(135deg, #ff6b6b, #ff3333);
        box-shadow: 0 8px 25px rgba(255, 94, 94, 0.4);
    }
    
    .confirm-btn:active {
        transform: scale(0.95);
        box-shadow: 0 3px 10px rgba(255, 94, 94, 0.3);
    }
    
    .confirm-btn.clicked::before {
        transform: scale(1);
        opacity: 1;
    }
    
    /* 动画效果 */
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        20%, 60% { transform: translateX(-8px); }
        40%, 80% { transform: translateX(8px); }
    }
    
    .shake {
        animation: shake 0.5s ease-in-out;
    }
    
    /* 倒计时模态框淡出动画 */
    @keyframes fadeOut {
        0% { opacity: 1; transform: translateY(0); }
        100% { opacity: 0.5; transform: translateY(-20px); }
    }
    
    /* 页面退出遮罩层 */
    #exit-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.9);
        z-index: 99999;
        display: none;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 2rem;
        backdrop-filter: blur(10px);
    }
    
    #exit-overlay.show {
        display: flex;
    }
    
    /* 音频播放器样式 */
    #audio-player {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background-color: rgba(255, 255, 255, 0.8);
        border-radius: 50px;
        padding: 10px 20px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.5s ease;
        z-index: 10000;
    }
    
    #audio-player.show {
        opacity: 1;
        transform: translateY(0);
    }
    
    .audio-icon {
        width: 30px;
        height: 30px;
        margin-right: 10px;
        fill: #ff5e5e;
    }
    
    .audio-controls {
        display: flex;
        align-items: center;
    }
    
    .audio-progress {
        flex: 1;
        height: 4px;
        background-color: #e0e0e0;
        border-radius: 2px;
        margin: 0 10px;
        position: relative;
    }
    
    .audio-progress-bar {
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        width: 0;
        background-color: #ff5e5e;
        border-radius: 2px;
        transition: width 0.1s linear;
    }
    
    .audio-time {
        font-size: 0.8rem;
        color: #555;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const accessModal = document.getElementById('access-modal');
        const accessInput = document.getElementById('access-input');
        const confirmBtn = document.getElementById('confirm-btn');
        const errorMessage = document.getElementById('error-message');
        const clownEmoticon = document.getElementById('clown-emoticon');
        const audioPlayer = document.getElementById('audio-player');
        const jokerAudio = document.getElementById('joker-audio');
        const progressBar = document.getElementById('progress-bar');
        const currentTimeEl = document.getElementById('current-time');
        const totalTimeEl = document.getElementById('total-time');
        const countdownMessage = document.getElementById('countdown-message');
        const countdownNumber = document.getElementById('countdown-number');
        
        // 创建退出遮罩层
        const exitOverlay = document.createElement('div');
        exitOverlay.id = 'exit-overlay';
        exitOverlay.innerHTML = `
            <div style="text-align: center; padding: 20px; border-radius: 10px; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(10px);">
                <div style="font-size: 2.5rem; margin-bottom: 20px;">再见啦~</div>
                <div style="font-size: 1.5rem; color: #ff5e5e;">正在关闭页面...</div>
            </div>
        `;
        document.body.appendChild(exitOverlay);
        
        // 检查Cookie验证状态
        const isVerified = getCookie('isVerified');
        const verificationTime = getCookie('verificationTime');
        
        if (isVerified === 'true' && verificationTime) {
            const currentTime = new Date().getTime();
            const oneHourAgo = currentTime - 60 * 60 * 1000; // 1小时前的时间戳
            
            if (parseInt(verificationTime) > oneHourAgo) {
                // 已验证且未超过1小时，直接跳过弹窗
                return;
            } else {
                // 验证已过期，需要重新验证
                removeExpiredCookie();
            }
        }
        
        // 显示弹窗
        setTimeout(() => {
            accessModal.classList.add('active');
            // 添加表情包弹跳动画
            clownEmoticon.style.transform = 'scale(1.2)';
            setTimeout(() => {
                clownEmoticon.style.transform = 'scale(1)';
            }, 300);
        }, 300);
        
        // 确认按钮点击事件
        confirmBtn.addEventListener('click', function() {
            checkAnswer();
        });
        
        // 输入框回车事件
        accessInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                checkAnswer();
            }
        });
        
        // 检查答案
        function checkAnswer() {
            const answer = accessInput.value.trim().toLowerCase();
            
            if (answer === '是的') {
                // 正确答案，隐藏弹窗
                confirmBtn.classList.add('clicked');
                
                // 播放按钮点击动画
                setTimeout(() => {
                    accessModal.classList.remove('active');
                    playAudio();
                    
                    // 添加弹窗退出动画
                    setTimeout(() => {
                        accessModal.style.display = 'none';
                    }, 500);
                    
                    // 设置验证Cookie（有效期1小时）
                    setCookie('isVerified', 'true', 1);
                    setCookie('verificationTime', new Date().getTime(), 1);
                }, 300);
            } else if (answer === '不是') {
                // 输入"不是"，显示倒计时并退出
                startCountdown();
            } else {
                // 错误答案，显示错误信息并添加抖动效果
                errorMessage.classList.add('show');
                accessModal.querySelector('.modal-content').classList.add('shake');
                
                // 同时让表情包也抖动
                clownEmoticon.classList.add('shake');
                
                // 移除抖动效果
                setTimeout(() => {
                    accessModal.querySelector('.modal-content').classList.remove('shake');
                    clownEmoticon.classList.remove('shake');
                    errorMessage.classList.remove('show');
                }, 500);
            }
        }
        
        // 开始倒计时
        function startCountdown() {
            // 隐藏错误信息
            errorMessage.classList.remove('show');
            
            // 显示倒计时信息
            countdownMessage.style.display = 'block';
            
            // 禁用输入框和按钮
            accessInput.disabled = true;
            confirmBtn.disabled = true;
            confirmBtn.style.opacity = '0.6';
            confirmBtn.style.cursor = 'not-allowed';
            
            // 倒计时动画效果
            accessModal.querySelector('.modal-content').style.animation = 'fadeOut 3s forwards';
            
            let count = 3;
            countdownNumber.textContent = count;
            
            const countdownInterval = setInterval(() => {
                count--;
                countdownNumber.textContent = count;
                
                // 数字动画效果
                countdownNumber.style.animation = 'none';
                void countdownNumber.offsetWidth; // 触发重绘
                countdownNumber.style.animation = 'pulse 1s infinite';
                
                if (count <= 0) {
                    clearInterval(countdownInterval);
                    // 显示退出遮罩层
                    exitOverlay.classList.add('show');
                    
                    // 尝试关闭窗口
                    attemptCloseWindow();
                }
            }, 1000);
        }
        
        // 尝试关闭窗口
        function attemptCloseWindow() {
            try {
                // 尝试使用不同方法关闭窗口
                window.open('', '_self', '');
                window.close();
                
                // 如果是在iframe中，尝试关闭父窗口
                if (window !== window.top) {
                    window.top.close();
                }
                
                // 如果关闭失败，5秒后隐藏退出遮罩层
                setTimeout(() => {
                    exitOverlay.classList.remove('show');
                }, 5000);
            } catch (e) {
                console.log('无法关闭窗口:', e);
                
                // 显示提示信息
                const messageElement = exitOverlay.querySelector('div:nth-child(2)');
                messageElement.textContent = '无法自动关闭窗口，请手动关闭';
                
                // 5秒后隐藏退出遮罩层
                setTimeout(() => {
                    exitOverlay.classList.remove('show');
                }, 5000);
            }
        }
        
        // 输入框聚焦时移除错误信息
        accessInput.addEventListener('focus', function() {
            errorMessage.classList.remove('show');
            countdownMessage.style.display = 'none';
            accessInput.disabled = false;
            confirmBtn.disabled = false;
            confirmBtn.style.opacity = '1';
            confirmBtn.style.cursor = 'pointer';
            accessModal.querySelector('.modal-content').style.animation = 'none';
        });
        
        // 播放音频函数
        function playAudio() {
            // 显示音频播放器
            audioPlayer.classList.add('show');
            
            // 播放音频
            jokerAudio.play().catch(e => {
                console.log('音频自动播放被阻止，请用户交互后播放', e);
                // 可添加用户点击后播放的逻辑
            });
            
            // 设置总时长
            jokerAudio.addEventListener('loadedmetadata', function() {
                totalTimeEl.textContent = formatTime(jokerAudio.duration);
            });
            
            // 更新进度条和当前时间
            jokerAudio.addEventListener('timeupdate', function() {
                const percent = (jokerAudio.currentTime / jokerAudio.duration) * 100;
                progressBar.style.width = percent + '%';
                currentTimeEl.textContent = formatTime(jokerAudio.currentTime);
            });
            
            // 音频结束时隐藏播放器
            jokerAudio.addEventListener('ended', function() {
                setTimeout(() => {
                    audioPlayer.classList.remove('show');
                }, 1000);
            });
        }
        
        // 格式化时间函数
        function formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${minutes}:${secs < 10 ? '0' : ''}${secs}`;
        }
        
        // 获取Cookie值
        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        }
        
        // 设置Cookie
        function setCookie(name, value, hoursToExpire) {
            const date = new Date();
            date.setTime(date.getTime() + hoursToExpire * 60 * 60 * 1000);
            document.cookie = `${name}=${value}; expires=${date.toUTCString()}; path=/`;
        }
        
        // 移除过期Cookie
        function removeExpiredCookie() {
            setCookie('isVerified', 'false', -1);
            setCookie('verificationTime', '0', -1);
        }
    });
</script>
<!--弹窗结束-->

</head>
<body class="bg-white min-h-screen flex flex-col">
    <!-- 导航栏 -->
    <header class="sticky top-0 z-50 bg-white shadow-sm">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <a href="#" class="text-xl font-bold text-primary flex items-center">
                        <i class="fa fa-heart-o mr-2"></i>
                        小丑恋爱日记
                    </a>
                </div>
                <nav class="hidden md:flex items-center space-x-8">
                    <a href="#index" class="text-gray-600 hover:text-primary font-medium transition-custom">首页</a>
                    <a href="#diaries" class="text-gray-600 hover:text-primary font-medium transition-custom">日记</a>
                    <a href="#celebrate-modal" class="text-gray-600 hover:text-primary font-medium transition-custom">记录</a>
                    <a href="#" class="text-gray-600 hover:text-primary font-medium transition-custom">关于</a>
                </nav>
                <div class="flex items-center space-x-4">
                    <button class="md:hidden text-gray-600" id="mobile-menu-button">
                        <i class="fa fa-bars text-xl"></i>
                    </button>
                    <?php if ($isLoggedIn): ?>
                        <a href="admin/login.php" class="hidden md:inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-custom">
                        <i class="fa fa-lock mr-2"></i>
                        管理员登录
                    </a>
                        <!--<a href="logout.php" class="hidden md:inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-custom">-->
                        <!--    <i class="fa fa-sign-out mr-2"></i>-->
                        <!--    退出-->
                        <!--</a>-->
                    <?php else: ?>
                        <a href="admin/login.php" class="hidden md:inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-custom">
                            <i class="fa fa-lock mr-2"></i>
                            登录
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- 移动端菜单 -->
        <div class="md:hidden hidden" id="mobile-menu">
            <div class="px-4 py-4 space-y-3 bg-gray-50">
                <a href="#index.php" class="block px-3 py-2 rounded-md text-base font-medium text-primary bg-primary/10">首页</a>
                <a href="#diaries.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:text-primary hover:bg-primary/5">日记</a>
                <a href="#celebrations.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:text-primary hover:bg-primary/5">记录</a>
                <a href="#" class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:text-primary hover:bg-primary/5">关于</a>
                <?php if ($isLoggedIn): ?>
                    <a href="dashboard.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:text-primary hover:bg-primary/5">
                        <i class="fa fa-cog mr-2"></i>
                        管理
                    </a>
                    <a href="logout.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:text-primary hover:bg-primary/5">
                        <i class="fa fa-sign-out mr-2"></i>
                        退出
                    </a>
                <?php else: ?>
                    <a href="admin/login.php" class="block px-3 py-2 rounded-md text-base font-medium text-primary hover:text-primary/80">
                        <i class="fa fa-lock mr-2"></i>
                        管理员登录
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- 英雄区 -->
    <section class="relative bg-gradient-to-r from-primary/10 to-secondary/10 py-20 md:py-32 overflow-hidden">
        <div class="container mx-auto px-4 relative z-10">
            <div class="max-w-3xl mx-auto text-center">
                <h1 class="text-[clamp(2rem,5vw,3.5rem)] font-bold text-gray-900 leading-tight mb-6 text-shadow">
                    小丑恋爱日记
                </h1>
                <p class="text-[clamp(1rem,2vw,1.25rem)] text-gray-600 mb-8">
                    记录张泽鑫的小丑恋爱日记，分享那些欢笑与泪水交织的瞬间
                </p>
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <a href="#diaries" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-custom shadow-lg hover:shadow-xl">
                        <i class="fa fa-book mr-2"></i>
                        查看日记
                    </a>
                    <a href="#" class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-custom shadow-lg hover:shadow-xl">
                        <i class="fa fa-heart-o mr-2"></i>
                        关于小丑
                    </a>
                </div>
            </div>
        </div>
        
        <!-- 装饰元素 -->
        <div class="absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-full -mr-32 -mt-32 animate-float"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-secondary/5 rounded-full -ml-48 -mb-48 animate-float" style="animation-delay: 2s;"></div>
    </section>

    <!-- 日记列表 -->
    <section id="diaries" class="py-16 md:py-24">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-[clamp(1.5rem,3vw,2.5rem)] font-bold text-gray-900 mb-4">小丑日记</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">记录那些欢笑与泪水交织的瞬间，见证一段段动人的故事</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">错误!</strong>
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (empty($diaries)): ?>
                <div class="text-center py-12">
                    <div class="w-16 h-16 mx-auto mb-4 text-gray-400">
                        <i class="fa fa-book text-4xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">暂无日记</h3>
                    <p class="text-gray-500 mb-6">管理员正在准备第一篇日记，请稍后再来</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($diaries as $diary): ?>
                        <div class="bg-white rounded-xl overflow-hidden border border-gray-200 shadow-sm hover:shadow-md transition-custom group">
                            <div class="relative h-56 overflow-hidden">
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
                                                <img src="<?php echo htmlspecialchars($image_links[$i]); ?>" alt="<?php echo $diary['title']; ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                                            </div>
                                        <?php endfor; ?>
                                        
                                        <?php if ($image_count > 4): ?>
                                            <div class="item-overlay flex items-center justify-center">
                                                <span>+<?php echo $image_count - 4; ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                        <i class="fa fa-image text-gray-400 text-4xl"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="absolute top-3 right-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary">
                                        <?php echo htmlspecialchars($diary['category']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="flex items-center text-sm text-gray-500 mb-3">
                                    <i class="fa fa-calendar-o mr-1"></i>
                                    <span><?php echo date('Y-m-d', strtotime($diary['publish_time'])); ?></span>
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 mb-3"><?php echo htmlspecialchars($diary['title']); ?></h3>
                                <p class="text-gray-600 mb-4 line-clamp-3"><?php echo nl2br(htmlspecialchars($diary['content'])); ?></p>
                                
                                <!-- 查看详情按钮 -->
                                <div class="mt-6">
                                    <a href="view_public_diary.php?id=<?php echo $diary['id']; ?>" class="inline-flex items-center w-full justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-custom">
                                        <i class="fa fa-eye mr-2"></i>
                                        查看详情
                                    </a>
                                </div>
                                
                                <!-- 庆祝按钮 -->
                                <div class="mt-6">
                                    <button class="celebrate-btn w-full flex justify-center items-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-secondary hover:bg-secondary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary transition-custom" data-diary-id="<?php echo $diary['id']; ?>">
                                        <i class="fa fa-thumbs-up mr-2"></i>
                                        庆祝
                                    </button>
                                </div>
                                
                                <!-- 庆祝记录 -->
                                <div class="mt-4 pt-4 border-t border-gray-100">
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">已庆祝的人</h4>
                                    <div class="flex flex-wrap gap-2" id="celebrations-<?php echo $diary['id']; ?>">
                                        <!-- 庆祝记录将通过AJAX加载 -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- 庆祝记录 -->
    <section class="py-16 md:py-24">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-[clamp(1.5rem,3vw,2.5rem)] font-bold text-gray-900 mb-4">最新庆祝记录</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">看看谁在为小丑的故事欢呼</p>
            </div>
            
            <?php if (empty($celebrations)): ?>
                <div class="text-center py-12">
                    <div class="w-16 h-16 mx-auto mb-4 text-gray-400">
                        <i class="fa fa-users text-4xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">暂无庆祝记录</h3>
                    <p class="text-gray-500 mb-6">快来成为第一个庆祝的人吧</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($celebrations as $celebration): ?>
                        <div class="bg-white rounded-xl overflow-hidden border border-gray-200 shadow-sm hover:shadow-md transition-custom group">
                            <div class="p-6">
                                <div class="flex items-center mb-3">
                                    <img class="h-10 w-10 rounded-full" src="https://q1.qlogo.cn/g?b=qq&nk=<?php echo $celebration['qq']; ?>&s=640" alt="<?php echo $celebration['name']; ?>">
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo $celebration['name']; ?></div>
                                        <div class="text-xs text-gray-500"><?php echo $celebration['qq']; ?></div>
                                    </div>
                                </div>
                                <p class="text-gray-600 mb-4">为日记 <span class="text-primary font-medium"><?php echo $celebration['diary_title']; ?></span> 庆祝于 <?php echo date('Y-m-d H:i', strtotime($celebration['created_at'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- 关于小丑 -->
    <section class="py-16 md:py-24 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                <div>
                    <h2 class="text-[clamp(1.5rem,3vw,2.5rem)] font-bold text-gray-900 mb-6">关于小丑</h2>
                    <p class="text-gray-600 mb-6">
                        小丑，本名张泽鑫，一个平凡而又特别的人。他用日记记录着自己的感情历程，那些欢笑与泪水交织的瞬间，那些无法言说的心事。
                    </p>
                    <p class="text-gray-600 mb-6">
                        他的日记不仅仅是记录，更是一种宣泄，一种成长的见证。每一篇日记都充满了真实的情感，让人感同身受。
                    </p>
                    <div class="flex items-center space-x-4 mt-8">
                        <a href="#" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-custom">
                            <i class="fa fa-heart-o mr-2"></i>
                            了解更多
                        </a>
                        <a href="#" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-custom">
                            <i class="fa fa-comment-o mr-2"></i>
                            留言支持
                        </a>
                    </div>
                </div>
                <div class="relative">
                    <div class="relative z-10 rounded-xl overflow-hidden shadow-xl">
                        <img src="https://picsum.photos/600/800" alt="小丑照片" class="w-full h-auto">
                    </div>
                    <div class="absolute -bottom-6 -right-6 w-40 h-40 bg-primary/20 rounded-full z-0"></div>
                    <div class="absolute -top-6 -left-6 w-32 h-32 bg-secondary/20 rounded-full z-0"></div>
                </div>
            </div>
        </div>
    </section>

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

    <!-- 庆祝模态框 -->
    <div id="celebrate-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity" id="modal-backdrop"></div>
        <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full mx-4 overflow-hidden transform transition-all" id="modal-content">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">庆祝小丑</h3>
                    <button id="close-modal" class="text-gray-400 hover:text-gray-500">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="space-y-4">
                    <div>
                        <label for="celebration-name" class="block text-sm font-medium text-gray-700 mb-1">您的名字</label>
                        <input type="text" id="celebration-name" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary transition-custom" placeholder="请输入您的名字">
                    </div>
                    <div>
                        <label for="celebration-qq" class="block text-sm font-medium text-gray-700 mb-1">QQ号</label>
                        <input type="text" id="celebration-qq" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary transition-custom" placeholder="请输入您的QQ号">
                    </div>
                    <div id="avatar-preview" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-1">QQ头像</label>
                        <div class="flex items-center">
                            <img id="qq-avatar" src="" alt="QQ头像" class="w-12 h-12 rounded-full mr-3">
                            <button id="change-qq" class="text-sm text-primary hover:text-primary/80">
                                更换
                            </button>
                        </div>
                    </div>
                </div>
                <div class="mt-6 pt-4 border-t border-gray-100">
                    <button id="submit-celebration" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-secondary hover:bg-secondary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary transition-custom">
                        提交庆祝
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 结果模态框 -->
    <div id="result-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity" id="result-backdrop"></div>
        <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full mx-4 overflow-hidden transform transition-all" id="result-content">
            <div class="p-6 text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fa fa-check text-green-500 text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">庆祝成功！</h3>
                <p class="text-gray-600 mb-4" id="celebration-result">
                    感谢您的庆祝，您的头像和名字已被记录
                </p>
                <div class="text-xl font-bold text-gray-900 mb-6" id="celebration-emoji">
                    🤣👉🤡
                </div>
                <div class="text-gray-600 mb-6" id="tian-gou-quote">
                    舔狗语录将在这里显示
                </div>
                <button id="close-result" class="inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-custom">
                    确定
                </button>
            </div>
        </div>
    </div>

    <script>
        // 移动端菜单切换
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });
        
        // 庆祝模态框
        const celebrateModal = document.getElementById('celebrate-modal');
        const modalBackdrop = document.getElementById('modal-backdrop');
        const modalContent = document.getElementById('modal-content');
        const closeModal = document.getElementById('close-modal');
        const celebrateBtns = document.querySelectorAll('.celebrate-btn');
        const celebrationName = document.getElementById('celebration-name');
        const celebrationQQ = document.getElementById('celebration-qq');
        const avatarPreview = document.getElementById('avatar-preview');
        const qqAvatar = document.getElementById('qq-avatar');
        const changeQQ = document.getElementById('change-qq');
        const submitCelebration = document.getElementById('submit-celebration');
        let currentDiaryId = null;
        
        // 结果模态框
        const resultModal = document.getElementById('result-modal');
        const resultBackdrop = document.getElementById('result-backdrop');
        const resultContent = document.getElementById('result-content');
        const closeResult = document.getElementById('close-result');
        const celebrationResult = document.getElementById('celebration-result');
        const celebrationEmoji = document.getElementById('celebration-emoji');
        const tianGouQuote = document.getElementById('tian-gou-quote');
        
        // 打开庆祝模态框
        celebrateBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                currentDiaryId = this.getAttribute('data-diary-id');
                celebrationName.value = '';
                celebrationQQ.value = '';
                avatarPreview.classList.add('hidden');
                
                celebrateModal.classList.remove('hidden');
                setTimeout(() => {
                    modalBackdrop.classList.add('opacity-100');
                    modalContent.classList.add('scale-100');
                }, 10);
                
                // 预加载庆祝记录
                loadCelebrations(currentDiaryId);
            });
        });
        
        // 关闭庆祝模态框
        function closeCelebrationModal() {
            modalBackdrop.classList.remove('opacity-100');
            modalContent.classList.remove('scale-100');
            setTimeout(() => {
                celebrateModal.classList.add('hidden');
            }, 300);
        }
        
        closeModal.addEventListener('click', closeCelebrationModal);
        modalBackdrop.addEventListener('click', closeCelebrationModal);
        
        // QQ号输入验证
        celebrationQQ.addEventListener('input', function() {
            const qq = this.value.trim();
            if (qq.length >= 5) {
                // 获取QQ头像
                qqAvatar.src = `https://q1.qlogo.cn/g?b=qq&nk=${qq}&s=640`;
                avatarPreview.classList.remove('hidden');
            } else {
                avatarPreview.classList.add('hidden');
            }
        });
        
        // 更换QQ号
        changeQQ.addEventListener('click', function() {
            celebrationQQ.value = '';
            avatarPreview.classList.add('hidden');
            celebrationQQ.focus();
        });
        
        // 提交庆祝
        submitCelebration.addEventListener('click', function() {
            const name = celebrationName.value.trim();
            const qq = celebrationQQ.value.trim();
            
            if (!name || !qq) {
                alert('请输入您的名字和QQ号');
                return;
            }
            
            // 提交庆祝
            fetch('api/celebrate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    diary_id: currentDiaryId,
                    name: name,
                    qq: qq
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 关闭庆祝模态框
                    closeCelebrationModal();
                    
                    // 显示结果模态框
                    celebrationResult.textContent = `感谢您的庆祝，${name}的头像和名字已被记录`;
                    tianGouQuote.textContent = data.quote || '舔狗语录加载失败';
                    
                    resultModal.classList.remove('hidden');
                    setTimeout(() => {
                        resultBackdrop.classList.add('opacity-100');
                        resultContent.classList.add('scale-100');
                    }, 10);
                    
                    // 更新庆祝记录
                    loadCelebrations(currentDiaryId);
                } else {
                    alert('庆祝失败: ' + data.message);
                }
            })
            .catch((error) => {
                console.error('Error:', error);
                alert('庆祝失败，请稍后再试');
            });
        });
        
        // 关闭结果模态框
        function closeResultModal() {
            resultBackdrop.classList.remove('opacity-100');
            resultContent.classList.remove('scale-100');
            setTimeout(() => {
                resultModal.classList.add('hidden');
            }, 300);
        }
        
        closeResult.addEventListener('click', closeResultModal);
        resultBackdrop.addEventListener('click', closeResultModal);
        
        // 加载庆祝记录
        function loadCelebrations(diaryId) {
            const container = document.getElementById(`celebrations-${diaryId}`);
            if (!container) return;
            
            fetch(`api/get_celebrations.php?diary_id=${diaryId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.celebrations) {
                    container.innerHTML = '';
                    data.celebrations.forEach(celebration => {
                        const avatarUrl = celebration.avatar_url || 'https://picsum.photos/200/200';
                        const item = document.createElement('div');
                        item.className = 'flex items-center space-x-1';
                        item.innerHTML = `
                            <img src="${avatarUrl}" alt="${celebration.name}" class="w-6 h-6 rounded-full">
                            <span class="text-xs text-gray-600">${celebration.name}</span>
                        `;
                        container.appendChild(item);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading celebrations:', error);
            });
        }
        
        // 页面加载时预加载所有日记的庆祝记录
        document.addEventListener('DOMContentLoaded', function() {
            celebrateBtns.forEach(btn => {
                const diaryId = btn.getAttribute('data-diary-id');
                loadCelebrations(diaryId);
            });
        });
    </script>
</body>
</html>