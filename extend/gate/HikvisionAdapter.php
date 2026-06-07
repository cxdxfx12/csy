<?php
/**
 * 海康威视(Hikvision)道闸适配器
 * 接口协议: ISAPI HTTP/HTTPS
 * 支持型号: DS-TMG系列, iDS-TCD系列
 */
namespace gate;

class HikvisionAdapter extends GateAdapter
{
    protected $brand = 'hikvision';
    
    public function name(): string { return '海康威视'; }
    
    public function onPlateRecognized(array $data): array
    {
        $this->recordHeartbeat();
        
        $plateNumber = $data['plate_number'] ?? '';
        $direction = $data['direction'] ?? 'in';
        $communityId = $this->config['community_id'] ?? 0;
        
        $eventData = [
            'plate_number' => $plateNumber,
            'direction' => $direction,
            'image_url' => $data['image_url'] ?? '',
            'recognize_time' => $data['timestamp'] ?? date('Y-m-d H:i:s'),
            'action' => 'unknown',
            'space_id' => 0,
        ];
        
        // 海康设备可能返回多车牌识别结果，取置信度最高的
        if (isset($data['candidates']) && is_array($data['candidates'])) {
            usort($data['candidates'], fn($a,$b) => ($b['confidence']??0) <=> ($a['confidence']??0));
            $plateNumber = $data['candidates'][0]['plate'] ?? $plateNumber;
            $eventData['plate_number'] = $plateNumber;
        }
        
        if ($direction === 'in') {
            $vehicle = $this->matchVehicle($plateNumber, $communityId);
            if ($vehicle) {
                $eventData['action'] = 'pass';
                $eventData['space_id'] = $vehicle['parking_space_id'] ?? 0;
                $this->recordEvent($eventData);
                $this->createParkingRecord($eventData);
                return [
                    'action' => 'open',
                    'reason' => '已登记车辆',
                    'vehicle_info' => [
                        'owner_id' => $vehicle['owner_id'],
                        'brand'    => $vehicle['brand'],
                        'type'     => 'registered',
                    ],
                ];
            }
            $eventData['action'] = 'pass';
            $this->recordEvent($eventData);
            $this->createParkingRecord($eventData);
            return [
                'action' => 'open',
                'reason' => '访客车辆',
                'vehicle_info' => ['type' => 'visitor'],
            ];
        }
        
        $eventData['action'] = 'pass';
        $this->recordEvent($eventData);
        $this->createParkingRecord($eventData);
        return ['action' => 'open', 'reason' => '放行', 'vehicle_info' => ['type' => 'exit']];
    }
    
    public function openGate(string $direction = 'in'): bool
    {
        // 海康 ISAPI 协议
        $channel = ($direction === 'in') ? 1 : 2;
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . "/ISAPI/System/IO/Outputs/{$channel}/trigger";
        
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $apiUrl,
                CURLOPT_CUSTOMREQUEST  => 'PUT',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/xml'],
                CURLOPT_POSTFIELDS     => '<IOOutputParams><trigger>high</trigger></IOOutputParams>',
                CURLOPT_USERPWD        => ($this->config['api_username'] ?? 'admin') . ':' . ($this->config['api_token'] ?? 'admin123'),
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
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/ISAPI/System/deviceInfo';
        
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/xml'],
                CURLOPT_USERPWD        => ($this->config['api_username'] ?? 'admin') . ':' . ($this->config['api_token'] ?? 'admin123'),
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return [
                'online'         => $code === 200,
                'status'         => 'closed',
                'last_heartbeat' => date('Y-m-d H:i:s'),
            ];
        } catch (\Throwable $e) {
            return ['online' => false, 'status' => 'unknown', 'last_heartbeat' => ''];
        }
    }
    
    public function syncWhitelist(array $plates): bool { return true; }
}
