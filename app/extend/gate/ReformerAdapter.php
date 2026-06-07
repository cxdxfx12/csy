<?php
/**
 * 立方(Reformer)道闸适配器
 * 接口协议: HTTP REST API，MD5签名
 * 支持型号: RF-IS01, RF-IS02, RF-IS03 系列
 */
namespace app\extend\gate;

class ReformerAdapter extends GateAdapter
{
    protected $brand = 'reformer';
    
    public function name(): string { return '立方'; }
    
    public function onPlateRecognized(array $data): array
    {
        $this->recordHeartbeat();
        
        $plateNumber = $data['plate_number'] ?? $data['plateNum'] ?? $data['plate'] ?? '';
        $direction = $data['direction'] ?? $data['dir'] ?? ($data['type'] === 'enter' ? 'in' : 'out');
        $communityId = $this->config['community_id'] ?? 0;
        
        $eventData = [
            'plate_number'   => $plateNumber,
            'direction'      => $direction,
            'image_url'      => $data['image_url'] ?? $data['img'] ?? '',
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
    
    private function sign(array $params): string
    {
        ksort($params);
        $str = '';
        foreach ($params as $k => $v) { $str .= $k . $v; }
        return strtoupper(md5($str . ($this->config['api_token'] ?? '')));
    }
    
    public function openGate(string $direction = 'in'): bool
    {
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/api/v1/gate/open';
        
        try {
            $body = [
                'deviceSn'  => $this->config['device_sn'] ?? '',
                'channelNo' => $this->config['channel_no'] ?? 1,
                'command'   => $direction === 'in' ? 'OPEN_ENTRY' : 'OPEN_EXIT',
                'timestamp' => (string)time(),
            ];
            $body['sign'] = $this->sign($body);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $apiUrl,
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
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
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/api/v1/device/info';
        
        try {
            $params = [
                'deviceSn'  => $this->config['device_sn'] ?? '',
                'timestamp' => (string)time(),
            ];
            $params['sign'] = $this->sign($params);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $apiUrl . '?' . http_build_query($params),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
            ]);
            $resp = curl_exec($ch);
            curl_close($ch);
            $data = json_decode($resp, true);
            return [
                'online'         => ($data['code'] ?? -1) === 200,
                'status'         => ($data['data']['barrier'] ?? 'closed') === 'closed' ? 'closed' : 'open',
                'last_heartbeat' => $data['data']['updateTime'] ?? '',
            ];
        } catch (\Throwable $e) {
            return ['online' => false, 'status' => 'unknown', 'last_heartbeat' => ''];
        }
    }
    
    public function syncWhitelist(array $plates): bool
    {
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/api/v1/whitelist/sync';
        
        try {
            $body = [
                'deviceSn'  => $this->config['device_sn'] ?? '',
                'plateList' => $plates,
                'timestamp' => (string)time(),
            ];
            $body['sign'] = $this->sign($body);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $apiUrl,
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
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
