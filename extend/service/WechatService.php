<?php
/**
 * 大圣物业 - 微信服务封装
 * 支持公众号 OAuth / 小程序 code2Session / access_token 缓存
 */
namespace service;

use think\facade\Db;
use think\facade\Cache;

class WechatService
{
    // ---------- 公众号 OAuth ----------

    /**
     * 构建公众号授权跳转 URL
     * scope: snsapi_base（静默, 不需要用户确认) / snsapi_userinfo（需确认, 可拿头像昵称）
     */
    public static function buildOAuthUrl(string $appId, string $redirectUri, string $scope = 'snsapi_base', string $state = ''): string
    {
        $redirectUri = urlencode($redirectUri);
        $state = urlencode($state);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appId}&redirect_uri={$redirectUri}&response_type=code&scope={$scope}&state={$state}#wechat_redirect";
    }

    /**
     * 公众号 OAuth: code → access_token + openid
     * @return array{openid:string,access_token:string,refresh_token:string,scope:string}|array{error:true,msg:string}
     */
    public static function oauth2AccessToken(string $appId, string $appSecret, string $code): array
    {
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$appId}&secret={$appSecret}&code={$code}&grant_type=authorization_code";
        return self::requestGet($url);
    }

    /**
     * 通过 openid + access_token 拉取用户信息（需要 snsapi_userinfo 授权）
     */
    public static function oauth2UserInfo(string $accessToken, string $openid): array
    {
        $url = "https://api.weixin.qq.com/sns/userinfo?access_token={$accessToken}&openid={$openid}&lang=zh_CN";
        return self::requestGet($url);
    }

    // ---------- 小程序登录 ----------

    /**
     * 小程序 code2Session: code → openid + session_key + unionid
     */
    public static function code2Session(string $appId, string $appSecret, string $code): array
    {
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid={$appId}&secret={$appSecret}&js_code={$code}&grant_type=authorization_code";
        return self::requestGet($url);
    }

    // ---------- access_token ----------

    /**
     * 获取公众号 access_token（带缓存，7200s）
     */
    public static function getAccessToken(string $appId, string $appSecret): string
    {
        $cacheKey = 'wx_access_token_' . md5($appId);
        $token = Cache::get($cacheKey);
        if ($token) return $token;

        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appId}&secret={$appSecret}";
        $result = self::requestGet($url);

        if (empty($result['access_token'])) {
            throw new \Exception('获取 access_token 失败: ' . ($result['errmsg'] ?? '未知错误'));
        }

        $ttl = ($result['expires_in'] ?? 7200) - 300; // 提前5分钟刷新
        if ($ttl < 60) $ttl = 60;
        Cache::set($cacheKey, $result['access_token'], $ttl);

        return $result['access_token'];
    }

    // ---------- 模板消息 ----------

    /**
     * 发送模板消息
     */
    public static function sendTemplateMsg(string $appId, string $appSecret, string $openid, string $templateId, array $data, string $url = ''): array
    {
        $accessToken = self::getAccessToken($appId, $appSecret);
        $apiUrl = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$accessToken}";

        $body = [
            'touser'      => $openid,
            'template_id' => $templateId,
            'data'        => $data,
        ];
        if ($url) $body['url'] = $url;

        return self::requestPost($apiUrl, $body);
    }

    // ---------- 小区配置 ----------

    /**
     * 根据小区ID获取公众号配置
     */
    public static function getCommunityWechatConfig(int $communityId): ?array
    {
        return Db::name('community_wechat_config')
            ->where('community_id', $communityId)
            ->where('status', 1)
            ->find();
    }

    // ---------- 内部工具 ----------

    private static function requestGet(string $url): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        ]);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) return ['error' => true, 'msg' => '请求失败: ' . $err];

        $data = json_decode($resp, true);
        if (!is_array($data)) return ['error' => true, 'msg' => '解析响应失败: ' . substr($resp, 0, 200)];

        // 微信错误统一处理
        if (isset($data['errcode']) && $data['errcode'] != 0) {
            return ['error' => true, 'msg' => '微信接口错误: ' . ($data['errmsg'] ?? '未知'), 'errcode' => $data['errcode']];
        }

        return $data;
    }

    private static function requestPost(string $url, array $body): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json; charset=utf-8'],
        ]);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) return ['error' => true, 'msg' => '请求失败: ' . $err];

        $data = json_decode($resp, true);
        if (!is_array($data)) return ['error' => true, 'msg' => '解析响应失败'];

        if (isset($data['errcode']) && $data['errcode'] != 0) {
            return ['error' => true, 'msg' => '微信接口错误: ' . ($data['errmsg'] ?? '未知'), 'errcode' => $data['errcode']];
        }

        return $data;
    }
}
