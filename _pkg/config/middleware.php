<?php
// 中间件配置
return [
    // 默认中间件
    'default' => [
        // 全局请求缓存
        // \think\middleware\CheckRequestCache::class,
        // 多语言加载
        // \think\middleware\LoadLangPack::class,
        // Session初始化
        // \think\middleware\SessionInit::class,
    ],
    // 模块中间件
    'admin' => [
        \middleware\AuthMiddleware::class,
        \middleware\LogMiddleware::class,
    ],
    'api' => [
        \middleware\ApiAuthMiddleware::class,
        \middleware\CorsMiddleware::class,
    ],
    'staff' => [
        \middleware\StaffAuthMiddleware::class,
        \middleware\CorsMiddleware::class,
    ],
    'manager' => [
        \middleware\StaffAuthMiddleware::class,
        \middleware\CorsMiddleware::class,
    ],
    'device' => [
        \middleware\DeviceAuthMiddleware::class,
    ],
];
