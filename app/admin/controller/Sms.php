<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Sms extends BaseAdmin
{
    /**
     * 获取所有小区及其短信配置状态
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

        foreach ($list as &$c) {
            $c['sms_status'] = !empty($c['sms_key']) ? 1 : 0;
            $c['sms_key_mask'] = $this->maskKey($c['sms_key']);
            unset($c['sms_key']); // 列表不暴露原始key
        }

        return $this->table($list, $total);
    }

    /**
     * 获取某个小区的短信配置详情（密钥脱敏）
     */
    public function detail()
    {
        $communityId = $this->request->param('community_id', 0);
        if ($communityId <= 0) {
            return $this->error('请选择小区');
        }

        $community = Db::name('community')->where('id', $communityId)->find();
        if (!$community) return $this->error('小区不存在');

        $smsKey = $this->maskKey($community['sms_key'] ?? '');

        return $this->success([
            'community' => ['id' => $community['id'], 'name' => $community['name'], 'code' => $community['code']],
            'sms_key'   => $smsKey,
        ]);
    }

    /**
     * 保存/更新短信配置
     */
    public function save()
    {
        $data = $this->request->post();
        $communityId = $data['community_id'] ?? 0;

        if ($communityId <= 0) return $this->error('请选择小区');

        $community = Db::name('community')->where('id', $communityId)->find();
        if (!$community) return $this->error('小区不存在');

        $smsKey = $data['sms_key'] ?? '';

        // 空值不允许保存
        if ($smsKey === '') {
            return $this->error('请输入短信接口KEY');
        }

        // 如果是脱敏值（包含****），说明未修改，不更新
        if (mb_strpos($smsKey, '****') !== false || mb_strpos($smsKey, '***') !== false) {
            return $this->success([], '未做修改');
        }

        Db::name('community')->where('id', $communityId)->update([
            'sms_key'     => $smsKey,
            'update_time' => date('Y-m-d H:i:s'),
        ]);

        return $this->success([], '保存成功');
    }

    /**
     * 测试短信配置（校验参数完整性）
     */
    public function test()
    {
        $communityId = $this->request->param('community_id', 0);

        if ($communityId <= 0) return $this->error('请选择小区');

        $community = Db::name('community')->where('id', $communityId)->find();
        if (!$community) return $this->error('小区不存在');

        if (empty($community['sms_key'])) {
            return $this->error('该小区未配置短信接口KEY');
        }

        // 基础校验：KEY长度
        $keyLength = mb_strlen($community['sms_key']);
        if ($keyLength < 8) {
            return $this->error('短信KEY过短（小于8位），请检查');
        }

        return $this->success([
            'community' => $community['name'],
            'key_length' => $keyLength,
            'key_prefix' => mb_substr($this->maskKey($community['sms_key']), 0, 8) . '...',
        ], '短信KEY配置校验通过，长度：' . $keyLength . ' 位');
    }

    /**
     * 密钥脱敏
     */
    private function maskKey($val, $show = 4)
    {
        if (empty($val) || mb_strlen($val) <= $show * 2) return $val ?: '';
        return mb_substr($val, 0, $show) . '****' . mb_substr($val, -$show);
    }
}
