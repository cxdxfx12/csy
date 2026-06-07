<?php
/**
 * 硬件设备协议适配器 - 抽象基类
 * 所有设备类型的协议驱动必须继承此类
 * 统一管理：测试连接、远程操作、状态查询、心跳、事件记录
 */
namespace app\extend\device;

use think\facade\Db;

abstract class DeviceAdapter
{
    /** @var array 设备配置 */
    protected $config = [];

    /** @var string 设备类型标识 */
    protected $type = '';

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * 获取设备类型名称
     * @return string
     */
    abstract public function name(): string;

    /**
     * 测试连接 — 验证设备是否可达（ping/HTTP/MQTT等）
     * @return array { success:bool, message, latency_ms }
     */
    abstract public function testConnection(): array;

    /**
     * 查询设备实时状态（在线/离线/运行参数）
     * @return array { online:bool, status:string, data:[] }
     */
    abstract public function getStatus(): array;

    /**
     * 远程控制操作（开闸/开门/重启/开关等）
     * @param string $action 具体动作
     * @param array $params 动作参数
     * @return array { success:bool, message }
     */
    abstract public function remoteAction(string $action, array $params = []): array;

    /**
     * 处理设备心跳上报
     * @param array $data 心跳数据
     */
    public function handleHeartbeat(array $data): void
    {
        Db::name('device')
            ->where('id', $this->config['id'] ?? 0)
            ->update([
                'last_heartbeat' => date('Y-m-d H:i:s'),
                'status'         => 1,
                'update_time'    => date('Y-m-d H:i:s'),
            ]);

        // 记录心跳到事件表
        Db::name('device_event')->insert([
            'device_id'   => $this->config['id'] ?? 0,
            'community_id'=> $this->config['community_id'] ?? 0,
            'event_type'  => '心跳',
            'content'     => json_encode($data, JSON_UNESCAPED_UNICODE),
            'create_time' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 记录设备事件（通用）
     * @param string $eventType 事件类型
     * @param array  $payload   事件内容
     */
    protected function recordEvent(string $eventType, array $payload): void
    {
        Db::name('device_event')->insert([
            'device_id'   => $this->config['id'] ?? 0,
            'community_id'=> $this->config['community_id'] ?? 0,
            'event_type'  => $eventType,
            'content'     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'create_time' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 更新设备在线状态
     */
    protected function setOnline(bool $online): void
    {
        Db::name('device')
            ->where('id', $this->config['id'] ?? 0)
            ->update([
                'status'      => $online ? 1 : 0,
                'last_heartbeat' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s'),
            ]);
    }

    /**
     * HTTP GET 请求封装
     */
    protected function httpGet(string $url, array $headers = []): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $start = microtime(true);
        $body = curl_exec($ch);
        $latency = round((microtime(true) - $start) * 1000);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errNo = curl_errno($ch);
        curl_close($ch);

        if ($errNo) return ['success' => false, 'message' => "连接失败: " . curl_strerror($errNo), 'latency_ms' => $latency];

        return ['success' => ($httpCode >= 200 && $httpCode < 300), 'message' => "HTTP {$httpCode}", 'http_code' => $httpCode, 'body' => $body, 'latency_ms' => $latency];
    }

    /**
     * HTTP POST 请求封装
     */
    protected function httpPost(string $url, $body = '', array $headers = [], int $timeout = 10): array
    {
        if (is_array($body)) $body = json_encode($body, JSON_UNESCAPED_UNICODE);

        $defaultHeaders = ['Content-Type: application/json; charset=utf-8', 'Content-Length: ' . strlen($body)];
        $headers = array_merge($defaultHeaders, $headers);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $start = microtime(true);
        $resp = curl_exec($ch);
        $latency = round((microtime(true) - $start) * 1000);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errNo = curl_errno($ch);
        curl_close($ch);

        if ($errNo) return ['success' => false, 'message' => "连接失败: " . curl_strerror($errNo), 'latency_ms' => $latency];

        $result = is_string($resp) ? json_decode($resp, true) : null;
        return ['success' => ($httpCode >= 200 && $httpCode < 300), 'message' => "HTTP {$httpCode}", 'http_code' => $httpCode, 'body' => $result ?: $resp, 'raw_body' => $resp, 'latency_ms' => $latency];
    }

    /**
     * TCP Socket 连接检测
     */
    protected function tcpPing(string $host, int $port, int $timeoutMs = 2000): array
    {
        $start = microtime(true);
        $fp = @fsockopen($host, $port, $errno, $errstr, $timeoutMs / 1000);
        $latency = round((microtime(true) - $start) * 1000);

        if (!$fp) {
            return ['success' => false, 'message' => $errstr ?: "端口 {$port} 不通", 'latency_ms' => $latency];
        }
        fclose($fp);
        return ['success' => true, 'message' => "连接成功", 'latency_ms' => $latency];
    }

    /**
     * 解析 config JSON 字段中的扩展配置
     */
    protected function getConfigValue(string $key, $default = null)
    {
        $cfg = [];
        if (!empty($this->config['config'])) {
            $cfg = is_string($this->config['config']) ? json_decode($this->config['config'], true) : (array)$this->config['config'];
        }
        return isset($cfg[$key]) ? $cfg[$key] : $default;
    }
}
