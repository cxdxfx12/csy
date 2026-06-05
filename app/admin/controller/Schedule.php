<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Schedule extends BaseAdmin
{
    public function lists()
    {
        $params = $this->request->param();
        $query = Db::name('staff_schedule')->alias('sc')
            ->join('staff s', 'sc.staff_id = s.id', 'left')
            ->field('sc.*, s.realname as staff_name, s.job_no, s.community_id');

        // 单条查询（表单编辑回显）
        if (!empty($params['id'])) {
            $info = Db::name('staff_schedule')->alias('sc')
                ->join('staff s', 'sc.staff_id = s.id', 'left')
                ->field('sc.*, s.realname as staff_name, s.job_no')
                ->where('sc.id', $params['id'])->find();
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
            $query->where('sc.schedule_date', '>=', $params['date_start']);
        }
        if (!empty($params['date_end'])) {
            $query->where('sc.schedule_date', '<=', $params['date_end']);
        }
        if (isset($params['shift']) && $params['shift'] !== '') {
            $query->where('sc.shift', $params['shift']);
        }

        $total = $query->count();
        $list = $query->order('sc.schedule_date', 'asc')
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
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('staff_schedule')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        Db::name('staff_schedule')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('staff_schedule')->where('id', $id)->delete();
        return $this->success([], '删除成功');
    }

    public function batch()
    {
        $data = $this->request->post();
        $staffIds = $data['staff_ids'] ?? [];
        $dateStart = $data['date_start'] ?? date('Y-m-d');
        $dateEnd = $data['date_end'] ?? date('Y-m-d');
        $shift = $data['shift'] ?? '早班';

        if (empty($staffIds)) {
            return $this->error('请选择员工');
        }

        $records = [];
        $current = strtotime($dateStart);
        $end = strtotime($dateEnd);
        while ($current <= $end) {
            $d = date('Y-m-d', $current);
            foreach ($staffIds as $sid) {
                $exist = Db::name('staff_schedule')->where('staff_id', $sid)->where('schedule_date', $d)->find();
                if (!$exist) {
                    $records[] = ['staff_id' => $sid, 'schedule_date' => $d, 'shift' => $shift, 'create_time' => date('Y-m-d H:i:s')];
                }
            }
            $current += 86400;
        }
        if (!empty($records)) {
            Db::name('staff_schedule')->insertAll($records);
        }
        return $this->success([], '批量排班成功，新增' . count($records) . '条记录');
    }
}
