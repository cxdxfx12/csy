<?php
// 操作日志中间件
namespace middleware;

use think\facade\Db;

class LogMiddleware
{
    public function handle($request, \Closure $next)
    {
        $response = $next($request);

        // 只记录写操作
        if (!in_array($request->method(), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            return $response;
        }

        try {
            $adminInfo = $request->adminInfo ?? [];
            $data = [
                'admin_id'   => $adminInfo['id'] ?? 0,
                'admin_name' => $adminInfo['nickname'] ?? $adminInfo['username'] ?? '',
                'module'     => $request->module() ?? '',
                'action'     => $request->controller() . '/' . $request->action(),
                'url'        => $request->url(true),
                'method'     => $request->method(),
                'params'     => json_encode($request->param(), JSON_UNESCAPED_UNICODE),
                'result'     => $response->getContent(),
                'ip'         => $request->ip(),
                'user_agent' => $request->header('User-Agent', ''),
                'duration'   => (int)((microtime(true) - $request->time()) * 1000),
            ];

            Db::name('system_log')->insert($data);
        } catch (\Exception $e) {
            // 日志记录失败不影响主流程
        }

        return $response;
    }
}
