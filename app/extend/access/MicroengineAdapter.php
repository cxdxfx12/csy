<?php
/**
 * 微耕(Microengine)门禁适配器
 * 接口协议: HTTP POST JSON，MD5签名
 * 支持型号: WG-2000 系列控制器
 */
namespace app\extend\access;

class MicroengineAdapter extends AccessAdapter
{
    protected $brand = 'microengine';
    
    public function name(): string { return '微耕'; }
    
    public function onCardSwiped(array $data): array
    {
        $this->recordHeartbeat();
        
        $cardNo = $data['card_no'] ?? $data['cardId'] ?? $data['card'] ?? '';
        $direction = $data['direction'] ?? $data['dir'] ?? 'in';
        $communityId = $this->config['community_id'] ?? 0;
        
        $eventData = [
            'card_no'      => $cardNo,
            'direction'    => $direction,
            'open_method'  => $data['open_method'] ?? $data['mode'] ?? 'card',
            'photo_url'    => $data['photo_url'] ?? '',
            'event_time'   => $data['event_time'] ?? $data['time'] ?? date('Y-m-d H:i:s'),
            'action'       => 'unknown',
            'holder_name'  => '',
            'reason'       => '',
        ];
        
        $card = $this->matchCard($cardNo, $communityId);
        $valid = $this->validateCard($card);
        
        if ($valid === 'ok') {
            $eventData['action'] = 'pass';
            $eventData['holder_name'] = $card['holder_name'] ?? '';
            $this->recordEvent($eventData);
            return ['action'=>'open','reason'=>'验证通过','holder_info'=>['name'=>$card['holder_name']??'','type'=>$card['holder_type']??0,'card_no'=>$cardNo]];
        }
        
        $eventData['action'] = 'deny';
        $eventData['reason'] = $valid;
        if ($card) $eventData['holder_name'] = $card['holder_name'] ?? '';
        $this->recordEvent($eventData);
        return ['action'=>'deny','reason'=>$valid,'holder_info'=>null];
    }
    
    private function sign(array $params): string
    {
        ksort($params);
        $str = '';
        foreach ($params as $k => $v) { $str .= $k . '=' . $v . '&'; }
        return md5(rtrim($str, '&') . ($this->config['api_token'] ?? ''));
    }
    
    public function remoteOpen(int $doorNo = 1): bool
    {
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/api/door/open';
        
        try {
            $body = ['deviceSn'=>(string)($this->config['device_sn']??''), 'doorNo'=>(string)$doorNo, 'timestamp'=>(string)time()];
            $body['sign'] = $this->sign($body);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl, CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
            ]);
            curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
            return $code === 200;
        } catch (\Throwable $e) { return false; }
    }
    
    public function lockDoor(int $doorNo = 1): bool
    {
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/api/door/lock';
        
        try {
            $body = ['deviceSn'=>(string)($this->config['device_sn']??''), 'doorNo'=>(string)$doorNo, 'timestamp'=>(string)time()];
            $body['sign'] = $this->sign($body);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl, CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
            ]);
            curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
            return $code === 200;
        } catch (\Throwable $e) { return false; }
    }
    
    public function getStatus(): array
    {
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/api/device/status';
        
        try {
            $params = ['deviceSn'=>(string)($this->config['device_sn']??''), 'timestamp'=>(string)time()];
            $params['sign'] = $this->sign($params);
            
            $ch = curl_init();
            curl_setopt_array($ch, [CURLOPT_URL=>$apiUrl.'?'.http_build_query($params), CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>5]);
            $resp = curl_exec($ch); curl_close($ch);
            $data = json_decode($resp, true);
            return [
                'online'         => ($data['code']??-1)===0,
                'door_status'    => ($data['data']['doorStatus']??0)===0?'closed':'open',
                'last_heartbeat' => $data['data']['lastTime']??'',
                'total_users'    => $data['data']['userCount']??0,
            ];
        } catch (\Throwable $e) {
            return ['online'=>false,'door_status'=>'unknown','last_heartbeat'=>'','total_users'=>0];
        }
    }
    
    public function syncWhitelist(array $cards): bool
    {
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/api/whitelist/sync';
        
        try {
            $cardList = [];
            foreach ($cards as $c) {
                $cardList[] = ['cardId'=>$c['card_no'], 'name'=>$c['holder_name']??'', 'expireDate'=>$c['expire_date']??''];
            }
            $body = ['deviceSn'=>(string)($this->config['device_sn']??''), 'cards'=>$cardList, 'timestamp'=>(string)time()];
            $body['sign'] = $this->sign($body);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL=>$apiUrl, CURLOPT_POST=>true, CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>30,
                CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
                CURLOPT_POSTFIELDS=>json_encode($body, JSON_UNESCAPED_UNICODE),
            ]);
            curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
            return $code === 200;
        } catch (\Throwable $e) { return false; }
    }
}
