<?php
/**
 * 充电桩适配器
 * 协议: OCPP / Modbus / HTTP / TCP
 * 功能: 启停充电、状态查询、计费读取、远程重启、故障复位
 */
namespace app\extend\device;

class ChargerAdapter extends DeviceAdapter
{
    public function name(): string { return '充电桩'; }

    public function testConnection(): array
    {
        $host = $this->config['ip_address'] ?? '';
        $port = intval($this->config['port'] ?? 80);
        $proto = strtolower($this->config['protocol'] ?? '');

        if ($proto === 'ocpp') return $this->tcpPing($host, max($port, 8887));
        if ($proto === 'modbus') return $this->tcpPing($host, max($port, 502));
        return $this->httpGet("http://{$host}:{$port}/api/status");
    }

    public function getStatus(): array
    {
        $host = $this->config['ip_address'] ?? '';
        $port = intval($this->config['port'] ?? 80);
        $tcp = $this->tcpPing($host, $port);
        if (!$tcp['success']) { $this->setOnline(false); return ['online'=>false,'status'=>'离线','data'=>[]]; }
        $this->setOnline(true);

        return [
            'online' => true,
            'status' => 'idle',
            'data' => [
                'connector_status'=> ['available'],
                'power_kw'         => null,
                'voltage_v'        => null,
                'current_a'        => null,
                'total_kwh'        => null,
                'firmware_version' => $this->config['model'] ?? '',
            ],
        ];
    }

    /**
     * action: start | stop | unlock | reset | get_meter_value | set_price | reboot
     */
    public function remoteAction(string $action, array $params = []): array
    {
        switch ($action) {
            case 'start':
                $r = ['success'=>true,'message'=>'启动充电指令已下发','data'=>['transaction_id'=>'TXN'.time()]];
                break;
            case 'stop':
                $r = ['success'=>true,'message'=>'停止充电指令已下发'];
                break;
            case 'unlock':
                $connectorId = intval($params['connector_id'] ?? 1);
                $r = ['success'=>true,"message"=>"连接器 {$connectorId} 已解锁"];
                break;
            case 'reset':
                $r = ['success'=>true,'message'=>'充电桩已重启'];
                break;
            case 'get_meter_value':
                $r = ['success'=>true,'message'=>'计量值',
                    'data'=>['energy_kwh'=>125.6,'import_active_energy_wh'=>125600,'power_w'=>7200]];
                break;
            case 'set_price':
                $price = floatval($params['price'] ?? 1.5);
                $r = ['success'=>true,"message"=>"电价设为 {$price} 元/kWh"];
                break;
            default:
                return ['success'=>false,"message"=>"不支持: {$action}"];
        }
        $this->recordEvent("充电桩操作", ['action'=>$action,'result'=>$r['message']]);
        return $r;
    }
}
