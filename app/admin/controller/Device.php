<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;
use app\extend\device\DeviceFactory;

class Device extends BaseAdmin
{
    public function listAll()
    {
        $cid = $this->getFilteredCommunityId();

        // 联合三表列出所有设备（下拉选择用）
        $unionSQL = "
            (SELECT id, community_id, device_code, device_name, device_type, ref_type, ref_id, 'device' as source
             FROM ds_device WHERE delete_time IS NULL)
            UNION ALL
            (SELECT id, community_id, device_sn as device_code, device_name, '道闸设备' as device_type, 'gate' as ref_type, config_id as ref_id, 'gate' as source
             FROM ds_gate_device WHERE delete_time IS NULL)
            UNION ALL
            (SELECT id, community_id, device_sn as device_code, device_name, '门禁控制器' as device_type, 'access' as ref_type, config_id as ref_id, 'access' as source
             FROM ds_access_device WHERE delete_time IS NULL)
        ";

        $pdo = Db::name('device')->getPdo();
        $where = [];
        $binds = [];
        if ($cid === -1 && !empty($this->request->boundCommunityIds)) {
            $holders = [];
            foreach ($this->request->boundCommunityIds as $i => $bid) {
                $holders[] = '?';
                $binds[] = intval($bid);
            }
            $where[] = 'u.community_id IN (' . implode(',', $holders) . ')';
        } elseif ($cid > 0) {
            $where[] = 'u.community_id = ?';
            $binds[] = intval($cid);
        }
        $whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT u.* FROM ({$unionSQL}) as u {$whereSQL} ORDER BY u.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($binds);
        return $this->success($stmt->fetchAll());
    }

    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $cid = $this->getFilteredCommunityId();
        $keyword = $this->request->param('keyword', '');
        $deviceType = $this->request->param('device_type', '');
        $status = $this->request->param('status', '');

        // 构建 UNION ALL 子查询：合并 ds_device, ds_gate_device, ds_access_device
        // 三张独立表的设备在这里集中显示，字段统一映射
        $unionSQL = "
            (SELECT id, community_id, device_type, device_code, device_name, location,
                    manufacturer, model, protocol, ip_address, port, serial_no, auth_key,
                    ref_type, ref_id, status, last_heartbeat, remark, create_time, update_time,
                    'device' as source
             FROM ds_device WHERE delete_time IS NULL)
            UNION ALL
            (SELECT id, community_id, '道闸设备' as device_type, device_sn as device_code, device_name, '' as location,
                    brand as manufacturer, '' as model, 'http' as protocol, '' as ip_address, 0 as port,
                    device_sn as serial_no, '' as auth_key, 'gate' as ref_type, config_id as ref_id,
                    online as status, last_heartbeat, '' as remark, create_time, update_time,
                    'gate' as source
             FROM ds_gate_device WHERE delete_time IS NULL)
            UNION ALL
            (SELECT id, community_id, '门禁控制器' as device_type, device_sn as device_code, device_name, IFNULL(door_name,'') as location,
                    brand as manufacturer, '' as model, 'http' as protocol, IFNULL(ip_address,'') as ip_address, IFNULL(port,80) as port,
                    device_sn as serial_no, '' as auth_key, 'access' as ref_type, config_id as ref_id,
                    online as status, last_heartbeat, IFNULL(remark,'') as remark, create_time, update_time,
                    'access' as source
             FROM ds_access_device WHERE delete_time IS NULL)
        ";

        // 构建 WHERE 条件（位置占位符 ? 防注入）
        $where = [];
        $binds = [];

        if ($cid === -1 && !empty($this->request->boundCommunityIds)) {
            $holders = [];
            foreach ($this->request->boundCommunityIds as $bid) {
                $holders[] = '?';
                $binds[] = intval($bid);
            }
            $where[] = 'u.community_id IN (' . implode(',', $holders) . ')';
        } elseif ($cid > 0) {
            $where[] = 'u.community_id = ?';
            $binds[] = intval($cid);
        }
        if ($keyword) {
            $where[] = '(u.device_name LIKE ? OR u.device_code LIKE ? OR u.location LIKE ? OR u.remark LIKE ?)';
            $kw = "%{$keyword}%";
            $binds[] = $kw;
            $binds[] = $kw;
            $binds[] = $kw;
            $binds[] = $kw;
        }
        if ($deviceType !== '') {
            $where[] = 'u.device_type = ?';
            $binds[] = $deviceType;
        }
        if ($status !== '') {
            $where[] = 'u.status = ?';
            $binds[] = intval($status);
        }

        $whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $pdo = Db::name('device')->getPdo();

        // 计数
        $countSQL = "SELECT COUNT(*) as cnt FROM ({$unionSQL}) as u {$whereSQL}";
        $stmt = $pdo->prepare($countSQL);
        $stmt->execute($binds);
        $total = (int) $stmt->fetchColumn();

        // 列表查询（关联小区名）
        $offset = ($page - 1) * $limit;
        $listSQL = "
            SELECT u.*, c.name as community_name
            FROM ({$unionSQL}) as u
            LEFT JOIN ds_community c ON c.id = u.community_id
            {$whereSQL}
            ORDER BY u.create_time DESC
            LIMIT {$offset}, {$limit}
        ";
        $stmt = $pdo->prepare($listSQL);
        $stmt->execute($binds);
        $list = $stmt->fetchAll();

