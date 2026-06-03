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
        $data = $this->request->post();
        Db::name('owner')->where('id', $this->ownerId)->update($data);
        return $this->success([], '修改成功');
    }

    public function password()
    {
        $oldPwd = $this->request->post('old_password', '');
        $newPwd = $this->request->post('new_password', '');
        $owner = Db::name('owner')->where('id', $this->ownerId)->find();

        if ($owner['password'] !== encrypt_password($oldPwd)) {
            return $this->error('原密码错误');
        }
        Db::name('owner')->where('id', $this->ownerId)->update(['password' => encrypt_password($newPwd)]);
        return $this->success([], '密码修改成功');
    }
}
