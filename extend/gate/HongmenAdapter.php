<?php
/**
 * 红门(Hongmen)道闸适配器
 * 接口协议: HTTP POST JSON
 */
namespace gate;

class HongmenAdapter extends GateAdapter
{
    protected $brand = 'hongmen';
    
    public function name(): string { return '红门'; }
    
    public function onPlateRecognized(array $data): array
    {
        $this->recordHeartbeat();
        
        $plateNumber = $data['plate_number'] ?? $data['carNo'] ?? '';
        $direction = $data['direction'] ?? ($data['ioFlag'] === '1' ? 'in' : 'out');
        $communityId = $this->config['community_id'] ?? 0;
        
        $eventData = [
            'plate_number' => $plateNumber,
            'direction' => $direction,
            'image_url' => $data['image_url'] ?? $data['image'] ?? '',
            'recognize_time' => $data['timestamp'] ?? date('Y-m-d H:i:s'),
            'action' => 'unknown',
            'space_id' => 0,
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
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/gate/openGate';
        
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded',
                ],
                CURLOPT_POSTFIELDS => http_build_query([
                    'device' => $this->config['device_sn'] ?? '',
                    'channel' => ($direction === 'in') ? 1 : 2,
                    'v' => time(),
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