        return $this->success(['list' => $list, 'total' => $total], '获取成功');
    }

    /**
     * 获取支持的设备类型列表（含协议选项）
     */
    public function types()
    {
        return $this->success(DeviceFactory::types());
    }

    /**
     * 测试设备连接
     */
    public function testConnection()
    {
        $id = intval($this->request->post('id', 0));
        $device = Db::name('device')->where('id', $id)->whereNull('delete_time')->find();
        if (empty($device)) return $this->error('设备不存在');

        try {
            $adapter = DeviceFactory::create($device);
            $result = $adapter->testConnection();

            // 更新心跳时间
            if ($result['success']) {
                Db::name('device')->where('id', $id)->update([
                    'last_heartbeat' => date('Y-m-d H:i:s'),
                    'status'         => 1,
                ]);
            }

            return $this->success($result);
        } catch (\Exception $e) {
            return $this->error('测试失败: ' . $e->getMessage());
        }
    }

    /**
     * 查询设备实时状态
     */
    public function getStatus()
    {
        $id = intval($this->request->param('id', 0));
        $device = Db::name('device')->where('id', $id)->whereNull('delete_time')->find();
        if (empty($device)) return $this->error('设备不存在');

        try {
            // 如果有关联业务设备，优先用业务适配器
            $adapter = null;
            if (!empty($device['ref_type']) && !empty($device['ref_id'])) {
                $businessAdapter = DeviceFactory::createBusiness($device['ref_type'], $device['ref_id'], $device['community_id']);
            }

            if (!$adapter) {
                $adapter = DeviceFactory::create($device);
            }
            $result = $adapter->getStatus();
            return $this->success($result);
        } catch (\Exception $e) {
            return $this->error('状态查询失败: ' . $e->getMessage());
        }
    }

    /**
     * 远程操作
     */
    public function remoteAction()
    {
        $id = intval($this->request->post('id', 0));
        $action = $this->request->post('action', '');
        $params = $this->request->post('params', []);

        if (empty($action)) return $this->error('请指定操作');

        $device = Db::name('device')->where('id', $id)->whereNull('delete_time')->find();
        if (empty($device)) return $this->error('设备不存在');

        try {
            // 优先使用关联的业务适配器
            $adapter = null;
            if (!empty($device['ref_type']) && !empty($device['ref_id'])) {
                $adapter = DeviceFactory::createBusiness($device['ref_type'], $device['ref_id'], $device['community_id']);
            }
            if (!$adapter) {
                $adapter = DeviceFactory::create($device);
            }
            $result = $adapter->remoteAction($action, is_array($params) ? $params : []);

            return $this->success($result);
        } catch (\Exception $e) {
            return $this->error('操作失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取某设备类型可用的远程操作列表（供前端动态渲染按钮）
     */
    public function actions()
    {
        $type = $this->request->param('type', '');

        $actionMap = [
            'gateway'      => ['label'=>'智能网关','actions'=>[
                ['key'=>'restart','name'=>'重启网关','icon'=>'RefreshRight'],
                ['key'=>'sync_config','name'=>'同步配置','icon'=>'Sync'],
                ['key'=>'clear_cache','name'=>'清除缓存','icon'=>'Delete'],
                ['key'=>'firmware_upgrade','name'=>'固件升级','icon'=>'Upload','need_param'=>true,'param_label'=>'固件URL']
            ]],
            'access_ctrl'  => ['label'=>'门禁控制器','actions'=>[
                ['key'=>'open','name'=>'远程开门','icon'=>'Unlock'],
                ['key'=>'lock','name'=>'锁定','icon'=>'Lock'],
                ['key'=>'unlock','name'=>'解锁','icon'=>'Unlock'],
                ['key'=>'sync_whitelist','name'=>'同步白名单','icon'=>'UserFilled'],
                ['key'=>'reboot','name'=>'重启','icon'=>'RefreshRight'],
                ['key'=>'get_log','name'=>'获取日志','icon'=>'Document']
            ]],
            'camera'       => ['label'=>'监控摄像头','actions'=>[
                ['key'=>'ptz_up','name'=>'云台上','icon'=>'Top'],
                ['key'=>'ptz_down','name'=>'云台下','icon'=>'Bottom'],
                ['key'=>'ptz_left','name'=>'云台左','icon'=>'Back'],
                ['key'=>'ptz_right','name'=>'云台右','icon'=>'Right'],
                ['key'=>'zoom_in','name'=>'放大','icon'=>'ZoomIn'],
                ['key'=>'zoom_out','name'=>'缩小','icon'=>'ZoomOut'],
                ['key'=>'snapshot','name'=>'抓拍','icon'=>'Camera'],
                ['key'=>'reboot','name'=>'重启','icon'=>'RefreshRight'],
                ['key'=>'get_stream_url','name'=>'获取流地址','icon'=>'VideoPlay']
            ]],
            'sensor'       => ['label'=>'环境传感器','actions'=>[
                ['key'=>'read_data','name'=>'读取数据','icon'=>'DataLine'],
                ['key'=>'calibrate','name'=>'校准','icon'=>'Aim'],
                ['key'=>'set_threshold','name'=>'设置阈值','icon'=>'Setting','need_param'=>true,'param_form'=>['temp_high'=>'高温阈值','temp_low'=>'低温阈值','pm25_high'=>'PM2.5告警值']],
                ['key'=>'get_history','name'=>'历史数据','icon'=>'Clock']
            ]],
            'fire'         => ['label'=>'消防设备','actions'=>[
                ['key'=>'reset_alarm','name'=>'复位报警','icon'=>'Warning'],
                ['key'=>'test_device','name'=>'自检','icon'=>'CircleCheck'],
                ['key'=>'silence','name'=>'消音','icon'=>'Mute'],
                ['key'=>'get_status','name'=>'查看状态','icon'=>'View']
            ]],
            'gate'         => ['label'=>'道闸设备','actions'=>[
                ['key'=>'open_in','name'=>'开闸(入场)','icon'=>'ArrowDownBold'],
                ['key'=>'open_out','name'=>'开闸(出场)','icon'=>'ArrowUpBold'],
                ['key'=>'stop','name'=>'停止','icon'=>'VideoPause'],
                ['key'=>'reset','name'=>'复位','icon'=>'RefreshRight'],
                ['key'=>'sync_whitelist','name'=>'同步白名单','icon'=>'List']
            ]],
            'charger'      => ['label'=>'充电桩','actions'=>[
                ['key'=>'start','name'=>'开始充电','icon'=>'VideoPlay'],
                ['key'=>'stop','name'=>'停止充电','icon'=>'VideoPause'],
                ['key'=>'unlock','name'=>'解锁枪头','icon'=>'Unlock','need_param'=>true,'param_label'=>'连接器编号'],
                ['key'=>'reset','name'=>'重启','icon'=>'RefreshRight'],
                ['key'=>'get_meter_value','name'=>'读计量','icon'=>'Odometer'],
                ['key'=>'set_price','name'=>'设电价','icon'=>'Money','need_param'=>true,'param_label'=>'元/kWh']
            ]],
            'water_meter'  => ['label'=>'水表采集器','actions'=>[
                ['key'=>'read_meter','name'=>'读取用量','icon'=>'Odometer'],
                ['key'=>'open_valve','name'=>'开水阀','icon'=>'Open'],
                ['key'=>'close_valve','name'=>'关水阀','icon'=>'Close'],
                ['key'=>'get_history','name'=>'历史数据','icon'=>'Clock']
            ]],
            'power_meter'  => ['label'=>'电表采集器','actions'=>[
                ['key'=>'read_meter','name'=>'读取电量','icon'=>'Odometer'],
                ['key'=>'breaker_on','name'=>'合闸通电','icon'=>'SwitchButton'],
                ['key'=>'breaker_off','name'=>'跳闸断电','icon'=>'SwitchButton'],
                ['key'=>'get_history','name'=>'历史数据','icon'=>'Clock']
            ]],
            'elevator'     => ['label'=>'电梯控制器','actions'=>[
                ['key'=>'call_floor','name'=>'召唤楼层','icon'=>'Building','need_param'=>true,'param_label'=>'目标楼层'],
                ['key'=>'open_door','name'=>'开门','icon'=>'Open'],
                ['key'=>'close_door','name'=>'关门','icon'=>'Close'],
                ['key'=>'set_mode','name'=>'切换模式','icon'=>'Setting','need_param'=>true,'param_options'=>['normal'=>'正常模式','inspection'=>'检修模式','firefighter'=>'消防模式']],
                ['key'=>'emergency_stop','name'=>'急停','icon'=>'WarningFilled','danger'=>true]
            ]],
            'intercom'     => ['label'=>'可视对讲','actions'=>[
                ['key'=>'call','name'=>'发起呼叫','icon'=>'PhoneFilled'],
                ['key'=>'open_door','name'=>'远程开锁','icon'=>'Unlock'],
                ['key'=>'send_message','name'=>'发送消息','icon'=>'Message','need_param'=>true,'param_label'=>'消息内容']
            ]],
            'face_device'  => ['label'=>'人脸识别终端','actions'=>[
                ['key'=>'capture','name'=>'抓拍','icon'=>'Camera'],
                ['key'=>'open_door','name'=>'开门','icon'=>'Unlock'],
                ['key'=>'sync_faces','name'=>'同步人脸库','icon'=>'UserFilled'],
                ['key'=>'reboot','name'=>'重启','icon'=>'RefreshRight']
            ]]
        ];

        if (empty($type)) {
            return $this->success($actionMap);
        }
        $typeKey = DeviceFactory::typeToKey($type);
        return $this->success($actionMap[$typeKey] ?? []);
    }

    public function add()
    {
        $data = $this->request->post();
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['update_time'] = date('Y-m-d H:i:s');
        Db::name('device')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        $data['update_time'] = date('Y-m-d H:i:s');
        Db::name('device')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('device')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }
}
