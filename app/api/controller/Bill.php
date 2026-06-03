<?php
namespace app\api\controller;

use app\api\BaseApi;
use think\facade\Db;
use service\WechatService;

class Bill extends BaseApi
{
    public function lists()
    {
        $ownerId = $this->ownerId;
        [$page, $limit] = $this->getPage();
        $status = $this->request->param('status', '');
        $where = [['owner_id', '=', $ownerId]];
        if ($status !== '') $where[] = ['status', '=', $status];
        $query = Db::name('bill')->where($where)->whereNull('delete_time');
        $total = $query->count();
        $list = $query->page($page, $limit)->order('id', 'desc')->select();
        if (is_object($list)) $list = $list->toArray();
        return $this->success(['list' => $list, 'total' => $total]);
    }

    public function unpaid()
    {
        $ownerId = $this->ownerId;
        $where = [['owner_id', '=', $ownerId], ['status', 'in', [1, 2]]];
        $query = Db::name('bill')->where($where)->whereNull('delete_time');
        $list = $query->select();
        if (is_object($list)) $list = $list->toArray();
        $total = $query->sum('total_amount - paid_amount');
        return $this->success(['list' => $list, 'total_unpaid' => $total]);
    }

    public function detail()
    {
        $id = $this->request->param('id', 0);
        $bill = Db::name('bill')->where('id', $id)->find();
        return $this->success($bill);
    }

    /**
     * 获取支付配置（微信/支付宝支付渠道可用性）
     */
    public function payConfig()
    {
        $ownerId = $this->ownerId;
        $owner = Db::name('owner')->where('id', $ownerId)->find();
        if (!$owner || !$owner['community_id']) {
            return $this->success(['channels' => []]);
        }

        $config = Db::name('community_payment_config')
            ->where('community_id', $owner['community_id'])
            ->where('status', 1)
            ->find();

        $channels = [];
        if ($config && !empty($config['pay_channel'])) {
            $channel = $config['pay_channel'];
            if ($channel === 'wechat' || $channel === 'both') {
                $channels[] = ['id' => 2, 'name' => '微信支付', 'icon' => '💚'];
            }
            if ($channel === 'alipay' || $channel === 'both') {
                $channels[] = ['id' => 3, 'name' => '支付宝', 'icon' => '💙'];
            }
        }
        // 线下支付始终可用
        $channels[] = ['id' => 4, 'name' => '银行转账', 'icon' => '🏦'];
        $channels[] = ['id' => 1, 'name' => '现金缴费', 'icon' => '💵'];
        $channels[] = ['id' => 6, 'name' => '其他方式', 'icon' => '📋'];

        return $this->success(['channels' => $channels]);
    }

