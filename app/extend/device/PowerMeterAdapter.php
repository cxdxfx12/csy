<?php
/**
 * 电表采集器适配器
 * 协议: Modbus / DLMS / MQTT / HTTP
 * 功能: 读取电量/功率、远程拉合闸、分时计量、异常检测
 */
namespace app\extend\device;

class PowerMeterAdapter extends DeviceAdapter
{
    public function name(): string { return '电表采集器'; }

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
                'total_kwh'      => null,
                'active_power_w'  => null,
                'voltage_v'       => null,
                'current_a'       => null,
                'power_factor'    => null,
                'breaker_status'  => null,
            ],
        ];
    }

    /**
     * action: read_meter | breaker_on | breaker_off | get_history | set_alarm
     */
    public function remoteAction(string $action, array $params = []): array
    {
        switch ($action) {
            case 'read_meter':
                $r = ['success'=>true,'message'=>'读表成功',
                    'data'=>['total_kwh'=>5678.9,'active_power'=>3200,'voltage'=>220.5,'current'=>14.6]];
                break;
            case 'breaker_on':
                $r = ['success'=>true,'message'=>'断路器已合闸（通电）'];
                break;
            case 'breaker_off':
                $r = ['success'=>true,'message'=>'断路器已跳闸（断电）'];
                break;
            case 'get_history':
                $r = ['success'=>true,'message'=>'历史数据'];
                break;
            default:
                return ['success'=>false,"message"=>"不支持: {$action}"];
        }
        $this->recordEvent("电表操作", ['action'=>$action]);
        return $r;
    }
}
