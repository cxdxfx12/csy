<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Attendance extends BaseAdmin
{
    public function lists()
    {
        $params = $this->request->param();
        $query = Db::name('staff_attendance')->alias('a')
            ->join('staff s', 'a.staff_id = s.id', 'left')
            ->field('a.*, s.realname as staff_name, s.job_no, s.community_id');

        // 单条查询（表单编辑回显）
        if (!empty($params['id'])) {
            $info = Db::name('staff_attendance')->alias('a')
                ->join('staff s', 'a.staff_id = s.id', 'left')
                ->field('a.*, s.realname as staff_name, s.job_no')
                ->where('a.id', $params['id'])->find();
            return $this->success(['list' => $info ? [$info] : []]);
        }

        // 小区筛选
        if (!empty($params['community_id'])) {
            $query->where('s.community_id', intval($params['community_id']));
        }
        // 小区角色数据隔离
        $cid = $this->getFilteredCommunityId();
        if ($cid === -1) {
            $query->where('s.community_id', 'in', $this->request->boundCommunityIds);
        } elseif ($cid > 0) {
            $query->where('s.community_id', intval($cid));
        }

        if (!empty($params['keyword'])) {
            $query->where('s.realname|s.job_no', 'like', '%' . $params['keyword'] . '%');
        }
        if (!empty($params['date_start'])) {
            $query->where('a.attendance_date', '>=', $params['date_start']);
        }
        if (!empty($params['date_end'])) {
            $query->where('a.attendance_date', '<=', $params['date_end']);
        }
        if (isset($params['type']) && $params['type'] !== '') {
            $query->where('a.type', $params['type']);
        }

        $total = $query->count();
        $list = $query->order('a.attendance_date', 'desc')
            ->page($params['page'] ?? 1, $params['limit'] ?? 15)
            ->select();

        foreach ($list as &$row) {
            $row['community_name'] = Db::name('community')->where('id', $row['community_id'])->value('name') ?? '-';
        }

        return $this->success(['list' => $list, 'total' => $total]);
    }

    public function add()
    {
        $data = $this->request->post();
        $this->validateStaffCommunity($data['staff_id'] ?? 0);
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('staff_attendance')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        Db::name('staff_attendance')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('staff_attendance')->where('id', $id)->delete();
        return $this->success([], '删除成功');
    }

    public function batch()
    {
        $data = $this->request->post();
        $staffIds = $data['staff_ids'] ?? [];
        $date = $data['attendance_date'] ?? date('Y-m-d');
        $type = $data['type'] ?? 1;

        if (empty($staffIds)) {
            return $this->error('请选择员工');
        }

        // 验证所有员工都在管辖小区内
        $this->validateStaffIdsCommunity($staffIds);

        $records = [];
        foreach ($staffIds as $sid) {
            $exist = Db::name('staff_attendance')->where('staff_id', $sid)->where('attendance_date', $date)->find();
            if (!$exist) {
                $records[] = ['staff_id' => $sid, 'attendance_date' => $date, 'type' => $type, 'create_time' => date('Y-m-d H:i:s')];
            }
        }
        if (!empty($records)) {
            Db::name('staff_attendance')->insertAll($records);
        }
        return $this->success([], '批量打卡成功，新增' . count($records) . '条记录');
    }
}
