<?php
/**
 * 大华门禁(Dahua Access)适配器
 * 接口协议: HTTP REST API，Digest 认证
 * 支持型号: DHI-ASI 系列门禁控制器
 */
namespace app\extend\access;

class DahuaAccessAdapter extends AccessAdapter
{
    protected $brand = 'dahua';
    
    public function name(): string { return '大华门禁'; }
    
    public function onCardSwiped(array $data): array
    {
        $this->recordHeartbeat();
        
        $cardNo = $data['card_no'] ?? $data['CardNo'] ?? $data['cardId'] ?? '';
        $direction = $data['direction'] ?? $data['dir'] ?? 'in';
        $communityId = $this->config['community_id'] ?? 0;
        
        $eventData = [
            'card_no'      => $cardNo,
            'direction'    => $direction,
            'open_method'  => $data['open_method'] ?? $data['Method'] ?? 'card',
            'photo_url'    => $data['photo_url'] ?? $data['Snapshot'] ?? '',
            'event_time'   => $data['event_time'] ?? $data['Time'] ?? date('Y-m-d H:i:s'),
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
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/RPC2';
        $user = $this->config['api_username'] ?? 'admin';
        $pass = $this->config['api_token'] ?? '';
        
        try {
            $body = json_encode([
                'method' => 'accessControl.openDoor',
                'params' => ['deviceId'=>($this->config['device_sn']??''), 'channel'=>$doorNo],
                'id' => time(),
            ]);
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL=>$apiUrl, CURLOPT_POST=>true, CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>5,
                CURLOPT_HTTPAUTH=>CURLAUTH_DIGEST, CURLOPT_USERPWD=>"$user:$pass",
                CURLOPT_HTTPHEADER=>['Content-Type: application/json'], CURLOPT_POSTFIELDS=>$body,
            ]);
            curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
            return $code === 200;
        } catch (\Throwable $e) { return false; }
    }
    
    public function lockDoor(int $doorNo = 1): bool
    {
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/RPC2';
        $user = $this->config['api_username'] ?? 'admin';
        $pass = $this->config['api_token'] ?? '';
        
        try {
            $body = json_encode([
                'method' => 'accessControl.closeDoor',
                'params' => ['deviceId'=>($this->config['device_sn']??''), 'channel'=>$doorNo],
                'id' => time(),
            ]);
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL=>$apiUrl, CURLOPT_POST=>true, CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>5,
                CURLOPT_HTTPAUTH=>CURLAUTH_DIGEST, CURLOPT_USERPWD=>"$user:$pass",
                CURLOPT_HTTPHEADER=>['Content-Type: application/json'], CURLOPT_POSTFIELDS=>$body,
            ]);
            curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
            return $code === 200;
        } catch (\Throwable $e) { return false; }
    }
    
    public function getStatus(): array
    {
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/RPC2';
        $user = $this->config['api_username'] ?? 'admin';
        $pass = $this->config['api_token'] ?? '';
        
        try {
            $body = json_encode([
                'method'=>'accessControl.getStatus',
                'params'=>['deviceId'=>($this->config['device_sn']??'')],
                'id'=>time(),
            ]);
            $ch = curl_init();
            curl_setopt_array($ch, [CURLOPT_URL=>$apiUrl, CURLOPT_POST=>true, CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>5, CURLOPT_HTTPAUTH=>CURLAUTH_DIGEST, CURLOPT_USERPWD=>"$user:$pass", CURLOPT_HTTPHEADER=>['Content-Type: application/json'], CURLOPT_POSTFIELDS=>$body]);
            $resp = curl_exec($ch); curl_close($ch);
            $data = json_decode($resp, true);
            return [
                'online'         => isset($data['result']) && ($data['result']??false),
                'door_status'    => ($data['params']['status']??'close')==='open'?'open':'closed',
                'last_heartbeat' => $data['params']['lastUpdate']??'',
                'total_users'    => $data['params']['userCount']??0,
            ];
        } catch (\Throwable $e) {
            return ['online'=>false,'door_status'=>'unknown','last_heartbeat'=>'','total_users'=>0];
        }
    }
    
    public function syncWhitelist(array $cards): bool
    {
        $apiUrl = rtrim($this->config['api_url'] ?? '', '/') . '/RPC2';
        $user = $this->config['api_username'] ?? 'admin';
        $pass = $this->config['api_token'] ?? '';
        
        try {
            $cardList = [];
            foreach ($cards as $c) {
                $cardList[] = ['CardNo'=>$c['card_no'], 'UserName'=>$c['holder_name']??'', 'ValidDate'=>$c['expire_date']??''];
            }
            $body = json_encode(['method'=>'accessControl.syncCards','params'=>['deviceId'=>($this->config['device_sn']??''),'cards'=>$cardList],'id'=>time()]);
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL=>$apiUrl, CURLOPT_POST=>true, CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>30,
                CURLOPT_HTTPAUTH=>CURLAUTH_DIGEST, CURLOPT_USERPWD=>"$user:$pass",
                CURLOPT_HTTPHEADER=>['Content-Type: application/json'], CURLOPT_POSTFIELDS=>$body,
            ]);
            curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
            return $code === 200;
        } catch (\Throwable $e) { return false; }
    }
}
