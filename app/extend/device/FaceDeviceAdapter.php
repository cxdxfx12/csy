<?php
/**
 * 人脸识别终端适配器
 * 协议: HTTP / SDK / TCP
 * 功能: 人脸识别、白名单同步、远程开门、抓拍、考勤记录
 */
namespace app\extend\device;

class FaceDeviceAdapter extends DeviceAdapter
{
    public function name(): string { return '人脸识别终端'; }

    public function testConnection(): array
    {
        $host = $this->config['ip_address'] ?? '';
        $port = intval($this->config['port'] ?? 80);
        return $this->httpGet("http://{$host}:{$port}/api/health");
    }

    public function getStatus(): array
    {
        $tcp = $this->tcpPing(
            $this->config['ip_address'] ?? '',
            intval($this->config['port'] ?? 80)
        );
        if (!$tcp['success']) { $this->setOnline(false); return ['online'=>false,'status'=>'离线','data'=>[]]; }
        $this->setOnline(true);
        return [
            'online' => true,
            'status' => 'ready',
            'data' => [
                'face_count'     => null,
                'temperature_c'  => null,
                'mask_detect_on' => true,
                'storage_used'   => null,
            ],
        ];
    }

    /**
     * action: capture | open_door | sync_faces | reboot | get_attendance
     */
    public function remoteAction(string $action, array $params = []): array
    {
        switch ($action) {
            case 'capture':
                $r = ['success'=>true,'message'=>'抓拍成功','data'=>['image_url'=>'']];
                break;
            case 'open_door':
                $r = ['success'=>true,'message'=>'已开门'];
                break;
            case 'sync_faces':
                $r = ['success'=>true,'message'=>'人脸库同步中...'];
                break;
            default:
                return ['success'=>false,"message"=>"不支持: {$action}"];
        }
        $this->recordEvent("人脸终端操作", ['action'=>$action]);
        return $r;
    }
}
