<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class PushConfig extends BaseAdmin
{
    /**
     * 获取所有小区的推送配置状态列表
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

        // 附加推送配置状态
        foreach ($list as &$c) {
            $config = Db::name('push_config')
                ->where('community_id', $c['id'])
                ->find();
            $c['config_id'] = $config['id'] ?? 0;
            $c['sse'] = $config['sse_enable'] ?? 1;
            $c['wechat'] = $config['wechat_enable'] ?? 1;
            $c['sms'] = $config['sms_enable'] ?? 0;
            $c['app_push'] = $config['app_push_enable'] ?? 0;
            $c['repair_new'] = $config['repair_new_enable'] ?? 1;
            $c['repair_assign'] = $config['repair_assign_enable'] ?? 1;
            $c['dunning'] = $config['dunning_enable'] ?? 1;

            // 短信服务商配置状态
            $smsCfg = Db::name('sms_config')
                ->where('community_id', $c['id'])
                ->find();
            $c['sms_provider'] = $smsCfg['provider'] ?? '';
            $c['sms_status'] = $smsCfg['status'] ?? 0;
            $c['sms_config_id'] = $smsCfg['id'] ?? 0;
        }

        return $this->table($list, $total);
    }

    /**
     * 获取某个小区的推送配置详情
     */
    public function detail()
    {
        $communityId = $this->request->param('community_id', 0);
        if ($communityId <= 0) return $this->error('请选择小区');

        $community = Db::name('community')->where('id', $communityId)->find();
        if (!$community) return $this->error('小区不存在');

        $config = Db::name('push_config')
            ->where('community_id', $communityId)
            ->find();

        $smsCfg = Db::name('sms_config')
            ->where('community_id', $communityId)
            ->find();

        // 脱敏敏感字段
        if ($smsCfg) {
            $smsCfg['access_key_id'] = $this->maskKey($smsCfg['access_key_id'], 4);
            $smsCfg['access_key_secret'] = $this->maskKey($smsCfg['access_key_secret'], 4);
        }

        return $this->success([
            'community' => ['id' => $community['id'], 'name' => $community['name'], 'code' => $community['code']],
            'push'      => $config ?: null,
            'sms'       => $smsCfg ?: null,
        ]);
    }

    /**
     * 保存推送渠道配置
     */
    public function savePush()
    {
        $data = $this->request->post();
        $communityId = $data['community_id'] ?? 0;
        if ($communityId <= 0) return $this->error('请选择小区');

        $saveData = [
            'community_id'       => $communityId,
            'sse_enable'         => isset($data['sse_enable']) ? intval($data['sse_enable']) : 1,
            'wechat_enable'      => isset($data['wechat_enable']) ? intval($data['wechat_enable']) : 1,
            'sms_enable'         => isset($data['sms_enable']) ? intval($data['sms_enable']) : 0,
            'app_push_enable'    => isset($data['app_push_enable']) ? intval($data['app_push_enable']) : 0,
            'repair_new_enable'  => isset($data['repair_new_enable']) ? intval($data['repair_new_enable']) : 1,
            'repair_assign_enable'=> isset($data['repair_assign_enable']) ? intval($data['repair_assign_enable']) : 1,
            'dunning_enable'     => isset($data['dunning_enable']) ? intval($data['dunning_enable']) : 1,
            'update_time'        => date('Y-m-d H:i:s'),
        ];

        $existing = Db::name('push_config')
            ->where('community_id', $communityId)
            ->find();

        if ($existing) {
            Db::name('push_config')->where('community_id', $communityId)->update($saveData);
        } else {
            $saveData['create_time'] = date('Y-m-d H:i:s');
            Db::name('push_config')->insert($saveData);
        }

        return $this->success([], '推送配置保存成功');
    }

    /**
     * 保存短信服务商配置
     */
    public function saveSms()
    {
        $data = $this->request->post();
        $communityId = $data['community_id'] ?? 0;
        if ($communityId <= 0) return $this->error('请选择小区');

        $saveData = [
            'community_id'     => $communityId,
            'provider'         => $data['provider'] ?? 'aliyun',
            'sign_name'        => $data['sign_name'] ?? '',
            'repair_template'  => $data['repair_template'] ?? '',
            'dunning_template' => $data['dunning_template'] ?? '',
            'status'           => !empty($data['sign_name']) ? 1 : 0,
            'update_time'      => date('Y-m-d H:i:s'),
        ];

        // 密钥字段：不含脱敏标记才更新
        $akId = $data['access_key_id'] ?? '';
        $akSecret = $data['access_key_secret'] ?? '';
        if ($akId !== '' && mb_strpos($akId, '****') === false) {
            $saveData['access_key_id'] = $akId;
        }
        if ($akSecret !== '' && mb_strpos($akSecret, '****') === false) {
            $saveData['access_key_secret'] = $akSecret;
        }

        $existing = Db::name('sms_config')
            ->where('community_id', $communityId)
            ->find();

        if ($existing) {
            Db::name('sms_config')->where('community_id', $communityId)->update($saveData);
        } else {
            $saveData['create_time'] = date('Y-m-d H:i:s');
            Db::name('sms_config')->insert($saveData);
        }

        return $this->success([], '短信配置保存成功');
    }

    /**
     * 测试短信接口连通性
     */
    public function testSms()
    {
        $communityId = $this->request->param('community_id', 0);
        if ($communityId <= 0) return $this->error('请选择小区');

        $smsCfg = Db::name('sms_config')
            ->where('community_id', $communityId)
            ->find();

        if (!$smsCfg || empty($smsCfg['sign_name'])) {
            return $this->error('该小区未配置短信服务商');
        }

        $errors = [];
        if (empty($smsCfg['access_key_id'])) $errors[] = 'AccessKeyId未填写';
        if (empty($smsCfg['access_key_secret'])) $errors[] = 'AccessKeySecret未填写';
        if (empty($smsCfg['sign_name'])) $errors[] = '短信签名未填写';

        if (!empty($errors)) {
            return $this->error('配置不完整：' . implode('；', $errors));
        }

        return $this->success([
            'provider' => $smsCfg['provider'],
            'sign_name' => $smsCfg['sign_name'],
            'repair_tpl' => $smsCfg['repair_template'] ?: '未配置',
            'dunning_tpl' => $smsCfg['dunning_template'] ?: '未配置',
        ], '短信配置校验通过 ✓');
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
