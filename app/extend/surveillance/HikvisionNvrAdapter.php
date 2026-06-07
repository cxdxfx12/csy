<?php
/**
 * 海康威视 NVR 适配器
 * 协议: ISAPI (HTTP Digest Auth)
 * 常用型号: DS-7600/7700/8600/9600 系列 NVR, iDS-9600 系列 AI NVR
 */
namespace app\extend\surveillance;

class HikvisionNvrAdapter extends SurveillanceAdapter
{
    protected $brand = 'hikvision';

    public function name(): string { return '海康威视'; }

    public function models(): array
    {
        return [
            ['model' => 'DS-7604NI-K1', 'description' => '4路入门级NVR，适合小小区'],
            ['model' => 'DS-7608NI-K2', 'description' => '8路中端NVR，适合中型小区'],
            ['model' => 'DS-7616NI-K2', 'description' => '16路中端NVR，适合大型小区'],
            ['model' => 'DS-9632NI-I8', 'description' => '32路高性能NVR，支持AI'],
            ['model' => 'iDS-9632NXI-I8', 'description' => '32路AI智能NVR，支持人脸/车牌'],
            ['model' => 'DS-8664NI-K8', 'description' => '64路旗舰级NVR，超大型项目'],
        ];
    }

    public function testConnection(): array
    {
        $apiUrl = $this->buildUrl('/ISAPI/System/deviceInfo');
        $username = $this->config['api_username'] ?? 'admin';
        $password = $this->config['api_password'] ?? '';

        $result = $this->httpGet($apiUrl, $username, $password);
        if (!$result['success']) {
            return ['success' => false, 'message' => '连接失败: ' . $result['message']];
        }

        $xml = $result['data'];
        // 简单解析XML获取设备名称
        preg_match('/<deviceName>(.*?)<\/deviceName>/s', $xml, $m1);
        preg_match('/<model>(.*?)<\/model>/s', $xml, $m2);
        preg_match('/<firmwareVersion>(.*?)<\/firmwareVersion>/s', $xml, $m3);

        return [
            'success' => true,
            'message' => '连接成功',
            'device_info' => [
                'device_name' => $m1[1] ?? 'Unknown',
                'model'       => $m2[1] ?? 'Unknown',
                'firmware'    => $m3[1] ?? 'Unknown',
                'protocol'    => 'ISAPI',
            ],
        ];
    }

    public function getCameraStatus(): array
    {
        $apiUrl = $this->buildUrl('/ISAPI/ContentMgmt/StreamingProxy/channels/status');
        $username = $this->config['api_username'] ?? 'admin';
        $password = $this->config['api_password'] ?? '';

        $result = $this->httpGet($apiUrl, $username, $password);
        if (!$result['success']) {
            // 单个通道查询
            return $this->getCameraStatusPerChannel($username, $password);
        }
        return $this->parseChannelXml($result['data']);
    }

    /** 逐通道查询状态 */
    private function getCameraStatusPerChannel(string $username, string $password): array
    {
        $channels = [];
        $maxChannel = intval($this->config['channel_count'] ?? 4);

        for ($i = 1; $i <= $maxChannel; $i++) {
            $apiUrl = $this->buildUrl("/ISAPI/ContentMgmt/StreamingProxy/channels/{$i}01/status");
            $result = $this->httpGet($apiUrl, $username, $password, 5);
            $channels[] = [
                'channel_no'    => $i,
                'camera_name'   => "通道{$i}",
                'online'        => $result['success'],
                'record_status' => $result['success'] ? 'recording' : 'unknown',
                'resolution'    => '',
                'error_info'    => $result['success'] ? '' : '无法获取通道状态',
            ];
        }
        return $channels;
    }

