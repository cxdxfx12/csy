<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Community extends BaseAdmin
{
    public function lists()
    {
        $roleId = $this->adminInfo['role_id'] ?? 0;
        [$page, $limit] = $this->getPage();
        $where = [['delete_time', 'null', '']];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['name|code|address', 'like', "%{$keyword}%"];

        // 非超管/系统管理员只看绑定的小区
        if ($roleId > 2) {
            $boundIds = $this->request->boundCommunityIds ?? [];
            if (!empty($boundIds)) {
                $where[] = ['id', 'in', $boundIds];
            }
        }
        $total = Db::name('community')->where($where)->count();
        $list = Db::name('community')->where($where)->page($page, $limit)->order('id', 'desc')->select();
        return $this->table($list, $total);
    }

    public function add()
    {
        $roleId = $this->adminInfo['role_id'] ?? 0;
        if ($roleId > 2) return $this->error('仅超级管理员/系统管理员可添加小区');
        $data = $this->request->post();
        // 字段白名单
        $allowFields = ['name', 'code', 'address', 'province', 'city', 'district', 'area', 'developer', 'property_company', 'contact', 'phone', 'description', 'sort', 'status'];
        $filtered = [];
        foreach ($allowFields as $f) {
            if (isset($data[$f])) $filtered[$f] = $data[$f];
        }
        $filtered['create_time'] = date('Y-m-d H:i:s');
        Db::name('community')->insert($filtered);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $roleId = $this->adminInfo['role_id'] ?? 0;
        if ($roleId > 2) return $this->error('仅超级管理员/系统管理员可修改小区');
        $data = $this->request->post();
        // 字段白名单
        $allowFields = ['id', 'name', 'code', 'address', 'province', 'city', 'district', 'area', 'developer', 'property_company', 'contact', 'phone', 'description', 'sort', 'status'];
        $filtered = [];
        foreach ($allowFields as $f) {
            if (isset($data[$f])) $filtered[$f] = $data[$f];
        }
        if (empty($filtered['id'])) return $this->error('参数错误');
        Db::name('community')->where('id', $filtered['id'])->update($filtered);
        return $this->success([], '修改成功');
    }

    public function listAll()
    {
        $roleId = $this->adminInfo['role_id'] ?? 0;
        $boundIds = $this->request->boundCommunityIds ?? [];
        $query = Db::name('community')->where('delete_time', null)->field('id, name')->order('id', 'desc');
        if ($roleId > 2 && !empty($boundIds)) {
            $query->where('id', 'in', $boundIds);
        }
        $list = $query->select();
        return $this->success($list);
    }

    public function delete()
    {
        $roleId = $this->adminInfo['role_id'] ?? 0;
        if ($roleId > 2) return $this->error('仅超级管理员/系统管理员可删除小区');
        $id = $this->request->post('id', 0);
        Db::name('community')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }
}
