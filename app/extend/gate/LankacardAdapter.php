<?php
/**
 * 蓝卡(Lankacard)道闸适配器
 * 接口协议: HTTP POST JSON
 * 支持型号: LC-IS01, LC-IS02, LC-IS03 系列
 */
namespace app\extend\gate;

class LankacardAdapter extends GateAdapter
{
    protected $brand = 'lankacard';
    
    public function name(): string { return '蓝卡'; }
    
    public function onPlateRecognized(array $data): array
    {
        $this->recordHeartbeat();
        
        $plateNumber = $data['plate_number'] ?? $data['carNo'] ?? $data['plate'] ?? '';
        $direction = $data['direction'] ?? $data['dir'] ?? ($data['type'] === 'enter' ? 'in' : 'out');
        $communityId = $this->config['community_id'] ?? 0;
        
        $eventData = [
            'plate_number'   => $plateNumber,
            'direction'      => $direction,
            'image_url'      => $data['image_url'] ?? $data['pic'] ?? '',
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
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/openGate';
        
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $apiUrl,
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'token: ' . ($this->config['api_token'] ?? ''),
                ],
                CURLOPT_POSTFIELDS     => json_encode([
                    'deviceId'  => $this->config['device_sn'] ?? '',
                    'direction' => $direction === 'in' ? 0 : 1,
                    'nonce'     => bin2hex(random_bytes(8)),
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
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/deviceStatus';
        
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $apiUrl . '?deviceId=' . urlencode($this->config['device_sn'] ?? ''),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_HTTPHEADER     => ['token: ' . ($this->config['api_token'] ?? '')],
            ]);
            $resp = curl_exec($ch);
            curl_close($ch);
            $data = json_decode($resp, true);
            return [
                'online'         => ($data['result'] ?? false) ? ($data['data']['isOnline'] ?? false) : false,
                'status'         => ($data['data']['barrierState'] ?? 0) === 0 ? 'closed' : 'open',
                'last_heartbeat' => $data['data']['lastReportTime'] ?? '',
            ];
        } catch (\Throwable $e) {
            return ['online' => false, 'status' => 'unknown', 'last_heartbeat' => ''];
        }
    }
    
    public function syncWhitelist(array $plates): bool
    {
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/whitelist/sync';
        
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $apiUrl,
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'token: ' . ($this->config['api_token'] ?? ''),
                ],
                CURLOPT_POSTFIELDS     => json_encode([
                    'deviceId' => $this->config['device_sn'] ?? '',
                    'carList'  => array_map(function($p){ return ['plate'=>$p,'type'=>1]; }, $plates),
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
