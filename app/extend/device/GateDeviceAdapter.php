<?php
/**
 * 道闸设备适配器（硬件设备层）
 * 协议: HTTP / TCP / Modbus / SDK
 * 功能: 远程开闸、状态查询、车牌识别联动
 * 
 * 当 ref_type='gate' 时，优先调用 GateFactory 品牌适配器
 */
namespace app\extend\device;

class GateDeviceAdapter extends DeviceAdapter
{
    public function name(): string { return '道闸设备'; }

    public function testConnection(): array
    {
        $host = $this->config['ip_address'] ?? '';
        $port = intval($this->config['port'] ?? 80);
        $proto = strtolower($this->config['protocol'] ?? '');

        if (in_array($proto, ['modbus', 'tcp', 'sdk'])) {
            return $this->tcpPing($host, max($port, 502));
        }
        // HTTP API
        $url = "http://{$host}:{$port}/api/health";
        return $this->httpGet($url, ["Authorization: Bearer " . ($this->config['auth_key'] ?? '')]);
    }

    public function getStatus(): array
    {
        $host = $this->config['ip_address'] ?? '';
        $port = intval($this->config['port'] ?? 80);
        $tcp = $this->tcpPing($host, $port);
        if (!$tcp['success']) { $this->setOnline(false); return ['online'=>false,'status'=>'离线','data'=>[]]; }
        $this->setOnline(true);

        $url = "http://{$host}:{$port}/api/status";
        $resp = $this->httpGet($url, ["Authorization: Bearer " . ($this->config['auth_key'] ?? '')]);
        $data = is_array($resp['body']) ? $resp['body'] : [];

        return [
            'online' => true,
            'status' => $data['gate_status'] ?? 'closed',
            'data' => [
                'gate_position' => $data['gate_position'] ?? null,
                'motor_temp'    => $data['temperature'] ?? null,
                'loop_count'    => $data['loop_count'] ?? null,
            ],
        ];
    }

    /**
     * action: open_in | open_out | stop | reset | sync_whitelist | get_log
     */
    public function remoteAction(string $action, array $params = []): array
    {
        $host = $this->config['ip_address'] ?? '';
        $port = intval($this->config['port'] ?? 80);
        $authKey = $this->config['auth_key'] ?? '';

        switch ($action) {
            case 'open_in':
                $r = $this->httpPost("http://{$host}:{$port}/api/gate/open", ['direction'=>'in'], ["Authorization: Bearer {$authKey}"]);
                break;
            case 'open_out':
                $r = $this->httpPost("http://{$host}:{$port}/api/gate/open", ['direction'=>'out'], ["Authorization: Bearer {$authKey}"]);
                break;
            case 'stop':
                $r = $this->httpPost("http://{$host}:{$port}/api/gate/stop", [], ["Authorization: Bearer {$authKey}"]);
                break;
            case 'reset':
                $r = $this->httpPost("http://{$host}:{$port}/api/gate/reset", [], ["Authorization: Bearer {$authKey}"]);
                break;
            case 'sync_whitelist':
                $plates = Db::name('vehicle')
                    ->where('community_id', $this->config['community_id'] ?? 0)
                    ->whereNull('delete_time')->column('plate_number');
                $r = $this->httpPost("http://{$host}:{$port}/api/whitelist/sync", ['plates'=>$plates], ["Authorization: Bearer {$authKey}"]);
                break;
            default:
                return ['success'=>false,"message"=>"不支持: {$action}"];
        }

        $this->recordEvent("道闸操作", ['action'=>$action,'result'=>$r['message']]);
        return $r;
    }
}
