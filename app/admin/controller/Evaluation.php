<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Evaluation extends BaseAdmin
{
    public function lists()
    {
        $params = $this->request->param();
        // 单条查询（表单编辑回显）
        if (!empty($params['id'])) {
            $info = Db::name('supplier_evaluation')->alias('ev')
                ->join('supplier sp', 'ev.supplier_id = sp.id', 'left')
                ->field('ev.*, sp.name as supplier_name')
                ->where('ev.id', $params['id'])->find();
            return $this->success(['list' => $info ? [$info] : []]);
        }

        $query = Db::name('supplier_evaluation')->alias('ev')
            ->join('supplier sp', 'ev.supplier_id = sp.id', 'left')
            ->field('ev.*, sp.name as supplier_name');

        if (!empty($params['keyword'])) {
            $query->where('sp.name|ev.evaluator', 'like', '%' . $params['keyword'] . '%');
        }
        if (!empty($params['supplier_id'])) {
            $query->where('ev.supplier_id', $params['supplier_id']);
        }
        if (isset($params['rating']) && $params['rating'] !== '') {
            $query->where('ev.rating', $params['rating']);
        }

        $total = $query->count();
        $list = $query->order('ev.id', 'desc')
            ->page($params['page'] ?? 1, $params['limit'] ?? 15)
            ->select();

        return $this->success(['list' => $list, 'total' => $total]);
    }

    public function add()
    {
        $data = $this->request->post();
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('supplier_evaluation')->insert($data);

        // 更新供应商综合评分
        $avgRating = round(Db::name('supplier_evaluation')->where('supplier_id', $data['supplier_id'])->avg('rating'), 1);
        Db::name('supplier')->where('id', $data['supplier_id'])->update(['rating' => $avgRating]);

        return $this->success([], '评价成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        Db::name('supplier_evaluation')->where('id', $data['id'])->update($data);

        $avgRating = round(Db::name('supplier_evaluation')->where('supplier_id', $data['supplier_id'])->avg('rating'), 1);
        Db::name('supplier')->where('id', $data['supplier_id'])->update(['rating' => $avgRating]);

        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        $ev = Db::name('supplier_evaluation')->find($id);
        Db::name('supplier_evaluation')->where('id', $id)->delete();

        if ($ev) {
            $avgRating = round(Db::name('supplier_evaluation')->where('supplier_id', $ev['supplier_id'])->avg('rating'), 1);
            Db::name('supplier')->where('id', $ev['supplier_id'])->update(['rating' => $avgRating]);
        }

        return $this->success([], '删除成功');
    }

    public function stats()
    {
        $supplierId = $this->request->param('supplier_id', 0);
        $allEvaluations = Db::name('supplier_evaluation')
            ->where('supplier_id', $supplierId)
            ->field('rating, count(*) as count')
            ->group('rating')
            ->order('rating', 'desc')
            ->select();
        return $this->success($allEvaluations);
    }
}
