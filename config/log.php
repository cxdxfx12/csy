<?php
// 日志配置
return [
    'default'      => 'file',
    'level'        => [],
    'type_channel' => [],
    'auto_record'  => true,
    'channels'     => [
        'file' => [
            'type'           => 'file',
            'path'           => RUNTIME_PATH . 'log',
            'single'         => false,
            'file_size'      => 10485760,
            'apart_level'    => ['error', 'sql', 'notice'],
            'max_files'      => 30,
            'time_format'    => 'Y-m-d H:i:s',
            'format'         => '[%s] %s: %s',
            'time_format'    => 'Y-m-d H:i:s',
            'json'           => false,
        ],
    ],
];
