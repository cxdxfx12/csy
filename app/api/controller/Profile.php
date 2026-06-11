<?php
namespace app\api\controller;

use app\api\BaseApi;
use think\facade\Db;

class Profile extends BaseApi
{
    public function info()
    {
        $owner = Db::name('owner')->where('id', $this->ownerId)->find();
        unset($owner['password']);
        return $this->success($owner);
    }

    public function edit()
    {
        // 安全：字段白名单，防止批量赋值越权修改 community_id/status/type 等敏感字段
        $raw = $this->request->post();
        $allowed = ['realname','avatar','email','gender','birthday','id_card','emergency_contact','emergency_phone'];
        $data = [];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $raw)) {
                $data[$f] = $raw[$f];
            }
        }
        if (empty($data)) return $this->error('无有效修改字段');
        Db::name('owner')->where('id', $this->ownerId)->update($data);
        return $this->success([], '修改成功');
    }

    public function password()
    {
        $oldPwd = $this->request->post('old_password', '');
        $newPwd = $this->request->post('new_password', '');
        $owner = Db::name('owner')->where('id', $this->ownerId)->find();

        if (!verify_password($oldPwd, $owner['password'])) {
            return $this->error('原密码错误');
        }
        Db::name('owner')->where('id', $this->ownerId)->update(['password' => encrypt_password($newPwd)]);
        return $this->success([], '密码修改成功');
    }
}
