<?php
/**
 * 捷顺(Jieshun)道闸适配器
 * 接口协议: HTTP POST JSON
 * 支持型号: JS-I, JS-II, JS-III 系列
 */
namespace gate;

class JieshunAdapter extends GateAdapter
{
    protected $brand = 'jieshun';
    
    public function name(): string { return '捷顺'; }
    
    public function onPlateRecognized(array $data): array
    {
        $this->recordHeartbeat();
        
        $plateNumber = $data['plate_number'] ?? '';
        $direction = $data['direction'] ?? 'in';
        $communityId = $this->config['community_id'] ?? 0;
        
        // 记录事件
        $eventData = [
            'plate_number' => $plateNumber,
            'direction' => $direction,
            'image_url' => $data['image_url'] ?? '',
            'recognize_time' => $data['timestamp'] ?? date('Y-m-d H:i:s'),
            'action' => 'unknown',
            'space_id' => 0,
        ];
        
        // 入场
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
                        'owner_id'   => $vehicle['owner_id'],
                        'brand'      => $vehicle['brand'],
                        'space_no'   => '',
                        'type'       => 'registered',
                    ],
                ];
            }
            // 访客车辆：记录入场
            $eventData['action'] = 'pass';
            $this->recordEvent($eventData);
            $this->createParkingRecord($eventData);
            return [
                'action' => 'open',
                'reason' => '访客车辆',
                'vehicle_info' => ['type' => 'visitor'],
            ];
        }
        
        // 出场：记录出场，计算费用
        $eventData['action'] = 'pass';
        $this->recordEvent($eventData);
        $this->createParkingRecord($eventData);
        
        return [
            'action' => 'open',
            'reason' => '放行',
            'vehicle_info' => ['type' => 'exit'],
        ];
    }
    
    public function openGate(string $direction = 'in'): bool
    {
        $apiUrl = ($this->config['api_url'] ?? '') . '/open';
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
                    'Authorization: Bearer ' . $token,
                ],
                CURLOPT_POSTFIELDS     => json_encode([
                    'device_sn'  => $this->config['device_sn'] ?? '',
                    'direction'  => $direction,
                    'timestamp'  => time(),
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
        $apiUrl = ($this->config['api_url'] ?? '') . '/status';
        $token = $this->config['api_token'] ?? '';
        
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $apiUrl . '?device_sn=' . ($this->config['device_sn'] ?? ''),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
            ]);
            $resp = curl_exec($ch);
            curl_close($ch);
            $data = json_decode($resp, true);
            return [
                'online'         => $data['online'] ?? false,
                'status'         => ($data['gate_status'] ?? 0) === 0 ? 'closed' : 'open',
                'last_heartbeat' => $data['last_time'] ?? '',
            ];
        } catch (\Throwable $e) {
            return ['online' => false, 'status' => 'unknown', 'last_heartbeat' => ''];
        }
    }
    
    public function syncWhitelist(array $plates): bool
    {
        $apiUrl = ($this->config['api_url'] ?? '') . '/whitelist/sync';
        $token = $this->config['api_token'] ?? '';
        
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $apiUrl,
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $token,
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
