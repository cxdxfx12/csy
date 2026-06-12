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
        // 字段白名单：防止注入非预期字段
        $allowFields = ['name', 'code', 'description', 'sort', 'status'];
        $filtered = [];
        foreach ($allowFields as $f) {
            if (isset($data[$f])) $filtered[$f] = $data[$f];
        }
        // 自动生成唯一编码，避免 uk_code 唯一索引冲突
        if (empty($filtered['code'])) {
            $filtered['code'] = 'RL_' . date('YmdHis') . '_' . substr(md5(uniqid(mt_rand(), true)), 0, 6);
        }
        $filtered['create_time'] = date('Y-m-d H:i:s');
        $id = Db::name('role')->insertGetId($filtered);
        return $this->success(['id' => $id], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        // 字段白名单：防止注入非预期字段
        $allowFields = ['id', 'name', 'code', 'description', 'sort', 'status'];
        $filtered = [];
        foreach ($allowFields as $f) {
            if (isset($data[$f])) $filtered[$f] = $data[$f];
        }
        if (empty($filtered['id'])) return $this->error('参数错误');
        // 如果 code 被清空，保留原值
        if (empty($filtered['code'])) {
            $item = Db::name('role')->where('id', $filtered['id'])->find();
            if ($item && !empty($item['code'])) {
                $filtered['code'] = $item['code'];
            }
        }
        Db::name('role')->where('id', $filtered['id'])->update($filtered);
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

        // 不再合并硬编码权限基线：用户通过 UI 保存什么就是什么，回显与 DB 完全一致。
        $checkedMenuIds = array_values($checkedMenuIds);
        
        // 确保 checkedMenuIds 中的 ID 都在菜单表中存在
        $allMenuIds = [];
        foreach ($allMenus as $menu) {
            $allMenuIds[$menu['id']] = true;
        }
        $checkedMenuIds = array_values(array_filter($checkedMenuIds, function ($id) use ($allMenuIds) {
            return isset($allMenuIds[$id]);
        }));

        return $this->success([
            'menus'           => tree_list($allMenus),
            'checkedMenuIds'  => $checkedMenuIds,
        ]);
    }

    public function savePermission()
    {
        $roleId = (int)$this->request->post('role_id', 0);
        // 注意：自定义 Request 类不支持 ThinkPHP 的 /a 修饰符，直接用 post() 拿数组
        $menuIds = $this->request->post('menu_ids', []);
        if (!is_array($menuIds)) $menuIds = [];

        Db::name('role_menu')->where('role_id', $roleId)->delete();
        if (!empty($menuIds)) {
            $data = [];
            foreach ($menuIds as $menuId) {
                $data[] = ['role_id' => $roleId, 'menu_id' => intval($menuId)];
            }
            Db::name('role_menu')->insertAll($data);
        }
        return $this->success([], '权限保存成功');
    }

    // 临时诊断：查看某角色当前权限（使用后请删除）
    public function debugMenu()
    {
        $roleId = (int)$this->request->param('role_id', 3);
        $role = Db::name('role')->where('id', $roleId)->find();
        $rows = Db::name('role_menu')->alias('rm')
            ->join('ds_menu m', 'm.id = rm.menu_id')
            ->where('rm.role_id', $roleId)
            ->field('rm.menu_id, m.name')
            ->order('rm.menu_id')
            ->select();
        return $this->success([
            'role' => $role['name'] ?? '?',
            'count' => count($rows),
            'items' => $rows,
        ]);
    }

    // 临时：清空某角色权限（使用后请删除）
    public function resetPermission()
    {
        $roleId = (int)$this->request->post('role_id', 3);
        Db::name('role_menu')->where('role_id', $roleId)->delete();
        return $this->success([], '已清空权限');
    }
}
