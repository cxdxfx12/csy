<?php
/**
 * 员工端 Server-Sent Events (SSE) 实时推送端点
 * GET /api/staff/sse/stream
 * 
 * 工作原理：
 * 1. 客户端以 EventSource 连接此端点
 * 2. 服务端每3秒检查是否有新推送事件
 * 3. 有新事件则推送给客户端
 * 4. 每15秒发送心跳保持连接
 */
namespace app\staff\controller;

use app\staff\BaseStaff;
use service\PushService;

class StaffSse extends BaseStaff
{
    // SSE 端点不需要 auth 拦截（通过 token 参数自行验证）
    protected $noAuth = ['stream'];

    public function stream()
    {
        // 通过查询参数获取 token 并验证身份
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
            $staffId = $payload['sub'] ?? 0;

            $tokenType = $payload['type'] ?? '';
            if (!in_array($tokenType, ['staff', 'manager'])) {
                $this->sendSseError('无效的凭证类型');
                return;
            }

            // 来源标记，防止 admin_user 和 repair_worker 跨表 ID 碰撞
            $source = $payload['source'] ?? '';

            // 查找员工信息
            $staffInfo = null;
            $isWorker = false;
            $workerId = 0;
            $communityId = 0;

            if ($source === 'worker') {
                // 维修工：sub = repair_worker.id
                $worker = \think\facade\Db::name('repair_worker')->where('id', $staffId)->where('status', 1)->find();
                if ($worker) {
                    $isWorker = true;
                    $workerId = $worker['id'];
                    $communityId = $worker['community_id'];
                }
            } elseif ($source === 'admin') {
                // 管理员：sub = admin_user.id
                $adminUser = \think\facade\Db::name('admin_user')->where('id', $staffId)->find();
                if ($adminUser) {
                    $allowedRoles = config('staff.allowed_roles', [2, 3, 4, 5, 6, 7, 8]);
                    if (!in_array((int)$adminUser['role_id'], $allowedRoles)) {
                        $this->sendSseError('无效的凭证类型');
                        return;
                    }
                    if ($adminUser['phone']) {
                        $worker = \think\facade\Db::name('repair_worker')->where('phone', $adminUser['phone'])->find();
                        if ($worker) {
                            $isWorker = true;
                            $workerId = $worker['id'];
                            $communityId = $worker['community_id'];
                        }
                    }
                    if (!$isWorker) {
                        $commIds = array_values(array_filter(array_map('intval', explode(',', $adminUser['community_ids'] ?? ''))));
                        $communityId = $commIds[0] ?? 0;
                    }
                }
            } else {
                // 向后兼容旧 token（无 source 字段）
                $staffInfo = \think\facade\Db::name('staff')->where('id', $staffId)->find();

                if (!$staffInfo && $staffId) {
                    $adminUser = \think\facade\Db::name('admin_user')->where('id', $staffId)->find();
                    if ($adminUser && $adminUser['phone']) {
                        $worker = \think\facade\Db::name('repair_worker')->where('phone', $adminUser['phone'])->find();
                        if ($worker) {
                            $isWorker = true;
                            $workerId = $worker['id'];
                            $communityId = $worker['community_id'];
                        }
                    }
                }
                // 兜底：JWT sub 直接是 repair_worker.id
                if (!$staffInfo && !$isWorker && $staffId) {
                    $worker = \think\facade\Db::name('repair_worker')->where('id', $staffId)->where('status', 1)->find();
                    if ($worker) {
                        $isWorker = true;
                        $workerId = $worker['id'];
                        $communityId = $worker['community_id'];
                    }
                }
                if ($staffInfo) {
                    $communityId = $staffInfo['community_id'] ?? 0;
                }
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
        header('X-Accel-Buffering: no');   // 禁用 nginx 缓冲
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Authorization, Content-Type');

        // 关闭输出缓冲
        if (ob_get_level()) ob_end_clean();

        // 发送初始连接确认
        $this->emit('connected', ['message' => 'SSE连接已建立', 'time' => date('Y-m-d H:i:s')]);

        $lastEventId = 0;
        $lastHeartbeat = time();
        $maxRuntime = 300; // 最大运行5分钟后重连

        // 标记所有未推送事件为可推送（本连接会消费它们）
        PushService::markAllSseReady();

        $startTime = time();

        while (true) {
            // 超时退出，客户端会重连
            if (time() - $startTime > $maxRuntime) {
                $this->emit('timeout', ['message' => '连接刷新']);
                break;
            }

            // 检查新事件
            if ($isWorker && $workerId > 0) {
                $events = PushService::getUnreadEvents('worker', $workerId, $communityId, $lastEventId);
            } else if ($communityId > 0) {
                $events = PushService::getUnreadEvents('admin', 0, $communityId, $lastEventId);
            } else {
                $events = PushService::getUnreadEvents('admin', 0, 0, $lastEventId);
            }

            // 推送新事件
            foreach ($events as $event) {
                $eventType = $event['event_type'] ?? 'repair_new';
                $eventData = [
                    'id'         => $event['id'],
                    'type'       => $eventType,
                    'title'      => $event['title'] ?? '',
                    'content'    => $event['content'] ?? '',
                    'data'       => $event['target_data'] ?? [],
                    'time'       => $event['create_time'] ?? '',
                ];
                $this->emit($eventType, $eventData);
                $lastEventId = max($lastEventId, (int)$event['id']);
            }

            // 每15秒心跳
            if (time() - $lastHeartbeat >= 15) {
                $this->emit('heartbeat', ['time' => date('Y-m-d H:i:s')]);
                $lastHeartbeat = time();
            }

            // 等待3秒再检查
            sleep(3);

            // 检查客户端是否断开
            if (connection_aborted()) break;
        }
    }

    /**
     * 发送 SSE 事件
     */
    private function emit(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo "data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
        if (ob_get_level()) ob_flush();
        flush();
    }

    /**
     * 发送 SSE 错误并结束
     */
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
