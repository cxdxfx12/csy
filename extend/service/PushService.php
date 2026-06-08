<?php
/**
 * 大圣物业 - 统一推送服务
 * 支持多渠道推送：SSE(实时推送) / 微信模板消息 / 短信
 */
namespace service;

use think\facade\Db;

class PushService
{
    /**
     * 推送新报修单通知
     * @param int $orderId 报修单ID
     * @param array $orderData 报修单数据
     * @param int $workerId 指派维修工ID (0=待派单)
     * @param int $communityId 小区ID
     */
    public static function pushNewRepair(int $orderId, array $orderData, int $workerId, int $communityId): void
    {
        $title = '新报修单';
        $content = $orderData['title'] ?? '';
        $orderNo = $orderData['order_no'] ?? '';
        $isUrgent = !empty($orderData['is_urgent']);

        if ($isUrgent) $title = '【紧急】新报修单';

        $eventData = [
            'order_id'     => $orderId,
            'order_no'     => $orderNo,
            'title'        => $content,
            'reporter'     => $orderData['reporter'] ?? '',
            'reporter_phone'=> $orderData['reporter_phone'] ?? '',
            'community_id' => $communityId,
            'is_urgent'    => $isUrgent,
            'worker_id'    => $workerId,
            'worker_name'  => $orderData['worker_name'] ?? '',
        ];

        if ($workerId > 0) {
            // 已指派的，推送给指定维修工
            self::createPushEvent('repair_assign', $title, $content, 'worker', $workerId, $eventData, $communityId);
        }

        // 同时推送给所有管理员（admin）
        self::createPushEvent('repair_new', $title, $content, 'admin', 0, $eventData, $communityId);
    }

    /**
     * 推送报修已派单通知（管理员手动派单时）
     */
    public static function pushRepairAssign(int $orderId, int $workerId, array $orderData, int $communityId): void
    {
        $orderNo = $orderData['order_no'] ?? '';
        $title = '新派单工单';
        $content = $orderData['title'] ?? '';

        $worker = Db::name('repair_worker')->where('id', $workerId)->find();
        $workerName = $worker['name'] ?? '';

        $eventData = [
            'order_id'     => $orderId,
            'order_no'     => $orderNo,
            'title'        => $content,
            'worker_id'    => $workerId,
            'worker_name'  => $workerName,
            'community_id' => $communityId,
        ];

        // 推送给指定维修工
        self::createPushEvent('repair_assign', '您有新的派单', $content, 'worker', $workerId, $eventData, $communityId);
    }

    /**
     * 获取推送配置（按小区，未配置则用默认值）
     */
    private static function getPushConfig(int $communityId): array
    {
        static $cache = [];
        $key = 'push_' . $communityId;
        if (isset($cache[$key])) return $cache[$key];

        $config = Db::name('push_config')
            ->where('community_id', $communityId)
            ->find();

        // 未配置时用默认值
        $cache[$key] = [
            'sse_enable'          => $config['sse_enable'] ?? 1,
            'wechat_enable'       => $config['wechat_enable'] ?? 1,
            'sms_enable'          => $config['sms_enable'] ?? 0,
            'repair_new_enable'   => $config['repair_new_enable'] ?? 1,
            'repair_assign_enable'=> $config['repair_assign_enable'] ?? 1,
        ];

        return $cache[$key];
    }

    /**
     * 检查事件类型是否启用
     */
    private static function isEventEnabled(string $eventType, int $communityId): bool
    {
        $cfg = self::getPushConfig($communityId);
        $map = [
            'repair_new'    => 'repair_new_enable',
            'repair_assign' => 'repair_assign_enable',
        ];
        $key = $map[$eventType] ?? '';
        return $key ? ($cfg[$key] ?? 1) : true;
    }

