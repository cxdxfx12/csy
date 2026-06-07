<?php
/**
 * 同方锐安(Tongfang)门禁适配器
 * 接口协议: HTTP POST JSON，Token认证
 */
namespace app\extend\access;

class TongfangAdapter extends AccessAdapter
{
    protected $brand = 'tongfang';
    
    public function name(): string { return '同方锐安'; }
    
    public function onCardSwiped(array $data): array
    {
        $this->recordHeartbeat();
        
        $cardNo = $data['card_no'] ?? $data['cardNum'] ?? $data['card'] ?? '';
        $direction = $data['direction'] ?? $data['dir'] ?? 'in';
        $communityId = $this->config['community_id'] ?? 0;
        
        $eventData = [
            'card_no'      => $cardNo,
            'direction'    => $direction,
            'open_method'  => $data['open_method'] ?? $data['mode'] ?? 'card',
            'photo_url'    => $data['photo_url'] ?? $data['image'] ?? '',
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
        
        $eventData['action'] = 'deny'; $eventData['reason'] = $valid;
        if ($card) $eventData['holder_name'] = $card['holder_name'] ?? '';
        $this->recordEvent($eventData);
        return ['action'=>'deny','reason'=>$valid,'holder_info'=>null];
    }
    
    public function remoteOpen(int $doorNo = 1): bool
    {
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/api/v1/door/control';
        
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl, CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Access-Token: '.($this->config['api_token']??'')],
                CURLOPT_POSTFIELDS => json_encode(['sn'=>$this->config['device_sn']??'', 'doorIndex'=>$doorNo, 'cmd'=>'open']),
            ]);
            curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
            return $code === 200;
        } catch (\Throwable $e) { return false; }
    }
    
    public function lockDoor(int $doorNo = 1): bool
    {
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/api/v1/door/control';
        
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl, CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Access-Token: '.($this->config['api_token']??'')],
                CURLOPT_POSTFIELDS => json_encode(['sn'=>$this->config['device_sn']??'', 'doorIndex'=>$doorNo, 'cmd'=>'lock']),
            ]);
            curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
            return $code === 200;
        } catch (\Throwable $e) { return false; }
    }
    
    public function getStatus(): array
    {
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/api/v1/device/info';
        
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [CURLOPT_URL=>$apiUrl.'?sn='.urlencode($this->config['device_sn']??''), CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>5, CURLOPT_HTTPHEADER=>['Access-Token: '.($this->config['api_token']??'')]]);
            $resp = curl_exec($ch); curl_close($ch);
            $data = json_decode($resp, true);
            return [
                'online'         => ($data['code']??-1)===0,
                'door_status'    => ($data['data']['doorState']??0)===0?'closed':'open',
                'last_heartbeat' => $data['data']['updateTime']??'',
                'total_users'    => $data['data']['userTotal']??0,
            ];
        } catch (\Throwable $e) {
            return ['online'=>false,'door_status'=>'unknown','last_heartbeat'=>'','total_users'=>0];
        }
    }
    
    public function syncWhitelist(array $cards): bool
    {
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/api/v1/card/batch';
        
        try {
            $cardList = [];
            foreach ($cards as $c) {
                $cardList[] = ['cardNum'=>$c['card_no'], 'name'=>$c['holder_name']??'', 'expire'=>$c['expire_date']??''];
            }
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL=>$apiUrl, CURLOPT_POST=>true, CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>30,
                CURLOPT_HTTPHEADER=>['Content-Type: application/json', 'Access-Token: '.($this->config['api_token']??'')],
                CURLOPT_POSTFIELDS=>json_encode(['sn'=>$this->config['device_sn']??'', 'cards'=>$cardList], JSON_UNESCAPED_UNICODE),
            ]);
            curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
            return $code === 200;
        } catch (\Throwable $e) { return false; }
    }
}
