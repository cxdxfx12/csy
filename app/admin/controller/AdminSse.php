<?php
/**
 * 后台管理端 Server-Sent Events (SSE) 实时推送端点
 * GET /api/admin/sse/stream
 */
namespace app\admin\controller;

use app\admin\BaseAdmin;
use service\PushService;

class AdminSse extends BaseAdmin
{
    protected $noAuth = ['stream'];

    public function stream()
    {
        // 通过查询参数获取 token
        $token = $this->request->param('token', '');
        $token = str_replace('Bearer ', '', $token);

        if (empty($token)) {
            $this->sendSseError('缺少认证token');
            return;
        }

        try {
            $jwtConfig = config('jwt');
            $payload = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($jwtConfig['key'], $jwtConfig['algorithm']));
            $payload = (array) $payload;
            $tokenType = $payload['type'] ?? '';

            if (!in_array($tokenType, ['admin', 'manager'])) {
                $this->sendSseError('无效的凭证类型');
                return;
            }

        } catch (\Exception $e) {
            $this->sendSseError('身份验证失败');
            return;
        }

        // 设置 SSE 响应头
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Authorization, Content-Type');

        if (ob_get_level()) ob_end_clean();

        $this->emit('connected', ['message' => 'SSE连接已建立', 'time' => date('Y-m-d H:i:s')]);

        $lastEventId = 0;
        $lastHeartbeat = time();
        $maxRuntime = 300;
        $startTime = time();

        // 标记所有未推送事件为可推送
        PushService::markAllSseReady();

        while (true) {
            if (time() - $startTime > $maxRuntime) {
                $this->emit('timeout', ['message' => '连接刷新']);
                break;
            }

            // 管理员接收所有推送给 admin 的事件
            $events = PushService::getUnreadEvents('admin', 0, 0, $lastEventId);

            foreach ($events as $event) {
                $eventType = $event['event_type'] ?? 'repair_new';
                $eventData = [
                    'id'      => $event['id'],
                    'type'    => $eventType,
                    'title'   => $event['title'] ?? '',
                    'content' => $event['content'] ?? '',
                    'data'    => $event['target_data'] ?? [],
                    'time'    => $event['create_time'] ?? '',
                ];
                $this->emit($eventType, $eventData);
                $lastEventId = max($lastEventId, (int)$event['id']);
            }

            if (time() - $lastHeartbeat >= 15) {
                $this->emit('heartbeat', ['time' => date('Y-m-d H:i:s')]);
                $lastHeartbeat = time();
            }

            sleep(3);
            if (connection_aborted()) break;
        }
    }

    private function emit(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo "data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
        if (ob_get_level()) ob_flush();
        flush();
    }

    private function sendSseError(string $message): void
    {
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');
        echo "event: error\n";
        echo "data: " . json_encode(['message' => $message], JSON_UNESCAPED_UNICODE) . "\n\n";
        flush();
    }
}
