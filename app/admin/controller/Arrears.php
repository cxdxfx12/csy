<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Arrears extends BaseAdmin
{
    /**
     * 获取PDO连接
     */
    protected function getPdo()
    {
        static $pdo = null;
        if ($pdo === null) {
            $pdo = new \PDO('mysql:host=127.0.0.1;port=3306;dbname=dasheng;charset=utf8mb4', 'root', 'cxdxfx12', [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
        }
        return $pdo;
    }

    /**
     * 欠费列表：按房间汇总
     */
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $communityId = intval($this->request->param('community_id', 0));
        $keyword = trim($this->request->param('keyword', ''));

        $pdo = $this->getPdo();
        $params = [];

        // 子查询条件
        $whereClause = "WHERE b.delete_time IS NULL AND b.status IN (1,2)";
        if ($communityId) $whereClause .= " AND b.community_id = " . $communityId;

        $sql = "SELECT 
            r.id as room_id, r.room_number, r.building_name, r.area,
            c.id as community_id, c.name as community_name,
            o.id as owner_id, o.realname as owner_name, o.phone as owner_phone,
            s.bill_count, s.total_amount, s.paid_amount, s.arrears_amount
        FROM ds_room r
        INNER JOIN (
            SELECT room_id, 
                COUNT(*) as bill_count,
                SUM(total_amount) as total_amount,
                SUM(paid_amount) as paid_amount,
                SUM(total_amount - paid_amount) as arrears_amount
            FROM ds_bill b
            $whereClause
            GROUP BY room_id
        ) s ON s.room_id = r.id
        LEFT JOIN ds_community c ON c.id = r.community_id
        LEFT JOIN ds_owner_room o_r ON o_r.room_id = r.id AND o_r.is_primary = 1 AND o_r.delete_time IS NULL
        LEFT JOIN ds_owner o ON o.id = o_r.owner_id AND o.delete_time IS NULL
        WHERE r.delete_time IS NULL";

        if ($keyword) {
            $kw = "%{$keyword}%";
            $sql .= " AND (r.room_number LIKE ? OR r.building_name LIKE ? OR o.realname LIKE ? OR o.phone LIKE ?)";
            $params = array_merge($params, [$kw, $kw, $kw, $kw]);
        }

        // Count
        $countSql = "SELECT COUNT(*) as total FROM ($sql) tmp";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int)($stmt->fetch()['total'] ?? 0);

        // Paginate
        $offset = ($page - 1) * $limit;
        $sql .= " ORDER BY s.arrears_amount DESC LIMIT {$limit} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $list = $stmt->fetchAll();

        // 补充每间房最近催单时间
        if (!empty($list)) {
            $roomIds = array_column($list, 'room_id');
            if (!empty($roomIds)) {
                $placeholders = implode(',', array_fill(0, count($roomIds), '?'));
                $stmt = $pdo->prepare("SELECT room_id, MAX(create_time) as last_dunning_time FROM ds_bill_dunning WHERE room_id IN ($placeholders) GROUP BY room_id");
                $stmt->execute(array_values($roomIds));
                $dunnings = $stmt->fetchAll();
                $dunningMap = [];
                foreach ($dunnings as $d) {
                    $dunningMap[$d['room_id']] = $d['last_dunning_time'];
                }
                foreach ($list as &$row) {
                    $row['last_dunning_time'] = $dunningMap[$row['room_id']] ?? '';
                }
            }
        }

        return $this->table($list, $total);
    }

    /**
     * 催单：记录催单，更新账单催单信息
     */
    public function dunning()
    {
        $roomId = intval($this->request->post('room_id', 0));
        $remark = trim($this->request->post('remark', ''));

        if (!$roomId) return $this->error('请选择房间');

        $room = Db::name('room')->where('id', $roomId)->find();
        if (!$room) return $this->error('房间不存在');

        // 查询该房间所有未缴/部分缴纳账单
        $bills = Db::name('bill')
            ->where('room_id', $roomId)
            ->where('delete_time', null)
            ->whereIn('status', [1, 2])
            ->select();

        if (empty($bills)) return $this->error('该房间无欠费账单');

        $totalAmount = 0;
        $paidAmount = 0;
        foreach ($bills as $b) {
            $totalAmount += $b['total_amount'];
            $paidAmount += $b['paid_amount'];
        }
        $arrears = round($totalAmount - $paidAmount, 2);

        // 找业主
        $ownerRoom = Db::name('owner_room')
            ->where('room_id', $roomId)
            ->where('is_primary', 1)
            ->where('delete_time', null)
            ->find();
        $ownerId = $ownerRoom['owner_id'] ?? 0;

        // 事务
        $pdo = $this->getPdo();
        $pdo->beginTransaction();
        try {
            // 插入催单记录
            Db::name('bill_dunning')->insert([
                'community_id'  => $room['community_id'],
                'room_id'       => $roomId,
                'owner_id'      => $ownerId,
                'total_amount'  => $totalAmount,
                'paid_amount'   => $paidAmount,
                'arrears_amount'=> $arrears,
                'bill_count'    => count($bills),
                'remark'        => $remark,
                'admin_id'      => $this->adminId,
                'create_time'   => date('Y-m-d H:i:s'),
            ]);

            // 更新账单催单次数和时间
            $now = date('Y-m-d H:i:s');
            foreach ($bills as $b) {
                $stmt = $pdo->prepare("UPDATE ds_bill SET dunning_count = dunning_count + 1, dunning_time = ? WHERE id = ?");
                $stmt->execute([$now, $b['id']]);
            }

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            return $this->error('催单失败：' . $e->getMessage());
        }

        // 组装详情
        $owner = $ownerId ? Db::name('owner')->where('id', $ownerId)->find() : null;
        return $this->success([
            'room_number'   => $room['room_number'],
            'owner_name'    => $owner['realname'] ?? '未知',
            'owner_phone'   => $owner['phone'] ?? '',
            'total_amount'  => $totalAmount,
            'paid_amount'   => $paidAmount,
            'arrears_amount'=> $arrears,
            'bill_count'    => count($bills),
            'bill_details'  => array_map(function ($b) {
                return [
                    'bill_no'           => $b['bill_no'],
                    'charge_item_name'  => $b['charge_item_name'],
                    'bill_period'       => $b['bill_period'],
                    'total_amount'      => $b['total_amount'],
                    'paid_amount'       => $b['paid_amount'],
                    'arrears'           => round($b['total_amount'] - $b['paid_amount'], 2),
                    'due_date'          => $b['due_date'],
                ];
            }, $bills),
        ], '催单成功');
    }

    /**
     * 催单历史
     */
    public function history()
    {
        $roomId = intval($this->request->param('room_id', 0));
        if (!$roomId) return $this->error('请选择房间');

        $pdo = $this->getPdo();
        $stmt = $pdo->prepare("SELECT d.*, a.nickname as admin_name FROM ds_bill_dunning d LEFT JOIN ds_admin_user a ON a.id = d.admin_id WHERE d.room_id = ? ORDER BY d.id DESC");
        $stmt->execute([$roomId]);
        $list = $stmt->fetchAll();

        return $this->success($list);
    }
}
