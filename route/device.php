<?php
// 设备端路由
use think\facade\Route;

Route::group('device', function () {
    Route::post('gateway/heartbeat', 'device/Gateway/heartbeat');
    Route::post('gateway/data', 'device/Gateway/data');
    Route::get('gateway/config', 'device/Gateway/config');
    Route::post('camera/recognize', 'device/Camera/recognize');
});
