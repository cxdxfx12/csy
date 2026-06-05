<?php
use think\facade\Route;

// 官网首页路由 - 使用控制器格式（路由解析器只支持字符串 handler）
Route::get('/', 'home/Index/index');
