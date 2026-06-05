<?php
// 经理端路由
use think\facade\Route;

Route::group('manager', function () {
    // 认证
    Route::post('login', 'manager/ManagerLogin/login');
    // 微信 OAuth
    Route::get('wechatOAuth', 'manager/ManagerLogin/wechatOAuth');
    Route::get('wechatCallback', 'manager/ManagerLogin/wechatCallback');
    Route::post('wechatLogin', 'manager/ManagerLogin/wechatLogin');
    Route::post('wechatRegister', 'manager/ManagerLogin/wechatRegister');

    // 驾驶舱首页
    Route::get('dashboard/statistics', 'manager/Dashboard/statistics');
    Route::get('dashboard/incomeTrend', 'manager/Dashboard/incomeTrend');
    Route::get('dashboard/repairStats', 'manager/Dashboard/repairStats');
    Route::get('dashboard/ownerStats', 'manager/Dashboard/ownerStats');
    Route::get('dashboard/pendingTodo', 'manager/Dashboard/pendingTodo');
    Route::get('dashboard/chargeRate', 'manager/Dashboard/chargeRate');
    Route::get('dashboard/communityInfo', 'manager/Dashboard/communityInfo');
    Route::get('dashboard/communityList', 'manager/Dashboard/communityList');

    // 列表
    Route::get('manager/owner/list', 'manager/Dashboard/ownerList');
    Route::get('manager/bill/list', 'manager/Dashboard/billList');
    Route::get('manager/repair/list', 'manager/Dashboard/repairList');
    Route::get('manager/complaint/list', 'manager/Dashboard/complaintList');

    // 投票管理
    Route::get('vote/list', 'manager/ManagerVote/lists');
    Route::post('vote/add', 'manager/ManagerVote/add');
    Route::post('vote/edit', 'manager/ManagerVote/edit');
    Route::post('vote/delete', 'manager/ManagerVote/delete');
    Route::get('vote/detail', 'manager/ManagerVote/detail');
    Route::post('vote/publish', 'manager/ManagerVote/publish');
    Route::post('vote/close', 'manager/ManagerVote/close');
    Route::get('vote/result', 'manager/ManagerVote/result');

    // 活动管理
    Route::get('activity/list', 'manager/ManagerActivity/lists');
    Route::post('activity/add', 'manager/ManagerActivity/add');
    Route::post('activity/edit', 'manager/ManagerActivity/edit');
    Route::post('activity/delete', 'manager/ManagerActivity/delete');
    Route::get('activity/detail', 'manager/ManagerActivity/detail');
    Route::post('activity/publish', 'manager/ManagerActivity/publish');
    Route::post('activity/start', 'manager/ManagerActivity/start');
    Route::post('activity/complete', 'manager/ManagerActivity/complete');
    Route::post('activity/cancel', 'manager/ManagerActivity/cancel');
    Route::get('activity/signups', 'manager/ManagerActivity/signups');
    Route::post('activity/approveSignup', 'manager/ManagerActivity/approveSignup');
    Route::post('activity/rejectSignup', 'manager/ManagerActivity/rejectSignup');
    Route::post('activity/cancelSignup', 'manager/ManagerActivity/cancelSignup');
    Route::get('activity/ensureSignupStatus', 'manager/ManagerActivity/ensureSignupStatusColumn');
});
