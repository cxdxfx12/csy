<?php
/**
 * 天地伟业 NVR 适配器
 * 协议: ONVIF + HTTP API (Basic/Digest Auth)
 * 常用型号: TC-NR 系列
 */
namespace app\extend\surveillance;

class TiandyNvrAdapter extends SurveillanceAdapter
{
    protected $brand = 'tiandy';

    public function name(): string { return '天地伟业'; }

    public function models(): array
    {
        return [
            ['model' => 'TC-NR1004-S2', 'description' => '4路入门级NVR，H.265+编码'],
            ['model' => 'TC-NR1008-S4', 'description' => '8路中端NVR，支持智能分析'],
            ['model' => 'TC-NR1016-S8', 'description' => '16路高端NVR，人脸/车牌识别'],
            ['model' => 'TC-NR2064', 'description' => '64路旗舰级NVR，支持RAID'],
            ['model' => 'TC-NR30128', 'description' => '128路超大型NVR，支持集群'],
        ];
    }

    public function testConnection(): array
    {
        $url = $this->buildUrl('/cgi-bin/getDevInfo.cgi');
        $result = $this->httpGet($url, $this->config['api_username'] ?? 'admin', $this->config['api_password'] ?? '');

        if (!$result['success']) {
            return ['success' => false, 'message' => '连接失败: ' . $result['message']];
        }
        return [
            'success' => true,
            'message' => '连接成功',
            'device_info' => [
                'device_name' => '天地伟业NVR',
                'model'       => $this->config['model'] ?? '',
                'firmware'    => '',
                'protocol'    => 'HTTP/ONVIF',
            ],
        ];
    }

    public function getCameraStatus(): array
    {
        $maxChannel = intval($this->config['channel_count'] ?? 4);
        $channels = [];

        for ($i = 1; $i <= $maxChannel; $i++) {
            $url = $this->buildUrl("/cgi-bin/getChannelStatus.cgi?channel={$i}");
            $result = $this->httpGet($url, $this->config['api_username'] ?? 'admin', $this->config['api_password'] ?? '', 5);

            preg_match('/online="(\d+)"/i', $result['data'] ?? '', $on);
            preg_match('/record="(\d+)"/i', $result['data'] ?? '', $rec);
            preg_match('/name="([^"]*)"/i', $result['data'] ?? '', $nm);

            $channels[] = [
                'channel_no'    => $i,
                'camera_name'   => $nm[1] ?? "通道{$i}",
                'online'        => ($on[1] ?? '0') === '1',
                'record_status' => ($rec[1] ?? '0') === '1' ? 'recording' : 'stopped',
                'resolution'    => '',
                'error_info'    => ($on[1] ?? '0') === '0' ? '摄像头离线' : '',
            ];
        }
        return $channels;
    }

    public function getHddStatus(): array
    {
        $url = $this->buildUrl('/cgi-bin/getDiskInfo.cgi');
        $result = $this->httpGet($url, $this->config['api_username'] ?? 'admin', $this->config['api_password'] ?? '');

        if (!$result['success']) {
            return [['hdd_no' => 1, 'capacity_gb' => 0, 'free_gb' => 0, 'status' => 'unknown', 'health_percent' => 0]];
        }

        $hdds = [];
        if (preg_match_all('/disk\[(\d+)\]/i', $result['data'], $matches)) {
            foreach ($matches[1] as $idx) {
                preg_match("/capacity\[{$idx}\]=\"(\d+)\"/i", $result['data'], $cap);
                preg_match("/free\[{$idx}\]=\"(\d+)\"/i", $result['data'], $free);
                preg_match("/status\[{$idx}\]=\"(\d+)\"/i", $result['data'], $sts);

                $hdds[] = [
                    'hdd_no'         => intval($idx) + 1,
                    'capacity_gb'    => round(intval($cap[1] ?? 0) / 1024, 1),
                    'free_gb'        => round(intval($free[1] ?? 0) / 1024, 1),
                    'status'         => ($sts[1] ?? '0') === '1' ? 'ok' : 'error',
                    'health_percent' => ($sts[1] ?? '0') === '1' ? 95 : 40,
                ];
            }
        }
        return $hdds ?: [['hdd_no' => 1, 'capacity_gb' => 0, 'free_gb' => 0, 'status' => 'unknown', 'health_percent' => 0]];
    }

    public function fetchAlarms(): array
    {
        $url = $this->buildUrl('/cgi-bin/getAlarmInfo.cgi');
        $result = $this->httpGet($url, $this->config['api_username'] ?? 'admin', $this->config['api_password'] ?? '', 5);
        if (!$result['success']) return [];

        $alarms = [];
        $types = ['VideoLoss' => '视频丢失', 'DiskError' => '硬盘故障', 'RecordFail' => '录像失败',
                  'DiskFull' => '硬盘已满', 'NetDisconnect' => '网络断开'];
        foreach ($types as $key => $desc) {
            if (stripos($result['data'], $key) !== false) {
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
