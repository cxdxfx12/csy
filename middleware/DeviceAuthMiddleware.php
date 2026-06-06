<?php
// 设备端认证中间件（简单密钥认证）
namespace middleware;

use think\facade\Cache;

class DeviceAuthMiddleware
{
    public function handle($request, \Closure $next)
    {
        if ($request->method() == 'OPTIONS') {
            return response();
        }

        // 设备认证：通过 Header 中的设备密钥
        $deviceKey = $request->header('X-Device-Key', '');
        $deviceId = $request->header('X-Device-Id', '');

        if (empty($deviceKey) || empty($deviceId)) {
            return json_error('设备认证失败', 401);
        }

        // 从缓存/数据库验证设备密钥（简单实现：检查是否在允许的设备列表中）
        $allowed = config('device.allowed_keys', []);
        if (!empty($allowed)) {
            if (!in_array($deviceKey, $allowed)) {
                return json_error('设备密钥无效', 401);
            }
        } else {
            // 没有配置允许列表时，拒绝所有请求（安全默认）
            return json_error('设备认证未配置', 403);
        }

        $request->deviceId = $deviceId;
        $request->deviceKey = $deviceKey;

        return $next($request);
    }
}
