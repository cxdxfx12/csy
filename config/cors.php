<?php
// 跨域配置（生产环境请在 .env 设置 CORS_ALLOW_ORIGIN 为实际域名）
return [
    'allow_origin'      => explode(',', env('CORS_ALLOW_ORIGIN', 'http://localhost:5173,http://localhost:3000')),
    'allow_methods'     => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
    'allow_headers'     => ['Content-Type', 'Authorization', 'Accept', 'Origin', 'X-Requested-With'],
    'allow_credentials' => false,
    'expose_headers'    => [],
    'max_age'           => 3600,
];
