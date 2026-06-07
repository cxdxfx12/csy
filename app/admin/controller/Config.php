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
        // key 白名单：只允许修改配置表中已存在的 key
        $existingKeys = Db::name('config')->column('key');
        foreach ($data as $key => $value) {
            if (!in_array($key, $existingKeys, true)) continue;
            Db::name('config')->where("`key`", $key)->update(['value' => $value]);
        }
        return $this->success([], '保存成功');
    }
}
