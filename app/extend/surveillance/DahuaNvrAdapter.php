<?php
/**
 * 大华 NVR 适配器
 * 协议: HTTP JSON API (Digest Auth)
 * 常用型号: NVR4/5/6 系列, XVR 系列
 */
namespace app\extend\surveillance;

class DahuaNvrAdapter extends SurveillanceAdapter
{
    protected $brand = 'dahua';

    public function name(): string { return '大华'; }

    public function models(): array
    {
        return [
            ['model' => 'NVR4104HS-4KS2', 'description' => '4路入门级NVR，H.265压缩'],
            ['model' => 'NVR5208-8P-4KS2E', 'description' => '8路PoE中端NVR，带AI检测'],
            ['model' => 'NVR5416-16P-4KS2E', 'description' => '16路高端NVR，人脸识别'],
            ['model' => 'NVR5864-4KS2', 'description' => '64路旗舰级NVR，热备双电源'],
            ['model' => 'XVR5104HS-X1', 'description' => '4路XVR，支持模拟/网络混合接入'],
            ['model' => 'NVR616-128-4KS2', 'description' => '128路超大型NVR，支持RAID'],
        ];
    }

    public function testConnection(): array
    {
        $url = $this->buildUrl('/cgi-bin/magicBox.cgi?action=getSoftwareVersion');
        $result = $this->httpGet($url, $this->config['api_username'] ?? 'admin', $this->config['api_password'] ?? '');

        if (!$result['success']) {
            return ['success' => false, 'message' => '连接失败: ' . $result['message']];
        }

        return [
            'success' => true,
            'message' => '连接成功',
            'device_info' => [
                'device_name' => '大华NVR',
                'model'       => $this->config['model'] ?? '',
                'firmware'    => trim(str_replace(['version=', "\n"], '', $result['data'] ?? '')),
                'protocol'    => 'HTTP API',
            ],
        ];
    }

    public function getCameraStatus(): array
    {
        $url = $this->buildUrl('/cgi-bin/devVideoInput.cgi?action=getEncodeInfo');
        $result = $this->httpGet($url, $this->config['api_username'] ?? 'admin', $this->config['api_password'] ?? '');
        $maxChannel = intval($this->config['channel_count'] ?? 4);

        $channels = [];
        for ($i = 1; $i <= $maxChannel; $i++) {
            // 检查通道是否有视频信号
            $signalUrl = $this->buildUrl("/cgi-bin/devVideoInput.cgi?action=getSignal&channel={$i}");
            $sigResult = $this->httpGet($signalUrl, $this->config['api_username'] ?? 'admin', $this->config['api_password'] ?? '', 5);

            $channels[] = [
                'channel_no'    => $i,
                'camera_name'   => "通道{$i}",
                'online'        => $sigResult['success'],
                'record_status' => $result['success'] ? 'recording' : 'unknown',
                'resolution'    => '',
                'error_info'    => $sigResult['success'] ? '' : '无视频信号',
            ];
        }
        return $channels;
    }

    public function getHddStatus(): array
    {
        $url = $this->buildUrl('/cgi-bin/storageDevice.cgi?action=getDeviceAllInfo');
        $result = $this->httpGet($url, $this->config['api_username'] ?? 'admin', $this->config['api_password'] ?? '');

        if (!$result['success']) {
            return [['hdd_no' => 1, 'capacity_gb' => 0, 'free_gb' => 0, 'status' => 'unknown', 'health_percent' => 0]];
        }

        // 解析大华HDD返回格式
        $data = $result['data'];
        $hdds = [];
        if (preg_match_all('/table\.Hdd\[(\d+)\]\.(.*?)\s*=\s*(.*)/s', $data, $matches, PREG_SET_ORDER)) {
            $hddInfo = [];
            foreach ($matches as $m) {
                $hddInfo[$m[1]][$m[2]] = trim($m[3]);
            }
            foreach ($hddInfo as $idx => $info) {
                $total = floatval($info['Detail[0].TotalBytes'] ?? 0) / (1024 * 1024 * 1024);
                $status = ($info['Status'] ?? '') === 'Normal' ? 'ok' : 'error';
                $hdds[] = [
                    'hdd_no'         => intval($idx) + 1,
                    'capacity_gb'    => round($total, 1),
                    'free_gb'        => 0,
                    'status'         => $status,
                    'health_percent' => $status === 'ok' ? 95 : 30,
                ];
            }
        }
        return $hdds ?: [['hdd_no' => 1, 'capacity_gb' => 0, 'free_gb' => 0, 'status' => 'unknown', 'health_percent' => 0]];
    }

    public function fetchAlarms(): array
    {
        $url = $this->buildUrl('/cgi-bin/alarm.cgi?action=getAlarmInfo');
        $result = $this->httpGet($url, $this->config['api_username'] ?? 'admin', $this->config['api_password'] ?? '', 5);
        return $result['success'] ? $this->parseAlarms($result['data']) : [];
    }

    private function parseAlarms(string $data): array
    {
        $alarms = [];
        $map = [
            'VideoLoss'    => '视频丢失',
            'VideoBlind'   => '视频遮挡',
            'StorageError' => '存储错误',
            'StorageNotExist' => '硬盘不存在',
            'DiskFull'     => '硬盘已满',
            'DiskError'    => '硬盘故障',
            'RecordError'  => '录像异常',
            'NetAbort'     => '网络断开',
        ];
        foreach ($map as $key => $desc) {
            if (strpos($data, $key) !== false) {
                $alarms[] = ['event_type' => $key, 'event_desc' => $desc, 'timestamp' => date('Y-m-d H:i:s')];
            }
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
