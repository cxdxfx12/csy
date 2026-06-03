<?php
// 业主端 API 路由
use think\facade\Route;

Route::group('api', function () {
    // 认证
    Route::post('ownerLogin', 'api/Login/login');
    Route::post('register', 'api/Login/register');
    Route::post('sendSms', 'api/Login/sendSms');
    Route::post('resetPassword', 'api/Login/resetPassword');
    // 微信认证
    Route::get('wechatOAuth', 'api/Login/wechatOAuth');
    Route::get('wechatCallback', 'api/Login/wechatCallback');
    Route::post('wechatLogin', 'api/Login/wechatLogin');
    Route::post('wechatBind', 'api/Login/wechatBind');
    Route::post('wechatUnbind', 'api/Login/wechatUnbind');

    // 经理端微信认证（复用 manager 控制器）
    Route::get('manager/wechatOAuth', 'manager/ManagerLogin/wechatOAuth');
    Route::get('manager/wechatCallback', 'manager/ManagerLogin/wechatCallback');
    Route::post('manager/wechatLogin', 'manager/ManagerLogin/wechatLogin');
    Route::post('manager/wechatRegister', 'manager/ManagerLogin/wechatRegister');

    // 首页
    Route::get('index/banner', 'api/Index/banner');
    Route::get('index/notice', 'api/Index/notice');
    Route::get('index/myInfo', 'api/Index/myInfo');

    // 房产
    Route::get('room/list', 'api/Room/lists');
    Route::get('room/detail', 'api/Room/detail');

    // 账单
    Route::get('bill/list', 'api/Bill/lists');
    Route::get('bill/detail', 'api/Bill/detail');
    Route::post('bill/pay', 'api/Bill/pay');
    Route::get('bill/unpaid', 'api/Bill/unpaid');
    Route::get('bill/payConfig', 'api/Bill/payConfig');
    Route::get('bill/payStatus', 'api/Bill/payStatus');
    // 支付回调
    Route::post('bill/wechatNotify', 'api/Bill/wechatNotify');
    Route::post('bill/alipayNotify', 'api/Bill/alipayNotify');

    // 报修
    Route::post('repair/add', 'api/Repair/add');
    Route::get('repair/list', 'api/Repair/lists');
    Route::get('repair/detail', 'api/Repair/detail');
    Route::post('repair/evaluate', 'api/Repair/evaluate');

    // 投诉
    Route::post('complaint/add', 'api/Complaint/add');
    Route::get('complaint/list', 'api/Complaint/lists');

    // 访客
    Route::post('visitor/add', 'api/Visitor/add');
    Route::get('visitor/list', 'api/Visitor/lists');

    // 车辆
    Route::get('vehicle/list', 'api/Vehicle/lists');
    Route::post('vehicle/add', 'api/Vehicle/add');

    // 公告
    Route::get('notice/list', 'api/Notice/lists');
    Route::get('notice/detail', 'api/Notice/detail');

    // 个人中心
    Route::post('profile/edit', 'api/Profile/edit');
    Route::post('profile/password', 'api/Profile/password');
    Route::get('profile/info', 'api/Profile/info');
});
