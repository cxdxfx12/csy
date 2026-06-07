<?php
/**
 * 门禁控制器适配器（硬件设备层）
 * 协议: HTTP / TCP / Modbus / WebSocket
 * 功能: 远程开门、状态查询、白名单同步、门锁控制
 * 
 * 注意: 当 ref_type='access' 时，会优先调用 AccessFactory 的品牌适配器；
 *       此适配器作为通用门禁控制器实现，适用于未配置具体品牌的场景。
 */
namespace app\extend\device;

class AccessCtrlAdapter extends DeviceAdapter
{
    public function name(): string { return '门禁控制器'; }

    public function testConnection(): array
    {
        $host = $this->config['ip_address'] ?? '';
        $port = intval($this->config['port'] ?? 80);
        $proto = strtolower($this->config['protocol'] ?? '');

        if ($proto === 'modbus') {
            return $this->tcpPing($host, max($port, 502));
        }
        // HTTP/WS
        $url = "http://{$host}:{$port}/api/health";
        return $this->httpGet($url, ["Authorization: Bearer " . ($this->config['auth_key'] ?? '')]);
    }

    public function getStatus(): array
    {
        $host = $this->config['ip_address'] ?? '';
        $port = intval($this->config['port'] ?? 80);

        $tcp = $this->tcpPing($host, $port);
        if (!$tcp['success']) {
            $this->setOnline(false);
            return ['online' => false, 'status' => '离线', 'data' => []];
        }

        $this->setOnline(true);

        $url = "http://{$host}:{$port}/api/status";
        $resp = $this->httpGet($url, ["Authorization: Bearer " . ($this->config['auth_key'] ?? '')]);

        $data = is_array($resp['body']) ? $resp['body'] : [];
        return [
            'online' => true,
            'status' => 'running',
            'data'   => [
                'door_status' => $data['door_status'] ?? null,
                'card_count'  => $data['whitelist_count'] ?? null,
                'temperature' => $data['temperature'] ?? null,
                'battery'     => $data['battery'] ?? null,
                'firmware'    => $data['version'] ?? ($this->config['model'] ?? ''),
            ],
        ];
    }

    /**
     * action: open | lock | unlock | sync_whitelist | reboot | get_log
     */
    public function remoteAction(string $action, array $params = []): array
    {
        $host = $this->config['ip_address'] ?? '';
        $port = intval($this->config['port'] ?? 80);
        $authKey = $this->config['auth_key'] ?? '';

        $actionMap = [
            'open'           => ['method'=>'POST', 'path'=>'/api/door/open',   'desc'=>'远程开门'],
            'lock'           => ['method'=>'POST', 'path'=>'/api/door/lock',   'desc'=>'锁定'],
            'unlock'         => ['method'=>'POST', 'path'=>'/api/door/unlock', 'desc'=>'解锁'],
            'sync_whitelist' => ['method'=>'POST', 'path'=>'/api/sync',        'desc'=>'同步白名单'],
            'reboot'         => ['method'=>'POST', 'path'=>'/api/system/reboot','desc'=>'重启设备'],
            'get_log'        => ['method'=>'GET',  'path'=>'/api/log/recent',  'desc'=>'获取日志'],
        ];

        if (!isset($actionMap[$action])) {
            return ['success'=>false, 'message'=>"不支持的操作: {$action}"];
        }
        $info = $actionMap[$action];
        $url = "http://{$host}:{$port}{$info['path']}";

        if ($action === 'sync_whitelist') {
            // 获取该小区的白名单卡号列表
            $cards = Db::name('access_card')
                ->where('community_id', $this->config['community_id'] ?? 0)
                ->where('status', 1)
                ->whereNull('delete_time')
                ->column('card_no');
            $payload = ['whitelist' => array_values($cards), 'timestamp' => time()];
        } elseif ($action === 'open') {
            $payload = ['duration' => intval($params['duration'] ?? 5)];
        } else {
            $payload = [];
        }

        $headers = ["Authorization: Bearer {$authKey}"];
        $resp = $info['method'] === 'POST'
            ? $this->httpPost($url, $payload ?: '{}', $headers)
            : $this->httpGet($url, $headers);

        $this->recordEvent("门禁操作", ['action'=>$action,'desc'=>$info['desc'],'result'=>$resp['message']]);
        return $resp;
    }
}
