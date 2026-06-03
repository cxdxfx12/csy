<?php
// 缓存配置
return [
    'default' => 'redis',
    'stores'  => [
        'file' => [
            'type'      => 'file',
            'path'      => RUNTIME_PATH . 'cache',
            'prefix'    => 'ds:',
            'expire'    => 0,
            'serialize' => true,
        ],
        'redis' => [
            'type'      => 'redis',
            'host'      => env('REDIS.HOST', '127.0.0.1'),
            'port'      => env('REDIS.PORT', '6379'),
            'password'  => env('REDIS.PASSWORD', ''),
            'select'    => env('REDIS.SELECT', 0),
            'prefix'    => 'ds:cache:',
            'expire'    => 0,
            'timeout'   => 0,
        ],
    ],
];
