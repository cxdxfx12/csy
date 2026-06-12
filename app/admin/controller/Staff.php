<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Staff extends BaseAdmin
{
    public function lists()
    {
        $params = $this->request->param();
        $query = Db::name('staff')->where('delete_time', null);

        if (!empty($params['keyword'])) {
            $query->where('realname|phone|job_no', 'like', '%' . $params['keyword'] . '%');
        }
        if (!empty($params['community_id'])) {
            $query->where('community_id', $params['community_id']);
        }
        // 小区角色强制数据隔离
        $cid = $this->getFilteredCommunityId();
        if ($cid === -1) {
            $query->where('community_id', 'in', $this->request->boundCommunityIds);
        } elseif ($cid > 0) {
            // already filtered above
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', $params['status']);
        }

        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($params['page'] ?? 1, $params['limit'] ?? 15)
            ->select();

        foreach ($list as &$row) {
            $row['community_name'] = Db::name('community')->where('id', $row['community_id'])->value('name') ?? '-';
        }

        return $this->success(['list' => $list, 'total' => $total]);
    }

    public function add()
    {
        $data = $this->request->post();
        unset($data['community_name']); // 表中无此字段，仅用于前端展示
        // 检查手机号唯一性
        if (!empty($data['phone'])) {
            $exist = Db::name('staff')->where('phone', $data['phone'])->where('delete_time', null)->find();
            if ($exist) {
                return $this->error('该手机号已被其他员工使用');
            }
        }
        // 检查工号唯一性
        if (!empty($data['job_no'])) {
            $exist = Db::name('staff')->where('job_no', $data['job_no'])->where('delete_time', null)->find();
            if ($exist) {
                return $this->error('该工号已存在');
            }
        }
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['entry_date'] = $data['entry_date'] ?: date('Y-m-d');
        $this->validateCommunityAccess($data['community_id'] ?? 0);
        Db::name('staff')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        unset($data['community_name']); // 表中无此字段，仅用于前端展示
        // 检查手机号唯一性（排除自身）
        if (!empty($data['phone'])) {
            $exist = Db::name('staff')->where('phone', $data['phone'])
                ->where('id', '<>', $data['id'])->where('delete_time', null)->find();
            if ($exist) {
                return $this->error('该手机号已被其他员工使用');
            }
        }
        // 检查工号唯一性（排除自身）
        if (!empty($data['job_no'])) {
            $exist = Db::name('staff')->where('job_no', $data['job_no'])
                ->where('id', '<>', $data['id'])->where('delete_time', null)->find();
            if ($exist) {
                return $this->error('该工号已存在');
            }
        }
        $this->validateCommunityAccess($data['community_id'] ?? 0);
        Db::name('staff')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        $record = Db::name('staff')->where('id', $id)->find();
        if ($record) {
            $this->validateCommunityAccess($record['community_id'] ?? 0);
        }
        Db::name('staff')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }

    public function detail()
    {
        $id = $this->request->param('id', 0);
        $info = Db::name('staff')->where('id', $id)->find();
        if ($info) {
            $info['community_name'] = Db::name('community')->where('id', $info['community_id'])->value('name') ?? '-';
        }
        return $this->success($info);
    }
}
