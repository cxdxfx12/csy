<?php
/**
 * 道尔智控(Daoer)道闸适配器
 * 接口协议: HTTP POST JSON
 * 支持型号: DE-IS01, DE-IS02, DE-IS03 系列
 */
namespace app\extend\gate;

class DaoerAdapter extends GateAdapter
{
    protected $brand = 'daoer';
    
    public function name(): string { return '道尔智控'; }
    
    public function onPlateRecognized(array $data): array
    {
        $this->recordHeartbeat();
        
        $plateNumber = $data['plate_number'] ?? $data['plateNo'] ?? $data['plate'] ?? '';
        $direction = $data['direction'] ?? $data['dir'] ?? ($data['type'] === 'enter' ? 'in' : 'out');
        $communityId = $this->config['community_id'] ?? 0;
        
        $eventData = [
            'plate_number'   => $plateNumber,
            'direction'      => $direction,
            'image_url'      => $data['image_url'] ?? $data['picUrl'] ?? '',
            'recognize_time' => $data['timestamp'] ?? $data['time'] ?? date('Y-m-d H:i:s'),
            'action'         => 'unknown',
            'space_id'       => 0,
        ];
        
        if ($direction === 'in') {
            $vehicle = $this->matchVehicle($plateNumber, $communityId);
            if ($vehicle) {
                $eventData['action'] = 'pass';
                $eventData['space_id'] = $vehicle['parking_space_id'] ?? 0;
                $this->recordEvent($eventData);
                $this->createParkingRecord($eventData);
                return ['action'=>'open','reason'=>'已登记车辆','vehicle_info'=>['owner_id'=>$vehicle['owner_id'],'type'=>'registered']];
            }
            $eventData['action'] = 'pass';
            $this->recordEvent($eventData);
            $this->createParkingRecord($eventData);
            return ['action'=>'open','reason'=>'访客车辆','vehicle_info'=>['type'=>'visitor']];
        }
        
        $eventData['action'] = 'pass';
        $this->recordEvent($eventData);
        $this->createParkingRecord($eventData);
        return ['action'=>'open','reason'=>'放行','vehicle_info'=>['type'=>'exit']];
    }
    
    public function openGate(string $direction = 'in'): bool
    {
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/cmd/openGate';
        
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $apiUrl,
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'App-Key: ' . ($this->config['api_token'] ?? ''),
                ],
                CURLOPT_POSTFIELDS     => json_encode([
                    'sn'        => $this->config['device_sn'] ?? '',
                    'openType'  => $direction,
                    'timestamp' => time() * 1000,
                ]),
            ]);
            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return $code === 200;
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    public function getStatus(): array
    {
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/cmd/getStatus';
        
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $apiUrl . '?sn=' . urlencode($this->config['device_sn'] ?? ''),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_HTTPHEADER     => ['App-Key: ' . ($this->config['api_token'] ?? '')],
            ]);
            $resp = curl_exec($ch);
            curl_close($ch);
            $data = json_decode($resp, true);
            return [
                'online'         => ($data['success'] ?? false) && ($data['data']['isOnline'] ?? false),
                'status'         => ($data['data']['barrierState'] ?? 0) === 0 ? 'closed' : 'open',
                'last_heartbeat' => $data['data']['lastReport'] ?? '',
            ];
        } catch (\Throwable $e) {
            return ['online' => false, 'status' => 'unknown', 'last_heartbeat' => ''];
        }
    }
    
    public function syncWhitelist(array $plates): bool
    {
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/cmd/syncWhitelist';
        
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $apiUrl,
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'App-Key: ' . ($this->config['api_token'] ?? ''),
                ],
                CURLOPT_POSTFIELDS     => json_encode([
                    'sn'     => $this->config['device_sn'] ?? '',
                    'plates' => $plates,
                ]),
            ]);
            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return $code === 200;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
