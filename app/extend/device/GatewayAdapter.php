<?php
/**
 * 智能网关适配器
 * 协议: MQTT / HTTP / TCP
 * 功能: 心跳上报、数据转发、设备管理、固件升级
 */
namespace app\extend\device;

class GatewayAdapter extends DeviceAdapter
{
    public function name(): string { return '智能网关'; }

    /**
     * 通过 HTTP API 测试网关连接（MQTT 则用 TCP ping）
     */
    public function testConnection(): array
    {
        $host = $this->config['ip_address'] ?? '';
        $port = intval($this->config['port'] ?? 8080);
        $proto = strtolower($this->config['protocol'] ?? '');

        if (in_array($proto, ['mqtt', 'coap', 'tcp'])) {
            return $this->tcpPing($host, max($port, 1883));
        }
        // HTTP/HTTPS
        $url = ($proto === 'https' ? 'https' : 'http') . "://{$host}:{$port}/api/ping";
        $result = $this->httpGet($url);
        if (!$result['success']) {
            // fallback to TCP ping
            $tcp = $this->tcpPing($host, $port);
            if ($tcp['success']) {
                return ['success' => true, 'message' => "TCP连通(端口{$port}), HTTP接口异常", 'latency_ms' => $tcp['latency_ms']];
            }
        }
        return $result;
    }

    /**
     * 获取网关状态（在线设备数、CPU、内存、运行时间）
     */
    public function getStatus(): array
    {
        $host = $this->config['ip_address'] ?? '';
        $port = intval($this->config['port'] ?? 8080);

        // TCP 检测在线
        $tcp = $this->tcpPing($host, $port);
        if (!$tcp['success']) {
            $this->setOnline(false);
            return ['online' => false, 'status' => '离线', 'data' => []];
        }

        $this->setOnline(true);

        // 尝试获取详细状态
        $authKey = $this->config['auth_key'] ?? '';
        $url = "http://{$host}:{$port}/api/status";
        $resp = $this->httpGet($url, ["Authorization: Bearer {$authKey}"]);

        $data = [];
        if (!empty($resp['body'])) {
            $data = is_array($resp['body']) ? $resp['body'] : (json_decode($resp['body'], true) ?: []);
        }

        return [
            'online' => true,
            'status' => 'running',
            'data'   => array_merge([
                'uptime'      => null,
                'cpu_usage'   => null,
                'mem_usage'   => null,
                'device_count'=> null,
                'fw_version'  => $this->config['model'] ?? '',
            ], is_array($data) ? $data : []),
        ];
    }

    /**
     * 远程操作：重启网关 / 同步配置 / 清除缓存
     * action: restart | sync_config | clear_cache | firmware_upgrade
     */
    public function remoteAction(string $action, array $params = []): array
    {
        $host = $this->config['ip_address'] ?? '';
        $port = intval($this->config['port'] ?? 8080);
        $authKey = $this->config['auth_key'] ?? '';

        $actions = [
            'restart'          => ['path' => '/api/system/restart', 'desc' => '重启'],
            'sync_config'      => ['path' => '/api/config/sync',   'desc' => '同步配置'],
            'clear_cache'      => ['path' => '/api/system/cache',  'desc' => '清除缓存'],
            'firmware_upgrade' => ['path' => '/api/firmware/update', 'desc' => '固件升级'],
        ];

        if (!isset($actions[$action])) {
            return ['success' => false, 'message' => "不支持的操作: {$action}"];
        }

        $info = $actions[$action];
        $method = ($action === 'firmware_upgrade') ? $params['url'] ?? '' : '';
        $payload = $action === 'sync_config' ? ['timestamp' => time()] : ($action === 'firmware_upgrade' && $method ? ['url' => $method] : []);

        $url = "http://{$host}:{$port}{$info['path']}";
        $resp = empty($payload)
            ? $this->httpPost($url, '{}', ["Authorization: Bearer {$authKey}"])
            : $this->httpPost($url, $payload, ["Authorization: Bearer {$authKey}"]);

        $this->recordEvent("远程操作", ['action' => $action, 'desc' => $info['desc'], 'result' => $resp['message']]);

        return $resp;
    }
}
