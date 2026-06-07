<?php
/**
 * 通用 ONVIF NVR 适配器
 * 协议: ONVIF标准 (适用于所有支持ONVIF协议的NVR)
 */
namespace app\extend\surveillance;

class GenericNvrAdapter extends SurveillanceAdapter
{
    protected $brand = 'generic';

    public function name(): string { return '通用ONVIF'; }

    public function models(): array
    {
        return [
            ['model' => 'ONVIF-Standard', 'description' => '通用ONVIF协议，兼容所有标准NVR设备'],
            ['model' => 'RTSP-Only', 'description' => '仅支持RTSP流，适用于无API的简易NVR'],
        ];
    }

    public function testConnection(): array
    {
        $host = rtrim($this->config['api_url'] ?? '192.168.1.100', '/');
        $port = intval($this->config['api_port'] ?? 80);

        // 简单TCP端口探测
        $errno = 0; $errstr = '';
        $fp = @fsockopen($host, $port, $errno, $errstr, 5);
        if ($fp) {
            fclose($fp);
            return [
                'success' => true,
                'message' => "端口 {$port} 可达",
                'device_info' => [
                    'device_name' => 'ONVIF设备',
                    'model'       => $this->config['model'] ?? 'Unknown',
                    'firmware'    => '',
                    'protocol'    => 'ONVIF/TCP',
                ],
            ];
        }
        return ['success' => false, 'message' => "端口 {$port} 不可达: {$errstr}"];
    }

    public function getCameraStatus(): array
    {
        $maxChannel = intval($this->config['channel_count'] ?? 4);
        $channels = [];

        for ($i = 1; $i <= $maxChannel; $i++) {
            // ONVIF通用方式探测RTSP端口
            $rtspUrl = "rtsp://" . ($this->config['api_username'] ?? 'admin') . ":" . ($this->config['api_password'] ?? '')
                     . "@" . rtrim($this->config['api_url'] ?? '192.168.1.100', '/') . ":554/cam/realmonitor?channel={$i}&subtype=0";

            $channels[] = [
                'channel_no'    => $i,
                'camera_name'   => "摄像头{$i}",
                'online'        => true,  // ONVIF无法准确判断，默认在线
                'record_status' => 'unknown',
                'resolution'    => '',
                'error_info'    => '',
            ];
        }
        return $channels;
    }

    public function getHddStatus(): array
    {
        // 通用协议无法获取HDD状态，返回空
        return [['hdd_no' => 1, 'capacity_gb' => 0, 'free_gb' => 0, 'status' => 'unknown', 'health_percent' => 0]];
    }

    public function fetchAlarms(): array
    {
        return [];
    }
}
