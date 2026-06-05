<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Room extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $cid        = $this->getFilteredCommunityId();
        $buildingId = $this->request->param('building_id', 0);
        $keyword    = $this->request->param('keyword', '');
        $status     = $this->request->param('status', '');

        // count 直接查 room 表，不 JOIN
        $cntQuery = Db::name('room')->whereNull('delete_time');
        if ($cid === -1)  $cntQuery->where('community_id', 'in', $this->request->boundCommunityIds);
        elseif ($cid > 0) $cntQuery->where('community_id', $cid);
        if ($buildingId)  $cntQuery->where('building_id', $buildingId);
        if ($keyword)     $cntQuery->where('room_number', 'like', "%{$keyword}%");
        if ($status !== '') $cntQuery->where('status', $status);
        $total = $cntQuery->count();

        // list 查 + JOIN
        $listQuery = Db::name('room')->alias('r')
            ->leftJoin('community c', 'c.id = r.community_id')
            ->leftJoin('building b',  'b.id = r.building_id')
            ->leftJoin('owner o',     'o.id = r.owner_id')
            ->field('r.*, c.name as community_name, b.name as building_name, o.realname as owner_name, o.phone as owner_phone')
            ->whereNull('`r`.`delete_time`');
        if ($cid === -1)  $listQuery->where('`r`.`community_id`', 'in', $this->request->boundCommunityIds);
        elseif ($cid > 0) $listQuery->where('`r`.`community_id`', '=', intval($cid));
        if ($buildingId)  $listQuery->where('`r`.`building_id`', '=', intval($buildingId));
        if ($keyword)     $listQuery->where('r.room_number', 'like', "%{$keyword}%");
        if ($status !== '') $listQuery->where('`r`.`status`', '=', intval($status));
        $list = $listQuery->order('r.id', 'asc')->page($page, $limit)->select();
        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        $building = Db::name('building')->where('id', $data['building_id'])->find();
        if (!$building) return $this->error('楼栋不存在');
        $this->validateCommunityAccess($building['community_id'] ?? 0);
        $exist = Db::name('room')->where('building_id', $data['building_id'])
            ->where('room_number', $data['room_number'])->whereNull('delete_time')->find();
        if ($exist) {
            return $this->error('该楼栋下已存在相同编号的房间');
        }
        $building = Db::name('building')->where('id', $data['building_id'])->find();
        $data['community_id'] = $building['community_id'] ?? 0;
        $data['building_name'] = $building['name'] ?? '';
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('room')->insert($data);
        Db::name('building')->where('id', $data['building_id'])->inc('total_rooms')->update();
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $post = $this->request->post();
        $room = Db::name('room')->where('id', $post['id'])->find();
        if (!$room) return $this->error('房间不存在');
        $this->validateCommunityAccess($room['community_id'] ?? 0);
        $data = ['id' => $post['id']];
        if (isset($post['area']))   $data['area']   = $post['area'];
        if (isset($post['layout'])) $data['layout'] = $post['layout'];
        if (isset($post['status'])) $data['status'] = $post['status'];
        Db::name('room')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        $room = Db::name('room')->where('id', $id)->find();
        if (!$room) return $this->error('房间不存在');
        $this->validateCommunityAccess($room['community_id'] ?? 0);
        Db::name('room')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }

    public function batchAdd()
    {
        $buildingId   = $this->request->post('building_id', 0);
        $startFloor   = $this->request->post('start_floor', 1);
        $endFloor     = $this->request->post('end_floor', 1);
        $roomsPerFloor = $this->request->post('rooms_per_floor', 2);
        $unitCount    = $this->request->post('unit_count', 1);
        $prefix       = $this->request->post('prefix', '');

        $building = Db::name('building')->where('id', $buildingId)->find();
        if (!$building) return $this->error('楼栋不存在');

        // 底商楼层自动跳过
        $hasCommercial = intval($building['has_commercial'] ?? 0);
        $commercialFloors = intval($building['commercial_floors'] ?? 0);
        $realStart = ($hasCommercial && $commercialFloors > 0) ? max($startFloor, $commercialFloors + 1) : $startFloor;

        $insertData = [];
        for ($floor = $realStart; $floor <= $endFloor; $floor++) {
            for ($unit = 1; $unit <= $unitCount; $unit++) {
                for ($room = 1; $room <= $roomsPerFloor; $room++) {
                    $roomNumber = $prefix . ($unit > 1 ? $unit : '') . $floor . str_pad($room, 2, '0', STR_PAD_LEFT);
                    $insertData[] = [
                        'community_id'  => $building['community_id'],
                        'building_id'   => $buildingId,
                        'building_name' => $building['name'],
                        'unit'          => $unit > 1 ? $unit . '单元' : '',
                        'floor'         => $floor,
                        'room_number'   => $roomNumber,
                        'type'          => 1,
                        'status'        => 1,
                        'create_time'   => date('Y-m-d H:i:s'),
                    ];
                }
            }
        }
        if (!empty($insertData)) {
            Db::name('room')->insertAll($insertData);
            $count = count($insertData);
            Db::name('building')->where('id', $buildingId)->update([
                'total_rooms' => $building['total_rooms'] + $count,
                'floor_count' => max($building['floor_count'], $endFloor),
                'floor_rooms' => $roomsPerFloor * $unitCount,
            ]);
        }
        $msg = '批量生成成功，共生成' . count($insertData) . '个房间';
        if ($hasCommercial && $commercialFloors > 0 && $startFloor <= $commercialFloors) $msg .= '（底商' . $commercialFloors . '层已跳过）';
        return $this->success(['count' => count($insertData)], $msg);
    }

    public function select()
    {
        $communityId = $this->request->param('community_id', 0);
        $buildingId  = $this->request->param('building_id', 0);
        $list = Db::name('room')->alias('r')
            ->leftJoin('owner_room ocr', 'ocr.room_id = r.id AND ocr.delete_time IS NULL')
            ->leftJoin('owner o', 'o.id = ocr.owner_id AND o.delete_time IS NULL')
            ->whereNull('r.delete_time')
            ->where(function($q) use ($communityId, $buildingId) {
                if ($communityId) $q->where('r.community_id', '=', intval($communityId));
                if ($buildingId)  $q->where('r.building_id', '=', intval($buildingId));
            })
            ->field('r.id, r.room_number, r.building_name, r.unit, r.floor, r.area, o.realname as owner_name, ocr.owner_id')
            ->group('r.id')
            ->select();
        return $this->success($list);
    }
}
