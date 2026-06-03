<?php
// 员工端认证中间件
namespace middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\facade\Db;

class StaffAuthMiddleware
{
    public function handle($request, \Closure $next)
    {
        if ($request->method() == 'OPTIONS') {
            return response();
        }

        $path = $request->pathinfo();
        $ignorePaths = ['staff/login', 'manager/login'];

        foreach ($ignorePaths as $ignore) {
            if (strpos($path, $ignore) !== false) {
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
            $request->jwtPayload = $payload;
            return $next($request);
        } catch (\Exception $e) {
            return json_error('身份验证失败', 401);
        }
    }
}
