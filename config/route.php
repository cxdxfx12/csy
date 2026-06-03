<?php
// 路由配置
return [
    // 路由模式
    'route_pattern'      => 'rule',
    // 路由配置文件
    'route_config_file'  => ['admin', 'api', 'staff', 'manager', 'device'],
    // 是否开启路由解析
    'url_route_on'       => true,
    // 是否强制使用路由
    'url_route_must'     => true,
    // 是否开启路由合并解析
    'route_merge'        => true,
    // 路由缓存
    'route_check_anystr' => true,
];
