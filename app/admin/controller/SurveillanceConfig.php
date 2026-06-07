<?php
/**
 * 监控配置管理 - 按小区配置录像机品牌和接口参数
 * 管理NVR录像机、摄像头在线状态、硬盘健康、异常告警
 */
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;
use app\extend\surveillance\SurveillanceFactory;

class SurveillanceConfig extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['sc.delete_time', 'null', '']];

        $cid = $this->getFilteredCommunityId();
        if ($cid === -1) {
            $where[] = ['sc.community_id', 'in', $this->request->boundCommunityIds];
        } elseif ($cid > 0) {
            $where[] = ['sc.community_id', '=', $cid];
        }

        $brand = $this->request->param('brand', '');
        if ($brand) $where[] = ['sc.brand', '=', $brand];

        $enabled = $this->request->param('enabled', '');
        if ($enabled !== '') $where[] = ['sc.enabled', '=', $enabled];

        $total = Db::name('surveillance_config')->alias('sc')->where($where)->count();
        $list = Db::name('surveillance_config')->alias('sc')
            ->leftJoin('community c', 'c.id = sc.community_id')
            ->field('sc.*, c.name as community_name')
            ->where($where)
            ->page($page, $limit)
            ->order('sc.community_id', 'asc')
            ->order('sc.id', 'asc')
            ->select();

        // 计算每个NVR下的设备统计
        foreach ($list as &$row) {
            $stats = Db::name('surveillance_device')
                ->where('config_id', $row['id'])
                ->whereNull('delete_time')
                ->field("COUNT(*) as total, SUM(CASE WHEN online=1 THEN 1 ELSE 0 END) as online_cnt")
                ->find();
            $row['device_total'] = $stats['total'] ?? 0;
            $row['device_online'] = $stats['online_cnt'] ?? 0;
            // 未处理告警数
            $row['alarm_count'] = Db::name('surveillance_event')
                ->where('config_id', $row['id'])
                ->where('handled', 0)
                ->count();
        }

        return $this->table($list, $total);
    }

    /** 获取品牌列表供前端选择 */
    public function brands()
    {
        return json_success(SurveillanceFactory::brands(), 'ok');
    }

    /** 获取某品牌支持的型号 */
    public function models()
    {
        $brand = $this->request->param('brand', 'hikvision');
        $config = ['brand' => $brand];
        $adapter = SurveillanceFactory::create($config);
        return json_success($adapter->models(), 'ok');
    }

    public function add()
    {
        $data = $this->request->post();
        if (empty($data['community_id'])) return json_error('请选择小区');
        if (empty($data['nvr_name'])) return json_error('请输入录像机名称');

        $this->validateCommunityAccess($data['community_id']);

        $insert = [
            'community_id'  => (int)$data['community_id'],
            'nvr_name'      => $data['nvr_name'],
            'brand'         => $data['brand'] ?? 'hikvision',
            'model'         => $data['model'] ?? '',
            'api_url'       => $data['api_url'] ?? '',
            'api_port'      => (int)($data['api_port'] ?? 80),
            'api_username'  => $data['api_username'] ?? 'admin',
            'api_password'  => $data['api_password'] ?? '',
            'channel_count' => (int)($data['channel_count'] ?? 4),
            'enabled'       => (int)($data['enabled'] ?? 1),
            'remark'        => $data['remark'] ?? '',
            'create_time'   => date('Y-m-d H:i:s'),
        ];

        $configId = Db::name('surveillance_config')->insertGetId($insert);

        // 自动创建对应通道的设备记录
        $this->initDevices($configId, $data['community_id'], $data['brand'] ?? 'hikvision', intval($data['channel_count'] ?? 4));

        return json_success(['id' => $configId], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        if (empty($data['id'])) return json_error('缺少ID');

        $row = Db::name('surveillance_config')->where('id', (int)$data['id'])->find();
        if (!$row) return json_error('记录不存在');
        $this->validateCommunityAccess($row['community_id']);

        $update = [];
        foreach (['nvr_name','brand','model','api_url','api_port','api_username','api_password','channel_count','enabled','remark'] as $f) {
            if (isset($data[$f])) $update[$f] = $data[$f];
        }
        $update['update_time'] = date('Y-m-d H:i:s');

        Db::name('surveillance_config')->where('id', (int)$data['id'])->update($update);

        // 通道数变更时自动补充设备
        $newCount = intval($data['channel_count'] ?? $row['channel_count']);
        $curCount = Db::name('surveillance_device')
            ->where('config_id', $row['id'])
            ->whereNull('delete_time')
            ->count();
        if ($newCount > $curCount) {
            $this->initDevices($row['id'], $row['community_id'], $row['brand'], $newCount, $curCount + 1);
        }

        return json_success([], '更新成功');
    }

    public function delete()
    {
        $id = (int)$this->request->post('id', 0);
        if ($id <= 0) return json_error('缺少ID');

        $row = Db::name('surveillance_config')->where('id', $id)->find();
        if (!$row) return json_error('记录不存在');
        $this->validateCommunityAccess($row['community_id']);

        Db::name('surveillance_config')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        Db::name('surveillance_device')->where('config_id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return json_success([], '删除成功');
    }

    /** 初始化通道设备 */
    private function initDevices(int $configId, int $communityId, string $brand, int $count, int $start = 1): void
    {
        $now = date('Y-m-d H:i:s');
        for ($i = $start; $i <= $count; $i++) {
            $exists = Db::name('surveillance_device')
                ->where('config_id', $configId)
                ->where('channel_no', $i)
                ->whereNull('delete_time')
                ->find();
            if ($exists) continue;

            Db::name('surveillance_device')->insert([
                'config_id'    => $configId,
                'community_id' => $communityId,
                'channel_no'   => $i,
                'camera_name'  => "通道{$i}",
                'camera_sn'    => '',
                'online'       => 0,
                'status'       => 'unknown',
                'create_time'  => $now,
            ]);
        }
    }

    // ==================== 功能接口 ====================

    /** 测试录像机连接 */
    public function testConnection()
    {
        $id = (int)$this->request->post('id', 0);
        if ($id <= 0) return json_error('缺少ID');

        $config = Db::name('surveillance_config')->where('id', $id)->find();
        if (!$config) return json_error('配置不存在');

        $adapter = SurveillanceFactory::create($config);
        $result = $adapter->testConnection();
        return $result['success'] ? json_success($result, '连接成功') : json_error($result['message'] ?? '连接失败');
    }

    /** 获取摄像头状态（主动拉取NVR） */
    public function cameraStatus()
    {
        $id = (int)$this->request->param('id', 0);
        if ($id <= 0) return json_error('缺少ID');

        $config = Db::name('surveillance_config')->where('id', $id)->find();
        if (!$config) return json_error('配置不存在');

        $adapter = SurveillanceFactory::create($config);
        $cameras = $adapter->getCameraStatus();

        // 更新设备表状态
        $now = date('Y-m-d H:i:s');
        foreach ($cameras as $cam) {
            $device = Db::name('surveillance_device')
                ->where('config_id', $id)
                ->where('channel_no', $cam['channel_no'])
                ->whereNull('delete_time')
                ->find();

            if ($device) {
                $up = [
                    'online'         => $cam['online'] ? 1 : 0,
                    'record_status'  => $cam['record_status'] ?? 'unknown',
                    'resolution'     => $cam['resolution'] ?? '',
                    'last_heartbeat' => $cam['online'] ? $now : $device['last_heartbeat'],
                    'error_info'     => $cam['error_info'] ?? '',
                    'status'         => $cam['online'] ? 'online' : 'offline',
                    'update_time'    => $now,
                ];
                Db::name('surveillance_device')->where('id', $device['id'])->update($up);

                // 检测状态变化，记录事件
                if ($device['online'] == 1 && !$cam['online']) {
                    $adapter->recordEvent('camera_offline', "{$cam['camera_name']} 已离线", $device['id']);
                } elseif ($device['online'] == 0 && $cam['online']) {
                    $adapter->recordEvent('camera_online', "{$cam['camera_name']} 已恢复在线", $device['id']);
                }
            }
        }

        return json_success($cameras, 'ok');
    }

    /** 获取硬盘状态 */
    public function hddStatus()
    {
        $id = (int)$this->request->param('id', 0);
        if ($id <= 0) return json_error('缺少ID');

        $config = Db::name('surveillance_config')->where('id', $id)->find();
        if (!$config) return json_error('配置不存在');

        $adapter = SurveillanceFactory::create($config);
        $hdds = $adapter->getHddStatus();

        // 检测硬盘异常
        foreach ($hdds as $hdd) {
            if ($hdd['status'] === 'error') {
                $adapter->recordEvent('hdd_error', "硬盘{$hdd['hdd_no']} 状态异常");
            } elseif ($hdd['status'] === 'full') {
                $adapter->recordEvent('hdd_full', "硬盘{$hdd['hdd_no']} 存储已满");
            }
        }

        return json_success($hdds, 'ok');
    }

    /** 拉取告警信息 */
    public function fetchAlarms()
    {
        $id = (int)$this->request->param('id', 0);
        if ($id <= 0) return json_error('缺少ID');

        $config = Db::name('surveillance_config')->where('id', $id)->find();
        if (!$config) return json_error('配置不存在');

        $adapter = SurveillanceFactory::create($config);
        $alarms = $adapter->fetchAlarms();

        return json_success($alarms, 'ok');
    }

    /** 一键巡检：检测所有启用的录像机 */
    public function patrolAll()
    {
        $configs = Db::name('surveillance_config')
            ->where('enabled', 1)
            ->whereNull('delete_time')
            ->select();

        $results = [];
        foreach ($configs as $config) {
            try {
                $adapter = SurveillanceFactory::create($config);
                $cameras = $adapter->getCameraStatus();
                $hdds = $adapter->getHddStatus();
                $alarms = $adapter->fetchAlarms();

                $now = date('Y-m-d H:i:s');
                $offlineCnt = 0;
                foreach ($cameras as $cam) {
                    $device = Db::name('surveillance_device')
                        ->where('config_id', $config['id'])
                        ->where('channel_no', $cam['channel_no'])
                        ->whereNull('delete_time')
                        ->find();
                    if ($device) {
                        $up = [
                            'online'         => $cam['online'] ? 1 : 0,
                            'record_status'  => $cam['record_status'] ?? 'unknown',
                            'last_heartbeat' => $cam['online'] ? $now : $device['last_heartbeat'],
                            'error_info'     => $cam['error_info'] ?? '',
                            'status'         => $cam['online'] ? 'online' : 'offline',
                            'update_time'    => $now,
                        ];
                        Db::name('surveillance_device')->where('id', $device['id'])->update($up);
                        if (!$cam['online']) $offlineCnt++;
                        if ($device['online'] == 1 && !$cam['online']) {
                            $adapter->recordEvent('camera_offline', "{$cam['camera_name']} 已离线", $device['id']);
                        }
                    }
                }

                foreach ($hdds as $hdd) {
                    if (in_array($hdd['status'], ['error', 'full'])) {
                        $type = $hdd['status'] === 'error' ? 'hdd_error' : 'hdd_full';
                        $adapter->recordEvent($type, "NVR {$config['nvr_name']} 硬盘{$hdd['hdd_no']}异常");
                    }
                }

                foreach ($alarms as $alarm) {
                    $adapter->recordEvent($alarm['event_type'], $alarm['event_desc']);
                }

                $results[] = [
                    'id'           => $config['id'],
                    'nvr_name'     => $config['nvr_name'],
                    'success'      => true,
                    'camera_total' => count($cameras),
                    'camera_offline' => $offlineCnt,
                    'hdd_errors'   => count(array_filter($hdds, fn($h) => in_array($h['status'], ['error', 'full']))),
                    'alarm_count'  => count($alarms),
                ];
            } catch (\Throwable $e) {
                $results[] = ['id' => $config['id'], 'nvr_name' => $config['nvr_name'], 'success' => false, 'error' => $e->getMessage()];
            }
        }

        return json_success($results, '巡检完成');
    }

    // ==================== 事件管理 ====================

    /** 获取事件列表 */
    public function eventList()
    {
        [$page, $limit] = $this->getPage();
        $where = [];

        $communityId = $this->request->param('community_id', '');
        if ($communityId !== '') $where[] = ['se.community_id', '=', (int)$communityId];

        $configId = $this->request->param('config_id', '');
        if ($configId !== '') $where[] = ['se.config_id', '=', (int)$configId];

        $eventType = $this->request->param('event_type', '');
        if ($eventType) $where[] = ['se.event_type', '=', $eventType];

        $handled = $this->request->param('handled', '');
        if ($handled !== '') $where[] = ['se.handled', '=', (int)$handled];

        $total = Db::name('surveillance_event')->alias('se')->where($where)->count();
        $list = Db::name('surveillance_event')->alias('se')
            ->leftJoin('surveillance_config sc', 'sc.id = se.config_id')
            ->leftJoin('surveillance_device sd', 'sd.id = se.device_id')
            ->leftJoin('community c', 'c.id = se.community_id')
            ->field('se.*, sc.nvr_name, sd.camera_name, c.name as community_name')
            ->where($where)
            ->page($page, $limit)
            ->order('se.id', 'desc')
            ->select();

        return $this->table($list, $total);
    }

    /** 标记事件已处理 */
    public function handleEvent()
    {
        $id = (int)$this->request->post('id', 0);
        if ($id <= 0) return json_error('缺少ID');

        $remark = $this->request->post('remark', '');

        Db::name('surveillance_event')->where('id', $id)->update([
            'handled'       => 1,
            'handled_by'    => $this->adminId,
            'handled_time'  => date('Y-m-d H:i:s'),
            'handle_remark' => $remark,
        ]);

        return json_success([], '已标记为处理');
    }

    /** 获取设备列表（某个录像机下的摄像头） */
    public function deviceList()
    {
        $configId = (int)$this->request->param('config_id', 0);
        if ($configId <= 0) return json_error('请指定录像机');

        $list = Db::name('surveillance_device')
            ->where('config_id', $configId)
            ->whereNull('delete_time')
            ->order('channel_no', 'asc')
            ->select();

        return json_success($list, 'ok');
    }

    /** 今日监控概览（Dashboard用） */
    public function todayStats()
    {
        $communityId = $this->request->param('community_id', '');

        $whereNvr = [['enabled', '=', 1], ['delete_time', 'null', '']];
        $whereDev = [['delete_time', 'null', '']];
        $whereEvt = [];

        if ($communityId !== '') {
            $whereNvr[] = ['community_id', '=', (int)$communityId];
            $whereDev[] = ['community_id', '=', (int)$communityId];
            $whereEvt[] = ['community_id', '=', (int)$communityId];
        }

        // 总录像机
        $nvrTotal = Db::name('surveillance_config')->where($whereNvr)->count();
        // 总摄像头
        $camTotal = Db::name('surveillance_device')->where($whereDev)->count();
        // 在线摄像头
        $camOnline = Db::name('surveillance_device')->where($whereDev)->where('online', 1)->count();
        // 今日事件（单表查询，无需表前缀）
        $whereEvt[] = ['create_time', '>=', date('Y-m-d 00:00:00')];
        $eventToday = Db::name('surveillance_event')->where($whereEvt)->count();
        // 未处理事件
        $eventPending = Db::name('surveillance_event')
            ->where($whereEvt)
            ->where('handled', 0)
            ->count();

        // 最近10条告警（JOIN查询必须加表前缀，避免 create_time 列名歧义）
        $whereEvtJoin = $whereEvt;
        foreach ($whereEvtJoin as &$cond) {
            if (isset($cond[0]) && !str_contains($cond[0], '.')) $cond[0] = 'se.' . $cond[0];
        }
        unset($cond);
        $recentAlarms = Db::name('surveillance_event')->alias('se')
            ->leftJoin('surveillance_config sc', 'sc.id = se.config_id')
            ->leftJoin('community c', 'c.id = se.community_id')
            ->field('se.*, sc.nvr_name, c.name as community_name')
            ->where($whereEvtJoin)
            ->order('se.id', 'desc')
            ->limit(10)
            ->select();

        // 按小区统计
        $byCommunity = Db::name('surveillance_device')
            ->alias('sd')
            ->leftJoin('community c', 'c.id = sd.community_id')
            ->field('c.name, COUNT(*) as total, SUM(CASE WHEN sd.online=1 THEN 1 ELSE 0 END) as online_cnt')
            ->whereNull('sd.delete_time')
            ->group('sd.community_id')
            ->select();

        return json_success([
            'nvr_total'      => $nvrTotal,
            'camera_total'   => $camTotal,
            'camera_online'  => $camOnline,
            'camera_offline' => $camTotal - $camOnline,
            'event_today'    => $eventToday,
            'event_pending'  => $eventPending,
            'recent_alarms'  => $recentAlarms,
            'by_community'   => $byCommunity,
        ], 'ok');
    }
}
