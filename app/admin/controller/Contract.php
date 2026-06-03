<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Contract extends BaseAdmin
{
    public function lists()
    {
        $params = $this->request->param();
        // 单条查询（表单编辑回显）
        if (!empty($params['id'])) {
            $info = Db::name('contract')->alias('c')
                ->join('supplier sp', 'c.supplier_id = sp.id', 'left')
                ->field('c.*, sp.name as supplier_name')
                ->where('c.id', $params['id'])->find();
            return $this->success(['list' => $info ? [$info] : []]);
        }

        $query = Db::name('contract')->alias('c')
            ->join('supplier sp', 'c.supplier_id = sp.id', 'left')
            ->field('c.*, sp.name as supplier_name');

        if (!empty($params['keyword'])) {
            $query->where('c.contract_no|c.title|sp.name', 'like', '%' . $params['keyword'] . '%');
        }
        if (!empty($params['supplier_id'])) {
            $query->where('c.supplier_id', $params['supplier_id']);
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('c.status', $params['status']);
        }

        $total = $query->count();
        $list = $query->order('c.id', 'desc')
            ->page($params['page'] ?? 1, $params['limit'] ?? 15)
            ->select();

        return $this->success(['list' => $list, 'total' => $total]);
    }

    public function add()
    {
        $data = $this->request->post();
        $data['contract_no'] = 'HT' . date('YmdHis') . rand(10, 99);
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('contract')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        Db::name('contract')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('contract')->where('id', $id)->delete();
        return $this->success([], '删除成功');
    }

    public function expire()
    {
        $id = $this->request->post('id', 0);
        Db::name('contract')->where('id', $id)->update(['status' => 3, 'expire_time' => date('Y-m-d')]);
        return $this->success([], '已终止');
    }
}
