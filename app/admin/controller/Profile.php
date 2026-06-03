<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Profile extends BaseAdmin
{
    public function info()
    {
        $admin = Db::name('admin_user')
            ->alias('a')
            ->leftJoin('role r', 'r.id = a.role_id')
            ->field('a.id,a.username,a.nickname,a.avatar,a.email,a.phone,a.role_id,a.last_login_time,a.last_login_ip,a.login_count,a.create_time, r.name as role_name')
            ->where('a.id', get_admin_id())
            ->find();
        if (!$admin) return $this->error('用户不存在');
        $admin['role'] = $admin['role_name'] ?? '超级管理员';
        return $this->success($admin);
    }

    public function edit()
    {
        $data = $this->request->post();
        $allowed = ['nickname', 'email', 'phone', 'avatar'];
        $update = [];
        foreach ($allowed as $field) {
            if (isset($data[$field])) $update[$field] = $data[$field];
        }
        if (empty($update)) return $this->error('无有效数据');
        Db::name('admin_user')->where('id', get_admin_id())->update($update);
        return $this->success($update, '修改成功');
    }

    public function password()
    {
        $oldPwd = $this->request->post('old_password', '');
        $newPwd = $this->request->post('new_password', '');

        $admin = Db::name('admin_user')->where('id', get_admin_id())->find();
        if ($admin['password'] !== encrypt_password($oldPwd)) {
            return $this->error('原密码错误');
        }
        if (strlen($newPwd) < 6) return $this->error('密码长度至少6位');
        Db::name('admin_user')->where('id', get_admin_id())->update(['password' => encrypt_password($newPwd)]);
        return $this->success([], '密码修改成功');
    }
}
