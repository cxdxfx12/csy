<?php
namespace app\api\controller;

use app\api\BaseApi;
use Firebase\JWT\JWT;
use think\facade\Db;
use think\facade\Cache;

class Login extends BaseApi
{
    protected $noAuth = ['login', 'register', 'sendSms', 'resetPassword'];

    public function login()
    {
        $phone = $this->request->post('phone', '');
        $password = $this->request->post('password', '');

        if (empty($phone) || empty($password)) {
            return $this->error('请输入手机号和密码');
        }

        $owner = Db::name('owner')->where('phone', $phone)->find();
        if (!$owner || $owner['password'] !== encrypt_password($password)) {
            return $this->error('手机号或密码错误');
        }
        if ($owner['status'] != 1) {
            return $this->error('账户已禁用');
        }

        $jwtConfig = config('jwt');
        $now = time();
        $payload = [
            'iss' => $jwtConfig['iss'],
            'aud' => $jwtConfig['aud'],
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $jwtConfig['exp'],
            'sub' => $owner['id'],
            'type' => 'owner',
        ];
        $token = JWT::encode($payload, $jwtConfig['key'], $jwtConfig['algorithm']);

        Db::name('owner')->where('id', $owner['id'])->update([
            'last_login_time' => date('Y-m-d H:i:s'),
        ]);

        return $this->success([
            'token' => $token,
            'userInfo' => [
                'id' => $owner['id'],
                'realname' => $owner['realname'],
                'phone' => $owner['phone'],
                'avatar' => $owner['avatar'],
                'community_id' => $owner['community_id'],
            ],
        ], '登录成功');
    }

    public function register()
    {
        $data = $this->request->post();
        $exists = Db::name('owner')->where('phone', $data['phone'])->find();
        if ($exists) return $this->error('该手机号已注册');
        $data['password'] = encrypt_password($data['password']);
        $data['register_time'] = date('Y-m-d H:i:s');
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('owner')->insert($data);
        return $this->success([], '注册成功');
    }

    public function sendSms()
    {
        $phone = $this->request->post('phone', '');
        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) return $this->error('手机号格式错误');

        $code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::set('sms_code_' . $phone, $code, 300);

        return $this->success(['code' => $code], '验证码已发送（演示模式）');
    }

    public function resetPassword()
    {
        $phone = $this->request->post('phone', '');
        $code = $this->request->post('code', '');
        $password = $this->request->post('password', '');

        $cachedCode = Cache::get('sms_code_' . $phone);
        if ($cachedCode != $code) return $this->error('验证码错误');

        Db::name('owner')->where('phone', $phone)->update([
            'password' => encrypt_password($password),
        ]);
        Cache::delete('sms_code_' . $phone);
        return $this->success([], '密码重置成功');
    }
}
