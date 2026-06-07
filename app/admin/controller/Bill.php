<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Bill extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['b.delete_time', 'null', '']];
        $cid = $this->getFilteredCommunityId();
        if ($cid === -1) $where[] = ['b.community_id', 'in', $this->request->boundCommunityIds];
        elseif ($cid > 0) $where[] = ['b.community_id', '=', $cid];
        $ownerId = $this->request->param('owner_id', 0);
        if ($ownerId) $where[] = ['b.owner_id', '=', $ownerId];
        $status = $this->request->param('status', '');
        if ($status !== '') $where[] = ['b.status', '=', $status];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['b.bill_no|r.room_number|o.realname|o.phone', 'like', "%{$keyword}%"];
        $period = $this->request->param('period', '');
        if ($period) $where[] = ['b.bill_period', '=', $period];

        $total = Db::name('bill')->alias('b')
            ->leftJoin('owner o', 'o.id = b.owner_id')
            ->leftJoin('room r', 'r.id = b.room_id')
            ->where($where)->count();
        $list = Db::name('bill')->alias('b')
            ->leftJoin('owner o', 'o.id = b.owner_id')
            ->leftJoin('room r', 'r.id = b.room_id')
            ->leftJoin('charge_item ci', 'ci.id = b.charge_item_id')
            ->leftJoin('community com', 'com.id = b.community_id')
            ->field('b.*, o.realname as owner_name, o.phone as owner_phone, r.room_number, r.building_name, ci.name as charge_item_name, com.name as community_name')
            ->where($where)
            ->page($page, $limit)->order('b.id', 'desc')->select();

        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        // 字段白名单
        $allowFields = ['community_id', 'owner_id', 'room_id', 'charge_item_id', 'charge_item_name', 'bill_period', 'bill_year', 'bill_month', 'amount', 'total_amount', 'paid_amount', 'status', 'due_date', 'remark'];
        $filtered = [];
        foreach ($allowFields as $f) {
            if (isset($data[$f])) $filtered[$f] = $data[$f];
        }
        $this->validateCommunityAccess($filtered['community_id'] ?? 0);
        $filtered['bill_no'] = build_order_no('DSB');
        $filtered['create_time'] = date('Y-m-d H:i:s');
        Db::name('bill')->insert($filtered);
        return $this->success([], '添加成功');
    }

    public function generate()
    {
        $communityId = $this->request->post('community_id', 0);
        $period = $this->request->post('period', date('Y-m'));
        $chargeItemId = $this->request->post('charge_item_id', 0);

        $chargeItem = Db::name('charge_item')->where('id', $chargeItemId)->find();
        if (!$chargeItem) return $this->error('收费项目不存在');

        // 仅已售(2)和已租(3)的房间纳入收费，空置(1)不生成账单
        $allRooms = Db::name('room')->where('community_id', $communityId)
            ->where('delete_time', null)
            ->select();
        // 过滤：只保留已售(status=2)和已租(status=3)
        $rooms = array_filter($allRooms, function($r) { return in_array($r['status'], [2, 3]); });
        $skipped = count($allRooms) - count($rooms);

        $insertData = [];
        foreach ($rooms as $room) {
            $ownerRoom = Db::name('owner_room')->where('room_id', $room['id'])
                ->where('delete_time', null)->order('is_primary', 'desc')->find();
            $ownerId = $ownerRoom['owner_id'] ?? 0;

            // 计算金额
            $amount = 0;
            switch ($chargeItem['billing_mode']) {
                case 1: // 按面积
                    $amount = $room['area'] * $chargeItem['unit_price'];
                    break;
                case 2: // 按户
                    $amount = $chargeItem['unit_price'];
                    break;
                case 5: // 固定金额
                    $amount = $chargeItem['unit_price'];
                    break;
                default:
                    $amount = $chargeItem['unit_price'];
            }
            $amount = round($amount, 2);

            // 检查是否已生成
            $exists = Db::name('bill')->where([
                'room_id' => $room['id'],
                'charge_item_id' => $chargeItemId,
                'bill_period' => $period,
            ])->find();
            if ($exists) continue;

            $insertData[] = [
                'bill_no'           => build_order_no('DSB'),
                'community_id'      => $communityId,
                'owner_id'          => $ownerId,
                'room_id'           => $room['id'],
                'charge_item_id'    => $chargeItemId,
                'charge_item_name'  => $chargeItem['name'],
                'bill_period'       => $period,
                'bill_year'         => substr($period, 0, 4),
                'bill_month'        => substr($period, 5, 2),
                'amount'            => $amount,
                'paid_amount'       => 0,
                'total_amount'      => $amount,
                'status'            => 1,
                'due_date'          => date('Y-m-d', strtotime('+30 days')),
                'create_time'       => date('Y-m-d H:i:s'),
            ];
        }

        if (!empty($insertData)) {
            Db::name('bill')->insertAll($insertData);
        }

        $msg = '生成成功，共' . count($insertData) . '条账单（已售/已租房间）';
        if ($skipped > 0) $msg .= '，跳过' . $skipped . '间空置房';
        return $this->success(['count' => count($insertData), 'skipped' => $skipped], $msg);
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('bill')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }

    public function detail()
    {
        $id = $this->request->param('id', 0);
        $bill = Db::name('bill')->where('id', $id)->find();
        if (!$bill) return $this->error('账单不存在');
        $payments = Db::name('bill_payment')->where('bill_id', $id)->where('delete_time', null)->select();
        $bill['payments'] = $payments;
        return $this->success($bill);
    }

    public function export()
    {
        return $this->success([], '导出功能待完善');
    }
}
