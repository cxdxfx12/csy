<?php
// 应用配置
return [
    // 应用调试模式
    'debug'               => env('APP_DEBUG', false),
    // 应用名称
    'app_name'            => '大圣物业管理系统',
    // 应用地址
    'app_host'            => '',
    // 微信 OAuth 回调域名（必须与微信公众平台后台「网页授权域名」完全一致）
    // 留空则自动从请求域名获取。填写示例：http://www.hbdxm.com 或 https://www.hbdxm.com
    'wx_oauth_domain'     => 'https://www.hbdxm.com',
    // 应用Trace
    'app_trace'           => false,
    // 应用模式
    'app_multi_module'    => true,
    // 默认模块
    'default_module'      => 'admin',
    // 默认控制器
    'default_controller'  => 'Index',
    // 默认操作
    'default_action'      => 'index',
    // 控制器类后缀
    'controller_suffix'   => false,
    // URL伪静态后缀
    'url_html_suffix'     => '',
    // 路由配置
    'url_route_must'      => true,
    'route_check_anystr'  => true,
    // 自动写入路由模式
    'route_complete_match'=> false,
    // 异常处理
    'exception_handle'    => '',
    // 页面Trace
    'show_page_trace'     => false,
    // 错误信息显示
    'error_message'       => '页面错误！请稍后再试～',
    // 错误跳转地址
    'error_url'           => '',
    // 响应输出编码
    'default_return_type' => 'json',
    // +----------------------------------------------------------
    // | 时区设置
    // +----------------------------------------------------------
    'default_timezone'    => 'Asia/Shanghai',
    // +----------------------------------------------------------
    // | 自定义配置
    // +----------------------------------------------------------
    'dasheng' => [
        'version'     => '1.0.0',
        'name'        => '大圣物业管理系统',
        'company'     => '杭州喵喵至家网络有限公司',
        'copyright'   => '©2026 杭州喵喵至家网络有限公司 All Rights Reserved.',
        'upload_path' => ROOT_PATH . 'public' . DS . 'uploads',
        'auth_key'    => 'JUD6FCtZsqrmVXc2apev4TRn3O8gAhxbSlH9wfPN',
    ],
];
