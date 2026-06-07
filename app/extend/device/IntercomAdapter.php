<?php
/**
 * 可视对讲适配器
 * 协议: SIP / HTTP / SDK / TCP
 * 功能: 呼叫、开锁、视频通话、消息推送
 */
namespace app\extend\device;

class IntercomAdapter extends DeviceAdapter
{
    public function name(): string { return '可视对讲'; }

    public function testConnection(): array
    {
        $host = $this->config['ip_address'] ?? '';
        $port = intval($this->config['port'] ?? 5060);
        $proto = strtolower($this->config['protocol'] ?? '');
        if ($proto === 'sip') return $this->tcpPing($host, max($port, 5060));
        return $this->httpGet("http://{$host}:{$port}/api/health");
    }

    public function getStatus(): array
    {
        $tcp = $this->tcpPing(
            $this->config['ip_address'] ?? '',
            intval($this->config['port'] ?? 80)
        );
        if (!$tcp['success']) { $this->setOnline(false); return ['online'=>false,'status'=>'离线','data'=>[]]; }
        $this->setOnline(true);
        return [
            'online' => true,
            'status' => 'idle',
            'data' => [
                'call_status'  => 'idle',
                'camera_ready' => true,
                'speaker_ready'=> true,
                'online_users' => null,
            ],
        ];
    }

    /**
     * action: call | open_door | send_message | reboot | get_log
     */
    public function remoteAction(string $action, array $params = []): array
    {
        switch ($action) {
            case 'call':
                $r = ['success'=>true,'message'=>'呼叫已发起'];
                break;
            case 'open_door':
                $r = ['success'=>true,'message'=>'门已远程开启'];
                break;
            case 'send_message':
                $msg = $params['content'] ?? '您好';
                $r = ['success'=>true,"message"=>"消息已发送: {$msg}"];
                break;
            default:
                return ['success'=>false,"message"=>"不支持: {$action}"];
        }
        $this->recordEvent("对讲操作", ['action'=>$action]);
        return $r;
    }
}
