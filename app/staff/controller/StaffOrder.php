<?php
namespace app\staff\controller;

use app\staff\BaseStaff;
use think\facade\Db;

class StaffOrder extends BaseStaff
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['ro.delete_time', 'null', '']];

        // 安全：按员工绑定小区过滤工单列表，防止数据泄露
        $staffCommId = $this->staffInfo['community_id'] ?? 0;
        if ($staffCommId) {
            $where[] = ['ro.community_id', '=', $staffCommId];
        }

        $status = $this->request->param('status', '');
        if ($status !== '') {
            $where[] = ['ro.status', '=', $status];
        }

        $total = Db::name('repair_order')->alias('ro')->where($where)->count();
        $list = Db::name('repair_order')->alias('ro')
            ->leftJoin('community c', 'c.id = ro.community_id')
            ->field('ro.*, c.name as community_name')
            ->where($where)
            ->page($page, $limit)->order('ro.id', 'desc')->select();
        return $this->success(['list' => $list, 'total' => $total]);
    }

    public function create()
    {
        // 安全：字段白名单，防止批量赋值漏洞
        $raw = $this->request->post();
        $allowed = ['community_id','building_name','unit_name','room_name','owner_name','owner_phone',
            'category','description','images','appointment_time','urgent'];
        $data = [];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $raw)) $data[$f] = $raw[$f];
        }
        if (empty($data['community_id']) && !empty($this->staffInfo['community_id'])) {
            $data['community_id'] = $this->staffInfo['community_id'];
        }
        $data['order_no'] = build_order_no('DSR');
        $data['source'] = 2;
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('repair_order')->insert($data);
        return $this->success([], '创建成功');
    }

    public function close()
    {
        $id = $this->request->post('id', 0);
        // 安全：校验工单归属（本小区 + 未被删除）
        $order = Db::name('repair_order')->where('id', $id)->whereNull('delete_time')->find();
        if (!$order) return $this->error('工单不存在');
        $staffCommId = $this->staffInfo['community_id'] ?? 0;
        if ($staffCommId && $staffCommId != $order['community_id']) {
            return $this->error('无权操作该工单');
        }
        Db::name('repair_order')->where('id', $id)->update([
            'status' => 6,
            'remark' => $this->request->post('remark', ''),
        ]);
        return $this->success([], '已关闭');
    }
}
