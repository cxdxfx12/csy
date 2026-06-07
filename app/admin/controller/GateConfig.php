<?php
/**
 * 道闸配置管理 - 按小区配置道闸品牌和接口参数
 */
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;
use app\extend\gate\GateFactory;

class GateConfig extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['gc.delete_time', 'null', '']];

        $cid = $this->getFilteredCommunityId();
        if ($cid === -1) {
            $where[] = ['gc.community_id', 'in', $this->request->boundCommunityIds];
        } elseif ($cid > 0) {
            $where[] = ['gc.community_id', '=', $cid];
        }

        $brand = $this->request->param('brand', '');
        if ($brand) $where[] = ['gc.brand', '=', $brand];

        $enabled = $this->request->param('enabled', '');
        if ($enabled !== '') $where[] = ['gc.enabled', '=', $enabled];

        $total = Db::name('gate_config')->alias('gc')->where($where)->count();
        $list = Db::name('gate_config')->alias('gc')
            ->leftJoin('community c', 'c.id = gc.community_id')
            ->field('gc.*, c.name as community_name')
            ->where($where)
            ->page($page, $limit)
            ->order('gc.community_id', 'asc')
            ->order('gc.id', 'asc')
            ->select();

        return $this->table($list, $total);
    }

    /** 获取品牌列表供前端选择 */
    public function brands()
    {
        return json_success(GateFactory::brands(), 'ok');
    }

    /** 获取某小区的所有配置 */
    public function listByCommunity()
    {
        $communityId = (int)$this->request->param('community_id', 0);
        if ($communityId <= 0) return json_error('请选择小区');

        $this->validateCommunityAccess($communityId);

        $list = Db::name('gate_config')
            ->where('community_id', $communityId)
            ->whereNull('delete_time')
            ->select();

        return json_success($list, 'ok');
    }

    public function add()
    {
        $data = $this->request->post();
        if (empty($data['community_id'])) return json_error('请选择小区');
        if (empty($data['entrance_name'])) return json_error('请输入出入口名称');

        $this->validateCommunityAccess($data['community_id']);

        $insert = [
            'community_id'  => (int)$data['community_id'],
            'entrance_name' => $data['entrance_name'],
            'brand'         => $data['brand'] ?? 'generic',
            'direction'     => $data['direction'] ?? 'in',
            'api_url'       => $data['api_url'] ?? '',
            'api_token'     => $data['api_token'] ?? '',
            'api_username'  => $data['api_username'] ?? '',
            'device_sn'     => $data['device_sn'] ?? '',
            'channel_no'    => (int)($data['channel_no'] ?? 1),
            'enabled'       => (int)($data['enabled'] ?? 1),
            'remark'        => $data['remark'] ?? '',
            'create_time'   => date('Y-m-d H:i:s'),
        ];

        Db::name('gate_config')->insert($insert);
        return json_success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        if (empty($data['id'])) return json_error('缺少ID');

        $row = Db::name('gate_config')->where('id', (int)$data['id'])->find();
        if (!$row) return json_error('记录不存在');
        $this->validateCommunityAccess($row['community_id']);

        $update = [];
        foreach (['entrance_name','brand','direction','api_url','api_token','api_username','device_sn','channel_no','enabled','remark'] as $f) {
            if (isset($data[$f])) $update[$f] = $data[$f];
        }
        $update['update_time'] = date('Y-m-d H:i:s');

        Db::name('gate_config')->where('id', (int)$data['id'])->update($update);
        return json_success([], '更新成功');
    }

    public function delete()
    {
        $id = (int)$this->request->post('id', 0);
        if ($id <= 0) return json_error('缺少ID');

        $row = Db::name('gate_config')->where('id', $id)->find();
        if (!$row) return json_error('记录不存在');
        $this->validateCommunityAccess($row['community_id']);

        Db::name('gate_config')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return json_success([], '删除成功');
    }

    /** 测试连接 */
    public function testConnection()
    {
        $id = (int)$this->request->post('id', 0);
        if ($id <= 0) return json_error('缺少ID');

        $config = Db::name('gate_config')->where('id', $id)->find();
        if (!$config) return json_error('配置不存在');

        $adapter = GateFactory::create($config);
        $status = $adapter->getStatus();
        return json_success($status, 'ok');
    }

    /** 远程开闸 */
    public function remoteOpen()
    {
        $id = (int)$this->request->post('id', 0);
        if ($id <= 0) return json_error('缺少ID');

        $config = Db::name('gate_config')->where('id', $id)->find();
        if (!$config) return json_error('配置不存在');

        $adapter = GateFactory::create($config);
        $ok = $adapter->openGate($config['direction'] ?? 'in');
        return $ok ? json_success([], '已发送开闸指令') : json_error('开闸失败');
    }

    /** 同步白名单 */
    public function syncWhitelist()
    {
        $id = (int)$this->request->post('id', 0);
        if ($id <= 0) return json_error('缺少ID');

        $config = Db::name('gate_config')->where('id', $id)->find();
        if (!$config) return json_error('配置不存在');

        // 获取该小区所有登记车辆车牌
        $plates = Db::name('vehicle')
            ->where('community_id', $config['community_id'])
            ->where('status', 1)
            ->whereNull('delete_time')
            ->column('plate_number');

        $adapter = GateFactory::create($config);
        $ok = $adapter->syncWhitelist($plates);
        return $ok ? json_success(['count' => count($plates)], '同步成功') : json_error('同步失败');
    }
}
