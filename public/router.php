<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// /admin/index.html 浏览器访问 → 重定向到 /admin/
// Vue Router base 是 /admin/，index.html 不在路由表中会导致白板
if (($uri === '/admin/index.html' || $uri === '/admin/index') && strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'text/html') !== false) {
    header('Location: /admin/', true, 301);
    return true;
}

// 静态文件直接返回
if ($uri !== '/' && file_exists(__DIR__ . $uri)) return false;

// SPA前端路由：/admin/xxx 浏览器请求 → 返回 index.html
// 判断依据：Accept 头包含 text/html（浏览器页面请求，非AJAX API请求）
$isAdminPath = strpos($uri, '/admin/') === 0;
if ($isAdminPath && strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'text/html') !== false) {
    require __DIR__ . '/admin/index.html';
    return true;
}

// API路由：将 /api/xxx 转为 /xxx 交给index.php
if (strpos($uri, '/api/') === 0) {
    $_SERVER['REQUEST_URI'] = '/index.php/' . substr($uri, 5);
    require __DIR__ . '/index.php';
    return true;
}

// 其他请求交给index.php
require __DIR__ . '/index.php';