    /**
     * 创建推送事件记录并触发各渠道推送
     */
    private static function createPushEvent(string $eventType, string $title, string $content, string $targetType, int $targetId, array $eventData, int $communityId): void
    {
        // 检查事件类型开关
        if (!self::isEventEnabled($eventType, $communityId)) {
            return;
        }

        $cfg = self::getPushConfig($communityId);
        $now = date('Y-m-d H:i:s');

        try {
            $eventId = Db::name('push_event')->insertGetId([
                'event_type'   => $eventType,
                'title'        => $title,
                'content'      => $content,
                'target_type'  => $targetType,
                'target_id'    => $targetId,
                'target_data'  => json_encode($eventData, JSON_UNESCAPED_UNICODE),
                'community_id' => $communityId,
                'sse_sent'     => $cfg['sse_enable'] ? 0 : 2,
                'wechat_sent'  => 0,
                'sms_sent'     => 0,
                'create_time'  => $now,
            ]);
        } catch (\Exception $e) {
            return;
        }

        // SSE 由前端主动连接拉取，无需服务端额外操作

        // 微信模板消息（需 wechat_enable 开启）
        if ($targetType === 'worker' && $targetId > 0 && $cfg['wechat_enable']) {
            self::sendWechatTemplate($eventId, $targetId, $title, $content, $eventData, $communityId);
        }

        // 短信推送（需 sms_enable 开启）
        if ($targetType === 'worker' && $targetId > 0 && $cfg['sms_enable']) {
            self::sendSms($eventId, $targetId, $content, $communityId);
        }
    }

    /**
     * 微信模板消息推送
     */
    private static function sendWechatTemplate(int $eventId, int $workerId, string $title, string $content, array $eventData, int $communityId): void
    {
        try {
            // 获取维修工openid
            $worker = Db::name('repair_worker')->where('id', $workerId)->find();
            if (!$worker || empty($worker['openid'])) {
                Db::name('push_event')->where('id', $eventId)->update(['wechat_sent' => 2]);
                return;
            }

            // 获取公众号配置
            $wechatConfig = WechatService::getCommunityWechatConfig($communityId);
            if (!$wechatConfig || empty($wechatConfig['app_id']) || empty($wechatConfig['app_secret'])) {
                Db::name('push_event')->where('id', $eventId)->update(['wechat_sent' => 2]);
                return;
            }

            // 使用报修派单模板ID，如未配置则用催缴模板兜底
            $templateId = $wechatConfig['template_repair_assign'] ?: $wechatConfig['template_arrears'] ?? '';
            if (empty($templateId)) {
                Db::name('push_event')->where('id', $eventId)->update(['wechat_sent' => 2]);
                return;
            }

            $orderNo = $eventData['order_no'] ?? '';
            $reporter = $eventData['reporter'] ?? '';
            $workerName = $eventData['worker_name'] ?? $worker['name'] ?? '';

            // 构建模板消息数据（标准维修通知模板字段）
            $templateData = [
                'first'    => ['value' => $title . "：{$content}", 'color' => '#173177'],
                'keyword1' => ['value' => $orderNo, 'color' => '#173177'],
                'keyword2' => ['value' => $reporter ?: '未知业主', 'color' => '#173177'],
                'keyword3' => ['value' => date('Y-m-d H:i:s'), 'color' => '#173177'],
                'remark'   => ['value' => "点击查看详情并处理工单", 'color' => '#888888'],
            ];

            $url = WechatService::getOAuthDomain() . '/staff/repair';

            $result = WechatService::sendTemplateMsg(
                $wechatConfig['app_id'],
                $wechatConfig['app_secret'],
                $worker['openid'],
                $templateId,
                $templateData,
                $url
            );

            $sent = (!isset($result['error']) || !$result['error']) ? 1 : 2;
            Db::name('push_event')->where('id', $eventId)->update(['wechat_sent' => $sent]);

        } catch (\Exception $e) {
            Db::name('push_event')->where('id', $eventId)->update(['wechat_sent' => 2]);
        }
    }

    /**
     * 短信推送（待接入短信服务商）
     * @param int $eventId
     * @param int $workerId
     * @param string $content
     * @param int $communityId
     */
    public static function sendSms(int $eventId, int $workerId, string $content, int $communityId): void
    {
        try {
            $worker = Db::name('repair_worker')->where('id', $workerId)->find();
            if (!$worker || empty($worker['phone'])) {
                Db::name('push_event')->where('id', $eventId)->update(['sms_sent' => 2]);
                return;
            }

            // 获取短信配置（阿里云/腾讯云）
            $smsConfig = Db::name('sms_config')
                ->where('community_id', $communityId)
                ->where('status', 1)
                ->find();

            if (!$smsConfig) {
                Db::name('push_event')->where('id', $eventId)->update(['sms_sent' => 2]);
                return;
            }

            // 根据短信服务商类型发送
            $provider = $smsConfig['provider'] ?? 'aliyun';
            $phone = $worker['phone'];
            $templateCode = $smsConfig['repair_template'] ?? '';
            $signName = $smsConfig['sign_name'] ?? '';

            if ($provider === 'aliyun') {
                self::sendAliyunSms($smsConfig, $phone, $signName, $templateCode, ['content' => $content]);
            } elseif ($provider === 'tencent') {
                self::sendTencentSms($smsConfig, $phone, $signName, $templateCode, ['content' => $content]);
            }

            Db::name('push_event')->where('id', $eventId)->update(['sms_sent' => 1]);

        } catch (\Exception $e) {
            Db::name('push_event')->where('id', $eventId)->update(['sms_sent' => 2]);
        }
    }

