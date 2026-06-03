<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class EquipmentMaintain extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['em.delete_time', 'null', '']];
        $equipmentId = $this->request->param('equipment_id', 0);
        if ($equipmentId) $where[] = ['em.equipment_id', '=', $equipmentId];
        $startDate = $this->request->param('start_date', '');
        $endDate = $this->request->param('end_date', '');
        if ($startDate) $where[] = ['em.maintain_date', '>=', $startDate];
        if ($endDate) $where[] = ['em.maintain_date', '<=', $endDate];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['eq.name|eq.code', 'like', "%{$keyword}%"];

        $total = Db::name('equipment_maintain')->alias('em')
            ->leftJoin('equipment eq', 'eq.id = em.equipment_id')
            ->where($where)->count();
        $list = Db::name('equipment_maintain')->alias('em')
            ->leftJoin('equipment eq', 'eq.id = em.equipment_id')
            ->leftJoin('community com', 'com.id = eq.community_id')
            ->field('em.*, eq.name as equipment_name, eq.code as equipment_code, com.name as community_name')
            ->where($where)->page($page, $limit)->order('em.id', 'desc')->select();
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
