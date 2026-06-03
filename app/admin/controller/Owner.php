<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Owner extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $keyword     = $this->request->param('keyword', '');
        $communityId = $this->request->param('community_id', 0);
        $type        = $this->request->param('type', 0);

        // count 直接查 owner 表，不 JOIN
        $cntQuery = Db::name('owner')->whereNull('delete_time');
        if ($keyword)     $cntQuery->where('realname|phone|id_card', 'like', "%{$keyword}%");
        if ($communityId) $cntQuery->where('community_id', $communityId);
        if ($type)        $cntQuery->where('type', $type);
        $total = $cntQuery->count();

        // list 查 + JOIN（排除密码字段）
        $ownerFields = ['o.id','o.community_id','o.realname','o.gender','o.phone','o.openid','o.wechat_unionid',
            'o.id_card','o.birthday','o.email','o.avatar','o.type','o.status','o.remark',
            'o.register_time','o.last_login_time','o.create_time','o.update_time'];
        $listQuery = Db::name('owner')->alias('o')
            ->leftJoin('community c', 'c.id = o.community_id')
            ->field(implode(',', $ownerFields) . ', c.name as community_name')
            ->whereNull('`o`.`delete_time`');
        if ($keyword)     $listQuery->where('o.realname|o.phone|o.id_card', 'like', "%{$keyword}%");
        if ($communityId) $listQuery->where('`o`.`community_id`', '=', intval($communityId));
        if ($type)        $listQuery->where('`o`.`type`', '=', intval($type));
        $list = $listQuery->order('o.id', 'desc')->page($page, $limit)->select();

        // 附录：关联房间（批量查询，避免 N+1）
        $ownerIds = array_unique(array_column($list, 'id'));
        $roomMap = [];
        if (!empty($ownerIds)) {
            $placeholders = implode(',', array_fill(0, count($ownerIds), '?'));
            $pdo = Db::name('owner_room')->getPdo();
            $stmt = $pdo->prepare(
                "SELECT ocr.owner_id, r.room_number, r.building_name, r.area 
                 FROM `ds_owner_room` ocr 
                 LEFT JOIN `ds_room` r ON r.id = ocr.room_id 
                 WHERE ocr.owner_id IN ({$placeholders}) AND ocr.delete_time IS NULL"
            );
            $stmt->execute(array_values($ownerIds));
            foreach ($stmt->fetchAll() as $row) {
                $roomMap[$row['owner_id']][] = [
                    'room_number' => $row['room_number'],
                    'building_name' => $row['building_name'],
                    'area' => $row['area'],
                ];
            }
        }
        foreach ($list as &$item) {
            $rooms = $roomMap[$item['id']] ?? [];
            $item['rooms']      = $rooms;
            $item['room_count'] = count($rooms);
            $item['password']   = '';
            $item['wx_bound']   = !empty($item['openid']) ? 1 : 0;
            // 脱敏 openid
            if (!empty($item['openid'])) {
                $item['openid_masked'] = substr($item['openid'], 0, 6) . '****' . substr($item['openid'], -4);
            }
        }
        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();

        // 过滤不应插入 owner 表的字段
        unset($data['id'], $data['room_id']);

        // 手机号唯一性校验
        $exist = Db::name('owner')->where('phone', $data['phone'] ?? '')->whereNull('delete_time')->find();
        if ($exist) {
            return $this->error('该手机号已被业主「' . $exist['realname'] . '」使用');
        }

        $roomId = $this->request->post('room_id', 0);

        // 房间占用检查：不允许抢已经有人住的房间
        if ($roomId) {
            $occupied = Db::name('owner_room')
                ->where('room_id', $roomId)
                ->whereNull('delete_time')
                ->find();
            if ($occupied) {
                $occOwner = Db::name('owner')->where('id', $occupied['owner_id'])->find();
                return $this->error('该房间已被业主「' . ($occOwner['realname'] ?? '未知') . '」占用，请选择其他房间');
            }
        }

        if (empty($data['password'])) $data['password'] = substr($data['phone'], -6);
        $data['password']     = encrypt_password($data['password']);
        $data['register_time'] = date('Y-m-d H:i:s');
        $data['create_time']   = date('Y-m-d H:i:s');

        Db::name('owner')->getPdo()->beginTransaction();
        try {
            $ownerId = Db::name('owner')->insertGetId($data);

            if ($roomId) {
                Db::name('owner_room')->insert([
                    'owner_id'     => $ownerId,
                    'room_id'      => $roomId,
                    'community_id' => $data['community_id'],
                    'relation'     => '业主',
                    'is_primary'   => 1,
                    'create_time'  => date('Y-m-d H:i:s'),
                ]);
                Db::name('room')->where('id', $roomId)->update([
                    'owner_id' => $ownerId, 'status' => 2,
                ]);
                Db::name('community')->where('id', $data['community_id'])->inc('owner_count')->update();
            }
            Db::name('owner')->getPdo()->commit();
        } catch (\Exception $e) {
            Db::name('owner')->getPdo()->rollBack();
            return $this->error('添加失败：' . $e->getMessage());
        }
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        $id = intval($data['id'] ?? 0);
        if (!$id) return $this->error('参数错误');
        unset($data['id'], $data['room_id']);

        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = encrypt_password($data['password']);
        } else {
            unset($data['password']);
        }

        $roomId = $this->request->post('room_id', 0);

        // 房间占用检查：如果更换房间，不允许换到已被别人占用的房间
        if ($roomId) {
            $occupied = Db::name('owner_room')
                ->where('room_id', $roomId)
                ->where('owner_id', '<>', $id)
                ->whereNull('delete_time')
                ->find();
            if ($occupied) {
                $occOwner = Db::name('owner')->where('id', $occupied['owner_id'])->find();
                return $this->error('该房间已被业主「' . ($occOwner['realname'] ?? '未知') . '」占用，请选择其他房间');
            }
        }

        Db::name('owner')->getPdo()->beginTransaction();
        try {
            Db::name('owner')->where('id', $id)->update($data);

            if ($roomId) {
                $oldRooms = Db::name('owner_room')->where('owner_id', $id)->column('room_id');
                if (!empty($oldRooms)) {
                    Db::name('room')->where('id', 'in', $oldRooms)->update([
                        'owner_id' => null, 'status' => 1,
                    ]);
                }
                Db::name('owner_room')->where('owner_id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
                $exist = Db::name('owner_room')->where('owner_id', $id)
                    ->where('room_id', $roomId)->whereNull('delete_time')->find();
                if ($exist) {
                    Db::name('owner_room')->where('id', $exist['id'])->update([
                        'delete_time'  => null,
                        'community_id' => $data['community_id'] ?? 0,
                        'relation'     => '业主',
                        'is_primary'   => 1,
                    ]);
                } else {
                    Db::name('owner_room')->insert([
                        'owner_id'     => $id,
                        'room_id'      => $roomId,
                        'community_id' => $data['community_id'] ?? 0,
                        'relation'     => '业主',
                        'is_primary'   => 1,
                        'create_time'  => date('Y-m-d H:i:s'),
                    ]);
                }
                Db::name('room')->where('id', $roomId)->update([
                    'owner_id' => $id, 'status' => 2,
                ]);
            }
            Db::name('owner')->getPdo()->commit();
        } catch (\Exception $e) {
            Db::name('owner')->getPdo()->rollBack();
            return $this->error('修改失败：' . $e->getMessage());
        }
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('owner')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        $roomIds = Db::name('owner_room')->where('owner_id', $id)->whereNull('delete_time')->column('room_id');
        if (!empty($roomIds)) {
            Db::name('room')->where('id', 'in', $roomIds)->update([
                'owner_id' => null, 'status' => 1,
            ]);
        }
        Db::name('owner_room')->where('owner_id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }

    public function detail()
    {
        $id = $this->request->param('id', 0);
        $owner = Db::name('owner')->where('id', $id)->find();
        if (!$owner) return $this->error('业主不存在');
        $rooms = Db::name('owner_room')->alias('ocr')
            ->leftJoin('room r', 'r.id = ocr.room_id')
            ->where('`ocr`.`owner_id`', '=', intval($id))
            ->whereNull('`ocr`.`delete_time`')
            ->field('r.*, ocr.relation, ocr.is_primary, ocr.start_date, ocr.end_date')
            ->select();
        $families = Db::name('owner_family')->where('owner_id', $id)->whereNull('delete_time')->select();
        $bills = Db::name('bill')->where('owner_id', $id)->whereNull('delete_time')->order('id', 'desc')->limit(10)->select();
        $owner['rooms']    = $rooms;
        $owner['families'] = $families;
        $owner['bills']    = $bills;
        return $this->success($owner);
    }

    public function rooms()
    {
        $ownerId = $this->request->param('owner_id', 0);
        $rooms = Db::name('owner_room')->alias('ocr')
            ->leftJoin('room r', 'r.id = ocr.room_id')
            ->where('`ocr`.`owner_id`', '=', intval($ownerId))
            ->whereNull('`ocr`.`delete_time`')
            ->field('r.*, ocr.relation, ocr.is_primary')
            ->select();
        return $this->success($rooms);
    }

    /**
     * 管理员解绑业主微信
     */
    public function unbindWechat()
    {
        $id = intval($this->request->post('id', 0));
        if ($id <= 0) return $this->error('参数错误');

        $owner = Db::name('owner')->where('id', $id)->find();
        if (!$owner) return $this->error('业主不存在');

        Db::name('owner')->where('id', $id)->update([
            'openid'         => '',
            'wechat_unionid' => '',
        ]);
        return $this->success([], '微信已解绑');
    }
}
