<?php
/**
 * 百胜(Baisheng)道闸适配器
 * 接口协议: HTTP POST JSON
 * 支持型号: BS-IS01, BS-IS02, BS-IS03 系列
 */
namespace app\extend\gate;

class BaishengAdapter extends GateAdapter
{
    protected $brand = 'baisheng';
    
    public function name(): string { return '百胜'; }
    
    public function onPlateRecognized(array $data): array
    {
        $this->recordHeartbeat();
        
        $plateNumber = $data['plate_number'] ?? $data['plate'] ?? '';
        $direction = $data['direction'] ?? $data['dir'] ?? ($data['type'] === 'enter' ? 'in' : 'out');
        $communityId = $this->config['community_id'] ?? 0;
        
        $eventData = [
            'plate_number'   => $plateNumber,
            'direction'      => $direction,
            'image_url'      => $data['image_url'] ?? $data['img_url'] ?? '',
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
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/api/gate/open';
        
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $apiUrl,
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'X-Auth-Token: ' . ($this->config['api_token'] ?? ''),
                ],
                CURLOPT_POSTFIELDS     => json_encode([
                    'device_sn' => $this->config['device_sn'] ?? '',
                    'direction' => $direction,
                    'timestamp' => time(),
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
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/api/device/status';
        
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $apiUrl . '?device_sn=' . urlencode($this->config['device_sn'] ?? ''),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_HTTPHEADER     => [
                    'X-Auth-Token: ' . ($this->config['api_token'] ?? ''),
                ],
            ]);
            $resp = curl_exec($ch);
            curl_close($ch);
            $data = json_decode($resp, true);
            return [
                'online'         => ($data['code'] ?? 0) === 0 ? ($data['data']['online'] ?? false) : false,
                'status'         => ($data['data']['gate_status'] ?? 0) === 0 ? 'closed' : 'open',
                'last_heartbeat' => $data['data']['last_time'] ?? '',
            ];
        } catch (\Throwable $e) {
            return ['online' => false, 'status' => 'unknown', 'last_heartbeat' => ''];
        }
    }
    
    public function syncWhitelist(array $plates): bool
    {
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/api/whitelist/sync';
        
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $apiUrl,
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'X-Auth-Token: ' . ($this->config['api_token'] ?? ''),
                ],
                CURLOPT_POSTFIELDS     => json_encode([
                    'device_sn' => $this->config['device_sn'] ?? '',
                    'plates'    => $plates,
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
