<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Meter extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['m.delete_time', '=', null]];
        $communityId = $this->request->param('community_id', 0);
        if ($communityId) $where[] = ['m.community_id', '=', $communityId];
        $type = $this->request->param('type', 0);
        if ($type) $where[] = ['m.type', '=', $type];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['r.room_number|m.meter_no|m.reading_by', 'like', "%{$keyword}%"];
        $dateStart = $this->request->param('date_start', '');
        if ($dateStart) $where[] = ['m.reading_date', '>=', $dateStart];
        $dateEnd = $this->request->param('date_end', '');
        if ($dateEnd) $where[] = ['m.reading_date', '<=', $dateEnd];

        $total = Db::name('meter_reading')->alias('m')
            ->leftJoin('room r', 'r.id = m.room_id')
            ->where($where)->count();
        $list = Db::name('meter_reading')->alias('m')
            ->leftJoin('room r', 'r.id = m.room_id')
            ->field('m.*, r.room_number, r.building_name, r.community_id as room_community_id')
            ->where($where)->page($page, $limit)->order('m.id', 'desc')->select();

        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        // 自动计算用量
        $data['usage_amount'] = floatval($data['current_reading'] ?? 0) - floatval($data['previous_reading'] ?? 0);
        $data['operator_id'] = get_admin_id();
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['update_time'] = date('Y-m-d H:i:s');

        // 兼容前端传 reading_month 但 DB 是 reading_date
        if (empty($data['reading_date']) && !empty($data['reading_month'])) {
            $data['reading_date'] = $data['reading_month'] . '-01';
        }
        if (empty($data['reading_date'])) {
            $data['reading_date'] = date('Y-m-d');
        }

        // 从房间获取 community_id
        if (empty($data['community_id']) && !empty($data['room_id'])) {
            $room = Db::name('room')->where('id', $data['room_id'])->find();
            $data['community_id'] = $room['community_id'] ?? 0;
        }

        // 只保留 DB 实际存在的字段，防止 injection
        $allowed = ['community_id','room_id','type','meter_no','previous_reading','current_reading',
                    'usage_amount','reading_date','reading_by','operator_id','photo','status','remark',
                    'create_time','update_time'];
        $insert = array_intersect_key($data, array_flip($allowed));
        Db::name('meter_reading')->insert($insert);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        $id = $data['id'] ?? 0;
        if (!$id) return $this->error('缺少ID');

        // 自动计算用量
        if (isset($data['current_reading']) && isset($data['previous_reading'])) {
            $data['usage_amount'] = floatval($data['current_reading']) - floatval($data['previous_reading']);
        }

        // 兼容前端传 reading_month 但 DB 是 reading_date
        if (empty($data['reading_date']) && !empty($data['reading_month'])) {
            $data['reading_date'] = $data['reading_month'] . '-01';
        }

        $data['update_time'] = date('Y-m-d H:i:s');

        // 只保留 DB 字段
        $allowed = ['community_id','room_id','type','meter_no','previous_reading','current_reading',
                    'usage_amount','reading_date','reading_by','photo','status','remark','update_time'];
        $update = array_intersect_key($data, array_flip($allowed));
        Db::name('meter_reading')->where('id', $id)->update($update);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('meter_reading')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }

    public function export()
    {
        return $this->success([], '导出功能待完善');
    }
}
