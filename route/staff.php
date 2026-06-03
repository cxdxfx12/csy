<?php
// 物业员工移动端路由
use think\facade\Route;

Route::group('staff', function () {
    // 认证
    Route::post('login', 'staff/StaffLogin/login');
    Route::post('logout', 'staff/StaffLogin/logout');
    Route::post('password', 'staff/StaffLogin/password');

    // 报修处理
    Route::get('repair/list', 'staff/StaffRepair/lists');
    Route::get('repair/detail', 'staff/StaffRepair/detail');
    Route::post('repair/accept', 'staff/StaffRepair/accept');
    Route::post('repair/finish', 'staff/StaffRepair/finish');

    // 抄表录入
    Route::get('meter/list', 'staff/StaffMeter/lists');
    Route::post('meter/read', 'staff/StaffMeter/read');
    Route::get('meter/history', 'staff/StaffMeter/history');

    // 移动收费
    Route::get('charge/unpaidList', 'staff/StaffCharge/unpaidList');
    Route::post('charge/collect', 'staff/StaffCharge/collect');

    // 安保巡更
    Route::get('patrol/routes', 'staff/StaffPatrol/routes');
    Route::post('patrol/check', 'staff/StaffPatrol/check');
    Route::get('patrol/history', 'staff/StaffPatrol/history');

    // 访客
    Route::post('visitor/add', 'staff/StaffVisitor/add');
    Route::get('visitor/list', 'staff/StaffVisitor/lists');

    // 工单
    Route::get('order/list', 'staff/StaffOrder/lists');
    Route::post('order/create', 'staff/StaffOrder/create');
    Route::post('order/close', 'staff/StaffOrder/close');

    // 个人中心
    Route::post('profile/edit', 'staff/StaffProfile/edit');
    Route::get('profile/info', 'staff/StaffProfile/info');
});
