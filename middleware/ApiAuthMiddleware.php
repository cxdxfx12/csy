<?php
// API业主端认证中间件
namespace middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\facade\Db;

class ApiAuthMiddleware
{
    public function handle($request, \Closure $next)
    {
        if ($request->method() == 'OPTIONS') {
            return response();
        }

        $path = $request->pathinfo();
        $ignorePaths = ['api/login', 'api/register', 'api/sendSms', 'api/resetPassword', 'api/bill/wechatNotify', 'api/bill/alipayNotify'];

        foreach ($ignorePaths as $ignore) {
            if ($path === $ignore || strpos($path, $ignore . '/') === 0) {
                return $next($request);
            }
        }

        $token = $request->header('Authorization', '');
        $token = str_replace('Bearer ', '', $token);

        if (empty($token)) {
            return json_error('请先登录', 401);
        }

        try {
            $config = config('jwt');
            $payload = JWT::decode($token, new Key($config['key'], $config['algorithm']));
            $payload = (array) $payload;
            // 校验 token 类型：业主端只能使用 owner 类型的 token
            if (($payload['type'] ?? '') !== 'owner') {
                return json_error('身份验证失败', 401);
            }
            $request->ownerId = $payload['sub'] ?? 0;
            $request->jwtPayload = $payload;
            return $next($request);
        } catch (\Exception $e) {
            return json_error('身份验证失败', 401);
        }
    }
}
