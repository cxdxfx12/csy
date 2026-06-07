<?php
/**
 * 大华(Dahua)道闸适配器
 * 接口协议: HTTP REST API
 * 支持型号: DHI-ITC系列, DHI-IPMEC系列
 */
namespace gate;

class DahuaAdapter extends GateAdapter
{
    protected $brand = 'dahua';
    
    public function name(): string { return '大华'; }
    
    public function onPlateRecognized(array $data): array
    {
        $this->recordHeartbeat();
        
        $plateNumber = $data['plate_number'] ?? $data['PlateNumber'] ?? '';
        $direction = $data['direction'] ?? ($data['Direction'] ?? 'in');
        $communityId = $this->config['community_id'] ?? 0;
        
        $eventData = [
            'plate_number' => $plateNumber,
            'direction' => $direction,
            'image_url' => $data['image_url'] ?? ($data['ImageUrl'] ?? ''),
            'recognize_time' => $data['timestamp'] ?? ($data['Time'] ?? date('Y-m-d H:i:s')),
            'action' => 'unknown',
            'space_id' => 0,
        ];
        
        if ($direction === 'in' || $direction === 'Enter') {
            $vehicle = $this->matchVehicle($plateNumber, $communityId);
            $eventData['direction'] = 'in';
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
        
        $eventData['direction'] = 'out';
        $eventData['action'] = 'pass';
        $this->recordEvent($eventData);
        $this->createParkingRecord($eventData);
        return ['action'=>'open','reason'=>'放行','vehicle_info'=>['type'=>'exit']];
    }
    
    public function openGate(string $direction = 'in'): bool
    {
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/RPC2';
        $token = $this->config['api_token'] ?? '';
        
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $apiUrl,
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Digest ' . $token,
                ],
                CURLOPT_POSTFIELDS     => json_encode([
                    'method'  => 'gateControl.open',
                    'params'  => ['channel' => ($direction === 'in') ? 0 : 1],
                    'id'      => time(),
                    'session' => $token,
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
        return ['online' => false, 'status' => 'unknown', 'last_heartbeat' => ''];
    }
    
    public function syncWhitelist(array $plates): bool { return true; }
}
