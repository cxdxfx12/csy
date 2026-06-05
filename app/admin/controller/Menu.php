<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Menu extends BaseAdmin
{
    public function lists()
    {
        $list = Db::name('menu')->where('delete_time', null)->order('sort', 'asc')->select();
        $tree = tree_list($list);
        return $this->success($tree);
    }

    public function add()
    {
        $allowed = ['parent_id','name','route','permission','icon','sort','status'];
        $data = array_intersect_key($this->request->post(), array_flip($allowed));
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('menu')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $allowed = ['id','parent_id','name','route','permission','icon','sort','status'];
        $data = array_intersect_key($this->request->post(), array_flip($allowed));
        Db::name('menu')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        $childCount = Db::name('menu')->where('parent_id', $id)->count();
        if ($childCount > 0) return $this->error('存在子菜单，无法删除');
        Db::name('menu')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        Db::name('role_menu')->where('menu_id', $id)->delete();
        return $this->success([], '删除成功');
    }
}
