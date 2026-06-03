<?php
// 跨域配置
return [
    'allow_origin'      => ['*'],
    'allow_methods'     => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
    'allow_headers'     => ['Content-Type', 'Authorization', 'Accept', 'Origin', 'X-Requested-With'],
    'allow_credentials' => true,
    'expose_headers'    => [],
    'max_age'           => 3600,
];
