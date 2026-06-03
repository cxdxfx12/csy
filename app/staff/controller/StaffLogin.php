<?php
namespace app\staff\controller;

use app\staff\BaseStaff;
use Firebase\JWT\JWT;
use think\facade\Db;

class StaffLogin extends BaseStaff
{
    protected $noAuth = ['login'];

    public function login()
    {
        $username = $this->request->post('username', '');
        $password = $this->request->post('password', '');

        $staff = Db::name('admin_user')->where('username', $username)->find();
        if (!$staff || !verify_password($password, $staff['password'])) {
            return $this->error('用户名或密码错误');
        }
        if ($staff['status'] != 1) return $this->error('账户已禁用');

        $jwtConfig = config('jwt');
        $now = time();
        $payload = [
            'iss' => $jwtConfig['iss'],
            'iat' => $now,
            'exp' => $now + 86400,
            'sub' => $staff['id'],
            'type' => 'staff',
        ];
        $token = JWT::encode($payload, $jwtConfig['key'], $jwtConfig['algorithm']);

        return $this->success([
            'token' => $token,
            'userInfo' => [
                'id' => $staff['id'],
                'username' => $staff['username'],
                'nickname' => $staff['nickname'],
                'avatar' => $staff['avatar'],
            ],
        ], '登录成功');
    }

    public function logout()
    {
        return $this->success([], '退出成功');
    }

    public function password()
    {
        $oldPwd = $this->request->post('old_password', '');
        $newPwd = $this->request->post('new_password', '');
        $staff = Db::name('admin_user')->where('id', $this->staffId)->find();
        if (!verify_password($oldPwd, $staff['password'])) return $this->error('原密码错误');
        Db::name('admin_user')->where('id', $this->staffId)->update(['password' => encrypt_password($newPwd)]);
        return $this->success([], '修改成功');
    }
}
