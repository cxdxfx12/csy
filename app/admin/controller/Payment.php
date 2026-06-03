<?php

namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Payment extends BaseAdmin
{
    protected $table = 'bill_payment';

    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['p.delete_time', 'null', '']];

        $keyword = $this->request->param('keyword', '');
        if ($keyword) {
            $where[] = ['p.payment_no|o.realname|r.room_number', 'like', "%{$keyword}%"];
        }

        $communityId = $this->request->param('community_id', 0);
        if ($communityId) $where[] = ['p.community_id', '=', $communityId];

        $payMethod = $this->request->param('pay_method', '');
        if ($payMethod !== '') $where[] = ['p.pay_method', '=', intval($payMethod)];

        $total = Db::name($this->table)->alias('p')
            ->leftJoin('ds_owner o', 'o.id = p.owner_id')
            ->where($where)->count();

        $list = Db::name($this->table)->alias('p')
            ->leftJoin('ds_owner o', 'o.id = p.owner_id')
            ->leftJoin('ds_community c', 'c.id = p.community_id')
            ->leftJoin('ds_room r', 'r.id = p.room_id')
            ->leftJoin('ds_bill b', 'b.id = p.bill_id')
            ->field('p.*, o.realname as owner_name, c.name as community_name, r.room_number, r.building_name, b.bill_no as bill_no')
            ->where($where)
            ->page($page, $limit)
            ->order('p.id', 'desc')
            ->select();

        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        unset($data['id']);
        if (empty($data['payment_no'])) {
            $data['payment_no'] = 'PAY' . date('YmdHis') . rand(1000, 9999);
        }
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['update_time'] = date('Y-m-d H:i:s');
        if (empty($data['pay_time'])) $data['pay_time'] = null;
        Db::name($this->table)->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        $id = intval($data['id'] ?? 0);
        if (!$id) return $this->error('参数错误');
        unset($data['id']);
        $data['update_time'] = date('Y-m-d H:i:s');
        if (empty($data['pay_time'])) $data['pay_time'] = null;
        Db::name($this->table)->where('id', $id)->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        if (!$id) return $this->error('参数错误');
        Db::name($this->table)->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }

    public function export()
    {
        return $this->success([], '导出功能待完善');
    }
}
