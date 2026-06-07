<?php
// CORS跨域中间件
namespace middleware;

class CorsMiddleware
{
    public function handle($request, \Closure $next)
    {
        $config = config('cors');
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        // 根据请求的 Origin 动态设置允许的域名（不支持逗号分隔的多域名）
        $allowOrigins = $config['allow_origin'];
        if (in_array($origin, $allowOrigins) || in_array('*', $allowOrigins)) {
            header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
        }
        // 如果 origin 不在白名单中，则不发送 CORS 头，浏览器会拒绝跨域请求
        header('Access-Control-Allow-Methods: ' . implode(',', $config['allow_methods']));
        header('Access-Control-Allow-Headers: ' . implode(',', $config['allow_headers']));
        header('Access-Control-Allow-Credentials: ' . ($config['allow_credentials'] ? 'true' : 'false'));
        header('Access-Control-Max-Age: ' . $config['max_age']);

        if ($request->method() == 'OPTIONS') {
            return response()->code(204);
        }

        return $next($request);
    }
}
