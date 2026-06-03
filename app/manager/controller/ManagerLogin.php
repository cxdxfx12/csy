<?php
namespace app\manager\controller;

use app\manager\BaseManager;
use Firebase\JWT\JWT;
use think\facade\Db;

class ManagerLogin extends BaseManager
{
    protected $noAuth = ['login'];

    public function login()
    {
        $username = $this->request->post('username', '');
        $password = $this->request->post('password', '');
        $staff = Db::name('admin_user')->where('username', $username)->find();
        if (!$staff || $staff['password'] !== encrypt_password($password)) {
            return $this->error('用户名或密码错误');
        }
        $jwtConfig = config('jwt');
        $token = JWT::encode([
            'iss' => $jwtConfig['iss'],
            'iat' => time(),
            'exp' => time() + 86400,
            'sub' => $staff['id'],
            'type' => 'manager',
        ], $jwtConfig['key'], $jwtConfig['algorithm']);

        return $this->success(['token' => $token], '登录成功');
    }
}
