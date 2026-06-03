<?php
// CORS跨域中间件
namespace middleware;

class CorsMiddleware
{
    public function handle($request, \Closure $next)
    {
        $config = config('cors');
        header('Access-Control-Allow-Origin: ' . implode(',', $config['allow_origin']));
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