    /**
     * 提交支付（创建支付单）
     * 支持：1现金 2微信 3支付宝 4银行转账 5POS刷卡 6其他
     */
    public function pay()
    {
        $billId = $this->request->post('bill_id', 0);
        $payMethod = $this->request->post('pay_method', 2);
        $bill = Db::name('bill')->where('id', $billId)->find();
        if (!$bill) return $this->error('账单不存在');
        if ($bill['status'] == 3) return $this->error('账单已缴清');

        $amount = $bill['total_amount'] - $bill['paid_amount'];
        if ($amount <= 0) return $this->error('账单已缴清');

        $paymentNo = build_order_no('DSP');
        $communityId = $bill['community_id'];

        // 线下支付方式：直接标记为支付成功
        $offlineMethods = [1, 4, 5, 6]; // 现金、银行转账、POS刷卡、其他
        if (in_array($payMethod, $offlineMethods)) {
            $paymentData = [
                'payment_no'   => $paymentNo,
                'bill_id'      => $billId,
                'community_id' => $communityId,
                'owner_id'     => $bill['owner_id'],
                'room_id'      => $bill['room_id'],
                'amount'       => $amount,
                'pay_method'   => $payMethod,
                'trade_no'     => $paymentNo,
                'pay_time'     => date('Y-m-d H:i:s'),
                'operator_id'  => $this->ownerId,
                'operator_type'=> 2,
                'create_time'  => date('Y-m-d H:i:s'),
                'remark'       => '业主端线下支付确认',
            ];

            Db::name('bill_payment')->insert($paymentData);
            Db::name('bill')->where('id', $billId)->update([
                'paid_amount' => $bill['paid_amount'] + $amount,
                'status'      => 3,
                'pay_date'    => date('Y-m-d H:i:s'),
            ]);
            // 创建财务流水
            $this->createFinanceFlow($communityId, $paymentData['payment_no'], $amount, $billId);

            return $this->success([
                'payment_no'  => $paymentNo,
                'pay_method'  => $payMethod,
                'status'      => 'paid',
                'need_wechat' => false,
            ], '缴费确认成功');
        }

        // 微信支付：创建待支付记录，返回支付参数
        if ($payMethod == 2) {
            // 获取支付配置
            $payConfig = Db::name('community_payment_config')
                ->where('community_id', $communityId)
                ->where('status', 1)
                ->find();

            if (!$payConfig || empty($payConfig['wechat_app_id']) || empty($payConfig['wechat_mch_id'])) {
                // 微信支付未配置，降级为线下确认
                return $this->offlineFallback($billId, $bill, $amount, $paymentNo, $communityId);
            }

            // 创建待支付记录
            $paymentData = [
                'payment_no'   => $paymentNo,
                'bill_id'      => $billId,
                'community_id' => $communityId,
                'owner_id'     => $bill['owner_id'],
                'room_id'      => $bill['room_id'],
                'amount'       => $amount,
                'pay_method'   => 2,
                'trade_no'     => $paymentNo,
                'pay_time'     => null, // 未支付
                'operator_id'  => $this->ownerId,
                'operator_type'=> 2,
                'create_time'  => date('Y-m-d H:i:s'),
                'remark'       => '微信支付待付款',
            ];
            Db::name('bill_payment')->insert($paymentData);

            // 尝试调用微信统一下单
            $wechatResult = $this->wechatUnifiedOrder($payConfig, $paymentNo, $amount, $bill);
            if ($wechatResult && isset($wechatResult['prepay_id'])) {
                return $this->success([
                    'payment_no'  => $paymentNo,
                    'pay_method'  => 2,
                    'status'      => 'pending',
                    'need_wechat' => true,
                    'wechat_params' => $wechatResult,
                    'amount'      => $amount,
                ], '请完成微信支付');
            }

            // 微信下单失败，降级
            return $this->offlineFallback($billId, $bill, $amount, $paymentNo, $communityId);
        }

        // 支付宝支付
        if ($payMethod == 3) {
            $payConfig = Db::name('community_payment_config')
                ->where('community_id', $communityId)
                ->where('status', 1)
                ->find();

            if (!$payConfig || empty($payConfig['alipay_app_id'])) {
                return $this->offlineFallback($billId, $bill, $amount, $paymentNo, $communityId, 3);
            }

            $paymentData = [
                'payment_no'   => $paymentNo,
                'bill_id'      => $billId,
                'community_id' => $communityId,
                'owner_id'     => $bill['owner_id'],
                'room_id'      => $bill['room_id'],
                'amount'       => $amount,
                'pay_method'   => 3,
                'trade_no'     => $paymentNo,
                'pay_time'     => null,
                'operator_id'  => $this->ownerId,
                'operator_type'=> 2,
                'create_time'  => date('Y-m-d H:i:s'),
                'remark'       => '支付宝支付待付款',
            ];
            Db::name('bill_payment')->insert($paymentData);

            $alipayResult = $this->alipayOrder($payConfig, $paymentNo, $amount, $bill);
            if ($alipayResult && isset($alipayResult['pay_url'])) {
                return $this->success([
                    'payment_no'   => $paymentNo,
                    'pay_method'   => 3,
                    'status'       => 'pending',
                    'need_alipay'  => true,
                    'alipay_url'   => $alipayResult['pay_url'],
                    'amount'       => $amount,
                ], '请完成支付宝支付');
            }

            return $this->offlineFallback($billId, $bill, $amount, $paymentNo, $communityId, 3);
        }

        return $this->error('不支持的支付方式');
    }

