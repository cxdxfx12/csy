<?php
namespace app\staff\controller;

use app\staff\BaseStaff;
use Firebase\JWT\JWT;
use think\facade\Db;
use service\WechatService;

class StaffLogin extends BaseStaff
{
    protected $noAuth = ['login', 'wechatOAuth', 'wechatCallback', 'wechatBind'];

    public function login()
    {
        $username = $this->request->post('username', '');
        $password = $this->request->post('password', '');

        // 登录频率限制：同一IP 5分钟内最多10次尝试
        $ip = request()->ip();
        if (!login_rate_limit_check($ip, 10, 300)) {
            return $this->error('登录尝试过于频繁，请5分钟后再试');
        }
        login_rate_limit_record($ip);

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

    // ========== 微信 OAuth 跳转 ==========

    public function wechatOAuth()
    {
        $communityId = intval($this->request->param('community_id', 0));
        $redirectTo = $this->request->param('redirect', '/staff.html#/login');

        // 未传小区ID时，自动查找第一个已配置微信的小区
        if ($communityId <= 0) {
            $config = Db::name('community_wechat_config')
                ->where('status', 1)
                ->where('app_id', '<>', '')
                ->where('app_secret', '<>', '')
                ->order('id asc')
                ->find();
            if ($config) {
                $communityId = $config['community_id'];
            }
        }
        if ($communityId <= 0) {
            return $this->error('系统尚未配置微信公众号');
        }

        $wxConfig = WechatService::getCommunityWechatConfig($communityId);
        if (!$wxConfig || empty($wxConfig['app_id'])) {
            return $this->error('该小区未配置微信公众号');
        }

        $domain = WechatService::getOAuthDomain();
        $callbackUrl = $domain . '/index.php/staff/wechatCallback';
        $state = base64_encode(json_encode([
            'community_id' => $communityId,
            'redirect'     => $redirectTo,
        ]));

        $oauthUrl = WechatService::buildOAuthUrl($wxConfig['app_id'], $callbackUrl, 'snsapi_userinfo', $state);

        // 前端直接跳转模式：返回 JSON，避免 302 跨域重定向导致微信不拦截 OAuth
        if ($this->request->param('json') == '1') {
            return $this->success(['oauth_url' => $oauthUrl], 'ok');
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
        $redirectTo = $state['redirect'] ?? '/staff.html#/login';

        if ($communityId <= 0) return $this->error('参数错误：社区ID缺失');

        $wxConfig = WechatService::getCommunityWechatConfig($communityId);
        if (!$wxConfig) return $this->error('公众号配置不存在');

        $result = WechatService::oauth2AccessToken($wxConfig['app_id'], $wxConfig['app_secret'], $code);
        if (!empty($result['error'])) {
            return $this->error('微信授权失败: ' . $result['msg']);
        }
        $openid = $result['openid'] ?? '';
        if (empty($openid)) return $this->error('未能获取到 openid');

        // 查找是否已有绑定该 openid 的员工账号
        $staff = Db::name('admin_user')
            ->where('openid', $openid)
            ->where('status', 1)
            ->whereNull('delete_time')
            ->find();

        $domain = WechatService::getOAuthDomain();

        if ($staff) {
            // 已有绑定账号，签发 token
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

            $sep = (strpos($redirectTo, '?') === false) ? '?' : '&';
            $finalUrl = $domain . $redirectTo . $sep . 'wechat_token=' . urlencode($token);
            return redirect($finalUrl);
        } else {
            // 没有绑定账号 → 跳转到登录页，提示先绑定
            $sep = (strpos($redirectTo, '?') === false) ? '?' : '&';
            $finalUrl = $domain . $redirectTo . $sep . 'wx_openid=' . urlencode($openid) . '&wx_cid=' . $communityId . '&action=wx_bind';
            return redirect($finalUrl);
        }
    }

    // ========== 微信绑定已有账号 ==========

    public function wechatBind()
    {
        $openid = $this->request->post('openid', '');
        $username = $this->request->post('username', '');
        $password = $this->request->post('password', '');

        if (empty($openid)) return $this->error('缺少 openid');
        if (empty($username) || empty($password)) return $this->error('请输入用户名和密码');

        $staff = Db::name('admin_user')->where('username', $username)->find();
        if (!$staff || !verify_password($password, $staff['password'])) {
            return $this->error('用户名或密码错误');
        }
        if ($staff['status'] != 1) return $this->error('账户已被禁用');

        // 检查该 openid 是否已被其他账号绑定
        $existByOpenid = Db::name('admin_user')->where('openid', $openid)->where('id', '<>', $staff['id'])->whereNull('delete_time')->find();
        if ($existByOpenid) {
            return $this->error('该微信已被其他账号绑定');
        }

        // 绑定 openid
        Db::name('admin_user')->where('id', $staff['id'])->update(['openid' => $openid]);

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
        ], '微信绑定成功');
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
