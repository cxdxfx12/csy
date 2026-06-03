<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Config extends BaseAdmin
{
    public function lists()
    {
        $group = $this->request->param('group', 'system');
        $list = Db::name('config')->where("`group`", $group)->order('sort', 'asc')->select();
        return $this->success($list);
    }

    public function save()
    {
        $data = $this->request->post('data', []);
        foreach ($data as $key => $value) {
            Db::name('config')->where("`key`", $key)->update(['value' => $value]);
        }
        return $this->success([], '保存成功');
    }
}
