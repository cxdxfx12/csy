<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use Firebase\JWT\JWT;
use think\facade\Db;
use think\facade\Cache;
use service\WechatService;

class Login extends BaseAdmin
{
    protected $noAuth = ['login', 'captcha', 'logout', 'wechatOAuth', 'wechatCallback', 'wechatLogin', 'wechatBind'];

    public function login()
    {
        $username = $this->request->post('username', '');
        $password = $this->request->post('password', '');
        $captcha  = $this->request->post('captcha', '');
        $captchaKey = $this->request->post('captchaKey', '');

        if (empty($username) || empty($password)) {
            return $this->error('请输入用户名和密码');
        }

        // 验证码校验（必填）
        if (empty($captchaKey) || empty($captcha)) {
            return $this->error('请输入验证码');
        }
        $cached = Cache::get('captcha_' . $captchaKey);
        if (empty($cached) || strtolower($cached) != strtolower($captcha)) {
            return $this->error('验证码错误或已过期');
        }
        Cache::delete('captcha_' . $captchaKey);

        $admin = Db::name('admin_user')->where('username', $username)->find();
        if (!$admin) {
            return $this->error('用户名或密码错误');
        }

        if ($admin['status'] != 1) {
            return $this->error('账户已被禁用，请联系管理员');
        }

        if (!verify_password($password, $admin['password'])) {
            return $this->error('用户名或密码错误');
        }

        return $this->loginSuccess($admin, '登录成功');
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

        // 补充小区信息（仅角色3-7需要）
        $communityIds = $adminInfo['community_ids'] ?? '';
        $communityName = '';
        if (in_array($roleId, [3, 4, 5, 6, 7]) && !empty($communityIds)) {
            $ids = array_filter(explode(',', $communityIds));
            if (!empty($ids)) {
                $communityName = Db::name('community')->where('id', intval($ids[0]))->value('name') ?? '';
            }
        }

        return $this->success([
            'userInfo' => [
                'id'             => $adminInfo['id'],
                'username'       => $adminInfo['username'],
                'nickname'       => $adminInfo['nickname'],
                'avatar'         => $adminInfo['avatar'],
                'role'           => $role['name'] ?? '',
                'role_id'        => $roleId,
                'community_ids'  => $communityIds,
                'community_name' => $communityName,
            ],
            'menus'       => tree_list($menus),
            'permissions' => $permissions,
        ]);
    }

    // ========== 微信 OAuth 跳转 ==========

    public function wechatOAuth()
    {
        $communityId = intval($this->request->param('community_id', 0));
        if ($communityId <= 0) {
            $communityId = intval($this->request->param('cid', 0));
        }
        if ($communityId <= 0) {
            return $this->error('请指定小区ID（?community_id=X）');
        }

        $wxConfig = WechatService::getCommunityWechatConfig($communityId);
        if (!$wxConfig || empty($wxConfig['app_id'])) {
            return $this->error('该小区未配置微信公众号');
        }

        $domain = $this->request->domain();
        $callbackUrl = $domain . '/index.php/admin/wechatCallback';
        $state = base64_encode(json_encode([
            'community_id' => $communityId,
            'redirect'     => '/admin/login.html',
        ]));

        $oauthUrl = WechatService::buildOAuthUrl($wxConfig['app_id'], $callbackUrl, 'snsapi_base', $state);
        return redirect($oauthUrl);
    }

    // ========== 微信 OAuth 回调 ==========

    public function wechatCallback()
    {
        $code = $this->request->param('code', '');
        $stateRaw = $this->request->param('state', '');

        if (empty($code)) return $this->error('微信授权失败：缺少code');

        $state = [];
        if ($stateRaw) {
            $decoded = json_decode(base64_decode($stateRaw), true);
            if ($decoded) $state = $decoded;
        }
        $communityId = intval($state['community_id'] ?? 0);
        $redirectTo = $state['redirect'] ?? '/admin/login.html';

        if ($communityId <= 0) return $this->error('参数错误：社区ID缺失');

        $wxConfig = WechatService::getCommunityWechatConfig($communityId);
        if (!$wxConfig) return $this->error('公众号配置不存在');

        $result = WechatService::oauth2AccessToken($wxConfig['app_id'], $wxConfig['app_secret'], $code);
        if (!empty($result['error'])) {
            return $this->error('微信授权失败: ' . $result['msg']);
        }
        $openid = $result['openid'] ?? '';
        if (empty($openid)) return $this->error('未能获取到 openid');

        // 查找是否已有绑定该 openid 的管理员账号
        $admin = Db::name('admin_user')
            ->where('openid', $openid)
            ->where('status', 1)
            ->find();

        $domain = $this->request->domain();

        if ($admin) {
            // 已有绑定账号，签发 token 并重定向
            $token = $this->makeToken($admin);
            $sep = (strpos($redirectTo, '?') === false) ? '?' : '&';
            $finalUrl = $domain . $redirectTo . $sep . 'wechat_token=' . urlencode($token);
            return redirect($finalUrl);
        } else {
            // 没有绑定账号 → 跳转到绑定页，带上 openid
            $sep = (strpos($redirectTo, '?') === false) ? '?' : '&';
            $finalUrl = $domain . $redirectTo . $sep . 'wx_openid=' . urlencode($openid) . '&wx_cid=' . $communityId . '&action=wx_bind';
            return redirect($finalUrl);
        }
    }

