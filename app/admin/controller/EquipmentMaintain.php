<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class EquipmentMaintain extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $cid = $this->getFilteredCommunityId();
        $equipmentId = $this->request->param('equipment_id', 0);
        $startDate = $this->request->param('start_date', '');
        $endDate = $this->request->param('end_date', '');
        $keyword = $this->request->param('keyword', '');

        $cntQuery = Db::name('equipment_maintain')->alias('em')
            ->leftJoin('equipment eq', 'eq.id = em.equipment_id')
            ->whereNull('`em`.`delete_time`');
        if ($cid === -1) $cntQuery->where('`eq`.`community_id`', 'in', $this->request->boundCommunityIds);
        elseif ($cid > 0) $cntQuery->where('`eq`.`community_id`', '=', intval($cid));
        if ($equipmentId) $cntQuery->where('`em`.`equipment_id`', '=', $equipmentId);
        if ($startDate) $cntQuery->where('`em`.`maintain_date`', '>=', $startDate);
        if ($endDate) $cntQuery->where('`em`.`maintain_date`', '<=', $endDate);
        if ($keyword) $cntQuery->where('`eq`.`name`|`eq`.`code`', 'like', "%{$keyword}%");
        $total = $cntQuery->count();

        $listQuery = Db::name('equipment_maintain')->alias('em')
            ->leftJoin('equipment eq', 'eq.id = em.equipment_id')
            ->leftJoin('community com', 'com.id = eq.community_id')
            ->field('em.*, eq.name as equipment_name, eq.code as equipment_code, com.name as community_name')
            ->whereNull('`em`.`delete_time`');
        if ($cid === -1) $listQuery->where('`eq`.`community_id`', 'in', $this->request->boundCommunityIds);
        elseif ($cid > 0) $listQuery->where('`eq`.`community_id`', '=', intval($cid));
        if ($equipmentId) $listQuery->where('`em`.`equipment_id`', '=', $equipmentId);
        if ($startDate) $listQuery->where('`em`.`maintain_date`', '>=', $startDate);
        if ($endDate) $listQuery->where('`em`.`maintain_date`', '<=', $endDate);
        if ($keyword) $listQuery->where('`eq`.`name`|`eq`.`code`', 'like', "%{$keyword}%");
        $list = $listQuery->page($page, $limit)->order('em.id', 'desc')->select();
        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        $equip = Db::name('equipment')->where('id', $data['equipment_id'])->find();
        $data['equipment_name'] = $equip['name'] ?? '';
        $data['operator_id'] = get_admin_id();
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('equipment_maintain')->insert($data);

        // 更新设备维保日期
        if (!empty($data['maintain_date'])) {
            Db::name('equipment')->where('id', $data['equipment_id'])->update([
                'last_maintain_date' => $data['maintain_date'],
                'next_maintain_date' => $data['next_maintain_date'] ?? null,
            ]);
        }
        return $this->success([], '添加成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('equipment_maintain')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }
}
