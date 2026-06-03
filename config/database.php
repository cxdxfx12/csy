<?php
// 数据库配置
return [
    // 默认连接
    'default'         => 'mysql',
    // 数据库连接信息
    'connections'     => [
        'mysql' => [
            'type'            => env('DATABASE.TYPE', 'mysql'),
            'hostname'        => env('DATABASE.HOSTNAME', '127.0.0.1'),
            'database'        => env('DATABASE.DATABASE', 'dasheng'),
            'username'        => env('DATABASE.USERNAME', 'root'),
            'password'        => env('DATABASE.PASSWORD', ''),
            'hostport'        => env('DATABASE.HOSTPORT', '3306'),
            'params'          => [
                \PDO::ATTR_CASE            => \PDO::CASE_NATURAL,
                \PDO::ATTR_ERRMODE         => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_ORACLE_NULLS    => \PDO::NULL_NATURAL,
                \PDO::ATTR_STRINGIFY_FETCHES => false,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ],
            'charset'         => env('DATABASE.CHARSET', 'utf8mb4'),
            'prefix'          => env('DATABASE.PREFIX', 'ds_'),
            'debug'           => env('DATABASE.DEBUG', false),
            'deploy'          => 0,
            'rw_separate'     => false,
            'master_num'      => 1,
            'slave_no'        => '',
            'fields_strict'   => true,
            'break_reconnect' => true,
            'trigger_sql'     => env('APP_DEBUG', false),
            'auto_timestamp'  => 'datetime',
            'datetime_format' => 'Y-m-d H:i:s',
        ],
        // Redis缓存配置
        'redis' => [
            'type'     => 'redis',
            'host'     => env('REDIS.HOST', '127.0.0.1'),
            'port'     => env('REDIS.PORT', '6379'),
            'password' => env('REDIS.PASSWORD', ''),
            'select'   => env('REDIS.SELECT', 0),
            'timeout'  => 0,
            'expire'   => 0,
            'persistent' => false,
            'prefix'   => 'ds:',
        ],
    ],
];
