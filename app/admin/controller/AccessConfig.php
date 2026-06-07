<?php
/**
 * 门禁配置管理 - 按小区配置门禁品牌和接口参数
 */
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;
use app\extend\access\AccessFactory;

class AccessConfig extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['ac.delete_time', 'null', '']];

        $cid = $this->getFilteredCommunityId();
        if ($cid === -1) {
            $where[] = ['ac.community_id', 'in', $this->request->boundCommunityIds];
        } elseif ($cid > 0) {
            $where[] = ['ac.community_id', '=', $cid];
        }

        $brand = $this->request->param('brand', '');
        if ($brand) $where[] = ['ac.brand', '=', $brand];

        $enabled = $this->request->param('enabled', '');
        if ($enabled !== '') $where[] = ['ac.enabled', '=', $enabled];

        $total = Db::name('access_config')->alias('ac')->where($where)->count();
        $list = Db::name('access_config')->alias('ac')
            ->leftJoin('community c', 'c.id = ac.community_id')
            ->field('ac.*, c.name as community_name')
            ->where($where)
            ->page($page, $limit)
            ->order('ac.community_id', 'asc')
            ->order('ac.id', 'asc')
            ->select();

        return $this->table($list, $total);
    }

    /** 获取品牌列表供前端选择 */
    public function brands()
    {
        return json_success(AccessFactory::brands(), 'ok');
    }

    /** 获取某小区的所有配置 */
    public function listByCommunity()
    {
        $communityId = (int)$this->request->param('community_id', 0);
        if ($communityId <= 0) return json_error('请选择小区');
        $this->validateCommunityAccess($communityId);

        $list = Db::name('access_config')
            ->where('community_id', $communityId)
            ->whereNull('delete_time')
            ->select();
        return json_success($list, 'ok');
    }

    public function add()
    {
        $data = $this->request->post();
        if (empty($data['community_id'])) return json_error('请选择小区');
        if (empty($data['door_name'])) return json_error('请输入门点名称');

        $this->validateCommunityAccess($data['community_id']);

        $insert = [
            'community_id' => (int)$data['community_id'],
            'door_name'    => $data['door_name'],
            'door_type'    => $data['door_type'] ?? 'gate',
            'brand'        => $data['brand'] ?? 'generic',
            'api_url'      => $data['api_url'] ?? '',
            'api_token'    => $data['api_token'] ?? '',
            'api_username' => $data['api_username'] ?? '',
            'device_sn'    => $data['device_sn'] ?? '',
            'door_no'      => (int)($data['door_no'] ?? 1),
            'open_mode'    => $data['open_mode'] ?? 'card_and_remote',
            'enabled'      => (int)($data['enabled'] ?? 1),
            'remark'       => $data['remark'] ?? '',
            'create_time'  => date('Y-m-d H:i:s'),
        ];

        Db::name('access_config')->insert($insert);
        return json_success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        if (empty($data['id'])) return json_error('缺少ID');

        $row = Db::name('access_config')->where('id', (int)$data['id'])->find();
        if (!$row) return json_error('记录不存在');
        $this->validateCommunityAccess($row['community_id']);

        $update = [];
        foreach (['door_name','door_type','brand','api_url','api_token','api_username','device_sn','door_no','open_mode','enabled','remark'] as $f) {
            if (isset($data[$f])) $update[$f] = $data[$f];
        }
        $update['update_time'] = date('Y-m-d H:i:s');

        Db::name('access_config')->where('id', (int)$data['id'])->update($update);
        return json_success([], '更新成功');
    }

    public function delete()
    {
        $id = (int)$this->request->post('id', 0);
        if ($id <= 0) return json_error('缺少ID');

        $row = Db::name('access_config')->where('id', $id)->find();
        if (!$row) return json_error('记录不存在');
        $this->validateCommunityAccess($row['community_id']);

        Db::name('access_config')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return json_success([], '删除成功');
    }

    /** 测试连接 */
    public function testConnection()
    {
        $id = (int)$this->request->post('id', 0);
        if ($id <= 0) return json_error('缺少ID');

        $config = Db::name('access_config')->where('id', $id)->find();
        if (!$config) return json_error('配置不存在');

        $adapter = AccessFactory::create($config);
        $status = $adapter->getStatus();
        return json_success($status, 'ok');
    }

    /** 远程开门 */
    public function remoteOpen()
    {
        $id = (int)$this->request->post('id', 0);
        if ($id <= 0) return json_error('缺少ID');

        $config = Db::name('access_config')->where('id', $id)->find();
        if (!$config) return json_error('配置不存在');

        $adapter = AccessFactory::create($config);
        $doorNo = (int)($config['door_no'] ?? 1);
        $ok = $adapter->remoteOpen($doorNo);

        // 记录远程开门事件
        Db::name('access_event')->insert([
            'config_id'    => $config['id'],
            'community_id' => $config['community_id'],
            'card_no'      => 'REMOTE',
            'holder_name'  => $this->request->adminInfo['username'] ?? '管理员',
            'door_name'    => $config['door_name'] ?? '',
            'direction'    => 'in',
            'open_method'  => 'remote',
            'action'       => $ok ? 'pass' : 'error',
            'event_time'   => date('Y-m-d H:i:s'),
            'create_time'  => date('Y-m-d H:i:s'),
        ]);

        return $ok ? json_success([], '已发送开门指令') : json_error('开门失败');
    }

    /** 同步白名单到门禁控制器 */
    public function syncWhitelist()
    {
        $id = (int)$this->request->post('id', 0);
        if ($id <= 0) return json_error('缺少ID');

        $config = Db::name('access_config')->where('id', $id)->find();
        if (!$config) return json_error('配置不存在');

        // 获取该小区所有有效门禁卡
        $cards = Db::name('access_card')
            ->where('community_id', $config['community_id'])
            ->where('status', 1)
            ->whereNull('delete_time')
            ->field('card_no, holder_name, expire_date')
            ->select();

        if (empty($cards)) {
            return json_success(['count' => 0], '该小区暂无有效门禁卡');
        }

        $adapter = AccessFactory::create($config);
        $ok = $adapter->syncWhitelist($cards);
        return $ok ? json_success(['count' => count($cards)], '同步成功') : json_error('同步失败');
    }
}