    public function getHddStatus(): array
    {
        $apiUrl = $this->buildUrl('/ISAPI/ContentMgmt/Storage/hdd');
        $username = $this->config['api_username'] ?? 'admin';
        $password = $this->config['api_password'] ?? '';

        $result = $this->httpGet($apiUrl, $username, $password);
        if (!$result['success']) {
            return [['hdd_no' => 1, 'capacity_gb' => 0, 'free_gb' => 0, 'status' => 'unknown', 'health_percent' => 0]];
        }
        return $this->parseHddXml($result['data']);
    }

    public function fetchAlarms(): array
    {
        $apiUrl = $this->buildUrl('/ISAPI/Event/notification/alertStream');
        $username = $this->config['api_username'] ?? 'admin';
        $password = $this->config['api_password'] ?? '';

        $result = $this->httpGet($apiUrl, $username, $password, 5);
        if (!$result['success']) return [];

        $alarms = [];
        // 解析告警XML
        if (preg_match_all('/<eventType>(.*?)<\/eventType>/s', $result['data'], $types)) {
            foreach ($types[1] as $i => $type) {
                $desc = '';
                if (strpos($type, 'videoloss') !== false) $desc = '摄像头视频丢失';
                elseif (strpos($type, 'hdd') !== false) $desc = '硬盘异常';
                elseif (strpos($type, 'record') !== false) $desc = '录像异常';
                else $desc = $type;

                $alarms[] = [
                    'event_type' => $type,
                    'event_desc' => $desc,
                    'timestamp'  => date('Y-m-d H:i:s'),
                ];
            }
        }
        return $alarms;
    }

    private function buildUrl(string $path): string
    {
        $host = $this->config['api_url'] ?? '192.168.1.100';
        $host = rtrim($host, '/');
        $port = intval($this->config['api_port'] ?? 80);
        if ($port !== 80) {
            $host .= ':' . $port;
        }
        return 'http://' . $host . $path;
    }

    private function parseChannelXml(string $xml): array
    {
        $channels = [];
        if (preg_match_all('/<channelStatus.*?>(.*?)<\/channelStatus>/s', $xml, $blocks)) {
            foreach ($blocks[1] as $i => $block) {
                preg_match('/<id>.*?(\d+)\d\d<\/id>/s', $block, $id);
                preg_match('/<channelName>(.*?)<\/channelName>/s', $block, $name);
                preg_match('/<online>(.*?)<\/online>/s', $block, $online);
                preg_match('/<recordStatus>(.*?)<\/recordStatus>/s', $block, $rec);

                $channels[] = [
                    'channel_no'    => intval($id[1] ?? $i + 1),
                    'camera_name'   => $name[1] ?? "通道" . ($i + 1),
                    'online'        => ($online[1] ?? 'true') === 'true',
                    'record_status' => ($rec[1] ?? '') === 'recording' ? 'recording' : 'stopped',
                    'resolution'    => '',
                    'error_info'    => '',
                ];
            }
        }
        return $channels;
    }

    private function parseHddXml(string $xml): array
    {
        $hdds = [];
        if (preg_match_all('/<hdd.*?>(.*?)<\/hdd>/s', $xml, $blocks)) {
            foreach ($blocks[1] as $i => $block) {
                preg_match('/<id>(.*?)<\/id>/s', $block, $id);
                preg_match('/<capacity>(.*?)<\/capacity>/s', $block, $cap);
                preg_match('/<status>(.*?)<\/status>/s', $block, $sts);

                $hdds[] = [
                    'hdd_no'         => intval($id[1] ?? $i + 1),
                    'capacity_gb'    => round(intval($cap[1] ?? 0) / 1024, 1),
                    'free_gb'        => 0,
                    'status'         => ($sts[1] ?? '') === 'ok' ? 'ok' : 'error',
                    'health_percent' => ($sts[1] ?? '') === 'ok' ? 95 : 50,
                ];
            }
        }
        return $hdds ?: [['hdd_no' => 1, 'capacity_gb' => 0, 'free_gb' => 0, 'status' => 'unknown', 'health_percent' => 0]];
    }
}
