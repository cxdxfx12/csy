<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class LeaseProperty extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['delete_time', 'null', '']];

        $keyword      = $this->request->param('keyword', '');
        $propertyType = $this->request->param('property_type', '');
        $status       = $this->request->param('status', '');
        $decoration   = $this->request->param('decoration', '');

        if ($keyword)      $where[] = ['property_name|description|remark', 'like', "%{$keyword}%"];
        if ($propertyType) $where[] = ['property_type', '=', $propertyType];
        if ($status)       $where[] = ['status', '=', $status];
        if ($decoration)   $where[] = ['decoration', '=', $decoration];

        $cid = $this->getFilteredCommunityId();
        if ($cid === -1) $where[] = ['community_id', 'in', $this->request->boundCommunityIds];
        elseif ($cid > 0) $where[] = ['community_id', '=', $cid];

        $total = Db::name('lease_property')->where($where)->count();
        $list  = Db::name('lease_property')->where($where)->page($page, $limit)->order('id', 'desc')->select();

        return $this->table($list, $total);
    }

    /**
     * 获取房间管理中的空置房产，供录入房源时快速选取并自动填充
     * 只返回 status=1(未售) 或 5(空置) 且未被租赁占用的房间
     */
    public function vacantRooms()
    {
        $propertyType = $this->request->param('property_type', '住宅');
        $keyword      = $this->request->param('keyword', '');

        // 房源类型 → 房间 type 映射
        $typeMap = [
            '住宅' => 1, '公寓' => 1,
            '商铺' => 2, '办公室' => 3, '车位' => 4, '仓库' => 5,
        ];
        $roomType = $typeMap[$propertyType] ?? 0;

        // 已在租赁中的 room_id（排除）
        $listedRoomIds = Db::name('lease_property')
            ->whereIn('status', ['可租', '已租', '预留', '装修中'])
            ->whereNull('delete_time')
            ->where('room_id', '>', 0)
            ->column('room_id');

        $query = Db::name('room')->alias('r')
            ->leftJoin('community c', 'c.id = r.community_id')
            ->leftJoin('building b',  'b.id = r.building_id')
            ->field('r.id, r.community_id, r.building_id, r.room_number, r.floor, r.area, r.usable_area, r.orientation, r.layout, r.decorate_status, r.type, c.name as community_name, b.name as building_name')
            ->whereNull('r.delete_time')
            ->whereIn('r.status', [1, 5]); // 未售 或 空置

        // 排除已列租赁的
        if (!empty($listedRoomIds)) {
            $query->whereNotIn('r.id', $listedRoomIds);
        }

        // 按房间类型过滤
        if ($roomType > 0) {
            $query->where('r.type', $roomType);
        }

        // 小区权限
        $cid = $this->getFilteredCommunityId();
        if ($cid === -1) {
            $query->whereIn('r.community_id', $this->request->boundCommunityIds);
        } elseif ($cid > 0) {
            $query->where('r.community_id', $cid);
        }

        // 关键词搜索
        if ($keyword) {
            $query->where('r.room_number|b.name|c.name', 'like', "%{$keyword}%");
        }

        $list = $query->order('c.name, b.name, r.room_number')->select();

        return $this->success($list);
    }

    public function add()
    {
        $data = $this->request->post();
        // 移除无关字段
        unset($data['id']);
        // 若关联了房间，校验小区权限
        if (!empty($data['community_id'])) {
            $this->validateCommunityAccess($data['community_id']);
        }
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('lease_property')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        $prop = Db::name('lease_property')->where('id', $data['id'])->find();
        if (!$prop) return $this->error('房源不存在');
        $this->validateCommunityAccess($prop['community_id'] ?? 0);
        Db::name('lease_property')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        $prop = Db::name('lease_property')->where('id', $id)->find();
        if ($prop) $this->validateCommunityAccess($prop['community_id'] ?? 0);
        Db::name('lease_property')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }
}
