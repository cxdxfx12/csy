<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Purchase extends BaseAdmin
{
    public function lists()
    {
        $params = $this->request->param();
        // 单条查询（表单编辑回显）
        if (!empty($params['id'])) {
            $info = Db::name('purchase_order')->alias('po')
                ->join('supplier sp', 'po.supplier_id = sp.id', 'left')
                ->field('po.*, sp.name as supplier_name')
                ->where('po.id', $params['id'])->find();
            return $this->success(['list' => $info ? [$info] : []]);
        }

        $query = Db::name('purchase_order')->alias('po')
            ->join('supplier sp', 'po.supplier_id = sp.id', 'left')
            ->field('po.*, sp.name as supplier_name');

        if (!empty($params['keyword'])) {
            $query->where('po.order_no|po.title', 'like', '%' . $params['keyword'] . '%');
        }
        if (!empty($params['supplier_id'])) {
            $query->where('po.supplier_id', $params['supplier_id']);
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('po.status', $params['status']);
        }

        $total = $query->count();
        $list = $query->order('po.id', 'desc')
            ->page($params['page'] ?? 1, $params['limit'] ?? 15)
            ->select();

        return $this->success(['list' => $list, 'total' => $total]);
    }

    public function add()
    {
        $data = $this->request->post();
        $data['order_no'] = 'CG' . date('YmdHis') . rand(10, 99);
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('purchase_order')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        Db::name('purchase_order')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('purchase_order')->where('id', $id)->delete();
        return $this->success([], '删除成功');
    }

    public function approve()
    {
        $id = $this->request->post('id', 0);
        $order = Db::name('purchase_order')->find($id);
        if (!$order) return $this->error('订单不存在');
        if ($order['status'] != 1) return $this->error('订单状态不可审批');
        Db::name('purchase_order')->where('id', $id)->update(['status' => 2, 'approve_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '审批通过');
    }

    public function complete()
    {
        $id = $this->request->post('id', 0);
        $order = Db::name('purchase_order')->find($id);
        if (!$order) return $this->error('订单不存在');
        if ($order['status'] != 2) return $this->error('订单状态不可完结');
        Db::name('purchase_order')->where('id', $id)->update(['status' => 3, 'complete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '已完结');
    }
}
