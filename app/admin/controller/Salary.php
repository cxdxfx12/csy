<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Salary extends BaseAdmin
{
    public function lists()
    {
        $params = $this->request->param();
        // 单条查询（表单编辑回显）
        if (!empty($params['id'])) {
            $info = Db::name('staff_salary')->alias('sa')
                ->join('staff s', 'sa.staff_id = s.id', 'left')
                ->field('sa.*, s.realname as staff_name, s.job_no')
                ->where('sa.id', $params['id'])->find();
            return $this->success(['list' => $info ? [$info] : []]);
        }

        $query = Db::name('staff_salary')->alias('sa')
            ->join('staff s', 'sa.staff_id = s.id', 'left')
            ->field('sa.*, s.realname as staff_name, s.job_no');

        if (!empty($params['keyword'])) {
            $query->where('s.realname|s.job_no', 'like', '%' . $params['keyword'] . '%');
        }
        if (!empty($params['month'])) {
            $query->where('sa.salary_month', $params['month']);
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('sa.status', $params['status']);
        }

        $total = $query->count();
        $list = $query->order('sa.salary_month', 'desc')
            ->page($params['page'] ?? 1, $params['limit'] ?? 15)
            ->select();

        return $this->success(['list' => $list, 'total' => $total]);
    }

    public function add()
    {
        $data = $this->request->post();
        $data['create_time'] = date('Y-m-d H:i:s');
        // 计算实发
        $data['net_salary'] = round(
            ($data['base_salary'] ?? 0) + ($data['bonus'] ?? 0) + ($data['overtime_pay'] ?? 0)
            + ($data['subsidy'] ?? 0) - ($data['deduction'] ?? 0) - ($data['social_insurance'] ?? 0), 2
        );
        Db::name('staff_salary')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        $data['net_salary'] = round(
            ($data['base_salary'] ?? 0) + ($data['bonus'] ?? 0) + ($data['overtime_pay'] ?? 0)
            + ($data['subsidy'] ?? 0) - ($data['deduction'] ?? 0) - ($data['social_insurance'] ?? 0), 2
        );
        Db::name('staff_salary')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('staff_salary')->where('id', $id)->delete();
        return $this->success([], '删除成功');
    }

    public function pay()
    {
        $id = $this->request->post('id', 0);
        Db::name('staff_salary')->where('id', $id)->update(['status' => 1, 'pay_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '已发放');
    }

    public function batchGenerate()
    {
        $data = $this->request->post();
        $month = $data['salary_month'] ?? date('Y-m');
        $staffIds = Db::name('staff')->where('status', 1)->column('id');

        $count = 0;
        foreach ($staffIds as $sid) {
            $exist = Db::name('staff_salary')->where('staff_id', $sid)->where('salary_month', $month)->find();
            if (!$exist) {
                $staff = Db::name('staff')->find($sid);
                Db::name('staff_salary')->insert([
                    'staff_id' => $sid,
                    'salary_month' => $month,
                    'base_salary' => $staff['base_salary'] ?? 0,
                    'create_time' => date('Y-m-d H:i:s'),
                ]);
                $count++;
            }
        }
        return $this->success([], "批量生成 {$count} 条工资记录");
    }
}
