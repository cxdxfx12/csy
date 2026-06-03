<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Supplier extends BaseAdmin
{
    public function lists()
    {
        $params = $this->request->param();
        $query = Db::name('supplier')->where('delete_time', null);

        if (!empty($params['keyword'])) {
            $query->where('name|contact_person|contact_phone', 'like', '%' . $params['keyword'] . '%');
        }
        if (!empty($params['category'])) {
            $query->where('category', $params['category']);
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', $params['status']);
        }

        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($params['page'] ?? 1, $params['limit'] ?? 15)
            ->select();

        return $this->success(['list' => $list, 'total' => $total]);
    }

    public function add()
    {
        $data = $this->request->post();
        // 检查供应商名称唯一性
        if (!empty($data['name'])) {
            $exist = Db::name('supplier')->where('name', $data['name'])->where('delete_time', null)->find();
            if ($exist) {
                return $this->error('该供应商名称已存在');
            }
        }
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('supplier')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        // 检查供应商名称唯一性（排除自身）
        if (!empty($data['name'])) {
            $exist = Db::name('supplier')->where('name', $data['name'])
                ->where('id', '<>', $data['id'])->where('delete_time', null)->find();
            if ($exist) {
                return $this->error('该供应商名称已存在');
            }
        }
        Db::name('supplier')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('supplier')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }

    public function detail()
    {
        $id = $this->request->param('id', 0);
        $info = Db::name('supplier')->where('id', $id)->find();
        if (!$info) return $this->error('未找到');
        // 关联统计
        $info['purchase_count'] = Db::name('purchase_order')->where('supplier_id', $id)->count();
        $info['purchase_total'] = Db::name('purchase_order')->where('supplier_id', $id)->where('status', 3)->sum('amount');
        $info['contract_count'] = Db::name('contract')->where('supplier_id', $id)->count();
        $info['avg_rating'] = round(Db::name('supplier_evaluation')->where('supplier_id', $id)->avg('rating'), 1);
        return $this->success($info);
    }
}
