<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class PaymentConfig extends BaseAdmin
{
    /**
     * 获取所有小区及其支付配置状态
     */
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $keyword = $this->request->param('keyword', '');

        $where = [['delete_time', '=', null]];
        if ($keyword) $where[] = ['name|code|address', 'like', "%{$keyword}%"];

        $total = Db::name('community')->where($where)->count();
        $list = Db::name('community')
            ->where($where)
            ->page($page, $limit)
            ->order('id', 'desc')
            ->select();

        // 附加支付配置状态
        foreach ($list as &$c) {
            $config = Db::name('community_payment_config')
                ->where('community_id', $c['id'])
                ->find();
            $c['pay_channel'] = $config['pay_channel'] ?? '';
            $c['pay_channel_label'] = $this->channelLabel($c['pay_channel']);
            $c['pay_status'] = $config && $config['status'] == 1 ? 1 : 0;
            $c['pay_config_id'] = $config['id'] ?? 0;
        }

        return $this->table($list, $total);
    }

    /**
     * 获取某个小区的支付配置详情（密钥脱敏）
     */
    public function detail()
    {
        $communityId = $this->request->param('community_id', 0);
        if ($communityId <= 0) {
            return $this->error('请选择小区');
        }

        $config = Db::name('community_payment_config')
            ->where('community_id', $communityId)
            ->find();

        $community = Db::name('community')->where('id', $communityId)->find();
        if (!$community) return $this->error('小区不存在');

        if ($config) {
            // 脱敏显示密钥
            $config['wechat_api_key'] = $this->maskKey($config['wechat_api_key']);
            $config['wechat_api_v3_key'] = $this->maskKey($config['wechat_api_v3_key'], 6);
            $config['alipay_private_key'] = $this->maskLongText($config['alipay_private_key']);
            $config['alipay_public_key'] = $this->maskLongText($config['alipay_public_key']);
        }

        return $this->success([
            'community' => ['id' => $community['id'], 'name' => $community['name'], 'code' => $community['code']],
            'config'    => $config ?: null,
        ]);
    }

    /**
     * 保存/更新支付配置
     */
    public function save()
    {
        $data = $this->request->post();
        $communityId = $data['community_id'] ?? 0;

        if ($communityId <= 0) return $this->error('请选择小区');

        $community = Db::name('community')->where('id', $communityId)->find();
        if (!$community) return $this->error('小区不存在');

        // 基础字段（不脱敏的普通字段直接写）
        $saveData = [
            'community_id'     => $communityId,
            'pay_channel'      => $data['pay_channel'] ?? '',
            'wechat_app_id'    => $data['wechat_app_id'] ?? '',
            'wechat_mch_id'    => $data['wechat_mch_id'] ?? '',
            'wechat_serial_no' => $data['wechat_serial_no'] ?? '',
            'alipay_app_id'    => $data['alipay_app_id'] ?? '',
            'alipay_merchant_id' => $data['alipay_merchant_id'] ?? '',
            'notify_url'       => $data['notify_url'] ?? '',
            'status'           => !empty($data['pay_channel']) ? 1 : 0,
            'update_time'      => date('Y-m-d H:i:s'),
        ];

        // 密钥字段：如果前端传了且未脱敏（不含****），则更新
        $sensitiveFields = [
            'wechat_api_key'   => $data['wechat_api_key'] ?? '',
            'wechat_api_v3_key'=> $data['wechat_api_v3_key'] ?? '',
            'alipay_private_key'=> $data['alipay_private_key'] ?? '',
            'alipay_public_key' => $data['alipay_public_key'] ?? '',
        ];

        foreach ($sensitiveFields as $field => $val) {
            if ($val !== '' && mb_strpos($val, '****') === false && mb_strpos($val, '***') === false) {
                $saveData[$field] = $val;
            }
        }

        $existing = Db::name('community_payment_config')
            ->where('community_id', $communityId)
            ->find();

        if ($existing) {
            Db::name('community_payment_config')->where('community_id', $communityId)->update($saveData);
        } else {
            $saveData['create_time'] = date('Y-m-d H:i:s');
            Db::name('community_payment_config')->insert($saveData);
        }

        return $this->success([], '保存成功');
    }

    /**
     * 测试支付配置连通性（参数校验）
     */
    public function test()
    {
        $communityId = $this->request->param('community_id', 0);
        $channel = $this->request->param('channel', '');

        if ($communityId <= 0) return $this->error('请选择小区');

        $config = Db::name('community_payment_config')
            ->where('community_id', $communityId)
            ->find();

        if (!$config || empty($config['pay_channel'])) {
            return $this->error('该小区未配置支付接口');
        }

        $errors = [];

        if ($channel === 'wechat' || $config['pay_channel'] === 'wechat' || $config['pay_channel'] === 'both') {
            if (empty($config['wechat_app_id'])) $errors[] = '微信AppID未填写';
            if (empty($config['wechat_mch_id'])) $errors[] = '微信商户号未填写';
            if (empty($config['wechat_api_key'])) $errors[] = '微信API密钥未填写';
            if (empty($errors)) {
                return $this->success([
                    'channel' => 'wechat',
                    'app_id'  => $config['wechat_app_id'],
                    'mch_id'  => $config['wechat_mch_id'],
                ], '微信支付配置校验通过 ✓');
            }
        }

        if ($channel === 'alipay' || $config['pay_channel'] === 'alipay' || $config['pay_channel'] === 'both') {
            $alipayErrors = [];
            if (empty($config['alipay_app_id'])) $alipayErrors[] = '支付宝AppID未填写';
            if (empty($config['alipay_private_key'])) $alipayErrors[] = '应用私钥未填写';
            if (empty($config['alipay_public_key'])) $alipayErrors[] = '支付宝公钥未填写';
            if (empty($alipayErrors)) {
                return $this->success([
                    'channel' => 'alipay',
                    'app_id'  => $config['alipay_app_id'],
                    'pid'     => $config['alipay_merchant_id'],
                ], '支付宝支付配置校验通过 ✓');
            }
            $errors = array_merge($errors, $alipayErrors);
        }

        if (!empty($errors)) {
            return $this->error('配置不完整：' . implode('；', $errors));
        }
        return $this->error('未指定支付渠道');
    }

    /**
     * 渠道标签
     */
    private function channelLabel($channel)
    {
        $map = ['wechat' => '微信支付', 'alipay' => '支付宝', 'both' => '微信+支付宝'];
        return $map[$channel] ?? '未配置';
    }

    /**
     * 短密钥脱敏（前4后4）
     */
    private function maskKey($val, $show = 4)
    {
        if (empty($val) || mb_strlen($val) <= $show * 2) return $val ? '****' : '';
        return mb_substr($val, 0, $show) . '****' . mb_substr($val, -$show);
    }

    /**
     * 长文本脱敏（前20字符 + ...）
     */
    private function maskLongText($val)
    {
        if (empty($val)) return '';
        if (mb_strlen($val) <= 30) return mb_substr($val, 0, 8) . '****...';
        return mb_substr($val, 0, 20) . '****...已加密';
    }
}