    /**
     * 阿里云短信发送
     */
    private static function sendAliyunSms(array $config, string $phone, string $signName, string $templateCode, array $params): void
    {
        // 阿里云短信API签名逻辑
        $accessKeyId = $config['access_key_id'] ?? '';
        $accessKeySecret = $config['access_key_secret'] ?? '';

        if (!$accessKeyId || !$accessKeySecret) return;

        $apiParams = [
            'AccessKeyId'      => $accessKeyId,
            'Action'           => 'SendSms',
            'Format'           => 'JSON',
            'PhoneNumbers'     => $phone,
            'SignName'         => $signName,
            'TemplateCode'     => $templateCode,
            'TemplateParam'    => json_encode($params, JSON_UNESCAPED_UNICODE),
            'SignatureMethod'  => 'HMAC-SHA1',
            'SignatureNonce'   => uniqid(mt_rand()),
            'SignatureVersion' => '1.0',
            'Timestamp'        => gmdate("Y-m-d\TH:i:s\Z"),
            'Version'          => '2017-05-25',
        ];

        ksort($apiParams);
        $queryString = '';
        foreach ($apiParams as $k => $v) {
            $queryString .= '&' . self::specialUrlEncode($k) . '=' . self::specialUrlEncode($v);
        }
        $queryString = substr($queryString, 1);

        $stringToSign = 'POST&%2F&' . self::specialUrlEncode($queryString);
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $accessKeySecret . '&', true));
        $apiParams['Signature'] = $signature;

        $url = 'http://dysmsapi.aliyuncs.com/?' . http_build_query($apiParams);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * 腾讯云短信发送
     */
    private static function sendTencentSms(array $config, string $phone, string $signName, string $templateCode, array $params): void
    {
        // 腾讯云短信API - 需安装 SDK
        // 这里提供接入框架，实际使用需引入 tencentcloud-sdk-php
        // $cred = new \TencentCloud\Common\Credential($config['secret_id'], $config['secret_key']);
        // $client = new \TencentCloud\Sms\V20210111\SmsClient($cred, 'ap-guangzhou');
        // ...
    }

    /**
     * URL 特殊编码（阿里云签名用）
     */
    private static function specialUrlEncode(string $str): string
    {
        $str = urlencode($str);
        $str = str_replace(['+', '*', '%7E'], ['%20', '%2A', '~'], $str);
        return $str;
    }

    /**
     * 获取用户未读的SSE事件
     * @param string $targetType worker|admin
     * @param int $targetId 员工ID/0=所有管理员
     * @param int $communityId 小区ID
     * @param int $lastEventId 上次接收的最大事件ID
     * @return array
     */
    public static function getUnreadEvents(string $targetType, int $targetId, int $communityId, int $lastEventId = 0): array
    {
        $query = Db::name('push_event')
            ->where('create_time', '>=', date('Y-m-d H:i:s', time() - 3600)) // 只查最近1小时
            ->where('sse_sent', 1) // 已标记为SSE可推送
            ->where('id', '>', $lastEventId)
            ->order('id', 'asc');

        if ($targetType === 'admin') {
            // 管理员：接收所有小区的事件
            $query->where('target_type', 'admin');
            if ($communityId > 0) {
                $query->where('community_id', $communityId);
            }
        } else {
            // 维修工：仅接收推送给自己的
            $query->where('target_type', 'worker')
                ->where('target_id', $targetId);
        }

        $events = $query->select()->toArray();

        // 标记为SSE未读（保留在数据库供历史查询）
        foreach ($events as &$e) {
            $e['target_data'] = json_decode($e['target_data'] ?? '{}', true);
        }

        return $events;
    }

    /**
     * 标记事件为SSE已推送（客户端已收到）
     */
    public static function markSseSent(int $eventId): void
    {
        Db::name('push_event')->where('id', $eventId)->update(['sse_sent' => 1]);
    }

    /**
     * 标记所有新事件为可SSE推送
     */
    public static function markAllSseReady(): void
    {
        Db::name('push_event')
            ->where('sse_sent', 0)
            ->where('create_time', '>=', date('Y-m-d H:i:s', time() - 3600))
            ->update(['sse_sent' => 1]);
    }
}
