<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class RepairWorker extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['rw.delete_time', 'null', '']];
        $cid = $this->getFilteredCommunityId();
        if ($cid === -1) $where[] = ['rw.community_id', 'in', $this->request->boundCommunityIds];
        elseif ($cid > 0) $where[] = ['rw.community_id', '=', $cid];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['rw.name|rw.phone', 'like', "%{$keyword}%"];
        $total = Db::name('repair_worker')->alias('rw')->where($where)->count();
        $list = Db::name('repair_worker')->alias('rw')
            ->leftJoin('community com', 'com.id = rw.community_id')
            ->leftJoin('staff s', 's.id = rw.staff_id AND s.delete_time IS NULL')
            ->field('rw.*, com.name as community_name, s.job_no as staff_job_no, s.realname as staff_realname, s.department as staff_department, s.position as staff_position')
            ->where($where)->page($page, $limit)->order('rw.id', 'desc')->select();
        foreach ($list as &$row) {
            $row['specialty'] = $row['type'] ?? '';
        }
        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        unset($data['community_name']); // 表中无此字段，仅用于前端展示
        if (isset($data['specialty'])) {
            $data['type'] = $data['specialty'];
            unset($data['specialty']);
        }
        // 如果选择了员工档案，从 staff 表获取姓名、手机号、小区
        $staffId = intval($data['staff_id'] ?? 0);
        if ($staffId > 0) {
            $staff = Db::name('staff')->where('id', $staffId)->where('delete_time', null)->find();
            if (!$staff) {
                return $this->error('所选员工不存在或已离职');
            }
            $data['name'] = $staff['realname'];
            $data['phone'] = $staff['phone'];
            $data['community_id'] = $staff['community_id'] ?? 0;
        }
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('repair_worker')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        unset($data['community_name']); // 表中无此字段，仅用于前端展示
        if (isset($data['specialty'])) {
            $data['type'] = $data['specialty'];
            unset($data['specialty']);
        }
        // 如果修改了关联员工
        $staffId = intval($data['staff_id'] ?? 0);
        if ($staffId > 0) {
            $staff = Db::name('staff')->where('id', $staffId)->where('delete_time', null)->find();
            if (!$staff) {
                return $this->error('所选员工不存在或已离职');
            }
            $data['name'] = $staff['realname'];
            $data['phone'] = $staff['phone'];
            $data['community_id'] = $staff['community_id'] ?? 0;
        }
        Db::name('repair_worker')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('repair_worker')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }

    /**
     * 获取可作为维修人员的员工列表（在职、未被关联的）
     */
    public function staffList()
    {
        $communityId = $this->request->param('community_id', 0);
        $includeId = $this->request->param('include_id', 0);
        // 已被关联为维修人员的 staff_id（排除当前编辑的）
        $usedIds = Db::name('repair_worker')->where('delete_time', null)
            ->where('staff_id', '>', 0);
        if ($includeId) $usedIds = $usedIds->where('staff_id', '<>', intval($includeId));
        $usedIds = $usedIds->column('staff_id');
        $query = Db::name('staff')->where('delete_time', null)->where('status', 1);
        if ($communityId) $query->where('community_id', $communityId);
        if (!empty($usedIds)) $query->whereNotIn('id', $usedIds);
        $list = $query->field('id, job_no, realname, phone, department, position, community_id')
            ->order('id', 'asc')->select();
        return $this->success($list);
    }
}
