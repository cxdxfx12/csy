<?php
namespace app\staff\controller;

use app\staff\BaseStaff;
use think\facade\Db;

class StaffProfile extends BaseStaff
{
    public function info()
    {
        $staffId = $this->staffId;
        // 如果有 staffInfo（ds_staff 表记录），使用 staffInfo.id
        if (!empty($this->staffInfo['id']) && empty($this->staffInfo['is_worker'])) {
            $staffId = $this->staffInfo['id'];
        }
        
        $staff = Db::name('admin_user')->where('id', $this->staffId)->field('id,username,nickname,avatar,email,phone,last_login_time')->find();
        // 补充小区信息
        $communityId = $this->staffInfo['community_id'] ?? 0;
        $staff['community_id'] = $communityId;
        $staff['community_name'] = $communityId ? (Db::name('community')->where('id', $communityId)->value('name') ?? '') : '';
        
        // 查询负责的楼栋
        $buildings = Db::name('building')->where('manager_id', $staffId)->whereNull('delete_time')
            ->field('id, name, community_id, floor_count, unit_count, total_rooms')
            ->order('sort', 'asc')->select();
        foreach ($buildings as &$b) {
            $b['community_name'] = Db::name('community')->where('id', $b['community_id'])->value('name') ?? '';
        }
        $staff['buildings'] = $buildings;
        
        return $this->success($staff);
    }

    public function edit()
    {
        // 白名单：仅允许员工修改这些字段，防止 Mass Assignment 提权
        $raw = $this->request->post();
        $allowed = ['nickname', 'avatar', 'email', 'phone'];
        $data = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $raw)) {
                $data[$field] = $raw[$field];
            }
        }
        if (empty($data)) return $this->error('无有效修改字段');

        // 如果修改手机号，检查唯一性
        if (!empty($data['phone'])) {
            $exists = Db::name('admin_user')
                ->where('phone', $data['phone'])
                ->where('id', '<>', $this->staffId)
                ->find();
            if ($exists) return $this->error('该手机号已被其他账号使用');
        }

        Db::name('admin_user')->where('id', $this->staffId)->update($data);
        return $this->success([], '修改成功');
    }

    /**
     * 员工查看自己的考勤记录
     */
    public function attendance()
    {
        [$page, $limit] = [intval($this->request->param('page', 1)), intval($this->request->param('limit', 15))];
        $month = $this->request->param('month', date('Y-m'));

        $staffId = !empty($this->staffInfo['id']) && empty($this->staffInfo['is_worker']) ? $this->staffInfo['id'] : $this->staffId;

        $query = Db::name('staff_attendance')
            ->where('staff_id', $staffId)
            ->where('attendance_date', 'like', $month . '%');

        $total = $query->count();
        $list = $query->order('attendance_date', 'desc')->page($page, $limit)->select();

        return $this->success(['list' => $list, 'total' => $total]);
    }

    /**
     * 员工查看自己的排班记录
     */
    public function schedule()
    {
        [$page, $limit] = [intval($this->request->param('page', 1)), intval($this->request->param('limit', 15))];
        $month = $this->request->param('month', date('Y-m'));

        $staffId = !empty($this->staffInfo['id']) && empty($this->staffInfo['is_worker']) ? $this->staffInfo['id'] : $this->staffId;

        $query = Db::name('staff_schedule')
            ->where('staff_id', $staffId)
            ->where('schedule_date', 'like', $month . '%');

        $total = $query->count();
        $list = $query->order('schedule_date', 'asc')->page($page, $limit)->select();

        return $this->success(['list' => $list, 'total' => $total]);
    }

    /**
     * 员工查看自己的工资记录
     */
    public function salary()
    {
        [$page, $limit] = [intval($this->request->param('page', 1)), intval($this->request->param('limit', 15))];

        $staffId = !empty($this->staffInfo['id']) && empty($this->staffInfo['is_worker']) ? $this->staffInfo['id'] : $this->staffId;

        $query = Db::name('staff_salary')->where('staff_id', $staffId);

        $total = $query->count();
        $list = $query->order('salary_month', 'desc')->page($page, $limit)->select();

        return $this->success(['list' => $list, 'total' => $total]);
    }
}
