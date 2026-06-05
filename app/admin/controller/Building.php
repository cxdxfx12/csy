<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Building extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $cid = $this->getFilteredCommunityId();

        // count 直接用无别名查询
        $cntQuery = Db::name('building')->whereNull('delete_time');
        if ($cid === -1) $cntQuery->where('community_id', 'in', $this->request->boundCommunityIds);
        elseif ($cid > 0) $cntQuery->where('community_id', $cid);
        $total = $cntQuery->count();

        // list 查询用别名+JOIN，别名映射给前端用 floors/units
        $listQuery = Db::name('building')->alias('b')
            ->leftJoin('community c', 'c.id = b.community_id')
            ->leftJoin('staff s', 's.id = b.manager_id')
            ->field('b.*, b.floor_count as floors, b.unit_count as units, c.name as community_name, s.realname as manager_name')
            ->whereNull('`b`.`delete_time`');
        if ($cid === -1) $listQuery->where('`b`.`community_id`', 'in', $this->request->boundCommunityIds);
        elseif ($cid > 0) $listQuery->where('`b`.`community_id`', '=', intval($cid));
        $list = $listQuery->order('b.sort', 'asc')->page($page, $limit)->select();
        return $this->table($list, $total);
    }

    /** 前端字段名 → 数据库字段名映射 */
    private function mapFields(&$data)
    {
        if (isset($data['floors'])) { $data['floor_count'] = intval($data['floors']); unset($data['floors']); }
        if (isset($data['units']))  { $data['unit_count']  = intval($data['units']);  unset($data['units']); }
        if (isset($data['has_commercial']))    $data['has_commercial']    = intval($data['has_commercial']);
        if (isset($data['commercial_floors'])) $data['commercial_floors'] = intval($data['commercial_floors']);
        unset($data['code']);            // DB 无此字段，去除
        unset($data['rooms_per_floor']); // 旧字段，已废弃
        unset($data['units_config']);    // 只用于 add() 房间生成，不入 building 表
    }

    public function add()
    {
        $data = $this->request->post();
        // 先提取单元配置，再调用 mapFields（mapFields 会抹掉 units_config）
        $unitsConfig = $data['units_config'] ?? [];
        $this->mapFields($data);
        $exist = Db::name('building')->where('community_id', $data['community_id'])
            ->where('name', $data['name'])->whereNull('delete_time')->find();
        if ($exist) {
            return $this->error('该小区下已存在同名楼栋');
        }
        $data['create_time'] = date('Y-m-d H:i:s');
        $buildingId = Db::name('building')->insertGetId($data);

        // 根据 per-unit 配置自动生成房间（带面积），底商楼层跳过
        $roomCount = 0;
        $floorCount  = intval($data['floor_count'] ?? 0);
        $hasCommercial = intval($data['has_commercial'] ?? 0);
        $commercialFloors = intval($data['commercial_floors'] ?? 0);
        $startFloor = ($hasCommercial && $commercialFloors > 0) ? ($commercialFloors + 1) : 1;
        $totalPerFloor = 0;

        if ($startFloor > $floorCount) {
            $msg = '添加成功（底商层数≥总层数，未生成房间）';
        } elseif ($floorCount > 0 && !empty($unitsConfig)) {
            $insertData = [];
            foreach ($unitsConfig as $idx => $unitCfg) {
                $unitNum       = $idx + 1;
                $roomsPerFloor = intval($unitCfg['rooms_per_floor'] ?? 0);
                $areas         = $unitCfg['areas'] ?? [];

                if ($roomsPerFloor <= 0) continue;
                $totalPerFloor += $roomsPerFloor;

                for ($floor = $startFloor; $floor <= $floorCount; $floor++) {
                    for ($room = 1; $room <= $roomsPerFloor; $room++) {
                        $roomNumber = ($unitNum > 1 ? $unitNum : '') . $floor . str_pad($room, 2, '0', STR_PAD_LEFT);
                        $area       = floatval($areas[$room - 1] ?? 0);
                        $insertData[] = [
                            'community_id'  => $data['community_id'],
                            'building_id'   => $buildingId,
                            'building_name' => $data['name'],
                            'unit'          => $unitNum > 1 ? $unitNum . '单元' : '',
                            'floor'         => $floor,
                            'room_number'   => $roomNumber,
                            'area'          => $area,
                            'type'          => 1,
                            'status'        => 1,
                            'create_time'   => date('Y-m-d H:i:s'),
                        ];
                    }
                }
            }
            if (!empty($insertData)) {
                Db::name('room')->insertAll($insertData);
                $roomCount = count($insertData);
                Db::name('building')->where('id', $buildingId)->update([
                    'total_rooms' => $roomCount,
                    'floor_rooms' => $totalPerFloor,
                ]);
            }
            $msg = '添加成功';
            if ($roomCount > 0) $msg .= '，已自动生成' . $roomCount . '个房间';
            if ($hasCommercial && $commercialFloors > 0) $msg .= '（底商' . $commercialFloors . '层已跳过）';
        } else {
            $msg = '添加成功';
        }
        return $this->success(['room_count' => $roomCount], $msg);
    }

    public function edit()
    {
        $data = $this->request->post();
        $this->mapFields($data);
        $exist = Db::name('building')->where('community_id', $data['community_id'])
            ->where('name', $data['name'])->where('id', '<>', $data['id'])
            ->whereNull('delete_time')->find();
        if ($exist) {
            return $this->error('该小区下已存在同名楼栋');
        }
        Db::name('building')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        $force = $this->request->post('force', 0);
        $roleId = $this->adminInfo['role_id'] ?? 0;

        $roomCount = Db::name('room')->where('building_id', $id)->count();
        if ($roomCount > 0) {
            // 强制删除：仅超管(1)和系统管理员(2)可用，级联删除所有房间
            if ($force && in_array($roleId, [1, 2])) {
                Db::name('room')->where('building_id', $id)->delete();
            } else {
                return $this->error('该楼栋下还有房间，无法删除。仅超管/管理员可使用强制删除');
            }
        }
        Db::name('building')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success(['deleted_rooms' => $roomCount], '删除成功' . ($roomCount > 0 ? '，已级联删除 ' . $roomCount . ' 个房间' : ''));
    }

    public function listAll()
    {
        $list = Db::name('building')->where('delete_time', null)->field('id, name, community_id')->order('sort', 'asc')->select();
        return $this->success($list);
    }

    public function select()
    {
        $communityId = $this->request->param('community_id', 0);
        $list = Db::name('building')->where('community_id', $communityId)->whereNull('delete_time')
            ->field('id,name')->order('sort', 'asc')->select();
        return $this->success($list);
    }
}
