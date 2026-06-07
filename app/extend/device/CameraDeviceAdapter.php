<?php
/**
 * 监控摄像头适配器（硬件设备层）
 * 协议: RTSP / ONVIF / SDK / GB28181
 * 功能: 实时预览、云台控制、抓拍、录像回放、PTZ控制
 */
namespace app\extend\device;

class CameraDeviceAdapter extends DeviceAdapter
{
    public function name(): string { return '监控摄像头'; }

    public function testConnection(): array
    {
        $host = $this->config['ip_address'] ?? '';
        $port = intval($this->config['port'] ?? 554);
        $proto = strtolower($this->config['protocol'] ?? '');

        if ($proto === 'rtsp' || $proto === '') {
            // RTSP 端口检测
            return $this->tcpPing($host, max($port, 554));
        }
        if ($proto === 'onvif') {
            // ONVIF 默认端口 80 或 8080 (SOAP)
            $url = "http://{$host}:{$port}/onvif/device_service";
            $resp = $this->httpPost($url,
                '<?xml version="1.0"?><s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope"><s:Body><tds:GetDeviceInformation xmlns:tds="http://www.onvif.org/ver10/device/wsdl"/></s:Body></s:Envelope>',
                ['Content-Type: application/soap+xml; charset=utf-8', 'SOAPAction: "http://www.onvif.org/ver10/device/wsdl/GetDeviceInformation"'],
                5);
            return $resp['success']
                ? array_merge($resp, ['message' => 'ONVIF 设备已连接'])
                : $resp;
        }
        if ($proto === 'gb28181') {
            // SIP 信令检测
            return $this->tcpPing($host, max($port, 5060));
        }
        // SDK / HTTP
        return $this->httpGet("http://{$host}:{$port}/api/status");
    }

    public function getStatus(): array
    {
        $host = $this->config['ip_address'] ?? '';
        $port = intval($this->config['port'] ?? 554);

        $tcp = $this->tcpPing($host, $port);
        if (!$tcp['success']) {
            $this->setOnline(false);
            return ['online' => false, 'status' => '离线', 'data' => []];
        }
        $this->setOnline(true);

        return [
            'online' => true,
            'status' => 'recording',
            'data'   => [
                'stream_url'     => "rtsp://{$host}:{$port}/live",
                'resolution'     => null,
                'recording'      => true,
                'ptz_support'    => true,
                'model'          => $this->config['model'] ?? '',
            ],
        ];
    }

    /**
     * action: ptz_up | ptz_down | ptz_left | ptz_right | zoom_in | zoom_out | snapshot | reboot | get_stream_url
     */
    public function remoteAction(string $action, array $params = []): array
    {
        $host = $this->config['ip_address'] ?? '';
        $port = intval($this->config['port'] ?? 80);
        $authKey = $this->config['auth_key'] ?? '';

        $ptzActions = [
            'ptz_up','ptz_down','ptz_left','ptz_right',
            'ptz_upleft','ptz_upright','ptz_downleft','ptz_downright',
            'zoom_in','zoom_out','focus_near','focus_far',
            'preset_set','preset_goto',
        ];

        if (in_array($action, $ptzActions)) {
            $speed = floatval($params['speed'] ?? 1.0);
            $payload = [
                'command' => str_replace('ptz_', '', $action),
                'speed'   => $speed,
            ];
            $url = "http://{$host}:{$port}/api/ptz/control";
            $resp = $this->httpPost($url, $payload, ["Authorization: Bearer {$authKey}"]);
            $this->recordEvent("摄像头操作", ['action'=>$action,'result'=>$resp['message']]);
            return $resp;
        }

        switch ($action) {
            case 'snapshot':
                $url = "http://{$host}:{$port}/api/snapshot";
                $r = $this->httpGet($url, ["Authorization: Bearer {$authKey}"]);
                $this->recordEvent("抓拍", ['result'=>$r['message']]);
                return $r;
            case 'reboot':
                $r = $this->httpPost("http://{$host}:{$port}/api/reboot", [], ["Authorization: Bearer {$authKey}"]);
                $this->recordEvent("重启", ['result'=>$r['message']]);
                return $r;
            case 'get_stream_url':
                $rtspPort = intval($params['rtsp_port'] ?? 554);
                $streamUrl = "rtsp://{$host}:{$rtspPort}/cam/realmonitor?channel=1&subtype=0&proto=Onvif";
                return ['success'=>true,'message'=>'获取成功','data'=>['rtsp'=>$streamUrl]];
            default:
                return ['success'=>false,"message"=>"不支持的操作: {$action}"];
        }
    }
}
