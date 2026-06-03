<?php
// 领导驾驶舱路由
use think\facade\Route;

Route::group('manager', function () {
    Route::post('login', 'manager/ManagerLogin/login');

    // 驾驶舱首页
    Route::get('dashboard/statistics', 'manager/Dashboard/statistics');
    Route::get('dashboard/incomeTrend', 'manager/Dashboard/incomeTrend');
    Route::get('dashboard/repairStats', 'manager/Dashboard/repairStats');
    Route::get('dashboard/ownerStats', 'manager/Dashboard/ownerStats');
    Route::get('dashboard/communityRank', 'manager/Dashboard/communityRank');
    Route::get('dashboard/pendingTodo', 'manager/Dashboard/pendingTodo');
    Route::get('dashboard/chargeRate', 'manager/Dashboard/chargeRate');
});
