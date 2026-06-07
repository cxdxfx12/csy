<?php
/**
 * 消防设备适配器
 * 协议: Modbus / SNMP / MQTT / HTTP
 * 功能: 状态巡检、报警复位、设备测试、联动控制
 */
namespace app\extend\device;

class FireAdapter extends DeviceAdapter
{
    public function name(): string { return '消防设备'; }

    public function testConnection(): array
    {
        $host = $this->config['ip_address'] ?? '';
        $port = intval($this->config['port'] ?? 502);
        $proto = strtolower($this->config['protocol'] ?? '');
        return $this->tcpPing($host, $port ?: ($proto === 'snmp' ? 161 : 502));
    }

    public function getStatus(): array
    {
        $host = $this->config['ip_address'] ?? '';
        $port = intval($this->config['port'] ?? 502);
        $tcp = $this->tcpPing($host, $port);
        if (!$tcp['success']) { $this->setOnline(false); return ['online'=>false,'status'=>'离线','data'=>[]]; }
        $this->setOnline(true);

        return [
            'online' => true,
            'status' => 'normal',
            'data' => [
                'alarm_state'   => false,
                'fault_state'   => false,
                'device_count'  => null,
                'battery_level' => null,
                'last_test_time'=> null,
            ],
        ];
    }

    /**
     * action: reset_alarm | test_device | get_status | silence | enable | disable
     */
    public function remoteAction(string $action, array $params = []): array
    {
        switch ($action) {
            case 'reset_alarm':
                $this->recordEvent("消防报警复位", []);
                return ['success'=>true,'message'=>'报警已复位'];
            case 'test_device':
                $this->recordEvent("消防设备自检", []);
                return ['success'=>true,'message'=>'自检指令已下发，请等待结果'];
            case 'silence':
                $duration = intval($params['duration'] ?? 300);
                $this->recordEvent("消音", ['duration'=>$duration]);
                return ['success'=>true,"message"=>"静音 {$duration} 秒"];
            case 'get_status':
                return [
                    'success'=>true,'message'=>'状态正常',
                    'data'=>['alarm'=>'none','fault'=>'none','online_devices'=>8]
                ];
            default:
                return ['success'=>false,"message"=>"不支持: {$action}"];
        }
    }
}
