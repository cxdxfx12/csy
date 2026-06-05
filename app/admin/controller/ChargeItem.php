<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class ChargeItem extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['ci.delete_time', 'null', '']];
        $cid = $this->getFilteredCommunityId();
        if ($cid === -1) $where[] = ['ci.community_id', 'in', $this->request->boundCommunityIds];
        elseif ($cid > 0) $where[] = ['ci.community_id', '=', $cid];
        $total = Db::name('charge_item')->alias('ci')->where($where)->count();
        $list = Db::name('charge_item')->alias('ci')
            ->leftJoin('community c', 'c.id = ci.community_id')
            ->field('ci.*, c.name as community_name')
            ->where($where)->page($page, $limit)->order('ci.sort', 'asc')->select();
        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        $this->validateCommunityAccess($data['community_id'] ?? 0);
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('charge_item')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        $item = Db::name('charge_item')->where('id', $data['id'])->find();
        if (!$item) return $this->error('收费项目不存在');
        $this->validateCommunityAccess($item['community_id'] ?? 0);
        if (!empty($data['community_id'])) $this->validateCommunityAccess($data['community_id']);
        Db::name('charge_item')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        $item = Db::name('charge_item')->where('id', $id)->find();
        if ($item) $this->validateCommunityAccess($item['community_id'] ?? 0);
        Db::name('charge_item')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }

    public function select()
    {
        $communityId = $this->request->param('community_id', 0);
        $roleId = $this->adminInfo['role_id'] ?? 0;
        $boundIds = $this->request->boundCommunityIds ?? [];
        $query = Db::name('charge_item')
            ->where('status', 1)->where('delete_time', null)
            ->field('id,name,type,cycle,billing_mode,unit_price,unit');
        if ($communityId) {
            $query->where('community_id', $communityId);
        } elseif ($roleId != 1 && !empty($boundIds)) {
            $query->where('community_id', 'in', $boundIds);
        }
        $list = $query->select();
        return $this->success($list);
    }
}
