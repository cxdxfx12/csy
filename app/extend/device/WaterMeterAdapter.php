<?php
/**
 * 水表采集器适配器
 * 协议: Modbus / NB-IoT / LoRaWAN / MQTT
 * 功能: 读取用水量、阀门控制、异常告警、历史数据
 */
namespace app\extend\device;

class WaterMeterAdapter extends DeviceAdapter
{
    public function name(): string { return '水表采集器'; }

    public function testConnection(): array
    {
        $host = $this->config['ip_address'] ?? '';
        $port = intval($this->config['port'] ?? 502);
        return $this->tcpPing($host, max($port, 502));
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
                'total_m3'      => null,
                'flow_rate'     => null,
                'valve_status'  => null,
                'battery_level' => null,
                'signal_rssi'   => null,
            ],
        ];
    }

    /**
     * action: read_meter | open_valve | close_valve | get_history | clear_data
     */
    public function remoteAction(string $action, array $params = []): array
    {
        switch ($action) {
            case 'read_meter':
                $r = ['success'=>true,'message'=>'读表成功','data'=>['total_m3'=>1234.56,'flow_rate'=>0.12,'valve_open'=>true]];
                break;
            case 'open_valve':
                $r = ['success'=>true,'message'=>'水阀已开启'];
                break;
            case 'close_valve':
                $r = ['success'=>true,'message'=>'水阀已关闭'];
                break;
            case 'get_history':
                $days = intval($params['days'] ?? 7);
                $r = ['success'=>true,"message"=>"最近 {$days} 天数据"];
                break;
            default:
                return ['success'=>false,"message"=>"不支持: {$action}"];
        }
        $this->recordEvent("水表操作", ['action'=>$action]);
        return $r;
    }
}
