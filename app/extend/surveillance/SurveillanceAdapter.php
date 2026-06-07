<?php
/**
 * 监控录像机品牌适配器 - 抽象基类
 * 所有NVR品牌驱动必须继承此类并实现对应方法
 */
namespace app\extend\surveillance;

use think\facade\Db;

abstract class SurveillanceAdapter
{
    /** @var array NVR配置 */
    protected $config = [];

    /** @var string 品牌标识 */
    protected $brand = '';

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /** 获取品牌名称 */
    abstract public function name(): string;

    /**
     * 获取该品牌支持的型号列表
     * @return array [{ model, description }]
     */
    abstract public function models(): array;

    /**
     * 测试NVR连接
     * @return array { success:bool, message:string, device_info:array }
     */
    abstract public function testConnection(): array;

    /**
     * 获取所有通道的摄像头状态
     * @return array [{ channel_no, camera_name, online:bool, record_status, resolution, error_info }]
     */
    abstract public function getCameraStatus(): array;

    /**
     * 获取硬盘状态
     * @return array [{ hdd_no, capacity_gb, free_gb, status:'ok'|'error'|'full', health_percent }]
     */
    abstract public function getHddStatus(): array;

    /**
     * 拉取最近的异常事件
     * @return array [{ event_type, event_desc, timestamp }]
     */
    abstract public function fetchAlarms(): array;

    /**
     * 记录设备心跳
     */
    protected function recordHeartbeat(int $deviceId, bool $online, array $extra = []): void
    {
        $data = [
            'last_heartbeat' => date('Y-m-d H:i:s'),
            'online' => $online ? 1 : 0,
            'update_time' => date('Y-m-d H:i:s'),
        ];
        if (isset($extra['record_status'])) $data['record_status'] = $extra['record_status'];
        if (isset($extra['resolution'])) $data['resolution'] = $extra['resolution'];
        if (isset($extra['error_info'])) $data['error_info'] = $extra['error_info'];
        if (isset($extra['status'])) $data['status'] = $extra['status'];

        Db::name('surveillance_device')->where('id', $deviceId)->update($data);
    }

    /**
     * 记录事件
     */
    protected function recordEvent(string $eventType, string $eventDesc, int $deviceId = 0): void
    {
        // 避免重复记录相同事件（5分钟内同类型不重复）
        $recent = Db::name('surveillance_event')
            ->where('config_id', $this->config['id'] ?? 0)
            ->where('event_type', $eventType)
            ->where('create_time', '>', date('Y-m-d H:i:s', time() - 300))
            ->find();
        if ($recent) return;

        Db::name('surveillance_event')->insert([
            'config_id'    => $this->config['id'] ?? 0,
            'device_id'    => $deviceId,
            'community_id' => $this->config['community_id'] ?? 0,
            'event_type'   => $eventType,
            'event_desc'   => $eventDesc,
            'handled'      => 0,
            'create_time'  => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * HTTP请求辅助方法
     */
    protected function httpGet(string $url, string $username = '', string $password = '', int $timeout = 10): array
    {
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_HTTPHEADER     => ['Accept: application/json'],
                CURLOPT_HTTPAUTH       => CURLAUTH_DIGEST,
                CURLOPT_USERPWD        => $username . ':' . $password,
            ]);
            $body = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) return ['success' => false, 'message' => $error, 'data' => null];
            return ['success' => $httpCode >= 200 && $httpCode < 300, 'message' => "HTTP $httpCode", 'data' => $body];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => null];
        }
    }
}
