<?php
namespace app\api\controller;

use app\api\BaseApi;
use Firebase\JWT\JWT;
use think\facade\Db;
use think\facade\Cache;
use service\WechatService;

class Login extends BaseApi
{
    protected $noAuth = ['login', 'register', 'sendSms', 'resetPassword', 'wechatOAuth', 'wechatCallback', 'wechatLogin', 'wechatAutoRegister', 'communityList'];

    public function login()
    {
        $phone = $this->request->post('phone', '');
        $password = $this->request->post('password', '');

        if (empty($phone) || empty($password)) {
            return $this->error('请输入手机号和密码');
        }

        $owner = Db::name('owner')->where('phone', $phone)->find();
        if (!$owner || !verify_password($password, $owner['password'])) {
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

        // 验证短信验证码（注册必须校验）
        $smsCode = $data['sms_code'] ?? '';
        $phone = $data['phone'] ?? '';
        if (empty($smsCode)) return $this->error('请输入短信验证码');
        $cached = Cache::get('sms_code_' . $phone);
        if (!$cached || (string)$cached !== (string)$smsCode) {
            return $this->error('验证码错误或已过期');
        }
        Cache::delete('sms_code_' . $phone);

        $exists = Db::name('owner')->where('phone', $phone)->find();
        if ($exists) return $this->error('该手机号已注册');
        $data['password'] = encrypt_password($data['password']);
        $data['register_time'] = date('Y-m-d H:i:s');
        $data['create_time'] = date('Y-m-d H:i:s');

        // 只保留 ds_owner 表中存在的字段，避免 1054 错误
        $allowed = ['community_id','realname','gender','phone','password',
            'id_card','birthday','email','avatar','type','emergency_contact',
            'emergency_phone','status','remark','register_time','create_time'];
        $data = array_intersect_key($data, array_flip($allowed));

        Db::name('owner')->insert($data);
        return $this->success([], '注册成功');
    }

    public function sendSms()
    {
        $phone = $this->request->post('phone', '');
        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) return $this->error('手机号格式错误');

        // 频率限制：同一手机号 60 秒内只能发一次
        $last = Cache::get('sms_limit_' . $phone);
        if ($last && time() - $last < 60) {
            return $this->error('发送过于频繁，请60秒后再试');
        }

        $code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::set('sms_code_' . $phone, $code, 300);
        Cache::set('sms_limit_' . $phone, time(), 60);

        // 演示模式下仅在 log 中记录验证码，不返回给前端
        $isDemo = config('app.demo_mode', false);
        return $this->success([], $isDemo ? '验证码: ' . $code : '验证码已发送');
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

        // 回调地址：使用配置的OAuth域名
        $domain = WechatService::getOAuthDomain();
        $callbackUrl = $domain . '/index.php/api/wechatCallback';
        // state 存社区ID和回跳地址
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
        $domain = WechatService::getOAuthDomain();
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

        // 检查是否有已软删除的同 openid 记录，有则恢复
        $deleted = Db::name('owner')->where('openid', $openid)->whereNotNull('delete_time')->find();
        if ($deleted) {
            Db::name('owner')->where('id', $deleted['id'])->update([
                'delete_time'     => null,
                'last_login_time' => date('Y-m-d H:i:s'),
                'wechat_unionid'  => $unionid ?: $deleted['wechat_unionid'],
            ]);
            $owner = Db::name('owner')->where('id', $deleted['id'])->find();
            return $this->issueToken($owner, '微信登录成功');
        }

        // 新建业主记录
        // 检查是否存在同小区、有房产但未绑微信的业主记录（可直接绑定而非新建）
        $unclaimedOwner = $this->findUnclaimedOwner($communityId);
        if ($unclaimedOwner) {
            // 存在唯一未绑定微信的业主 → 直接挂载 openid，不新建
            Db::name('owner')->where('id', $unclaimedOwner['id'])->update([
                'openid'          => $openid,
                'wechat_unionid'  => $unionid,
                'last_login_time' => date('Y-m-d H:i:s'),
            ]);
            $unclaimedOwner['openid'] = $openid;
            $unclaimedOwner['wechat_unionid'] = $unionid;
            return $this->issueToken($unclaimedOwner, '微信登录成功（已关联档案）');
        }

        // 无匹配记录 → 新建微信用户
        $phonePlaceholder = 'WX_' . substr(md5($openid), 0, 8);  // 避免 phone='' 触发唯一约束
        $newOwnerId = Db::name('owner')->insertGetId([
            'community_id'    => $communityId,
            'realname'        => '微信用户',
            'phone'           => $phonePlaceholder,
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
        $extraData = [];
        $claimableCount = $this->countClaimableOwners($communityId);
        if ($claimableCount > 0) {
            $extraData['claimableCount'] = $claimableCount;
        }
        return $this->issueToken($newOwner, '微信登录成功（新用户）', $extraData);
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
     * 获取启用的小区列表（公开接口，供登录页下拉使用）
     */
    public function communityList()
    {
        $list = Db::name('community')
            ->where('delete_time', null)
            ->where('status', 1)
            ->field('id, name')
            ->order('id', 'asc')
            ->select();
        return $this->success($list);
    }

    /**
     * 签发 JWT token（纯字符串，不包装响应）
     */
    private function _makeToken(array $owner): string
    {
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

        return $token;
    }

    /**
     * 签发 JWT token + 更新登录时间（包装为 JSON 响应）
     */
    private function issueToken(array $owner, string $msg = '登录成功', array $extraData = [])
    {
        if ($owner['status'] != 1) {
            return $this->error('账户已被禁用');
        }

        $token = $this->_makeToken($owner);

        $data = [
            'token'    => $token,
            'userInfo' => [
                'id'           => $owner['id'],
                'realname'     => $owner['realname'],
                'phone'        => $owner['phone'],
                'avatar'       => $owner['avatar'],
                'openid'       => $owner['openid'] ? '已绑定' : '',
                'community_id' => $owner['community_id'],
            ],
            'isNew' => (empty($owner['phone']) || strpos($owner['phone'], 'WX_') === 0), // 占位手机号=新用户
        ];

        // 合并额外数据（如 claimableCount）
        if (!empty($extraData)) {
            $data = array_merge($data, $extraData);
        }

        return $this->success($data, $msg);
    }

    /**
     * 通过 openid 登录
     * @return string JWT token
     */
    private function loginByOpenid(string $openid, int $communityId, array $wxConfig): string
    {
        $owner = Db::name('owner')->where('openid', $openid)->whereNull('delete_time')->find();

        if (!$owner) {
            // 检查是否有已软删除的同 openid 记录，有则恢复
            $deleted = Db::name('owner')->where('openid', $openid)->whereNotNull('delete_time')->find();
            if ($deleted) {
                Db::name('owner')->where('id', $deleted['id'])->update([
                    'delete_time'     => null,
                    'last_login_time' => date('Y-m-d H:i:s'),
                ]);
                $owner = Db::name('owner')->where('id', $deleted['id'])->find();
            } else {
                // 检查是否可直接绑定到已有业主
                $unclaimedOwner = $this->findUnclaimedOwner($communityId);
                if ($unclaimedOwner) {
                    Db::name('owner')->where('id', $unclaimedOwner['id'])->update([
                        'openid'          => $openid,
                        'last_login_time' => date('Y-m-d H:i:s'),
                    ]);
                    $owner = Db::name('owner')->where('id', $unclaimedOwner['id'])->find();
                } else {
                    // 新建用户
                    $phonePlaceholder = 'WX_' . substr(md5($openid), 0, 8);
                    $newOwnerId = Db::name('owner')->insertGetId([
                        'community_id'    => $communityId,
                        'realname'        => '微信用户',
                        'phone'           => $phonePlaceholder,
                        'password'        => '',
                        'openid'          => $openid,
                        'type'            => 1,
                        'status'          => 1,
                        'register_time'   => date('Y-m-d H:i:s'),
                        'last_login_time' => date('Y-m-d H:i:s'),
                        'create_time'     => date('Y-m-d H:i:s'),
                    ]);
                    $owner = Db::name('owner')->where('id', $newOwnerId)->find();
                }
            }
        }

        return $this->_makeToken($owner);
    }

    /**
     * 查找同小区中唯一一个有房产但未绑定微信的业主
     * 条件：openid 为空、状态正常、有真实手机号、拥有关联房间
     * 仅当唯一匹配时返回，防止误绑他人
     */
    private function findUnclaimedOwner(int $communityId): ?array
    {
        // 找到所有符合条件的潜在业主 ID
        $candidates = Db::name('owner')->alias('o')
            ->join('owner_room ocr', 'ocr.owner_id = o.id AND ocr.delete_time IS NULL')
            ->join('room r', 'r.id = ocr.room_id')
            ->where('o.community_id', $communityId)
            ->where('o.status', 1)
            ->where('o.openid', '')
            ->where('o.phone', 'not like', 'WX_%')
            ->where('o.phone', 'not like', 'ARCHIVED_%')
            ->where('o.phone', '<>', '')
            ->whereNull('o.delete_time')
            ->group('o.id')
            ->column('o.id');

        // 只有唯一候选人才自动绑定，避免安全风险
        if (count($candidates) !== 1) {
            return null;
        }

        return Db::name('owner')->where('id', $candidates[0])->find();
    }

    /**
     * 统计可认领的业主数量（用于前台提示）
     */
    private function countClaimableOwners(int $communityId): int
    {
        return Db::name('owner')->alias('o')
            ->join('owner_room ocr', 'ocr.owner_id = o.id AND ocr.delete_time IS NULL')
            ->where('o.community_id', $communityId)
            ->where('o.status', 1)
            ->where('o.openid', '')
            ->where('o.phone', 'not like', 'WX_%')
            ->where('o.phone', 'not like', 'ARCHIVED_%')
            ->where('o.phone', '<>', '')
            ->whereNull('o.delete_time')
            ->group('o.id')
            ->count();
    }
}
