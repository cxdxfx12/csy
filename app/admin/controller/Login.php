<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use Firebase\JWT\JWT;
use think\facade\Db;
use think\facade\Cache;

class Login extends BaseAdmin
{
    protected $noAuth = ['login', 'captcha', 'logout'];

    public function login()
    {
        $username = $this->request->post('username', '');
        $password = $this->request->post('password', '');
        $captcha  = $this->request->post('captcha', '');
        $captchaKey = $this->request->post('captchaKey', '');

        if (empty($username) || empty($password)) {
            return $this->error('请输入用户名和密码');
        }

        // 验证码校验
        if (!empty($captchaKey)) {
            $cached = Cache::get('captcha_' . $captchaKey);
            if (empty($cached) || strtolower($cached) != strtolower($captcha)) {
                return $this->error('验证码错误');
            }
            Cache::delete('captcha_' . $captchaKey);
        }

        $admin = Db::name('admin_user')->where('username', $username)->find();
        if (!$admin) {
            return $this->error('用户名或密码错误');
        }

        if ($admin['status'] != 1) {
            return $this->error('账户已被禁用，请联系管理员');
        }

        $pwdEncrypt = encrypt_password($password);
        if ($admin['password'] !== $pwdEncrypt) {
            return $this->error('用户名或密码错误');
        }

        // 生成JWT Token
        $jwtConfig = config('jwt');
        $now = time();
        $payload = [
            'iss' => $jwtConfig['iss'],
            'aud' => $jwtConfig['aud'],
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $jwtConfig['exp'],
            'sub' => $admin['id'],
            'type' => 'admin',
        ];
        $token = JWT::encode($payload, $jwtConfig['key'], $jwtConfig['algorithm']);

        // 更新登录信息
        Db::name('admin_user')->where('id', $admin['id'])->update([
            'last_login_time' => date('Y-m-d H:i:s'),
            'last_login_ip'   => $this->request->ip(),
            'login_count'     => $admin['login_count'] + 1,
        ]);

        // 获取角色和权限
        $role = Db::name('role')->where('id', $admin['role_id'])->find();
        $menus = [];
        if ($admin['role_id'] == 1) {
            $menus = Db::name('menu')->where('status', 1)->order('sort', 'asc')->select();
        } else {
            $menuIds = Db::name('role_menu')->where('role_id', $admin['role_id'])->column('menu_id');
            if (!empty($menuIds)) {
                $menus = Db::name('menu')->whereIn('id', $menuIds)->where('status', 1)->order('sort', 'asc')->select();
            }
        }

        return $this->success([
            'token'    => $token,
            'userInfo' => [
                'id'       => $admin['id'],
                'username' => $admin['username'],
                'nickname' => $admin['nickname'],
                'avatar'   => $admin['avatar'],
                'role'     => $role['name'] ?? '',
                'role_id'  => $admin['role_id'],
            ],
            'menus' => tree_list($menus),
        ], '登录成功');
    }

    public function logout()
    {
        return $this->success([], '退出成功');
    }

    public function captcha()
    {
        $key = md5(uniqid(mt_rand(), true));
        $code = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 4);
        Cache::set('captcha_' . $key, $code, 300);

        // 生成验证码图片（不依赖字体文件）
        $width = 130; $height = 44;
        $img = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($img, 247, 248, 252);
        imagefill($img, 0, 0, $bg);

        // 噪点
        for ($i = 0; $i < 80; $i++) {
            imagesetpixel($img, mt_rand(0, $width), mt_rand(0, $height), imagecolorallocate($img, mt_rand(150,220), mt_rand(150,220), mt_rand(150,220)));
        }

        // 干扰线
        for ($i = 0; $i < 3; $i++) {
            $line = imagecolorallocate($img, mt_rand(180, 220), mt_rand(180, 220), mt_rand(180, 220));
            imageline($img, mt_rand(0, $width), mt_rand(0, $height), mt_rand(0, $width), mt_rand(0, $height), $line);
        }

        // 使用内置字体绘制字符
        for ($i = 0; $i < 4; $i++) {
            $color = imagecolorallocate($img, mt_rand(30, 80), mt_rand(80, 140), mt_rand(140, 200));
            $size = mt_rand(22, 28);
            $angle = mt_rand(-20, 20);
            $x = 10 + $i * 28 + mt_rand(0, 5);
            $y = mt_rand(30, 38);
            // 用imagestring替代imagettftext，不需要字体文件
            $char = $code[$i];
            for ($ox = -1; $ox <= 1; $ox++) {
                for ($oy = -1; $oy <= 1; $oy++) {
                    if ($ox != 0 || $oy != 0) {
                        $shadow = imagecolorallocate($img, 240, 240, 245);
                        imagechar($img, mt_rand(4, 5), $x + $ox, $y + $oy - 15, $char, $shadow);
                    }
                }
            }
            imagechar($img, mt_rand(4, 5), $x, $y - 15, $char, $color);
        }

        // 更多噪点
        for ($i = 0; $i < 40; $i++) {
            imagesetpixel($img, mt_rand(0, $width), mt_rand(0, $height), imagecolorallocate($img, mt_rand(180,255), mt_rand(180,255), mt_rand(180,255)));
        }

        ob_start();
        imagepng($img);
        $imageData = ob_get_clean();
        imagedestroy($img);

        return \json_success([
            'key'    => $key,
            'image'  => 'data:image/png;base64,' . base64_encode($imageData),
        ], '获取成功');
    }

    public function info()
    {
        $adminInfo = get_admin_info();
        $menus = [];
        $roleId = $adminInfo['role_id'];

        if ($roleId == 1) {
            $menus = Db::name('menu')->where('status', 1)->order('sort', 'asc')->select();
        } else {
            $menuIds = Db::name('role_menu')->where('role_id', $roleId)->column('menu_id');
            if (!empty($menuIds)) {
                $menus = Db::name('menu')->whereIn('id', $menuIds)->where('status', 1)->order('sort', 'asc')->select();
            }
        }

        $role = Db::name('role')->where('id', $roleId)->find();
        $permissions = Db::name('menu')->where('type', 'in', [2, 3])->column('permission');

        return $this->success([
            'userInfo' => [
                'id'       => $adminInfo['id'],
                'username' => $adminInfo['username'],
                'nickname' => $adminInfo['nickname'],
                'avatar'   => $adminInfo['avatar'],
                'role'     => $role['name'] ?? '',
                'role_id'  => $roleId,
            ],
            'menus'       => tree_list($menus),
            'permissions' => $permissions,
        ]);
    }
}