    /**
     * 线下支付降级处理
     */
    private function offlineFallback($billId, $bill, $amount, $paymentNo, $communityId, $payMethod = 2)
    {
        $now = date('Y-m-d H:i:s');
        $methodNames = [2=>'微信支付', 3=>'支付宝'];
        $methodName = $methodNames[$payMethod] ?? '线上支付';
        $paymentData = [
            'payment_no'   => $paymentNo,
            'bill_id'      => $billId,
            'community_id' => $communityId,
            'owner_id'     => $bill['owner_id'],
            'room_id'      => $bill['room_id'],
            'amount'       => $amount,
            'pay_method'   => $payMethod,
            'trade_no'     => $paymentNo,
            'pay_time'     => $now,
            'operator_id'  => $this->ownerId,
            'operator_type'=> 2,
            'create_time'  => $now,
            'remark'       => "业主端确认缴费（{$methodName}未配置，降级处理）",
        ];

        // 检查是否已存在（如微信/支付宝下单失败后降级的场景）
        $exists = Db::name('bill_payment')->where('payment_no', $paymentNo)->find();
        if ($exists) {
            Db::name('bill_payment')->where('payment_no', $paymentNo)->update($paymentData);
        } else {
            Db::name('bill_payment')->insert($paymentData);
        }
        Db::name('bill')->where('id', $billId)->update([
            'paid_amount' => $bill['paid_amount'] + $amount,
            'status'      => 3,
            'pay_date'    => date('Y-m-d H:i:s'),
        ]);
        $this->createFinanceFlow($communityId, $paymentNo, $amount, $billId);

        return $this->success([
            'payment_no'  => $paymentNo,
            'pay_method'  => $payMethod,
            'status'      => 'paid',
            'need_wechat' => false,
        ], '缴费确认成功');
    }

    /**
     * 微信支付统一下单 (JSAPI)
     */
    private function wechatUnifiedOrder($config, $paymentNo, $amount, $bill)
    {
        $appId = $config['wechat_app_id'];
        $mchId = $config['wechat_mch_id'];
        $apiKey = $config['wechat_api_key'] ?? '';
        $notifyUrl = $config['notify_url'] ?: 'http://dasheng.local/api/bill/wechatNotify';

        if (empty($apiKey)) return null;

        $totalFee = intval($amount * 100); // 分
        $body = ($bill['charge_item_name'] ?? '物业费') . ' - ' . ($bill['bill_period'] ?? '');

        $params = [
            'appid'            => $appId,
            'mch_id'           => $mchId,
            'nonce_str'        => $this->nonceStr(),
            'body'             => mb_substr($body, 0, 32),
            'out_trade_no'     => $paymentNo,
            'total_fee'        => $totalFee,
            'spbill_create_ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'notify_url'       => $notifyUrl,
            'trade_type'       => 'JSAPI',
            'openid'           => $this->ownerInfo['wx_openid'] ?? '',
        ];

        $params['sign'] = $this->makeSign($params, $apiKey);

        $xml = $this->toXml($params);
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $response = $this->httpPostXml($url, $xml);
        $result = $this->fromXml($response);

        if (isset($result['return_code']) && $result['return_code'] === 'SUCCESS'
            && isset($result['result_code']) && $result['result_code'] === 'SUCCESS'
            && isset($result['prepay_id'])) {
            // 构建 JSAPI 调用参数
            $jsParams = [
                'appId'     => $appId,
                'timeStamp' => (string)time(),
                'nonceStr'  => $this->nonceStr(),
                'package'   => 'prepay_id=' . $result['prepay_id'],
                'signType'  => 'MD5',
            ];
            $jsParams['paySign'] = $this->makeSign($jsParams, $apiKey);
            return $jsParams;
        }

        return null;
    }