    // ========== 微信登录 POST 接口（通用） ==========

    public function wechatLogin()
    {
        $code = $this->request->post('code', '');
        $communityId = intval($this->request->post('community_id', 0));

        if (empty($code)) return $this->error('缺少微信授权码');
        if ($communityId <= 0) return $this->error('请选择小区');

        $wxConfig = WechatService::getCommunityWechatConfig($communityId);
        if (!$wxConfig || empty($wxConfig['app_id'])) {
            return $this->error('该小区未配置微信公众号');
        }

        $result = WechatService::oauth2AccessToken($wxConfig['app_id'], $wxConfig['app_secret'], $code);
        if (!empty($result['error'])) {
            return $this->error('微信授权失败: ' . $result['msg']);
        }
        $openid = $result['openid'] ?? '';
        if (empty($openid)) return $this->error('未能获取到 openid');

        $admin = Db::name('admin_user')->where('openid', $openid)->where('status', 1)->find();
        if ($admin) {
            return $this->loginSuccess($admin, '微信登录成功');
        }

        // 未绑定 → 返回 openid 让前端走绑定流程
        return $this->success([
            'openid'       => $openid,
            'community_id' => $communityId,
            'need_bind'    => true,
        ], '请绑定管理员账号');
    }

    // ========== 微信绑定已有管理员账号 ==========

    public function wechatBind()
    {
        $openid = $this->request->post('openid', '');
        $username = $this->request->post('username', '');
        $password = $this->request->post('password', '');

        if (empty($openid)) return $this->error('缺少 openid');
        if (empty($username) || empty($password)) return $this->error('请输入用户名和密码');

        // 校验用户名密码
        $admin = Db::name('admin_user')->where('username', $username)->find();
        if (!$admin || !verify_password($password, $admin['password'])) {
            return $this->error('用户名或密码错误');
        }
        if ($admin['status'] != 1) {
            return $this->error('账户已被禁用');
        }

        // 检查这个 openid 是否已被其他账号绑定
        $existByOpenid = Db::name('admin_user')->where('openid', $openid)->where('id', '<>', $admin['id'])->find();
        if ($existByOpenid) {
            return $this->error('该微信已被其他账号绑定');
        }

        // 绑定 openid
        Db::name('admin_user')->where('id', $admin['id'])->update([
            'openid'      => $openid,
            'update_time' => date('Y-m-d H:i:s'),
        ]);

        return $this->loginSuccess($admin, '微信绑定成功');
    }

    // ========== 私有辅助 ==========

    private function makeToken(array $admin): string
    {
        $jwtConfig = config('jwt');
        $now = time();
        $payload = [
            'iss'  => $jwtConfig['iss'],
            'aud'  => $jwtConfig['aud'],
            'iat'  => $now,
            'nbf'  => $now,
            'exp'  => $now + $jwtConfig['exp'],
            'sub'  => $admin['id'],
            'type' => 'admin',
        ];
        return JWT::encode($payload, $jwtConfig['key'], $jwtConfig['algorithm']);
    }

    private function loginSuccess(array $admin, string $msg = '登录成功')
    {
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

        // 补充小区信息（仅角色3-7需要）
        $communityIds = $admin['community_ids'] ?? '';
        $communityName = '';
        if (in_array($admin['role_id'], [3, 4, 5, 6, 7]) && !empty($communityIds)) {
            $ids = array_filter(explode(',', $communityIds));
            if (!empty($ids)) {
                $communityName = Db::name('community')->where('id', intval($ids[0]))->value('name') ?? '';
            }
        }

        return $this->success([
            'token'    => $this->makeToken($admin),
            'userInfo' => [
                'id'             => $admin['id'],
                'username'       => $admin['username'],
                'nickname'       => $admin['nickname'],
                'avatar'         => $admin['avatar'],
                'role'           => $role['name'] ?? '',
                'role_id'        => $admin['role_id'],
                'community_ids'  => $communityIds,
                'community_name' => $communityName,
            ],
            'menus' => tree_list($menus),
        ], $msg);
    }
}
