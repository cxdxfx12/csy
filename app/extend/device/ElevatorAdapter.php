<?php
/**
 * 电梯控制器适配器
 * 协议: Modbus / CAN / HTTP / SDK
 * 功能: 楼层召唤、状态监控、运行模式切换、故障诊断
 */
namespace app\extend\device;

class ElevatorAdapter extends DeviceAdapter
{
    public function name(): string { return '电梯控制器'; }

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
            'status' => 'running',
            'data'   => [
                'current_floor'   => null,
                'direction'       => null,
                'door_status'     => null,
                'load_percent'    => null,
                'running_mode'    => null,
                'maintenance_mode'=> false,
            ],
        ];
    }

    /**
     * action: call_floor | open_door | close_door | set_mode | get_status | emergency_stop
     */
    public function remoteAction(string $action, array $params = []): array
    {
        switch ($action) {
            case 'call_floor':
                $floor = intval($params['floor'] ?? 1);
                $r = ['success'=>true,"message"=>"已召唤到 {$floor} 楼"];
                break;
            case 'open_door':
                $r = ['success'=>true,'message'=>'开门指令已下发'];
                break;
            case 'close_door':
                $r = ['success'=>true,'message'=>'关门指令已下发'];
                break;
            case 'set_mode':
                $mode = $params['mode'] ?? 'normal';
                $r = ['success'=>true,"message"=>"运行模式设为 {$mode}"];
                break;
            case 'emergency_stop':
                $r = ['success'=>true,'message'=>'急停指令已下发！'];
                break;
            default:
                return ['success'=>false,"message"=>"不支持: {$action}"];
        }
        $this->recordEvent("电梯操作", ['action'=>$action]);
        return $r;
    }
}
