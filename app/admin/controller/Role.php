<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Role extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['delete_time', 'null', '']];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['name|code', 'like', "%{$keyword}%"];

        $total = Db::name('role')->where($where)->count();
        $list = Db::name('role')->where($where)->page($page, $limit)->order('id', 'asc')->select();

        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        $data['create_time'] = date('Y-m-d H:i:s');
        $id = Db::name('role')->insertGetId($data);
        return $this->success(['id' => $id], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        Db::name('role')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        if ($id <= 7) return $this->error('系统内置角色不能删除');
        $userCount = Db::name('admin_user')->where('role_id', $id)->count();
        if ($userCount > 0) return $this->error('该角色下还有管理员，无法删除');
        Db::name('role')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }

    public function permission()
    {
        $roleId = $this->request->param('role_id', 0);
        $allMenus = Db::name('menu')->where('status', 1)->order('sort', 'asc')->select();
        $checkedMenuIds = Db::name('role_menu')->where('role_id', $roleId)->column('menu_id');

        return $this->success([
            'menus'           => tree_list($allMenus),
            'checkedMenuIds'  => $checkedMenuIds,
        ]);
    }

    public function savePermission()
    {
        $roleId = $this->request->post('role_id', 0);
        $menuIds = $this->request->post('menu_ids/a', []);

        Db::name('role_menu')->where('role_id', $roleId)->delete();
        if (!empty($menuIds)) {
            $data = [];
            foreach ($menuIds as $menuId) {
                $data[] = ['role_id' => $roleId, 'menu_id' => $menuId];
            }
            Db::name('role_menu')->insertAll($data);
        }
        return $this->success([], '权限保存成功');
    }
}
