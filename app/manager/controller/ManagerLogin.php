<?php
namespace app\manager\controller;

use app\manager\BaseManager;
use Firebase\JWT\JWT;
use think\facade\Db;
use service\WechatService;

class ManagerLogin extends BaseManager
{
    protected $noAuth = ['login', 'wechatOAuth', 'wechatCallback', 'wechatLogin', 'wechatRegister'];

    // ========== 用户名密码登录 ==========

    public function login()
    {
        $username = $this->request->post('username', '');
        $password = $this->request->post('password', '');
        $staff = Db::name('admin_user')->where('username', $username)->find();
        if (!$staff || !verify_password($password, $staff['password'])) {
            return $this->error('用户名或密码错误');
        }
        if ($staff['status'] != 1) {
            return $this->error('账户已被禁用');
        }
        return $this->issueToken($staff, '登录成功');
    }

    // ========== 微信 OAuth 跳转 ==========

    public function wechatOAuth()
    {
        $communityId = intval($this->request->param('community_id', 0));
        $redirectTo = $this->request->param('redirect', '/manager.html');

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
        $callbackUrl = $domain . '/index.php/api/manager/wechatCallback';
        $state = base64_encode(json_encode([
            'community_id' => $communityId,
            'redirect'     => $redirectTo,
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
        $redirectTo = $state['redirect'] ?? '/manager.html';

        if ($communityId <= 0) return $this->error('参数错误：社区ID缺失');

        $wxConfig = WechatService::getCommunityWechatConfig($communityId);
        if (!$wxConfig) return $this->error('公众号配置不存在');

        $result = WechatService::oauth2AccessToken($wxConfig['app_id'], $wxConfig['app_secret'], $code);
        if (!empty($result['error'])) {
            return $this->error('微信授权失败: ' . $result['msg']);
        }
        $openid = $result['openid'] ?? '';
        if (empty($openid)) return $this->error('未能获取到 openid');

        // 查找是否已有绑定该 openid 的经理账号
        $manager = Db::name('admin_user')
            ->where('openid', $openid)
            ->where('status', 1)
            ->find();

        if ($manager) {
            // 已有绑定账号，直接登录
            $resp = $this->issueToken($manager, '微信登录成功');
            $data = $resp->getData();
            $token = $data['data']['token'] ?? '';
        } else {
            // 没有绑定账号 → 跳转注册页，带上 openid 和 community_id
            $domain = $this->request->domain();
            $sep = (strpos($redirectTo, '?') === false) ? '?' : '&';
            $finalUrl = $domain . $redirectTo . $sep . 'wx_openid=' . urlencode($openid) . '&wx_cid=' . $communityId . '&action=wx_register';
            return redirect($finalUrl);
        }

        // 已有账号 → 重定向回前端
        $domain = $this->request->domain();
        $sep = (strpos($redirectTo, '?') === false) ? '?' : '&';
        $finalUrl = $domain . $redirectTo . $sep . 'wechat_token=' . urlencode($token);
        return redirect($finalUrl);
    }

    // ========== 通用微信登录 POST 接口 ==========

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

        $manager = Db::name('admin_user')->where('openid', $openid)->where('status', 1)->find();
        if ($manager) {
            return $this->issueToken($manager, '微信登录成功');
        }

        // 未绑定 → 返回 openid 让前端走注册流程
        return $this->success([
            'openid'        => $openid,
            'community_id'  => $communityId,
            'need_register' => true,
        ], '请先注册经理账号');
    }

    // ========== 微信注册（管理员审核后才能登录）==========

    public function wechatRegister()
    {
        $openid = $this->request->post('openid', '');
        $communityId = intval($this->request->post('community_id', 0));
        $realname = $this->request->post('realname', '');
        $phone = $this->request->post('phone', '');
        $username = $this->request->post('username', '');
        $password = $this->request->post('password', '');

        if (empty($openid)) return $this->error('缺少 openid');
        if (empty($username) || empty($password)) return $this->error('请填写用户名和密码');
        if (empty($realname)) return $this->error('请填写姓名');

        // 检查 openid 是否已被绑定
        $existByOpenid = Db::name('admin_user')->where('openid', $openid)->find();
        if ($existByOpenid) return $this->error('该微信已被其他账号绑定');

        // 检查用户名是否重复
        $existByUsername = Db::name('admin_user')->where('username', $username)->find();
        if ($existByUsername) return $this->error('该用户名已存在');

        $now = date('Y-m-d H:i:s');
        $insertId = Db::name('admin_user')->insertGetId([
            'username'       => $username,
            'password'       => encrypt_password($password),
            'openid'         => $openid,
            'nickname'       => $realname,
            'phone'          => $phone,
            'role_id'        => 3, // 小区物管经理角色
            'community_ids'  => (string)$communityId,
            'status'         => 1, // 默认启用
            'create_time'    => $now,
            'update_time'    => $now,
        ]);

        $manager = Db::name('admin_user')->where('id', $insertId)->find();
        return $this->issueToken($manager, '注册成功，欢迎使用小区经理工作台');
    }

    // ========== 私有辅助 ==========

    private function issueToken(array $manager, string $msg = '登录成功')
    {
        if ($manager['status'] != 1) {
            return $this->error('账户已被禁用');
        }

        $jwtConfig = config('jwt');
        $now = time();
        $payload = [
            'iss'  => $jwtConfig['iss'],
            'aud'  => $jwtConfig['aud'],
            'iat'  => $now,
            'nbf'  => $now,
            'exp'  => $now + $jwtConfig['exp'],
            'sub'  => $manager['id'],
            'type' => 'manager',
        ];
        $token = JWT::encode($payload, $jwtConfig['key'], $jwtConfig['algorithm']);

        Db::name('admin_user')->where('id', $manager['id'])->update([
            'update_time' => date('Y-m-d H:i:s'),
        ]);

        return $this->success([
            'token'    => $token,
            'userInfo' => [
                'id'           => $manager['id'],
                'realname'     => $manager['nickname'],
                'username'     => $manager['username'],
                'phone'        => $manager['phone'] ?? '',
                'openid'       => $manager['openid'] ? '已绑定' : '',
                'community_id' => $manager['community_ids'] ?? '',
            ],
            'isNew' => empty($manager['last_login_time']),
        ], $msg);
    }
}
