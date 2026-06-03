<?php
// API入口 - 用于前端Ajax请求代理
// 简化开发测试，生产环境请使用Nginx重写规则

$path = $_SERVER['REQUEST_URI'];
$path = ltrim($path, '/');

// 路由到 ThinkPHP
$_SERVER['PATH_INFO'] = $path;
$_SERVER['SCRIPT_NAME'] = '/index.php';

require __DIR__ . '/index.php';