    /**
     * 支付宝订单（返回支付链接，H5跳转或扫码）
     */
    private function alipayOrder($config, $paymentNo, $amount, $bill)
    {
        $appId = $config['alipay_app_id'];
        $notifyUrl = $config['notify_url'] ?: 'http://dasheng.local/api/bill/alipayNotify';
        $body = ($bill['charge_item_name'] ?? '物业费') . ' - ' . ($bill['bill_period'] ?? '');

        // 构建支付宝支付URL（简化版，无SDK）
        $params = [
            'app_id'        => $appId,
            'method'        => 'alipay.trade.wap.pay',
            'format'        => 'JSON',
            'charset'       => 'utf-8',
            'sign_type'     => 'RSA2',
            'timestamp'     => date('Y-m-d H:i:s'),
            'version'       => '1.0',
            'notify_url'    => $notifyUrl,
            'biz_content'   => json_encode([
                'out_trade_no' => $paymentNo,
                'total_amount' => number_format($amount, 2, '.', ''),
                'subject'      => mb_substr($body, 0, 128),
                'product_code' => 'QUICK_WAP_WAY',
            ], JSON_UNESCAPED_UNICODE),
        ];

        // 返回支付URL供前端跳转（实际签名需要SDK，这里返回基础URL）
        $payUrl = 'https://openapi.alipay.com/gateway.do?' . http_build_query($params);
        return ['pay_url' => $payUrl];
    }

    /**
     * 支付状态查询
     */
    public function payStatus()
    {
        $paymentNo = $this->request->param('payment_no', '');
        if (empty($paymentNo)) return $this->error('缺少支付单号');

        $payment = Db::name('bill_payment')->where('payment_no', $paymentNo)->find();
        if (!$payment) return $this->error('支付记录不存在');

        $isPaid = !empty($payment['pay_time']);
        $bill = Db::name('bill')->where('id', $payment['bill_id'])->find();

        return $this->success([
            'payment_no' => $paymentNo,
            'is_paid'    => $isPaid,
            'bill_status'=> $bill['status'] ?? 0,
        ]);
    }

    /**
     * 微信支付回调通知
     * 注意：此方法不需要登录认证，需要在 BaseApi 白名单中添加
     */
    public function wechatNotify()
    {
        $xml = file_get_contents('php://input');
        $data = $this->fromXml($xml);

        if (!isset($data['return_code']) || $data['return_code'] !== 'SUCCESS') {
            echo '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[参数错误]]></return_msg></xml>';
            exit;
        }

        $paymentNo = $data['out_trade_no'] ?? '';
        $tradeNo = $data['transaction_id'] ?? '';
        $totalFee = intval($data['total_fee'] ?? 0) / 100;

        $payment = Db::name('bill_payment')->where('payment_no', $paymentNo)->find();
        if (!$payment) {
            echo '<xml><return_code><![CDATA[FAIL]]></return_code></xml>';
            exit;
        }

        // 防止重复回调
        if (!empty($payment['trade_no'])) {
            echo '<xml><return_code><![CDATA[SUCCESS]]></return_code></xml>';
            exit;
        }

        // 验签
        $payConfig = Db::name('community_payment_config')
            ->where('community_id', $payment['community_id'])
            ->find();
        if ($payConfig && !empty($payConfig['wechat_api_key'])) {
            $sign = $data['sign'] ?? '';
            unset($data['sign']);
            $calcSign = $this->makeSign($data, $payConfig['wechat_api_key']);
            if ($sign !== $calcSign) {
                echo '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名错误]]></return_msg></xml>';
                exit;
            }
        }

        $bill = Db::name('bill')->where('id', $payment['bill_id'])->find();

        Db::name('bill_payment')->where('payment_no', $paymentNo)->update([
            'trade_no'   => $tradeNo,
            'pay_time'   => date('Y-m-d H:i:s'),
            'pay_account'=> '微信支付',
        ]);
        Db::name('bill')->where('id', $payment['bill_id'])->update([
            'paid_amount' => ($bill['paid_amount'] ?? 0) + $totalFee,
            'status'      => 3,
            'pay_date'    => date('Y-m-d H:i:s'),
        ]);
        // 创建财务流水
        $this->createFinanceFlow($payment['community_id'], $paymentNo, $totalFee, $payment['bill_id']);

        echo '<xml><return_code><![CDATA[SUCCESS]]></return_code></xml>';
        exit;
    }

