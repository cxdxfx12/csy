<?php
/**
 * 科拓(Keytop)道闸适配器
 * 接口协议: HTTP POST JSON，HMAC-SHA256 签名
 * 支持型号: KT-IS01, KT-IS02, KT-IS03 系列
 */
namespace app\extend\gate;

class KeytopAdapter extends GateAdapter
{
    protected $brand = 'keytop';
    
    public function name(): string { return '科拓'; }
    
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
    
    private function sign(array $params): string
    {
        ksort($params);
        $str = '';
        foreach ($params as $k => $v) { $str .= $k . '=' . $v . '&'; }
        $str = rtrim($str, '&');
        return hash_hmac('sha256', $str, $this->config['api_token'] ?? '');
    }
    
    public function openGate(string $direction = 'in'): bool
    {
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/v1/gate/control';
        
        try {
            $body = [
                'device_sn' => $this->config['device_sn'] ?? '',
                'action'    => $direction === 'in' ? 'open_entry' : 'open_exit',
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
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/v1/device/status';
        
        try {
            $params = [
                'device_sn' => $this->config['device_sn'] ?? '',
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
                'online'         => ($data['code'] ?? -1) === 0,
                'status'         => ($data['data']['gateStatus'] ?? '') === 'close' ? 'closed' : 'open',
                'last_heartbeat' => $data['data']['updateTime'] ?? '',
            ];
        } catch (\Throwable $e) {
            return ['online' => false, 'status' => 'unknown', 'last_heartbeat' => ''];
        }
    }
    
    public function syncWhitelist(array $plates): bool
    {
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/v1/whitelist/batch';
        
        try {
            $body = [
                'device_sn' => $this->config['device_sn'] ?? '',
                'plates'    => $plates,
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
