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
            $params = $request->param();
            // 脱敏：过滤密码、token 等敏感字段
            $sensitiveFields = ['password', 'old_password', 'new_password', 'token', 'secret', 'api_key'];
            foreach ($sensitiveFields as $field) {
                if (isset($params[$field])) $params[$field] = '***';
            }

            // 截断过长的响应体，防止日志膨胀和敏感数据泄露
            $resultContent = $response->getContent();
            if (mb_strlen($resultContent) > 1024) {
                $resultContent = mb_substr($resultContent, 0, 1024) . '...[truncated]';
            }

            $data = [
                'admin_id'   => $adminInfo['id'] ?? 0,
                'admin_name' => $adminInfo['nickname'] ?? $adminInfo['username'] ?? '',
                'module'     => $request->module() ?? '',
                'action'     => $request->controller() . '/' . $request->action(),
                'url'        => $request->url(true),
                'method'     => $request->method(),
                'params'     => json_encode($params, JSON_UNESCAPED_UNICODE),
                'result'     => $resultContent,
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
