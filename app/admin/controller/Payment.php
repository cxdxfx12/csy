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
        if ($communityId) {
            $where[] = ['p.community_id', '=', $communityId];
        } else {
            $filter = $this->getCommunityFilter('p.community_id');
            if (!empty($filter)) $where = array_merge($where, $filter);
        }

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
        // 字段白名单
        $allowFields = ['community_id', 'owner_id', 'room_id', 'bill_id', 'payment_no', 'amount', 'pay_method', 'trade_no', 'pay_time', 'pay_account', 'operator_id', 'operator_type', 'remark'];
        $filtered = [];
        foreach ($allowFields as $f) {
            if (isset($data[$f])) $filtered[$f] = $data[$f];
        }
        unset($filtered['id']);
        $this->validateCommunityAccess($filtered['community_id'] ?? 0);
        if (empty($filtered['payment_no'])) {
            $filtered['payment_no'] = 'PAY' . date('YmdHis') . rand(1000, 9999);
        }
        $filtered['create_time'] = date('Y-m-d H:i:s');
        $filtered['update_time'] = date('Y-m-d H:i:s');
        if (empty($filtered['pay_time'])) $filtered['pay_time'] = null;
        Db::name($this->table)->insert($filtered);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        $id = intval($data['id'] ?? 0);
        if (!$id) return $this->error('参数错误');
        $record = Db::name($this->table)->where('id', $id)->find();
        if ($record) {
            $this->validateCommunityAccess($record['community_id'] ?? 0);
        }
        // 字段白名单
        $allowFields = ['community_id', 'owner_id', 'room_id', 'bill_id', 'payment_no', 'amount', 'pay_method', 'trade_no', 'pay_time', 'pay_account', 'operator_id', 'operator_type', 'remark'];
        $filtered = [];
        foreach ($allowFields as $f) {
            if (isset($data[$f])) $filtered[$f] = $data[$f];
        }
        $filtered['update_time'] = date('Y-m-d H:i:s');
        if (empty($filtered['pay_time'])) $filtered['pay_time'] = null;
        Db::name($this->table)->where('id', $id)->update($filtered);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        if (!$id) return $this->error('参数错误');
        $record = Db::name($this->table)->where('id', $id)->find();
        if ($record) $this->validateCommunityAccess($record['community_id'] ?? 0);
        Db::name($this->table)->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }

    public function export()
    {
        return $this->success([], '导出功能待完善');
    }
}
