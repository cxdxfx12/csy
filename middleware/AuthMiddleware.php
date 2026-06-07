<?php
// JWT认证中间件
namespace middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use think\facade\Db;

class AuthMiddleware
{
    public function handle($request, \Closure $next)
    {
        if ($request->method() == 'OPTIONS') {
            return response();
        }

        $path = $request->pathinfo();
        $ignorePaths = [
            'admin/login', 'admin/captcha', 'admin/logout',
            'api/login', 'api/register', 'api/sendSms', 'api/resetPassword',
            'staff/login', 'manager/login',
        ];

        // 忽略登录等接口（精确前缀匹配）
        foreach ($ignorePaths as $ignore) {
            if ($path === $ignore || strpos($path, $ignore . '/') === 0) {
                return $next($request);
            }
        }

        $token = $request->header('Authorization', '');
        if (empty($token)) {
            $token = $request->param('token', '');
        }
        $token = str_replace('Bearer ', '', $token);

        if (empty($token)) {
            return json_error('请先登录', 401);
        }

        try {
            $config = config('jwt');
            $payload = JWT::decode($token, new Key($config['key'], $config['algorithm']));
            $payload = (array) $payload;
            // 全局中间件：根据路由前缀校验 token type，防止跨端越权
            $type = $payload['type'] ?? '';
            $pathLower = strtolower($path);
            if (strpos($pathLower, 'admin/') === 0 && $type !== 'admin') {
                return json_error('身份验证失败', 401);
            }
            if (strpos($pathLower, 'api/') === 0 && $type !== 'owner') {
                return json_error('身份验证失败', 401);
            }
            if ((strpos($pathLower, 'staff/') === 0 || strpos($pathLower, 'manager/') === 0)
                && !in_array($type, ['staff', 'manager'])) {
                return json_error('身份验证失败', 401);
            }
            $request->jwtPayload = $payload;
            return $next($request);
        } catch (ExpiredException $e) {
            return json_error('登录已过期', 401);
        } catch (\Exception $e) {
            return json_error('身份验证失败', 401);
        }
    }
}
