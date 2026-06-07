<?php
/**
 * 道闸设备管理
 */
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class GateDevice extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['gd.delete_time', 'null', '']];

        $cid = $this->getFilteredCommunityId();
        if ($cid === -1) {
            $where[] = ['gd.community_id', 'in', $this->request->boundCommunityIds];
        } elseif ($cid > 0) {
            $where[] = ['gd.community_id', '=', $cid];
        }

        $brand = $this->request->param('brand', '');
        if ($brand) $where[] = ['gd.brand', '=', $brand];

        $online = $this->request->param('online', '');
        if ($online !== '') $where[] = ['gd.online', '=', $online];

        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['gd.device_name|gd.device_sn', 'like', "%{$keyword}%"];

        $total = Db::name('gate_device')->alias('gd')->where($where)->count();
        $list = Db::name('gate_device')->alias('gd')
            ->leftJoin('community c', 'c.id = gd.community_id')
            ->leftJoin('gate_config gc', 'gc.id = gd.config_id')
            ->field('gd.*, c.name as community_name, gc.entrance_name as config_entrance')
            ->where($where)
            ->page($page, $limit)
            ->order('gd.id', 'desc')
            ->select();

        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        if (empty($data['community_id'])) return json_error('请选择小区');
        if (empty($data['device_name'])) return json_error('请输入设备名称');
        $this->validateCommunityAccess($data['community_id']);

        $insert = [
            'config_id'    => (int)($data['config_id'] ?? 0),
            'community_id' => (int)$data['community_id'],
            'entrance_name'=> $data['entrance_name'] ?? '',
            'device_sn'    => $data['device_sn'] ?? '',
            'device_name'  => $data['device_name'],
            'brand'        => $data['brand'] ?? '',
            'ip_address'   => $data['ip_address'] ?? '',
            'port'         => (int)($data['port'] ?? 80),
            'remark'       => $data['remark'] ?? '',
            'create_time'  => date('Y-m-d H:i:s'),
        ];
        Db::name('gate_device')->insert($insert);
        return json_success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        if (empty($data['id'])) return json_error('缺少ID');
        $row = Db::name('gate_device')->where('id', (int)$data['id'])->find();
        if (!$row) return json_error('记录不存在');
        $this->validateCommunityAccess($row['community_id']);

        $update = [];
        foreach (['config_id','entrance_name','device_sn','device_name','brand','ip_address','port','remark'] as $f) {
            if (isset($data[$f])) $update[$f] = $data[$f];
        }
        $update['update_time'] = date('Y-m-d H:i:s');
        Db::name('gate_device')->where('id', (int)$data['id'])->update($update);
        return json_success([], '更新成功');
    }

    public function delete()
    {
        $id = (int)$this->request->post('id', 0);
        if ($id <= 0) return json_error('缺少ID');
        $row = Db::name('gate_device')->where('id', $id)->find();
        if (!$row) return json_error('记录不存在');
        $this->validateCommunityAccess($row['community_id']);
        Db::name('gate_device')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return json_success([], '删除成功');
    }

    /** 获取设备通行事件列表（最近50条） */
    public function events()
    {
        $deviceId = (int)$this->request->param('device_id', 0);
        $communityId = (int)$this->request->param('community_id', 0);

        $where = [];
        if ($deviceId > 0) $where[] = ['device_id', '=', $deviceId];
        if ($communityId > 0) $where[] = ['community_id', '=', $communityId];

        $cid = $this->getFilteredCommunityId();
        if ($cid === -1) {
            $where[] = ['community_id', 'in', $this->request->boundCommunityIds];
        } elseif ($cid > 0) {
            $where[] = ['community_id', '=', $cid];
        }

        $list = Db::name('gate_event')
            ->where($where)
            ->order('id', 'desc')
            ->limit(50)
            ->select();

        return json_success($list, 'ok');
    }

    /** 今日统计 */
    public function todayStats()
    {
        $communityId = (int)$this->request->param('community_id', 0);
        $today = date('Y-m-d');

        $where = [['recognize_time', '>=', $today . ' 00:00:00']];
        if ($communityId > 0) $where[] = ['community_id', '=', $communityId];

        $total = Db::name('gate_event')->where($where)->count();
        $inCount = Db::name('gate_event')->where($where)->where('direction', 'in')->count();
        $outCount = Db::name('gate_event')->where($where)->where('direction', 'out')->count();

        $devices = Db::name('gate_device')
            ->where(function ($q) use ($communityId) {
                if ($communityId > 0) $q->where('community_id', $communityId);
            })
            ->whereNull('delete_time')
            ->select();

        $online = 0;
        foreach ($devices as $d) {
            if ($d['online'] == 1 && $d['last_heartbeat'] && (time() - strtotime($d['last_heartbeat']) < 300)) {
                $online++;
            }
        }

        return json_success([
            'total_events' => $total,
            'in_count'     => $inCount,
            'out_count'    => $outCount,
            'device_total' => count($devices),
            'device_online'=> $online,
        ], 'ok');
    }
}
