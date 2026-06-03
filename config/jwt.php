<?php
// JWT认证配置
return [
    // 密钥
    'key'         => env('JWT.KEY', 'ds_property_manager_jwt_key_2026'),
    // 签发者
    'iss'         => env('JWT.ISS', 'dasheng-pms'),
    // 受众
    'aud'         => env('JWT.AUD', 'dasheng-pms-client'),
    // 过期时间（秒）
    'exp'         => env('JWT.EXP', 86400),
    // 留白时间（秒）
    'leeway'      => 60,
    // 算法
    'algorithm'   => 'HS256',
    // 客户端标识字段
    'token_name'  => 'Authorization',
    // 刷新token过期时间（秒）
    'refresh_exp' => 604800,
];
