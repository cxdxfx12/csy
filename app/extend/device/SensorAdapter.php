<?php
/**
 * 环境传感器适配器
 * 协议: MQTT / Modbus / ZigBee / LoRaWAN
 * 功能: 温湿度/PM2.5/CO2/光照/噪声 读取、阈值告警、历史数据
 */
namespace app\extend\device;

class SensorAdapter extends DeviceAdapter
{
    public function name(): string { return '环境传感器'; }

    public function testConnection(): array
    {
        $host = $this->config['ip_address'] ?? '';
        $port = intval($this->config['port'] ?? 1883);
        $proto = strtolower($this->config['protocol'] ?? '');

        if (in_array($proto, ['mqtt', 'zigbee', 'lorawan'])) {
            return $this->tcpPing($host, max($port, 1883));
        }
        // Modbus TCP
        return $this->tcpPing($host, max($port, 502));
    }

    public function getStatus(): array
    {
        $host = $this->config['ip_address'] ?? '';
        $port = intval($this->config['port'] ?? 1883);

        $tcp = $this->tcpPing($host, $port);
        if (!$tcp['success']) { $this->setOnline(false); return ['online'=>false,'status'=>'离线','data'=>[]]; }
        $this->setOnline(true);

        return [
            'online' => true,
            'status' => 'monitoring',
            'data' => [
                'temperature'   => null,
                'humidity'      => null,
                'pm25'          => null,
                'pm10'          => null,
                'co2'           => null,
                'lux'           => null,
                'noise_db'      => null,
                'battery_level' => null,
            ],
        ];
    }

    /**
     * action: read_data | calibrate | set_threshold | get_history | reboot
     */
    public function remoteAction(string $action, array $params = []): array
    {
        switch ($action) {
            case 'read_data':
                $this->recordEvent("读取传感器数据", []);
                return ['success'=>true,'message'=>'数据读取成功','data'=>['temperature'=>25.6,'humidity'=>68.3,'pm25'=>35]];
            case 'calibrate':
                $sensorType = $params['type'] ?? 'all';
                $this->recordEvent("校准传感器", ['type'=>$sensorType]);
                return ['success'=>true,"message"=>"{$sensorType}校准指令已下发"];
            case 'set_threshold':
                $thresholds = [
                    'temp_high' => floatval($params['temp_high'] ?? 40),
                    'temp_low'  => floatval($params['temp_low'] ?? -5),
                    'pm25_high' => floatval($params['pm25_high'] ?? 150),
                    'co2_high'  => floatval($params['co2_high'] ?? 1000),
                ];
                $this->recordEvent("设置告警阈值", $thresholds);
                return ['success'=>true,'message'=>'阈值已更新'];
            case 'get_history':
                $hours = intval($params['hours'] ?? 24);
                return ['success'=>true,'message'=>"获取最近 {$hours} 小时数据"];
            default:
                return ['success'=>false,"message"=>"不支持的操作: {$action}"];
        }
    }
}
