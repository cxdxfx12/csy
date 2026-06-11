<?php
namespace app\staff\controller;

use app\staff\BaseStaff;
use think\facade\Db;

class StaffRepair extends BaseStaff
{
    /**
     * 获取当前登录员工对应的维修工信息
     */
    protected function getCurrentWorker()
    {
        // 如果是维修工直接登录（staffInfo.is_worker），直接按 id 查
        if ($this->staffInfo && !empty($this->staffInfo['is_worker'])) {
            return Db::name('repair_worker')->where('id', $this->staffInfo['id'])->find();
        }
        $adminUser = Db::name('admin_user')->where('id', $this->staffId)->find();
        if ($adminUser && $adminUser['phone']) {
            return Db::name('repair_worker')->where('phone', $adminUser['phone'])->find();
        }
        return null;
    }

    public function lists()
    {
        $worker = $this->getCurrentWorker();
        if (!$worker) return $this->error('未找到员工信息');

        [$page, $limit] = $this->getPage();
        $where = [['ro.delete_time', 'null', '']];

        // 按小区过滤
        $where[] = ['ro.community_id', '=', $worker['community_id']];

        $status = $this->request->param('status', '');
        if ($status !== '') $where[] = ['ro.status', '=', $status];

        // 状态 2/3/4/5/6 只显示指派给自己的；状态 1 本小区所有人都可见（可抢单）
        if (in_array((int)$status, [2, 3, 4, 5, 6])) {
            $where[] = ['ro.assignee_id', '=', $worker['id']];
        }

        $total = Db::name('repair_order')->alias('ro')->where($where)->count();
        $list = Db::name('repair_order')->alias('ro')
            ->leftJoin('room r', 'r.id = ro.room_id')
            ->leftJoin('community c', 'c.id = ro.community_id')
            ->field('ro.*, r.room_number, r.building_name, c.name as community_name, ro.reporter as contact, ro.reporter_phone as phone')
            ->where($where)->page($page, $limit)->order('ro.id', 'desc')->select();
        return $this->success(['list' => $list, 'total' => $total]);
    }

    public function detail()
    {
        $id = $this->request->param('id', 0);
        $worker = $this->getCurrentWorker();
        if (!$worker) return $this->error('未找到员工信息');

        $info = Db::name('repair_order')->alias('ro')
            ->leftJoin('room r', 'r.id = ro.room_id')
            ->leftJoin('community c', 'c.id = ro.community_id')
            ->field('ro.*, r.room_number, r.building_name, c.name as community_name, ro.reporter as contact, ro.reporter_phone as phone')
            ->where('ro.id', $id)
            ->where('ro.community_id', $worker['community_id'])
            ->find();

        if (!$info) return $this->error('工单不存在');

        // 已派单的工单需校验是否指派给自己
        if (in_array((int)$info['status'], [2, 3, 4, 5, 6]) && (int)$info['assignee_id'] !== (int)$worker['id']) {
            return $this->error('无权限查看该工单');
        }

        return $this->success($info);
    }

    public function accept()
    {
        $id = $this->request->post('id', 0);
        $worker = $this->getCurrentWorker();
        if (!$worker) return $this->error('未找到员工信息');

        // 查询工单，校验归属和状态
        $order = Db::name('repair_order')->where('id', $id)->find();
        if (!$order) return $this->error('工单不存在');
        if ((int)$order['assignee_id'] !== (int)$worker['id']) return $this->error('该工单未指派给您');
        if ((int)$order['status'] !== 2) return $this->error('当前工单状态不可接单');

        Db::name('repair_order')->where('id', $id)->update([
            'accept_time' => date('Y-m-d H:i:s'),
            'status' => 3,
        ]);
        return $this->success([], '接单成功');
    }

    /**
     * 抢单：本小区工人主动认领待处理的工单
     */
    public function claim()
    {
        $id = $this->request->post('id', 0);
        $worker = $this->getCurrentWorker();
        if (!$worker) return $this->error('未找到员工信息');

        $order = Db::name('repair_order')->where('id', $id)->find();
        if (!$order) return $this->error('工单不存在');
        if ((int)$order['status'] !== 1) return $this->error('该工单已分配，不可抢单');
        if ((int)$order['community_id'] !== (int)$worker['community_id']) return $this->error('工单不在您的小区');

        Db::name('repair_order')->where('id', $id)->update([
            'assignee_id' => $worker['id'],
            'status' => 2,
        ]);
        return $this->success([], '抢单成功，请尽快处理');
    }

    public function finish()
    {
        $id = $this->request->post('id', 0);
        $worker = $this->getCurrentWorker();
        if (!$worker) return $this->error('未找到员工信息');

        // 查询工单，校验归属和状态
        $order = Db::name('repair_order')->where('id', $id)->find();
        if (!$order) return $this->error('工单不存在');
        if ((int)$order['assignee_id'] !== (int)$worker['id']) return $this->error('该工单未指派给您');
        if ((int)$order['status'] !== 3) return $this->error('当前工单状态不可完工，请先接单');

        // 白名单：仅允许更新这些字段
        $raw = $this->request->post();
        $allowed = ['finish_remark', 'finish_images', 'repair_cost', 'repair_content'];
        $data = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $raw)) {
                $data[$field] = $raw[$field];
            }
        }
        // 强制字段
        $data['status'] = 4;
        $data['finish_time'] = date('Y-m-d H:i:s');

        Db::name('repair_order')->where('id', $id)->update($data);
        return $this->success([], '已完成');
    }
}
