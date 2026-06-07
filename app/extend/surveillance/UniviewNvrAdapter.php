<?php
/**
 * 宇视 NVR 适配器
 * 协议: HTTP JSON API (Digest Auth)
 * 常用型号: NVR3/5 系列, VS 系列
 */
namespace app\extend\surveillance;

class UniviewNvrAdapter extends SurveillanceAdapter
{
    protected $brand = 'uniview';

    public function name(): string { return '宇视'; }

    public function models(): array
    {
        return [
            ['model' => 'NVR301-04S', 'description' => '4路入门级NVR，H.265+智能编码'],
            ['model' => 'NVR302-08S', 'description' => '8路中端NVR，智能检索'],
            ['model' => 'NVR304-16S', 'description' => '16路高端NVR，人脸检测'],
            ['model' => 'NVR516-128', 'description' => '128路旗舰级NVR，超强接入能力'],
            ['model' => 'VS-4004', 'description' => '4路视频服务器，兼容模拟改造'],
        ];
    }

    public function testConnection(): array
    {
        $url = $this->buildUrl('/api/v1/system/deviceinfo');
        $result = $this->httpGet($url, $this->config['api_username'] ?? 'admin', $this->config['api_password'] ?? '');

        if (!$result['success']) {
            // 尝试 /cgi 格式
            $url2 = $this->buildUrl('/cgi-bin/getSystemInfo.cgi');
            $result = $this->httpGet($url2, $this->config['api_username'] ?? 'admin', $this->config['api_password'] ?? '');
        }

        if (!$result['success']) {
            return ['success' => false, 'message' => '连接失败: ' . $result['message']];
        }

        return [
            'success' => true,
            'message' => '连接成功',
            'device_info' => [
                'device_name' => '宇视NVR',
                'model'       => $this->config['model'] ?? '',
                'firmware'    => '',
                'protocol'    => 'HTTP API',
            ],
        ];
    }

    public function getCameraStatus(): array
    {
        $maxChannel = intval($this->config['channel_count'] ?? 4);
        $channels = [];
        $url = $this->buildUrl('/api/v1/channel/status');
        $result = $this->httpGet($url, $this->config['api_username'] ?? 'admin', $this->config['api_password'] ?? '', 5);

        if ($result['success']) {
            $json = json_decode($result['data'], true);
            if (isset($json['channels'])) {
                foreach ($json['channels'] as $ch) {
                    $channels[] = [
                        'channel_no'    => intval($ch['id'] ?? count($channels) + 1),
                        'camera_name'   => $ch['name'] ?? "通道" . (count($channels) + 1),
                        'online'        => ($ch['status'] ?? '') === 'online',
                        'record_status' => ($ch['recording'] ?? '') === 'true' ? 'recording' : 'stopped',
                        'resolution'    => $ch['resolution'] ?? '',
                        'error_info'    => $ch['error'] ?? '',
                    ];
                }
                return $channels;
            }
        }

        // fallback
        for ($i = 1; $i <= $maxChannel; $i++) {
            $chUrl = $this->buildUrl("/cgi-bin/getChannelInfo.cgi?channel={$i}");
            $chResult = $this->httpGet($chUrl, $this->config['api_username'] ?? 'admin', $this->config['api_password'] ?? '', 3);
            $channels[] = [
                'channel_no'    => $i,
                'camera_name'   => "通道{$i}",
                'online'        => $chResult['success'],
                'record_status' => 'unknown',
                'resolution'    => '',
                'error_info'    => $chResult['success'] ? '' : '无法获取通道信息',
            ];
        }
        return $channels;
    }

    public function getHddStatus(): array
    {
        $url = $this->buildUrl('/api/v1/storage/hdd');
        $result = $this->httpGet($url, $this->config['api_username'] ?? 'admin', $this->config['api_password'] ?? '');

        if ($result['success']) {
            $json = json_decode($result['data'], true);
            if (isset($json['hdd_list'])) {
                $hdds = [];
                foreach ($json['hdd_list'] as $h) {
                    $hdds[] = [
                        'hdd_no'         => intval($h['id'] ?? count($hdds) + 1),
                        'capacity_gb'    => round(floatval($h['capacity'] ?? 0) / 1024, 1),
                        'free_gb'        => round(floatval($h['free'] ?? 0) / 1024, 1),
                        'status'         => ($h['status'] ?? '') === 'normal' ? 'ok' : 'error',
                        'health_percent' => $h['health'] ?? 95,
                    ];
                }
                return $hdds;
            }
        }
        return [['hdd_no' => 1, 'capacity_gb' => 0, 'free_gb' => 0, 'status' => 'unknown', 'health_percent' => 0]];
    }

    public function fetchAlarms(): array
    {
        $url = $this->buildUrl('/api/v1/alarm/current');
        $result = $this->httpGet($url, $this->config['api_username'] ?? 'admin', $this->config['api_password'] ?? '', 5);
        if (!$result['success']) return [];

        $json = json_decode($result['data'], true);
        $alarms = [];
        foreach ($json['alarms'] ?? [] as $a) {
            $alarms[] = [
                'event_type' => $a['type'] ?? 'unknown',
                'event_desc' => $a['description'] ?? ($a['type'] ?? 'unknown'),
                'timestamp'  => $a['time'] ?? date('Y-m-d H:i:s'),
            ];
        }
        return $alarms;
    }

    private function buildUrl(string $path): string
    {
        $host = rtrim($this->config['api_url'] ?? '192.168.1.100', '/');
        $port = intval($this->config['api_port'] ?? 80);
        if ($port !== 80) $host .= ':' . $port;
        return 'http://' . $host . $path;
    }
}
