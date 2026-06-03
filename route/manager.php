<?php
// 经理端路由
use think\facade\Route;

Route::group('manager', function () {
    Route::post('login', 'manager/ManagerLogin/login');

    // 驾驶舱首页
    Route::get('dashboard/statistics', 'manager/Dashboard/statistics');
    Route::get('dashboard/incomeTrend', 'manager/Dashboard/incomeTrend');
    Route::get('dashboard/repairStats', 'manager/Dashboard/repairStats');
    Route::get('dashboard/ownerStats', 'manager/Dashboard/ownerStats');
    Route::get('dashboard/pendingTodo', 'manager/Dashboard/pendingTodo');
    Route::get('dashboard/chargeRate', 'manager/Dashboard/chargeRate');
    Route::get('dashboard/communityInfo', 'manager/Dashboard/communityInfo');

    // 列表
    Route::get('manager/owner/list', 'manager/Dashboard/ownerList');
    Route::get('manager/bill/list', 'manager/Dashboard/billList');
    Route::get('manager/repair/list', 'manager/Dashboard/repairList');
    Route::get('manager/complaint/list', 'manager/Dashboard/complaintList');
});
