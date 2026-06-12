<?php
// 数字孪生3D场景 - IoT设备数据API（从数据库读取）
namespace app\api\controller;

use app\BaseController;
use think\facade\Db;

class IotData extends BaseController
{
    // 不需要鉴权
    protected $noAuth = ['getDevices'];

    // 3D场景编码 → 数据库小区编码映射
    private $codeMap = [
        'feicui'     => 'YG001',
        'yunqi'      => 'CY001',
        'zhongliang' => '0004',
        'shanshui'   => '001',
        'yifeng'     => 'YG001',
    ];

    public function getDevices()
    {
        $cid = $this->request->param('community', 'feicui');

        // 先映射到数据库小区编码
        $dbCode = $this->codeMap[$cid] ?? $cid;

        // 根据小区编码查找数据库中的小区
        $communityId = Db::name('community')
            ->where('code', $dbCode)
            ->where('status', 1)
            ->value('id');

        if (!$communityId) {
            return $this->error('未知小区');
        }

        // 查询该小区的所有在线/离线设备及其最新数据
        $devices = Db::name('iot_device')
            ->alias('d')
            ->leftJoin('iot_device_type dt', 'dt.id = d.device_type_id')
            ->leftJoin('iot_protocol p', 'p.id = d.protocol_id')
            ->where('d.community_id', $communityId)
            ->field('d.id, d.code, d.name, d.x, d.y, d.z, d.install_location, d.floor, d.building, d.battery_level, d.firmware_ver, d.last_online, d.status as device_online, dt.code as type_code, dt.name as type_name, dt.category, dt.unit, dt.y_height, p.name as protocol_name, p.code as protocol_code')
            ->select();

        // 批量获取最新数据：先取全部数据按id倒序，PHP中去重只留每个设备第一条
        $didList = array_column($devices->toArray(), 'id');
        $latestData = [];
        if (!empty($didList)) {
            $dataRows = Db::name('iot_device_data')
                ->whereIn('device_id', $didList)
                ->order('id', 'desc')
                ->field('device_id, raw_value, unit, is_online, device_status, alarm_msg, data_time')
                ->select();
            
            foreach ($dataRows as $row) {
                if (!isset($latestData[$row['device_id']])) {
                    $latestData[$row['device_id']] = $row;
                }
            }
        }

        // 组装输出
        $result = [];
        $stats = ['online' => 0, 'alarm' => 0, 'warning' => 0, 'offline' => 0];

        foreach ($devices as $dev) {
            $did = $dev['id'];
            $data = $latestData[$did] ?? null;

            // 状态判定
            if ($data && $data['is_online'] == 1) {
                $status = $data['device_status']; // normal/warning/alarm
            } else {
                $status = 'offline';
            }

            $stats[$status === 'offline' ? 'offline' : ($status === 'alarm' ? 'alarm' : ($status === 'warning' ? 'warning' : 'online'))]++;

            $item = [
                'id'          => $dev['code'],
                'type'        => $dev['type_code'],
                'typeName'    => $dev['type_name'] ?: $dev['type_code'],
                'category'    => $dev['category'] ?? '',
                'x'           => (float)$dev['x'],
                'z'           => (float)$dev['z'],
                'y'           => (float)($dev['y'] ?: $dev['y_height'] ?: 1.5),
                'status'      => $status,
                'value'       => $data['raw_value'] ?? ($dev['device_online'] ? '正常' : '离线'),
                'updated'     => $data['data_time'] ?? date('H:i:s'),
                'protocol'    => $dev['protocol_name'] ?? '',
                'battery'     => (int)($dev['battery_level'] ?? 100),
                'location'    => $dev['install_location'] ?? '',
                'firmware'    => $dev['firmware_ver'] ?? '',
            ];

            // 告警消息
            if ($status === 'alarm' && !empty($data['alarm_msg'])) {
                $item['alarm'] = $data['alarm_msg'];
            }

            // 格式化 updated 时间
            if (!empty($item['updated']) && strlen($item['updated']) > 8) {
                $item['updated'] = substr($item['updated'], 11, 5); // 只取 HH:ii
                if (empty($item['updated'])) $item['updated'] = date('H:i:s');
            }
            if (empty($item['updated'])) $item['updated'] = date('H:i:s');

            $result[] = $item;
        }

        return $this->success([
            'community' => $cid,
            'total'     => count($result),
            'online'    => $stats['online'],
            'alarm'     => $stats['alarm'],
            'warning'   => $stats['warning'],
            'offline'   => $stats['offline'],
            'devices'   => $result,
        ]);
    }
}
