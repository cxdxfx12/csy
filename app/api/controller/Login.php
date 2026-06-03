<?php
namespace app\api\controller;

use app\api\BaseApi;
use Firebase\JWT\JWT;
use think\facade\Db;
use think\facade\Cache;
use service\WechatService;

class Login extends BaseApi
{
    protected $noAuth = ['login', 'register', 'sendSms', 'resetPassword', 'wechatOAuth', 'wechatCallback', 'wechatLogin', 'wechatAutoRegister'];

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

        return $this->issueToken($owner, '登录成功');
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

    // ========== 微信 OAuth 跳转（公众号 H5）==========

    /**
     * 公众号 OAuth 入口：GET /api/wechatOAuth?community_id=1
     * 重定向到微信授权页面
     */
    public function wechatOAuth()
    {
        $communityId = intval($this->request->param('community_id', 0));
        $redirectTo = $this->request->param('redirect', '/owner.html');

        if ($communityId <= 0) {
            // 未指定小区则跳到选择页
            $communityId = $this->request->param('cid', 0);
        }
        if ($communityId <= 0) {
            return $this->error('请指定小区ID（?community_id=X）');
        }

        $wxConfig = WechatService::getCommunityWechatConfig($communityId);
        if (!$wxConfig || empty($wxConfig['app_id'])) {
            return $this->error('该小区未配置微信公众号');
        }

        // 回调地址：当前域名的 /index.php/api/wechatCallback
        $domain = $this->request->domain();
        $callbackUrl = $domain . '/index.php/api/wechatCallback';
        // state 存社区ID和回跳地址
        $state = base64_encode(json_encode([
            'community_id' => $communityId,
            'redirect'     => $redirectTo,
        ]));

        $oauthUrl = WechatService::buildOAuthUrl($wxConfig['app_id'], $callbackUrl, 'snsapi_base', $state);
        return redirect($oauthUrl);
    }

    /**
     * 公众号 OAuth 回调：GET /api/wechatCallback?code=xxx&state=xxx
     * 微信重定向回来后处理登录
     */
    public function wechatCallback()
    {
        $code = $this->request->param('code', '');
        $stateRaw = $this->request->param('state', '');

        if (empty($code)) return $this->error('微信授权失败：缺少code');

        // 解析 state
        $state = [];
        if ($stateRaw) {
            $decoded = json_decode(base64_decode($stateRaw), true);
            if ($decoded) $state = $decoded;
        }
        $communityId = intval($state['community_id'] ?? 0);
        $redirectTo = $state['redirect'] ?? '/owner.html';

        if ($communityId <= 0) return $this->error('参数错误：社区ID缺失');

        $wxConfig = WechatService::getCommunityWechatConfig($communityId);
        if (!$wxConfig) return $this->error('公众号配置不存在');

        // code → openid
        $result = WechatService::oauth2AccessToken($wxConfig['app_id'], $wxConfig['app_secret'], $code);
        if (!empty($result['error'])) {
            return $this->error('微信授权失败: ' . $result['msg']);
        }
        $openid = $result['openid'] ?? '';

        // 登录或创建用户
        $token = $this->loginByOpenid($openid, $communityId, $wxConfig);

        // 重定向回前端，带上 token
        $domain = $this->request->domain();
        $sep = (strpos($redirectTo, '?') === false) ? '?' : '&';
        $finalUrl = $domain . $redirectTo . $sep . 'wechat_token=' . urlencode($token);
        return redirect($finalUrl);
    }

    // ========== 通用微信登录（小程序 / H5直接调用）==========

    /**
     * 微信登录接口：POST /api/wechatLogin
     * @param code         微信临时码
     * @param type         'mp'=小程序 / 'oa'=公众号（默认oa）
     * @param community_id 小区ID
     */
    public function wechatLogin()
    {
        $code = $this->request->post('code', '');
        $type = $this->request->post('type', 'oa');
        $communityId = intval($this->request->post('community_id', 0));

        if (empty($code)) return $this->error('缺少微信授权码(code)');
        if ($communityId <= 0) return $this->error('请选择小区');

        $wxConfig = WechatService::getCommunityWechatConfig($communityId);
        if (!$wxConfig || empty($wxConfig['app_id'])) {
            return $this->error('该小区未配置微信公众号');
        }

        // 根据类型换取 openid
        if ($type === 'mp') {
            $result = WechatService::code2Session($wxConfig['app_id'], $wxConfig['app_secret'], $code);
        } else {
            $result = WechatService::oauth2AccessToken($wxConfig['app_id'], $wxConfig['app_secret'], $code);
        }

        if (!empty($result['error'])) {
            return $this->error('微信授权失败: ' . $result['msg']);
        }
        $openid = $result['openid'] ?? '';
        $unionid = $result['unionid'] ?? '';

        if (empty($openid)) return $this->error('未能获取到 openid');

        $owner = Db::name('owner')->where('openid', $openid)->whereNull('delete_time')->find();

        if ($owner) {
            // 已有账号，直接登录
            if (!empty($unionid) && empty($owner['wechat_unionid'])) {
                Db::name('owner')->where('id', $owner['id'])->update(['wechat_unionid' => $unionid]);
            }
            return $this->issueToken($owner, '微信登录成功');
        }

        // 首次登录：创建业主记录
        // 尝试通过 unionid 查找
	
        if (!empty($unionid)) {
            $byUnion = Db::name('owner')->where('wechat_unionid', $unionid)->whereNull('delete_time')->find();
            if ($byUnion) {
                Db::name('owner')->where('id', $byUnion['id'])->update(['openid' => $openid]);
                return $this->issueToken($byUnion, '微信登录成功（已关联）');
            }
        }

        // 新建业主记录
        $newOwnerId = Db::name('owner')->insertGetId([
            'community_id'    => $communityId,
            'realname'        => '微信用户',
            'phone'           => '',
            'password'        => '',
            'openid'          => $openid,
            'wechat_unionid'  => $unionid,
            'type'            => 1,
            'status'          => 1,
            'register_time'   => date('Y-m-d H:i:s'),
            'last_login_time' => date('Y-m-d H:i:s'),
            'create_time'     => date('Y-m-d H:i:s'),
        ]);

        $newOwner = Db::name('owner')->where('id', $newOwnerId)->find();
        return $this->issueToken($newOwner, '微信登录成功（新用户）');
    }

