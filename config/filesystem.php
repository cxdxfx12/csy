<?php
// 文件上传配置
return [
    // 默认上传驱动
    'default'     => env('UPLOAD.DRIVER', 'local'),
    // 上传驱动
    'drivers'     => [
        'local' => [
            'type' => 'local',
            'root' => ROOT_PATH . 'public' . DS . 'uploads',
            'url'  => '/uploads',
        ],
        'oss' => [
            'type'       => 'oss',
            'access_id'  => env('OSS.ACCESS_KEY_ID', ''),
            'access_key' => env('OSS.ACCESS_KEY_SECRET', ''),
            'bucket'     => env('OSS.BUCKET', 'dasheng-property'),
            'endpoint'   => env('OSS.ENDPOINT', ''),
            'url'        => '',
        ],
    ],
    // 文件上传大小限制（字节）
    'max_size'    => env('UPLOAD.MAX_SIZE', 20971520),
    // 文件上传类型
    'ext'         => env('UPLOAD.EXT', 'jpg,jpeg,png,gif,bmp,mp4,doc,docx,xls,xlsx,pdf'),
];
