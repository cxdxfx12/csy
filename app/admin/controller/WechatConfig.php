<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class WechatConfig extends BaseAdmin
{
    /**
     * 获取所有小区及其公众号配置状态
     */
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $keyword = $this->request->param('keyword', '');

        $where = [['delete_time', 'null', '']];
        if ($keyword) $where[] = ['name|code|address', 'like', "%{$keyword}%"];

        $total = Db::name('community')->where($where)->count();
        $list = Db::name('community')
            ->where($where)
            ->page($page, $limit)
            ->order('id', 'desc')
            ->select();

        // 附加公众号配置状态
        foreach ($list as &$c) {
            $config = Db::name('community_wechat_config')
                ->where('community_id', $c['id'])
                ->find();
            $c['wx_status'] = $config && $config['status'] == 1 ? 1 : 0;
            $c['wx_app_id'] = $this->maskKey($config['app_id'] ?? '', 4);
            $c['wx_config_id'] = $config['id'] ?? 0;
        }

        return $this->table($list, $total);
    }

    /**
     * 获取某个小区的公众号配置详情（密钥脱敏）
     */
    public function detail()
    {
        $communityId = $this->request->param('community_id', 0);
        if ($communityId <= 0) return $this->error('请选择小区');

        $config = Db::name('community_wechat_config')
            ->where('community_id', $communityId)
            ->find();

        $community = Db::name('community')->where('id', $communityId)->find();
        if (!$community) return $this->error('小区不存在');

        if ($config) {
            // 脱敏显示密钥
            $config['app_secret'] = $this->maskKey($config['app_secret'], 4);
            $config['token'] = $this->maskKey($config['token'], 3);
            $config['encoding_aes_key'] = $this->maskKey($config['encoding_aes_key'], 5);
        }

        return $this->success([
            'community' => ['id' => $community['id'], 'name' => $community['name'], 'code' => $community['code']],
            'config'    => $config ?: null,
        ]);
    }

    /**
     * 保存/更新公众号配置
     */
    public function save()
    {
        $data = $this->request->post();
        $communityId = $data['community_id'] ?? 0;

        if ($communityId <= 0) return $this->error('请选择小区');

        $community = Db::name('community')->where('id', $communityId)->find();
        if (!$community) return $this->error('小区不存在');

        $saveData = [
            'community_id'    => $communityId,
            'app_id'          => $data['app_id'] ?? '',
            'original_id'     => $data['original_id'] ?? '',
            'mch_id'          => $data['mch_id'] ?? '',
            'template_pay_success' => $data['template_pay_success'] ?? '',
            'template_arrears' => $data['template_arrears'] ?? '',
            'status'          => !empty($data['app_id']) ? 1 : 0,
            'update_time'     => date('Y-m-d H:i:s'),
        ];

        // 密钥字段：不含脱敏标记才更新
        $sensitiveFields = [
            'app_secret'       => $data['app_secret'] ?? '',
            'token'            => $data['token'] ?? '',
            'encoding_aes_key' => $data['encoding_aes_key'] ?? '',
        ];

        foreach ($sensitiveFields as $field => $val) {
            if ($val !== '' && mb_strpos($val, '****') === false && mb_strpos($val, '***') === false) {
                $saveData[$field] = $val;
            }
        }

        $existing = Db::name('community_wechat_config')
            ->where('community_id', $communityId)
            ->find();

        if ($existing) {
            Db::name('community_wechat_config')->where('community_id', $communityId)->update($saveData);
        } else {
            $saveData['create_time'] = date('Y-m-d H:i:s');
            Db::name('community_wechat_config')->insert($saveData);
        }

        return $this->success([], '保存成功');
    }

    /**
     * 测试公众号配置连通性（校验参数完整性）
     */
    public function test()
    {
        $communityId = $this->request->param('community_id', 0);

        if ($communityId <= 0) return $this->error('请选择小区');

        $config = Db::name('community_wechat_config')
            ->where('community_id', $communityId)
            ->find();

        if (!$config || empty($config['app_id'])) {
            return $this->error('该小区未配置公众号');
        }

        $errors = [];
        if (empty($config['app_id'])) $errors[] = 'AppID未填写';
        if (empty($config['app_secret'])) $errors[] = 'AppSecret未填写';
        if (empty($config['token'])) $errors[] = '消息校验Token未填写';

        if (!empty($errors)) {
            return $this->error('配置不完整：' . implode('；', $errors));
        }

        return $this->success([
            'app_id'        => $config['app_id'],
            'original_id'   => $config['original_id'],
            'tpl_success'   => $config['template_pay_success'] ? '已配置' : '未配置',
            'tpl_arrears'   => $config['template_arrears'] ? '已配置' : '未配置',
        ], '公众号配置校验通过 ✓');
    }

    /**
     * 密钥脱敏
     */
    private function maskKey($val, $show = 4)
    {
        if (empty($val) || mb_strlen($val) <= $show * 2) return $val ? '****' : '';
        return mb_substr($val, 0, $show) . '****' . mb_substr($val, -$show);
    }
}