    // ========== 微信绑定/解绑（需已登录）==========

    /**
     * 绑定微信：已登录用户通过 code 绑定 openid
     */
    public function wechatBind()
    {
        $code = $this->request->post('code', '');
        $type = $this->request->post('type', 'oa');
        $communityId = intval($this->request->post('community_id', 0));

        if (empty($code)) return $this->error('缺少微信授权码');
        if ($communityId <= 0) return $this->error('请选择小区');

        $wxConfig = WechatService::getCommunityWechatConfig($communityId);
        if (!$wxConfig) return $this->error('公众号配置不存在');

        if ($type === 'mp') {
            $result = WechatService::code2Session($wxConfig['app_id'], $wxConfig['app_secret'], $code);
        } else {
            $result = WechatService::oauth2AccessToken($wxConfig['app_id'], $wxConfig['app_secret'], $code);
        }

        if (!empty($result['error'])) return $this->error('微信授权失败: ' . $result['msg']);
        $openid = $result['openid'] ?? '';
        $unionid = $result['unionid'] ?? '';
        if (empty($openid)) return $this->error('未获取到openid');

        // 检查该 openid 是否已被其他账号绑定
        $conflict = Db::name('owner')
            ->where('openid', $openid)
            ->where('id', '<>', $this->ownerId)
            ->whereNull('delete_time')
            ->find();
        if ($conflict) return $this->error('该微信已被其他账号绑定');

        $update = ['openid' => $openid];
        if (!empty($unionid)) $update['wechat_unionid'] = $unionid;

        Db::name('owner')->where('id', $this->ownerId)->update($update);

        return $this->success(['openid' => $openid], '微信绑定成功');
    }

    /**
     * 解绑微信
     */
    public function wechatUnbind()
    {
        Db::name('owner')->where('id', $this->ownerId)->update([
            'openid' => '', 'wechat_unionid' => '',
        ]);
        return $this->success([], '微信已解绑');
    }

    // ========== 私有辅助 ==========

    /**
     * 签发 JWT token + 更新登录时间
     */
    private function issueToken(array $owner, string $msg = '登录成功')
    {
        if ($owner['status'] != 1) {
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
            'sub'  => $owner['id'],
            'type' => 'owner',
        ];
        $token = JWT::encode($payload, $jwtConfig['key'], $jwtConfig['algorithm']);

        Db::name('owner')->where('id', $owner['id'])->update([
            'last_login_time' => date('Y-m-d H:i:s'),
        ]);

        return $this->success([
            'token'    => $token,
            'userInfo' => [
                'id'           => $owner['id'],
                'realname'     => $owner['realname'],
                'phone'        => $owner['phone'],
                'avatar'       => $owner['avatar'],
                'openid'       => $owner['openid'] ? '已绑定' : '',
                'community_id' => $owner['community_id'],
            ],
            'isNew' => empty($owner['phone']), // 新微信用户标记
        ], $msg);
    }

    /**
     * 通过 openid 登录
     * @return string JWT token
     */
    private function loginByOpenid(string $openid, int $communityId, array $wxConfig): string
    {
        $owner = Db::name('owner')->where('openid', $openid)->whereNull('delete_time')->find();

        if ($owner) {
            // 已有用户
            $resp = $this->issueToken($owner, '微信登录成功');
        } else {
            // 新建用户
            $newOwnerId = Db::name('owner')->insertGetId([
                'community_id'    => $communityId,
                'realname'        => '微信用户',
                'phone'           => '',
                'password'        => '',
                'openid'          => $openid,
                'type'            => 1,
                'status'          => 1,
                'register_time'   => date('Y-m-d H:i:s'),
                'last_login_time' => date('Y-m-d H:i:s'),
                'create_time'     => date('Y-m-d H:i:s'),
            ]);
            $newOwner = Db::name('owner')->where('id', $newOwnerId)->find();
            $resp = $this->issueToken($newOwner, '微信登录成功（新用户）');
        }

        // 从 issueToken 的 Json response 里取 token
        $data = $resp->getData();
        return $data['data']['token'] ?? '';
    }
}
