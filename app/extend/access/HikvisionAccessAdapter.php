<?php
/**
 * 海康威视门禁(Hikvision Access)适配器
 * 接口协议: ISAPI HTTP/HTTPS，Digest 认证
 * 支持型号: DS-K2600 系列门禁控制器
 */
namespace app\extend\access;

class HikvisionAccessAdapter extends AccessAdapter
{
    protected $brand = 'hikvision';
    
    public function name(): string { return '海康门禁'; }
    
    public function onCardSwiped(array $data): array
    {
        $this->recordHeartbeat();
        
        $cardNo = $data['card_no'] ?? $data['cardNo'] ?? $data['card'] ?? '';
        $direction = $data['direction'] ?? $data['dir'] ?? 'in';
        $communityId = $this->config['community_id'] ?? 0;
        
        $eventData = [
            'card_no'      => $cardNo,
            'direction'    => $direction,
            'open_method'  => $data['open_method'] ?? $data['method'] ?? 'card',
            'photo_url'    => $data['photo_url'] ?? $data['pic'] ?? '',
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
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/ISAPI/AccessControl/RemoteControl/door/' . $doorNo;
        $user = $this->config['api_username'] ?? 'admin';
        $pass = $this->config['api_token'] ?? '';
        
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $apiUrl,
                CURLOPT_CUSTOMREQUEST  => 'PUT',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_HTTPAUTH       => CURLAUTH_DIGEST,
                CURLOPT_USERPWD        => "$user:$pass",
                CURLOPT_HTTPHEADER     => ['Content-Type: application/xml'],
                CURLOPT_POSTFIELDS     => '<RemoteControlDoor><cmd>open</cmd></RemoteControlDoor>',
            ]);
            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return $code === 200;
        } catch (\Throwable $e) { return false; }
    }
    
    public function lockDoor(int $doorNo = 1): bool
    {
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/ISAPI/AccessControl/RemoteControl/door/' . $doorNo;
        $user = $this->config['api_username'] ?? 'admin';
        $pass = $this->config['api_token'] ?? '';
        
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl, CURLOPT_CUSTOMREQUEST => 'PUT', CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5,
                CURLOPT_HTTPAUTH => CURLAUTH_DIGEST, CURLOPT_USERPWD => "$user:$pass",
                CURLOPT_HTTPHEADER => ['Content-Type: application/xml'],
                CURLOPT_POSTFIELDS => '<RemoteControlDoor><cmd>close</cmd></RemoteControlDoor>',
            ]);
            curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
            return $code === 200;
        } catch (\Throwable $e) { return false; }
    }
    
    public function getStatus(): array
    {
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/ISAPI/AccessControl/status';
        $user = $this->config['api_username'] ?? 'admin';
        $pass = $this->config['api_token'] ?? '';
        
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [CURLOPT_URL=>$apiUrl, CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>5, CURLOPT_HTTPAUTH=>CURLAUTH_DIGEST, CURLOPT_USERPWD=>"$user:$pass"]);
            $resp = curl_exec($ch); curl_close($ch);
            // 解析XML或JSON
            $data = json_decode($resp, true) ?: [];
            return [
                'online'         => !empty($data),
                'door_status'    => ($data['doorStatus']??'closed')==='open'?'open':'closed',
                'last_heartbeat' => $data['lastUpdate']??'',
                'total_users'    => $data['userCount']??0,
            ];
        } catch (\Throwable $e) {
            return ['online'=>false,'door_status'=>'unknown','last_heartbeat'=>'','total_users'=>0];
        }
    }
    
    public function syncWhitelist(array $cards): bool
    {
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/ISAPI/AccessControl/Card/batch';
        $user = $this->config['api_username'] ?? 'admin';
        $pass = $this->config['api_token'] ?? '';
        
        try {
            $xml = '<CardBatch>';
            foreach ($cards as $c) {
                $xml .= '<Card><cardNo>' . htmlspecialchars($c['card_no']) . '</cardNo><employeeNo>' . htmlspecialchars($c['holder_name']??'') . '</employeeNo></Card>';
            }
            $xml .= '</CardBatch>';
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL=>$apiUrl, CURLOPT_POST=>true, CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>30,
                CURLOPT_HTTPAUTH=>CURLAUTH_DIGEST, CURLOPT_USERPWD=>"$user:$pass",
                CURLOPT_HTTPHEADER=>['Content-Type: application/xml'], CURLOPT_POSTFIELDS=>$xml,
            ]);
            curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
            return $code === 200;
        } catch (\Throwable $e) { return false; }
    }
}
