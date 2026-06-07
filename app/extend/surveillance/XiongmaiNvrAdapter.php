<?php
/**
 * 雄迈 NVR 适配器
 * 协议: HTTP JSON API (默认端口34567)
 * 常用型号: XM-NVR 系列, 部分贴牌NVR (中维世纪/天视通等)
 */
namespace app\extend\surveillance;

class XiongmaiNvrAdapter extends SurveillanceAdapter
{
    protected $brand = 'xiongmai';

    public function name(): string { return '雄迈'; }

    public function models(): array
    {
        return [
            ['model' => 'XM-NVR0404', 'description' => '4路经济型NVR，适合小店铺/门卫'],
            ['model' => 'XM-NVR0808', 'description' => '8路经济型NVR，适合中小小区'],
            ['model' => 'XM-NVR1616', 'description' => '16路中端NVR，H.265+压缩'],
            ['model' => 'XM-NVR3232', 'description' => '32路高端NVR，智能检索'],
            ['model' => 'XM-NVR64', 'description' => '64路旗舰级NVR'],
        ];
    }

    public function testConnection(): array
    {
        $url = $this->buildUrl('/cgi-bin/keepAlive.cgi');
        $result = $this->httpGet($url, $this->config['api_username'] ?? 'admin', $this->config['api_password'] ?? '');

        if (!$result['success']) {
            return ['success' => false, 'message' => '连接失败: ' . $result['message']];
        }
        return [
            'success' => true,
            'message' => '连接成功',
            'device_info' => [
                'device_name' => '雄迈系列NVR',
                'model'       => $this->config['model'] ?? '',
                'firmware'    => '',
                'protocol'    => 'HTTP API (XM SDK)',
            ],
        ];
    }

    public function getCameraStatus(): array
    {
        $maxChannel = intval($this->config['channel_count'] ?? 4);
        $channels = [];

        $url = $this->buildUrl('/cgi-bin/snapManager.cgi?action=getChannel');
        $result = $this->httpGet($url, $this->config['api_username'] ?? 'admin', $this->config['api_password'] ?? '', 5);

        for ($i = 1; $i <= $maxChannel; $i++) {
            $chUrl = $this->buildUrl("/cgi-bin/devVideoInput.cgi?action=getEncodeStatus&channel={$i}");
            $chResult = $this->httpGet($chUrl, $this->config['api_username'] ?? 'admin', $this->config['api_password'] ?? '', 3);

            $channels[] = [
                'channel_no'    => $i,
                'camera_name'   => "通道{$i}",
                'online'        => $chResult['success'],
                'record_status' => $chResult['success'] ? 'recording' : 'unknown',
                'resolution'    => '',
                'error_info'    => $chResult['success'] ? '' : '通道无响应',
            ];
        }
        return $channels;
    }

    public function getHddStatus(): array
    {
        $url = $this->buildUrl('/cgi-bin/storageDevice.cgi?action=getDeviceAllInfo');
        $result = $this->httpGet($url, $this->config['api_username'] ?? 'admin', $this->config['api_password'] ?? '');

        if ($result['success']) {
            $hdds = [];
            if (preg_match_all('/table\.Hdd\[(\d+)\].*?Status\s*=\s*(\w+)/is', $result['data'], $m1, PREG_SET_ORDER)) {
                foreach ($m1 as $i => $m) {
                    $hdds[] = [
                        'hdd_no'         => intval($m[1]) + 1,
                        'capacity_gb'    => 0,
                        'free_gb'        => 0,
                        'status'         => $m[2] === 'Normal' ? 'ok' : 'error',
                        'health_percent' => $m[2] === 'Normal' ? 90 : 30,
                    ];
                }
            }
            if ($hdds) return $hdds;
        }
        return [['hdd_no' => 1, 'capacity_gb' => 0, 'free_gb' => 0, 'status' => 'unknown', 'health_percent' => 0]];
    }

    public function fetchAlarms(): array
    {
        $url = $this->buildUrl('/cgi-bin/alarm.cgi?action=getAlarmState');
        $result = $this->httpGet($url, $this->config['api_username'] ?? 'admin', $this->config['api_password'] ?? '', 5);
        if (!$result['success']) return [];

        $alarms = [];
        $map = ['VideoLoss' => '视频丢失', 'StorageError' => '存储错误', 'DiskFull' => '硬盘已满',
                'DiskError' => '硬盘故障', 'RecordError' => '录像异常'];
        foreach ($map as $k => $d) {
            if (stripos($result['data'], $k) !== false) {
                $alarms[] = ['event_type' => $k, 'event_desc' => $d, 'timestamp' => date('Y-m-d H:i:s')];
            }
        }
        return $alarms;
    }

    private function buildUrl(string $path): string
    {
        $host = rtrim($this->config['api_url'] ?? '192.168.1.100', '/');
        $port = intval($this->config['api_port'] ?? 34567);
        if ($port !== 80) $host .= ':' . $port;
        return 'http://' . $host . $path;
    }
}