    /**
     * 支付宝支付回调通知
     */
    public function alipayNotify()
    {
        $data = $this->request->post();

        $paymentNo = $data['out_trade_no'] ?? '';
        $tradeNo = $data['trade_no'] ?? '';
        $totalAmount = floatval($data['total_amount'] ?? 0);
        $tradeStatus = $data['trade_status'] ?? '';

        if ($tradeStatus !== 'TRADE_SUCCESS' && $tradeStatus !== 'TRADE_FINISHED') {
            echo 'fail';
            exit;
        }

        $payment = Db::name('bill_payment')->where('payment_no', $paymentNo)->find();
        if (!$payment || !empty($payment['trade_no'])) {
            echo 'success';
            exit;
        }

        $bill = Db::name('bill')->where('id', $payment['bill_id'])->find();

        Db::name('bill_payment')->where('payment_no', $paymentNo)->update([
            'trade_no'    => $tradeNo,
            'pay_time'    => date('Y-m-d H:i:s'),
            'pay_account' => '支付宝',
        ]);
        Db::name('bill')->where('id', $payment['bill_id'])->update([
            'paid_amount' => ($bill['paid_amount'] ?? 0) + $totalAmount,
            'status'      => 3,
            'pay_date'    => date('Y-m-d H:i:s'),
        ]);
        $this->createFinanceFlow($payment['community_id'], $paymentNo, $totalAmount, $payment['bill_id']);

        echo 'success';
        exit;
    }

    /**
     * 创建财务流水
     */
    private function createFinanceFlow($communityId, $paymentNo, $amount, $billId)
    {
        $flowNo = build_order_no('DSF');
        Db::name('finance_flow')->insert([
            'flow_no'       => $flowNo,
            'community_id'  => $communityId,
            'type'          => 1, // 收入
            'category'      => '物业缴费',
            'amount'        => $amount,
            'balance'       => 0,
            'source_type'   => 'payment',
            'source_id'     => $billId,
            'description'   => '业主端缴费 - 单号:' . $paymentNo,
            'operator_id'   => $this->ownerId,
            'operator_name' => $this->ownerInfo['name'] ?? '',
            'status'        => 1,
            'create_time'   => date('Y-m-d H:i:s'),
        ]);
    }

    // ========== 工具方法 ==========

    private function nonceStr($length = 32)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $str;
    }

    private function makeSign($params, $key)
    {
        ksort($params);
        $str = '';
        foreach ($params as $k => $v) {
            if ($v !== '' && $v !== null && $k !== 'sign') {
                $str .= $k . '=' . $v . '&';
            }
        }
        $str .= 'key=' . $key;
        return strtoupper(md5($str));
    }

    private function toXml($data)
    {
        $xml = '<xml>';
        foreach ($data as $k => $v) {
            $xml .= '<' . $k . '>' . $v . '</' . $k . '>';
        }
        $xml .= '</xml>';
        return $xml;
    }

    private function fromXml($xml)
    {
        // PHP 8.0: libxml_disable_entity_loader is deprecated, use LIBXML_NOENT instead
        $data = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $json = json_encode($data);
        return json_decode($json, true);
    }

    private function httpPostXml($url, $xml)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $xml,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => ['Content-Type: text/xml'],
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        return $resp;
    }
}
