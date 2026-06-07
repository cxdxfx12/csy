<?php
/**
 * 通用门禁适配器
 * 适用于支持标准HTTP POST回调的第三方门禁控制器
 */
namespace app\extend\access;

class GenericAccessAdapter extends AccessAdapter
{
    protected $brand = 'generic';
    
    public function name(): string { return '通用协议'; }
    
    public function onCardSwiped(array $data): array
    {
        $this->recordHeartbeat();
        
        $cardNo = $data['card_no'] ?? $data['card'] ?? $data['cardId'] ?? '';
        $direction = $data['direction'] ?? $data['dir'] ?? 'in';
        $communityId = $this->config['community_id'] ?? 0;
        
        $eventData = [
            'card_no'      => $cardNo,
            'direction'    => $direction,
            'open_method'  => $data['open_method'] ?? 'card',
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
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/open';
        
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL=>$apiUrl, CURLOPT_POST=>true, CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>5,
                CURLOPT_HTTPHEADER=>['Content-Type: application/json', 'X-Token: '.($this->config['api_token']??'')],
                CURLOPT_POSTFIELDS=>json_encode(['device'=>$this->config['device_sn']??'', 'door'=>$doorNo, 'action'=>'open']),
            ]);
            curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
            return $code === 200;
        } catch (\Throwable $e) { return false; }
    }
    
    public function lockDoor(int $doorNo = 1): bool
    {
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/lock';
        
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL=>$apiUrl, CURLOPT_POST=>true, CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>5,
                CURLOPT_HTTPHEADER=>['Content-Type: application/json', 'X-Token: '.($this->config['api_token']??'')],
                CURLOPT_POSTFIELDS=>json_encode(['device'=>$this->config['device_sn']??'', 'door'=>$doorNo, 'action'=>'lock']),
            ]);
            curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
            return $code === 200;
        } catch (\Throwable $e) { return false; }
    }
    
    public function getStatus(): array
    {
        return ['online'=>false, 'door_status'=>'unknown', 'last_heartbeat'=>'', 'total_users'=>0];
    }
    
    public function syncWhitelist(array $cards): bool { return true; }
}
