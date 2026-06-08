<?php
namespace app\api\controller;

use app\BaseController;
use think\facade\Db;

class Consultation extends BaseController
{
    /**
     * 公开提交咨询（无需认证）
     */
    public function add()
    {
        $name = $this->request->post('name', '');
        $phone = $this->request->post('phone', '');
        $type = $this->request->post('type', '');
        $content = $this->request->post('message', '');

        if (empty($name) || mb_strlen($name) > 50) {
            return $this->error('请填写有效的姓名');
        }
        if (empty($phone) || !preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return $this->error('请填写有效的手机号码');
        }
        if (empty($type)) {
            return $this->error('请选择咨询类型');
        }

        $data = [
            'name'        => $name,
            'phone'       => $phone,
            'type'        => $type,
            'content'     => $content,
            'ip'          => $this->request->ip(),
            'user_agent'  => mb_substr($this->request->header('user-agent', ''), 0, 500),
            'status'      => 0,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ];

        Db::name('consultation')->insert($data);

        return $this->success([], '提交成功，我们将尽快与您联系！');
    }
}
