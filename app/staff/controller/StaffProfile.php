<?php
namespace app\staff\controller;

use app\staff\BaseStaff;
use think\facade\Db;

class StaffProfile extends BaseStaff
{
    public function info()
    {
        $staff = Db::name('admin_user')->where('id', $this->staffId)->field('id,username,nickname,avatar,email,phone,last_login_time')->find();
        // 补充小区信息
        $communityId = $this->staffInfo['community_id'] ?? 0;
        $staff['community_id'] = $communityId;
        $staff['community_name'] = $communityId ? (Db::name('community')->where('id', $communityId)->value('name') ?? '') : '';
        return $this->success($staff);
    }

    public function edit()
    {
        $data = $this->request->post();
        Db::name('admin_user')->where('id', $this->staffId)->update($data);
        return $this->success([], '修改成功');
    }
}
