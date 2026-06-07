<?php
/**
 * 通用设备适配器（兜底）
 * 适用于未识别设备类型或自定义协议
 */
namespace app\extend\device;

class GenericDeviceAdapter extends DeviceAdapter
{
    public function name(): string { return $this->config['device_type'] ?? '通用设备'; }

    public function testConnection(): array
    {
        $host = $this->config['ip_address'] ?? '';
        $port = intval($this->config['port'] ?? 80);
        if (empty($host)) return ['success'=>false,'message'=>'缺少IP地址'];
        return $this->tcpPing($host, max($port, 80));
    }

    public function getStatus(): array
    {
        $tcp = $this->tcpPing(
            $this->config['ip_address'] ?? '',
            intval($this->config['port'] ?? 80)
        );
        if (!$tcp['success']) { $this->setOnline(false); return ['online'=>false,'status'=>'离线','data'=>[]]; }
        $this->setOnline(true);
        return ['online'=>true,'status'=>'unknown','data'=>['protocol'=>$this->config['protocol']??'']];
    }

    public function remoteAction(string $action, array $params = []): array
    {
        return ['success'=>false,"message"=>"通用设备不支持远程操作: {$action}"];
    }
}
