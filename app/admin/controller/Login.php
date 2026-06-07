<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use Firebase\JWT\JWT;
use think\facade\Db;
use service\WechatService;

class Login extends BaseAdmin
{
    protected $noAuth = ['login', 'captcha', 'logout', 'wechatOAuth', 'wechatCallback', 'wechatLogin', 'wechatBind', 'wechatLoginStatus'];

    public function login()
    {
        $username = $this->request->post('username', '');
        $password = $this->request->post('password', '');
        $captcha  = $this->request->post('captcha', '');
        $captchaKey = $this->request->post('captchaKey', '');

        if (empty($username) || empty($password)) {
            return $this->error('请输入用户名和密码');
        }

        // 登录频率限制：同一IP 5分钟内最多10次尝试
        $ip = $this->request->ip();
        if (!login_rate_limit_check($ip, 10, 300)) {
            return $this->error('登录尝试过于频繁，请5分钟后再试');
        }
        login_rate_limit_record($ip);

        // 验证码校验（必填）—— 使用 Session 存储，不依赖文件缓存
        if (empty($captchaKey) || empty($captcha)) {
            return $this->error('请输入验证码');
        }
        $this->ensureSession();
        $sessCaptcha = $_SESSION['admin_captcha'] ?? null;
        $sessKey     = $_SESSION['admin_captcha_key'] ?? null;
        $sessTime    = $_SESSION['admin_captcha_time'] ?? 0;
        // 清除 Session 中的验证码（一次性使用）
        unset($_SESSION['admin_captcha'], $_SESSION['admin_captcha_key'], $_SESSION['admin_captcha_time']);
        if (empty($sessCaptcha) || $sessKey !== $captchaKey || time() - $sessTime > 300) {
            return $this->error('验证码错误或已过期');
        }
        if (strtolower($sessCaptcha) != strtolower($captcha)) {
            return $this->error('验证码错误或已过期');
        }

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
        // 使用 Session 存储验证码（不依赖文件缓存，避免 runtime 目录权限问题）
        $this->ensureSession();
        $_SESSION['admin_captcha']      = $code;
        $_SESSION['admin_captcha_key']  = $key;
        $_SESSION['admin_captcha_time'] = time();

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

        // 获取角色信息和权限白名单
        $role = Db::name('role')->where('id', $roleId)->find();
        $allowedControllers = $roleId == 1 ? '*' : $this->getRolePermissions($role['code'] ?? '');

        if ($roleId == 1) {
            // 超管：全部菜单
            $menus = Db::name('menu')->where('status', 1)->order('sort', 'asc')->select();
        } else {
            // 非超管：先按 role_menu 表取菜单，兜底全量后再按控制器权限过滤
            $menuIds = Db::name('role_menu')->where('role_id', $roleId)->column('menu_id');
            if (!empty($menuIds)) {
                $menus = Db::name('menu')->whereIn('id', $menuIds)->where('status', 1)->order('sort', 'asc')->select();
            }
            if (empty($menus)) {
                $menus = Db::name('menu')->where('status', 1)->order('sort', 'asc')->select();
            }
            // 始终按控制器白名单过滤菜单（处理 role_menu 中可能包含的超管菜单）
            if ($allowedControllers !== '*') {
                $permMap = $this->buildPermissionMap();
                // 第一遍：过滤掉无权限的叶子菜单（父级目录保留，后续再清理空目录）
                $menus = array_values(array_filter((array)$menus, function ($menu) use ($permMap, $allowedControllers) {
                    $perm = $menu['permission'] ?? '';
                    if (empty($perm) || ($menu['parent_id'] ?? 0) == 0) return true;
                    return isset($permMap[$perm]) && in_array($permMap[$perm], $allowedControllers);
                }));
                // 第二遍：删除没有子节点的空父级目录
                $childIds = [];
                foreach ($menus as $m) {
                    if (($m['parent_id'] ?? 0) > 0) {
                        $childIds[$m['parent_id']] = true;
                    }
                }
                $menus = array_values(array_filter($menus, function ($menu) use ($childIds) {
                    if (($menu['parent_id'] ?? 0) == 0) {
                        // 父级目录：只保留有子菜单的
                        return isset($childIds[$menu['id']]);
                    }
                    return true;
                }));
            }
        }

        // 权限列表也按角色过滤
        if ($allowedControllers === '*') {
            $permissions = Db::name('menu')->where('type', 'in', [2, 3])->column('permission');
        } else {
            $permMap = $this->buildPermissionMap();
            $allPerms = Db::name('menu')->where('type', 'in', [2, 3])->column('permission');
            $permissions = [];
            foreach ($allPerms as $p) {
                if (isset($permMap[$p]) && in_array($permMap[$p], $allowedControllers)) {
                    $permissions[] = $p;
                }
            }
        }

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
                'role_code'      => $role['code'] ?? '',
                'role_id'        => $roleId,
                'community_ids'  => $communityIds,
                'community_name' => $communityName,
            ],
            'menus'       => tree_list($menus),
            'permissions' => $permissions,
            'debug'       => [
                'allowedControllers' => is_array($allowedControllers) ? array_values($allowedControllers) : $allowedControllers,
                'menuCount'          => count((array)$menus),
            ],
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
            // 自动取第一个有微信配置的小区
            $first = Db::name('community_wechat_config')
                ->where('status', 1)
                ->where('app_id', '<>', '')
                ->order('id', 'asc')
                ->find();
            if ($first) {
                $communityId = intval($first['community_id']);
            }
        }
        if ($communityId <= 0) {
            return $this->error('没有可用的小区微信配置，请先在后台配置微信公众号');
        }

        $wxConfig = WechatService::getCommunityWechatConfig($communityId);
        if (!$wxConfig || empty($wxConfig['app_id'])) {
            return $this->error('该小区未配置微信公众号');
        }

        $domain = WechatService::getOAuthDomain();
        $callbackUrl = $domain . '/index.php/admin/wechatCallback';

        // 生成扫码登录会话（PC端轮询用），使用直接文件存储避免 Cache 权限问题
        $sessionKey = md5(uniqid('wxlogin_', true));
        $this->wxSessionSet($sessionKey, ['status' => 'pending', 'time' => time()], 300);

        $state = base64_encode(json_encode([
            'community_id' => $communityId,
            'redirect'     => '/admin/login',
            'session_key'  => $sessionKey,
        ]));

        $oauthUrl = WechatService::buildOAuthUrl($wxConfig['app_id'], $callbackUrl, 'snsapi_userinfo', $state);

        // 前端直接跳转模式：返回 JSON，由前端用 location.href 跳转
        // 避免服务器 302 跨域重定向链导致微信浏览器不拦截 OAuth
        if ($this->request->param('json') == '1') {
            return $this->success([
                'oauth_url'   => $oauthUrl,
                'session_key' => $sessionKey,
            ], 'ok');
        }

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

        $domain = WechatService::getOAuthDomain();

        if ($admin) {
            // 已有绑定账号，签发 token 并重定向
            $token = $this->makeToken($admin);
            // 通过 session_key 回传给 PC 端轮询
            $sessionKey = $state['session_key'] ?? '';
            if ($sessionKey) {
                $this->wxSessionSet($sessionKey, [
                    'status' => 'ok',
                    'token'  => $token,
                    'time'   => time(),
                ], 300);
            }
            $sep = (strpos($redirectTo, '?') === false) ? '?' : '&';
            $finalUrl = $domain . $redirectTo . $sep . 'wechat_token=' . urlencode($token);
            return redirect($finalUrl);
        } else {
            // 没有绑定账号 → 跳转到绑定页，带上 openid
            $sessionKey = $state['session_key'] ?? '';
            if ($sessionKey) {
                $this->wxSessionSet($sessionKey, [
                    'status'    => 'need_bind',
                    'openid'    => $openid,
                    'cid'       => $communityId,
                    'time'      => time(),
                ], 300);
            }
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

    // ========== PC 端扫码轮询状态 ==========

    public function wechatLoginStatus()
    {
        $sessionKey = $this->request->param('session', '');
        if (empty($sessionKey)) {
            return $this->error('缺少会话标识');
        }

        $data = $this->wxSessionGet($sessionKey);
        if (!$data) {
            return $this->success(['status' => 'expired'], '会话已过期');
        }

        if ($data['status'] === 'ok') {
            // 返回 token 并清除缓存
            $this->wxSessionDelete($sessionKey);
            return $this->success([
                'status' => 'ok',
                'token'  => $data['token'],
            ], '登录成功');
        }

        if ($data['status'] === 'need_bind') {
            $this->wxSessionDelete($sessionKey);
            return $this->success([
                'status'    => 'need_bind',
                'openid'    => $data['openid'] ?? '',
                'cid'       => $data['cid'] ?? 0,
            ], '需要绑定账号');
        }

        return $this->success(['status' => 'pending'], '等待扫码');
    }

    // ========== 私有辅助 ==========

    /**
     * 确保 Session 已启动（用于验证码存储）
     */
    private function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $savePath = session_save_path() ?: sys_get_temp_dir();
            if (!is_dir($savePath)) {
                @mkdir($savePath, 0755, true);
            }
            session_start();
        }
    }

    /**
     * 直接文件存储：写入扫码登录会话（绕过 ThinkPHP Cache 避免文件权限问题）
     */
    private function wxSessionSet(string $key, array $data, int $ttl = 300): void
    {
        $dir = RUNTIME_PATH . 'cache' . DIRECTORY_SEPARATOR;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $file = $dir . 'wxlogin_' . md5($key) . '.php';
        $payload = [
            'data'      => $data,
            'expire_at' => time() + $ttl,
        ];
        @file_put_contents($file, '<?php return ' . var_export($payload, true) . ';', LOCK_EX);
    }

    /**
     * 直接文件存储：读取扫码登录会话
     */
    private function wxSessionGet(string $key): ?array
    {
        $file = RUNTIME_PATH . 'cache' . DIRECTORY_SEPARATOR . 'wxlogin_' . md5($key) . '.php';
        if (!file_exists($file)) return null;
        $payload = @include $file;
        if (!$payload || !is_array($payload) || ($payload['expire_at'] ?? 0) < time()) {
            @unlink($file);
            return null;
        }
        return $payload['data'] ?? null;
    }

    /**
     * 直接文件存储：删除扫码登录会话
     */
    private function wxSessionDelete(string $key): void
    {
        $file = RUNTIME_PATH . 'cache' . DIRECTORY_SEPARATOR . 'wxlogin_' . md5($key) . '.php';
        if (file_exists($file)) {
            @unlink($file);
        }
    }

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
        $allowedControllers = $admin['role_id'] == 1 ? '*' : $this->getRolePermissions($role['code'] ?? '');
        $menus = [];
        if ($admin['role_id'] == 1) {
            $menus = Db::name('menu')->where('status', 1)->order('sort', 'asc')->select();
        } else {
            $menuIds = Db::name('role_menu')->where('role_id', $admin['role_id'])->column('menu_id');
            if (!empty($menuIds)) {
                $menus = Db::name('menu')->whereIn('id', $menuIds)->where('status', 1)->order('sort', 'asc')->select();
            }
            // 兜底：如果角色菜单表为空，显示所有可用菜单（后端控制器权限仍会拦截无权限操作）
            if (empty($menus)) {
                $menus = Db::name('menu')->where('status', 1)->order('sort', 'asc')->select();
            }
            // 按控制器白名单过滤菜单
            if ($allowedControllers !== '*') {
                $permMap = $this->buildPermissionMap();
                $menus = array_values(array_filter((array)$menus, function ($menu) use ($permMap, $allowedControllers) {
                    $perm = $menu['permission'] ?? '';
                    if (empty($perm) || ($menu['parent_id'] ?? 0) == 0) return true;
                    return isset($permMap[$perm]) && in_array($permMap[$perm], $allowedControllers);
                }));
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
