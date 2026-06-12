<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class AdminUser extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['delete_time', 'null', '']];
        
        $keyword = $this->request->param('keyword', '');
        if (!empty($keyword)) {
            $where[] = ['username|nickname|phone|email', 'like', "%{$keyword}%"];
        }
        $roleId = $this->request->param('role_id', 0);
        if ($roleId) $where[] = ['role_id', '=', $roleId];
        $status = $this->request->param('status', '');
        if ($status !== '') $where[] = ['status', '=', $status];

        $total = Db::name('admin_user')->where($where)->count();
        $list = Db::name('admin_user')->where($where)
            ->field('id,username,nickname,avatar,email,phone,role_id,community_ids,status,last_login_time,last_login_ip,login_count,create_time,openid')
            ->page($page, $limit)->order('id', 'asc')->select();

        $roleNames = array_to_keyval(Db::name('role')->select());
        // 仅需要小区隔离的角色才查询小区名
        $needCommunityRoles = [3, 4, 5, 6, 7, 8]; // 物管经理、客服主管、财务专员、安保主管、工程主管、小区管理员
        $needCommunity = !empty(array_intersect(array_column($list, 'role_id'), $needCommunityRoles));
        $communityNames = [];
        if ($needCommunity) {
            $communityNames = array_to_keyval(Db::name('community')->whereNull('delete_time')->select());
        }
        foreach ($list as &$item) {
            $item['role_name'] = $roleNames[$item['role_id']] ?? '';
            $item['community_name'] = '';
            // 仅非全局角色（3-7）才解析小区名
            if (in_array($item['role_id'], $needCommunityRoles) && !empty($item['community_ids'])) {
                $ids = explode(',', $item['community_ids']);
                $names = [];
                foreach ($ids as $cid) {
                    $cid = trim($cid);
                    if ($cid && isset($communityNames[$cid])) {
                        $names[] = $communityNames[$cid];
                    }
                }
                $item['community_name'] = implode('、', $names);
            }
        }

        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        // 移除空id防止MySQL严格模式报错
        unset($data['id']);

        // 只保留允许的字段
        $allowFields = ['username', 'password', 'nickname', 'avatar', 'email', 'phone', 'role_id', 'community_ids', 'status'];
        $data = array_intersect_key($data, array_flip($allowFields));
        // status 复选框未勾选时默认值
        if (!isset($data['status'])) $data['status'] = 1;

        // 字段校验
        if (empty($data['username'])) return $this->error('用户名不能为空');
        if (empty($data['password']) || strlen($data['password']) < 6) return $this->error('密码至少6位');
        if (empty($data['role_id'])) return $this->error('请选择角色');
        // 用户名唯一性
        if (Db::name('admin_user')->where('username', $data['username'])->find()) {
            return $this->error('用户名已存在');
        }

        // community_ids 一致性校验
        $communityIds = $data['community_ids'] ?? '';
        if (!empty($communityIds)) {
            $communityIds = trim($communityIds, ', ');
        }
        if ($data['role_id'] <= 2 && !empty($communityIds)) {
            return $this->error('超级管理员和系统管理员无需绑定小区');
        }
        if ($data['role_id'] >= 3 && empty($communityIds)) {
            return $this->error('请为小区级角色绑定至少一个小区');
        }
        $data['community_ids'] = $communityIds;

        // 手机号若与维修人员重复，登录后身份会关联到维修工
        if (!empty($data['phone'])) {
            $dupWorker = Db::name('repair_worker')->where('phone', $data['phone'])->find();
            if ($dupWorker) {
                return $this->error('手机号已被维修人员「' . $dupWorker['name'] . '」使用，请更换手机号');
            }
        }

        $data['password'] = encrypt_password($data['password']);
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('admin_user')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        $id = $data['id'] ?? 0;
        if (empty($id)) return $this->error('参数错误');

        // 只保留允许的字段，排除id
        $allowFields = ['username', 'password', 'nickname', 'avatar', 'email', 'phone', 'role_id', 'community_ids', 'status'];
        $data = array_intersect_key($data, array_flip($allowFields));

        // 字段校验
        if (empty($data['username'])) return $this->error('用户名不能为空');
        if (empty($data['role_id'])) return $this->error('请选择角色');
        // 用户名唯一性（排除自身）
        $dup = Db::name('admin_user')->where('username', $data['username'])->where('id', '<>', $id)->find();
        if ($dup) return $this->error('用户名已存在');

        // community_ids 一致性校验
        $communityIds = $data['community_ids'] ?? '';
        if (!empty($communityIds)) {
            $communityIds = trim($communityIds, ', ');
        }
        if ($data['role_id'] <= 2 && !empty($communityIds)) {
            return $this->error('超级管理员和系统管理员无需绑定小区');
        }
        if ($data['role_id'] >= 3 && empty($communityIds)) {
            return $this->error('请为小区级角色绑定至少一个小区');
        }
        $data['community_ids'] = $communityIds;

        // 手机号若与维修人员重复，登录后身份会关联到维修工（仅变更时校验）
        if (!empty($data['phone'])) {
            $oldPhone = Db::name('admin_user')->where('id', $id)->value('phone') ?? '';
            if ($data['phone'] !== $oldPhone) {
                $dupWorker = Db::name('repair_worker')->where('phone', $data['phone'])->find();
                if ($dupWorker) {
                    return $this->error('手机号已被维修人员「' . $dupWorker['name'] . '」使用，请更换手机号');
                }
            }
        }

        // 密码处理
        if (isset($data['password']) && !empty($data['password'])) {
            if (strlen($data['password']) < 6) return $this->error('密码至少6位');
            $data['password'] = encrypt_password($data['password']);
        } else {
            unset($data['password']);
        }

        Db::name('admin_user')->where('id', $id)->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        if ($id == 1) return $this->error('不能删除超级管理员');
        Db::name('admin_user')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }

    public function status()
    {
        $id = $this->request->post('id', 0);
        $status = $this->request->post('status', 0);
        if ($id == 1) return $this->error('不能禁用超级管理员');
        Db::name('admin_user')->where('id', $id)->update(['status' => $status]);
        return $this->success([], '操作成功');
    }

    public function changePassword()
    {
        $id = $this->request->post('id', 0);
        $password = $this->request->post('password', '');
        if (strlen($password) < 6) return $this->error('密码长度至少6位');

        // 非超管只能修改自己的密码
        $currentUser = Db::name('admin_user')->where('id', $this->adminId)->find();
        if ((int)$currentUser['role_id'] !== 1 && (int)$id !== (int)$this->adminId) {
            return $this->error('无权限修改他人密码');
        }

        Db::name('admin_user')->where('id', $id)->update(['password' => encrypt_password($password)]);
        return $this->success([], '密码修改成功');
    }

    public function unbindWechat()
    {
        $id = $this->request->post('id', 0);
        if (empty($id)) return $this->error('参数错误');
        if ($id == 1) return $this->error('不能解绑超级管理员的微信');

        // 非超管只能解绑自己的微信
        $currentUser = Db::name('admin_user')->where('id', $this->adminId)->find();
        if ((int)$currentUser['role_id'] !== 1 && (int)$id !== (int)$this->adminId) {
            return $this->error('无权限解绑他人微信');
        }

        Db::name('admin_user')->where('id', $id)->update([
            'openid'      => '',
            'update_time' => date('Y-m-d H:i:s'),
        ]);
        return $this->success([], '微信解绑成功');
    }
}
