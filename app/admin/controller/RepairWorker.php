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
            // 统计该工人已分配的工单总数（排除已删除）
            $row['order_count'] = Db::name('repair_order')
                ->where('assignee_id', $row['id'])
                ->whereNull('delete_time')
                ->count();
            unset($row['password'], $row['openid']);
        }
        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        unset($data['community_name']); // 表中无此字段，仅用于前端展示
        // 去除无关字段，避免干扰 INSERT
        unset($data['id']);
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
            // 验证该员工是否在当前管理员管辖小区内
            $boundIds = $this->request->boundCommunityIds;
            if (is_array($boundIds) && count($boundIds) > 0) {
                if (!in_array(intval($staff['community_id'] ?? 0), $boundIds)) {
                    return $this->error('所选员工不在您管辖的小区范围内');
                }
            }
            $data['name'] = $staff['realname'];
            $data['phone'] = $staff['phone'];
            $data['community_id'] = $staff['community_id'] ?? 0;
        }
        // 如果 community_id 为空或未设置，取管理员管辖的第一个小区
        if (empty($data['community_id']) && !empty($this->request->boundCommunityIds)) {
            $data['community_id'] = is_array($this->request->boundCommunityIds)
                ? $this->request->boundCommunityIds[0]
                : $this->request->boundCommunityIds;
        }
        // 密码处理
        if (!empty($data['password'])) {
            $data['password'] = encrypt_password($data['password']);
        } else {
            $data['password'] = ''; // NOT NULL 字段必须给值
        }
        $data['create_time'] = date('Y-m-d H:i:s');

        try {
            Db::name('repair_worker')->insert($data);
        } catch (\Throwable $e) {
            $msg = '添加维修人员失败: ' . $e->getMessage();
            file_put_contents(RUNTIME_PATH . 'log' . DS . 'error.log', date('Y-m-d H:i:s') . ' ' . $msg . ' | data=' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
            return $this->error('添加失败：' . $e->getMessage());
        }
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
            // 验证该员工是否在当前管理员管辖小区内
            $boundIds = $this->request->boundCommunityIds;
            if (is_array($boundIds) && count($boundIds) > 0) {
                if (!in_array(intval($staff['community_id'] ?? 0), $boundIds)) {
                    return $this->error('所选员工不在您管辖的小区范围内');
                }
            }
            $data['name'] = $staff['realname'];
            $data['phone'] = $staff['phone'];
            $data['community_id'] = $staff['community_id'] ?? 0;
        }
        // 密码处理：空则不变，有值则加密
        if (!empty($data['password'])) {
            $data['password'] = encrypt_password($data['password']);
        } else {
            unset($data['password']);
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
        // 限制在当前管理员管辖的小区范围内
        $boundIds = $this->request->boundCommunityIds;
        if (is_array($boundIds) && count($boundIds) > 0) {
            $query->whereIn('community_id', $boundIds);
        }
        if ($communityId) $query->where('community_id', $communityId);
        if (!empty($usedIds)) $query->whereNotIn('id', $usedIds);
        $list = $query->field('id, job_no, realname, phone, department, position, community_id')
            ->order('id', 'asc')->select();
        return $this->success($list);
    }
}
