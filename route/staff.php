<?php
// 物业员工移动端路由
use think\facade\Route;

Route::group('staff', function () {
    // 认证
    Route::post('login', 'staff/StaffLogin/login');
    Route::post('logout', 'staff/StaffLogin/logout');
    Route::post('password', 'staff/StaffLogin/password');
    // 微信 OAuth
    Route::get('wechatOAuth', 'staff/StaffLogin/wechatOAuth');
    Route::get('wechatCallback', 'staff/StaffLogin/wechatCallback');
    Route::post('wechatBind', 'staff/StaffLogin/wechatBind');

    // 报修处理
    Route::get('repair/list', 'staff/StaffRepair/lists');
    Route::get('repair/detail', 'staff/StaffRepair/detail');
    Route::post('repair/accept', 'staff/StaffRepair/accept');
    Route::post('repair/claim', 'staff/StaffRepair/claim');
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

    // 投诉建议
    Route::get('complaint/list', 'staff/StaffComplaint/lists');
    Route::get('complaint/detail', 'staff/StaffComplaint/detail');
    Route::post('complaint/handle', 'staff/StaffComplaint/handle');

    // 角标 & 消息提醒
    Route::get('badge/counts', 'staff/StaffBadge/counts');

    // SSE 实时推送
    Route::get('sse/stream', 'staff/StaffSse/stream');

    // 个人中心
    Route::post('profile/edit', 'staff/StaffProfile/edit');
    Route::get('profile/info', 'staff/StaffProfile/info');

    // 考勤 & 排班 & 工资查看
    Route::get('attendance/lists', 'staff/StaffProfile/attendance');
    Route::get('schedule/lists', 'staff/StaffProfile/schedule');
    Route::get('salary/lists', 'staff/StaffProfile/salary');

    // ========== 装修管理（移动端） ==========
    // 装修申请
    Route::get('decoration/applyList', 'staff/StaffDecoration/applyList');
    Route::get('decoration/applyDetail', 'staff/StaffDecoration/applyDetail');
    // 巡查
    Route::get('decoration/inspectTodoList', 'staff/StaffDecoration/inspectTodoList');
    Route::post('decoration/inspectSubmit', 'staff/StaffDecoration/inspectSubmit');
    Route::get('decoration/inspectMyHistory', 'staff/StaffDecoration/inspectMyHistory');
    // 违规上报
    Route::post('decoration/violationReport', 'staff/StaffDecoration/violationReport');
});
