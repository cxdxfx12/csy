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
        $communityId = $this->request->param('community_id', 0);
        if ($communityId) $where[] = ['ci.community_id', '=', $communityId];
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
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('charge_item')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        Db::name('charge_item')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('charge_item')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }

    public function select()
    {
        $communityId = $this->request->param('community_id', 0);
        $query = Db::name('charge_item')
            ->where('status', 1)->where('delete_time', null)
            ->field('id,name,type,cycle,billing_mode,unit_price,unit');
        if ($communityId) {
            $query->where('community_id', $communityId);
        }
        $list = $query->select();
        return $this->success($list);
    }
}
