<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class SmsSend extends BaseAdmin
{
    /**
     * 批量发送短信
     */
    public function send()
    {
        $data = $this->request->post();
        $communityId = $data['community_id'] ?? 0;
        $templateId = $data['template_id'] ?? '';
        $phones = $data['phones'] ?? [];
        $content = $data['content'] ?? '';

        if ($communityId <= 0) return $this->error('请选择小区');
        if (empty($phones) || !is_array($phones)) return $this->error('手机号码不能为空');
        if (empty($content)) return $this->error('短信内容不能为空');

        $community = Db::name('community')->where('id', $communityId)->find();
        if (!$community) return $this->error('小区不存在');

        // 获取短信KEY配置
        $smsKey = $community['sms_key'] ?? '';
        if (empty($smsKey)) return $this->error('该小区未配置短信接口，请先前往【服务商配置】页进行配置');

        $success = 0;
        $fail = 0;
        $now = date('Y-m-d H:i:s');

        foreach ($phones as $phone) {
            $phone = trim($phone);
            if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
                $fail++;
                Db::name('sms_log')->insert([
                    'phone'       => $phone,
                    'template_id' => (string)$templateId,
                    'content'     => $content,
                    'result'      => '手机号格式不正确',
                    'status'      => 2,
                    'create_time' => $now,
                ]);
                continue;
            }

            // 模拟短信发送（实际对接短信服务商时替换此处）
            $sendResult = $this->mockSend($phone, $content, $smsKey);

            Db::name('sms_log')->insert([
                'phone'       => $phone,
                'template_id' => (string)$templateId,
                'content'     => $content,
                'result'      => $sendResult['msg'],
                'status'      => $sendResult['code'] == 0 ? 1 : 2,
                'create_time' => $now,
            ]);

            if ($sendResult['code'] == 0) $success++; else $fail++;
        }

        return $this->success([
            'count'   => count($phones),
            'success' => $success,
            'fail'    => $fail,
        ], "发送完成：成功 {$success} 条，失败 {$fail} 条");
    }

    /**
     * 重发短信
     */
    public function resend()
    {
        $data = $this->request->post();
        $id = $data['id'] ?? 0;
        $phone = $data['phone'] ?? '';
        $content = $data['content'] ?? '';

        if ($id <= 0 || empty($phone) || empty($content)) {
            return $this->error('参数不完整');
        }

        // 模拟重新发送
        $result = $this->mockSend($phone, $content, '');

        Db::name('sms_log')->insert([
            'phone'       => $phone,
            'template_id' => '',
            'content'     => $content,
            'result'      => $result['msg'],
            'status'      => $result['code'] == 0 ? 1 : 2,
            'create_time' => date('Y-m-d H:i:s'),
        ]);

        return $result['code'] == 0
            ? $this->success([], '重发成功')
            : $this->error($result['msg']);
    }

    /**
     * 模拟短信发送（实际项目请对接阿里云/腾讯云SDK）
     */
    private function mockSend($phone, $content, $key)
    {
        // 模拟90%成功率
        $success = rand(1, 100) <= 90;
        return [
            'code' => $success ? 0 : -1,
            'msg'  => $success ? '发送成功' : '模拟发送失败：网络超时',
        ];
    }
}
