<?php
/**
 * 大圣物业 - 微信公众号服务器消息回调
 * 
 * 用于接收微信服务器推送的消息和事件（关注/取消关注等）
 * 配置 URL: http://你的域名/api/wechat/server/小区ID
 */
namespace app\api\controller;

use think\facade\Db;

class WechatServer
{
    /**
     * 统一入口：GET 验证URL / POST 接收消息
     * 注意：index.php 调用 action 时不传参数，需从 request 中读取
     */
    public function index()
    {
        // 从路由参数 cid 获取小区ID（index.php 已注入到 $_REQUEST / $_GET）
        $communityId = intval(request()->param('cid', 0));
        if ($communityId <= 0) {
            echo 'missing cid';
            exit;
        }

        // 读取小区公众号配置
        $config = Db::name('community_wechat_config')
            ->where('community_id', $communityId)
            ->where('status', 1)
            ->find();

        if (!$config || empty($config['token'])) {
            echo 'config not found';
            exit;
        }

        // GET 请求：微信服务器验证URL
        if (request()->method() === 'GET') {
            $this->verifyUrl($config['token']);
            exit;
        }

        // POST 请求：接收消息
        if (request()->method() === 'POST') {
            $this->handleMessage($config, $communityId);
            exit;
        }
    }

    /**
     * URL 验证（微信服务器配置时调用）
     */
    private function verifyUrl(string $token)
    {
        $signature = request()->param('signature', '');
        $timestamp = request()->param('timestamp', '');
        $nonce     = request()->param('nonce', '');
        $echostr   = request()->param('echostr', '');

        if (empty($signature) || empty($timestamp) || empty($nonce) || empty($echostr)) {
            echo 'params incomplete';
            return;
        }

        // 校验签名
        $tmpArr = [$token, $timestamp, $nonce];
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode('', $tmpArr);
        $mySignature = sha1($tmpStr);

        if ($mySignature === $signature) {
            // 微信要求原样返回 echostr
            header('Content-Type: text/plain; charset=utf-8');
            echo $echostr;
            return;
        }

        echo 'signature fail';
    }

    /**
     * 处理微信推送的消息/事件
     */
    private function handleMessage(array $config, int $communityId)
    {
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            echo 'success';
            return;
        }

        // 解析 XML
        $xml = simplexml_load_string($raw, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (!$xml) {
            echo 'success';
            return;
        }

        $data = json_decode(json_encode($xml), true);

        $msgType = $data['MsgType'] ?? '';
        $event   = $data['Event'] ?? '';
        $fromUserName = $data['FromUserName'] ?? '';

        if (empty($fromUserName)) {
            echo 'success';
            return;
        }

        // 只处理事件消息
        if ($msgType === 'event') {
            switch ($event) {
                case 'subscribe':
                    $this->handleSubscribe($fromUserName, $communityId, $data);
                    break;
                case 'unsubscribe':
                    $this->handleUnsubscribe($fromUserName);
                    break;
            }
        }

        echo 'success';
    }

    /**
     * 处理关注事件
     */
    private function handleSubscribe(string $openid, int $communityId, array $eventData)
    {
        try {
            $existing = Db::name('owner')->where('openid', $openid)->find();

            if ($existing) {
                $update = [
                    'last_login_time' => date('Y-m-d H:i:s'),
                    'status'          => 1,
                ];
                if (!empty($existing['delete_time'])) {
                    $update['delete_time'] = null;
                }
                Db::name('owner')->where('id', $existing['id'])->update($update);
            } else {
                // 用 openid 生成唯一占位手机号，避免 phone 字段 UNIQUE 约束冲突
                $placeholderPhone = 'WX_' . strtoupper(substr(md5($openid), 0, 8));
                Db::name('owner')->insert([
                    'community_id'    => $communityId,
                    'realname'        => '微信用户',
                    'phone'           => $placeholderPhone,
                    'password'        => '',
                    'openid'          => $openid,
                    'type'            => 1,
                    'status'          => 1,
                    'register_time'   => date('Y-m-d H:i:s'),
                    'last_login_time' => date('Y-m-d H:i:s'),
                    'create_time'     => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (\Exception $e) {
            error_log('[WechatServer] subscribe error: ' . $e->getMessage());
        }
    }

    /**
     * 处理取消关注事件
     */
    private function handleUnsubscribe(string $openid)
    {
        try {
            Db::name('owner')
                ->where('openid', $openid)
                ->update(['status' => 0]);
        } catch (\Exception $e) {
            // 静默处理
        }
    }
}
