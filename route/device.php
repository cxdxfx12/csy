<?php
// 设备端路由
use think\facade\Route;

Route::group('device', function () {
    Route::post('gateway/heartbeat', 'device/Gateway/heartbeat');
    Route::post('gateway/data', 'device/Gateway/data');
    Route::get('gateway/config', 'device/Gateway/config');
    Route::post('camera/recognize', 'device/Camera/recognize');
    // 道闸回调（无需认证，设备主动推送）
    Route::post('gate/notify', 'device/Gate/notify');
    Route::post('gate/heartbeat', 'device/Gate/heartbeat');
    // 门禁回调（无需认证，门禁控制器主动推送刷卡事件）
    Route::post('access/notify', 'device/Access/notify');
    Route::post('access/heartbeat', 'device/Access/heartbeat');
});
