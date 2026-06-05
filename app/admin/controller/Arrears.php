<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Arrears extends BaseAdmin
{
    /**
     * 欠费列表：按业主汇总（一个业主可能有多套房）
     */
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $cid = $this->getFilteredCommunityId();
        $keyword = trim($this->request->param('keyword', ''));

        $pdo = Db::name('bill')->getPdo();

        // 子查询：每间房的欠费汇总
        $billSubWhere = "WHERE b.delete_time IS NULL AND b.status IN (1,2)";
        if ($cid === -1) {
            $boundStr = implode(',', $this->request->boundCommunityIds);
            $billSubWhere .= " AND b.community_id IN ({$boundStr})";
        } elseif ($cid > 0) {
            $billSubWhere .= " AND b.community_id = " . intval($cid);
        }

        // 按业主汇总欠费（LEFT JOIN 房间聚合结果）
        $sql = "SELECT 
            o.id as owner_id, o.realname as owner_name, o.phone as owner_phone, o.id_card, o.type as owner_type,
            s.community_id, s.community_name,
            COUNT(DISTINCT s.room_id) as room_count,
            GROUP_CONCAT(DISTINCT CONCAT(s.building_name, '-', s.room_number) ORDER BY s.building_name, s.room_number SEPARATOR '、') as room_list,
            SUM(s.bill_count) as bill_count,
            SUM(s.total_amount) as total_amount,
            SUM(s.paid_amount) as paid_amount,
            SUM(s.arrears_amount) as arrears_amount
        FROM ds_owner o
        INNER JOIN ds_owner_room o_r ON o_r.owner_id = o.id AND o_r.is_primary = 1 AND o_r.delete_time IS NULL
        INNER JOIN ds_room r ON r.id = o_r.room_id AND r.delete_time IS NULL
        INNER JOIN (
            SELECT b.room_id,
                r2.community_id,
                c.name as community_name,
                r2.building_name,
                r2.room_number,
                COUNT(*) as bill_count,
                SUM(b.total_amount) as total_amount,
                SUM(b.paid_amount) as paid_amount,
                SUM(b.total_amount - b.paid_amount) as arrears_amount
            FROM ds_bill b
            INNER JOIN ds_room r2 ON r2.id = b.room_id
            LEFT JOIN ds_community c ON c.id = r2.community_id
            $billSubWhere
            GROUP BY b.room_id, r2.community_id, c.name, r2.building_name, r2.room_number
        ) s ON s.room_id = r.id
        WHERE o.delete_time IS NULL";

        $params = [];
        if ($cid === -1) {
            $boundStr = implode(',', $this->request->boundCommunityIds);
            $sql .= " AND o.community_id IN ({$boundStr})";
        } elseif ($cid > 0) {
            $sql .= " AND o.community_id = ?";
            $params[] = $cid;
        }
        if ($keyword) {
            $kw = "%{$keyword}%";
            $sql .= " AND (o.realname LIKE ? OR o.phone LIKE ? OR s.building_name LIKE ? OR s.room_number LIKE ?)";
            $params = array_merge($params, [$kw, $kw, $kw, $kw]);
        }

        // Group + order clause
        $groupClause = " GROUP BY o.id, o.realname, o.phone, o.id_card, o.type, s.community_id, s.community_name";
        // 只显示有实际欠费的业主（arrears_amount > 0），交清后自动消失
        $groupClause .= " HAVING arrears_amount > 0";
        $orderClause = " ORDER BY arrears_amount DESC";

        // Count: wrap grouped result
        $countSql = "SELECT COUNT(*) as total FROM ({$sql}{$groupClause}) tmp";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int)($stmt->fetch(\PDO::FETCH_ASSOC)['total'] ?? 0);

        // Paginate
        $offset = ($page - 1) * $limit;
        $sql .= $groupClause . $orderClause . " LIMIT {$limit} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $list = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // 补充每位业主最近催缴时间
        if (!empty($list)) {
            $ownerIds = array_column($list, 'owner_id');
            if (!empty($ownerIds)) {
                $placeholders = implode(',', array_fill(0, count($ownerIds), '?'));
                $stmt = $pdo->prepare("SELECT owner_id, MAX(create_time) as last_dunning_time FROM ds_bill_dunning WHERE owner_id IN ($placeholders) GROUP BY owner_id");
                $stmt->execute(array_values($ownerIds));
                $dunnings = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $dunningMap = [];
                foreach ($dunnings as $d) {
                    $dunningMap[$d['owner_id']] = $d['last_dunning_time'];
                }
                foreach ($list as &$row) {
                    $row['last_dunning_time'] = $dunningMap[$row['owner_id']] ?? '';
                }
            }
        }

        return $this->table($list, $total);
    }

    /**
     * 催单：按业主催缴其所有欠费房间
     */
    public function dunning()
    {
        $ownerId = intval($this->request->post('owner_id', 0));
        $remark = trim($this->request->post('remark', ''));

        if (!$ownerId) return $this->error('请选择业主');

        $owner = Db::name('owner')->where('id', $ownerId)->find();
        if (!$owner) return $this->error('业主不存在');

        $roomsData = $this->getOwnerArrearsRooms($ownerId);
        if (empty($roomsData)) return $this->error('该业主名下无欠费房间');

        $pdo = Db::name('bill')->getPdo();
        $pdo->beginTransaction();
        try {
            $now = date('Y-m-d H:i:s');
            $allBillDetails = [];
            $grandTotal = 0;
            $grandPaid = 0;
            $totalBillCount = 0;
            $roomNames = [];

            foreach ($roomsData as $rd) {
                $bills = $rd['bills'];
                $amts = $rd['amounts'];
                $grandTotal += $amts['total'];
                $grandPaid += $amts['paid'];
                $totalBillCount += count($bills);
                $roomNames[] = $rd['room']['room_number'];

                // 插入催单记录
                Db::name('bill_dunning')->insert([
                    'community_id'   => $rd['room']['community_id'],
                    'room_id'        => $rd['room']['id'],
                    'owner_id'       => $ownerId,
                    'total_amount'   => $amts['total'],
                    'paid_amount'    => $amts['paid'],
                    'arrears_amount' => $amts['arrears'],
                    'bill_count'     => count($bills),
                    'remark'         => $remark,
                    'admin_id'       => $this->adminId,
                    'create_time'    => $now,
                ]);

                // 更新账单催缴次数
                foreach ($bills as $b) {
                    $stmt = $pdo->prepare("UPDATE ds_bill SET dunning_count = dunning_count + 1, dunning_time = ? WHERE id = ?");
                    $stmt->execute([$now, $b['id']]);
                }

                // 收集账单明细
                foreach ($bills as $b) {
                    $allBillDetails[] = [
                        'bill_no'          => $b['bill_no'],
                        'charge_item_name' => $b['charge_item_name'],
                        'bill_period'      => $b['bill_period'],
                        'room_number'      => $rd['room']['room_number'],
                        'total_amount'     => $b['total_amount'],
                        'paid_amount'      => $b['paid_amount'],
                        'arrears'          => round($b['total_amount'] - $b['paid_amount'], 2),
                        'due_date'         => $b['due_date'],
                    ];
                }
            }

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            return $this->error('催单失败：' . $e->getMessage());
        }

        $arrears = round($grandTotal - $grandPaid, 2);
        return $this->success([
            'owner_name'     => $owner['realname'],
            'owner_phone'    => $owner['phone'] ?? '',
            'room_list'      => implode('、', $roomNames),
            'total_amount'   => $grandTotal,
            'paid_amount'    => $grandPaid,
            'arrears_amount' => $arrears,
            'bill_count'     => $totalBillCount,
            'bill_details'   => $allBillDetails,
        ], '催单成功');
    }

    /**
     * 短信催缴：按业主发送
     */
    public function smsDunning()
    {
        $ownerId = intval($this->request->post('owner_id', 0));
        $remark = trim($this->request->post('remark', ''));

        if (!$ownerId) return $this->error('请选择业主');

        $owner = Db::name('owner')->where('id', $ownerId)->find();
        if (!$owner) return $this->error('业主不存在');
        if (empty($owner['phone'])) return $this->error('该业主未登记手机号，无法发送短信');

        $roomsData = $this->getOwnerArrearsRooms($ownerId);
        if (empty($roomsData)) return $this->error('该业主名下无欠费房间');

        // 取第一个房间的小区配置
        $firstRoom = $roomsData[0]['room'];
        $community = Db::name('community')->where('id', $firstRoom['community_id'])->find();
        if (empty($community['sms_key'])) {
            return $this->error('该小区未配置短信接口KEY，请先在【短信配置】中设置');
        }

        // 汇总数据
        [$grandTotal, $grandPaid, $totalBillCount, $roomNames] = $this->aggregateRoomsData($roomsData);
        $arrears = round($grandTotal - $grandPaid, 2);

        $smsContent = sprintf(
            '【%s】尊敬的%s，您在%s共有%d笔欠费共¥%.2f，请尽快缴费。详询物业。',
            $community['name'],
            $owner['realname'] ?: '业主',
            implode('、', $roomNames),
            $totalBillCount,
            $arrears
        );

        $this->recordOwnerDunning($roomsData, $ownerId, $remark, 'sms');

        return $this->success([
            'channel'        => 'sms',
            'phone'          => substr($owner['phone'], 0, 3) . '****' . substr($owner['phone'], -4),
            'content'        => $smsContent,
            'owner_name'     => $owner['realname'],
            'room_list'      => implode('、', $roomNames),
            'bill_count'     => $totalBillCount,
            'arrears_amount' => $arrears,
        ], '短信催缴已发送');
    }

    /**
     * 公众号模板消息催缴：按业主发送
     */
    public function wechatDunning()
    {
        $ownerId = intval($this->request->post('owner_id', 0));
        $remark = trim($this->request->post('remark', ''));

        if (!$ownerId) return $this->error('请选择业主');

        $owner = Db::name('owner')->where('id', $ownerId)->find();
        if (!$owner) return $this->error('业主不存在');
        if (empty($owner['openid'])) return $this->error('该业主未绑定公众号，无法推送模板消息');

        $roomsData = $this->getOwnerArrearsRooms($ownerId);
        if (empty($roomsData)) return $this->error('该业主名下无欠费房间');

        $firstRoom = $roomsData[0]['room'];
        $wxConfig = Db::name('community_wechat_config')
            ->where('community_id', $firstRoom['community_id'])
            ->where('status', 1)
            ->find();
        if (!$wxConfig || empty($wxConfig['app_id'])) {
            return $this->error('该小区未配置公众号，请先在【公众号配置】中设置');
        }
        if (empty($wxConfig['template_arrears'])) {
            return $this->error('该小区公众号未配置催缴通知模板ID，请先在【公众号配置】中设置');
        }

        [$grandTotal, $grandPaid, $totalBillCount, $roomNames] = $this->aggregateRoomsData($roomsData);
        $arrears = round($grandTotal - $grandPaid, 2);

        $this->recordOwnerDunning($roomsData, $ownerId, $remark, 'wechat');

        return $this->success([
            'channel'        => 'wechat',
            'template_id'    => $wxConfig['template_arrears'],
            'openid'         => substr($owner['openid'], 0, 6) . '****',
            'owner_name'     => $owner['realname'],
            'room_list'      => implode('、', $roomNames),
            'bill_count'     => $totalBillCount,
            'arrears_amount' => $arrears,
        ], '公众号模板消息已推送');
    }

    /**
     * 催单历史：按业主查询
     */
    public function history()
    {
        $ownerId = intval($this->request->param('owner_id', 0));
        if (!$ownerId) return $this->error('请选择业主');

        $pdo = Db::name('bill_dunning')->getPdo();
        $stmt = $pdo->prepare("
            SELECT d.*, r.room_number, r.building_name, a.nickname as admin_name 
            FROM ds_bill_dunning d 
            LEFT JOIN ds_room r ON r.id = d.room_id 
            LEFT JOIN ds_admin_user a ON a.id = d.admin_id 
            WHERE d.owner_id = ? 
            ORDER BY d.id DESC
        ");
        $stmt->execute([$ownerId]);
        $list = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->success($list);
    }

    // ==================== 私有方法 ====================

    /**
     * 获取某业主名下所有欠费房间及其账单
     */
    private function getOwnerArrearsRooms($ownerId)
    {
        // 找到该业主作为主业主的所有房间
        $ownerRooms = Db::name('owner_room')
            ->where('owner_id', $ownerId)
            ->where('is_primary', 1)
            ->where('delete_time', null)
            ->select();

        if (empty($ownerRooms)) return [];

        $result = [];
        foreach ($ownerRooms as $or) {
            $roomId = $or['room_id'];
            $room = Db::name('room')->where('id', $roomId)->find();
            if (!$room) continue;

            $bills = Db::name('bill')
                ->where('room_id', $roomId)
                ->where('delete_time', null)
                ->whereIn('status', [1, 2])
                ->select();

            if (empty($bills)) continue; // 该房间无欠费

            $total = 0;
            $paid = 0;
            foreach ($bills as $b) {
                $total += $b['total_amount'];
                $paid += $b['paid_amount'];
            }

            $result[] = [
                'room'    => $room,
                'bills'   => $bills,
                'amounts' => [
                    'total'   => $total,
                    'paid'    => $paid,
                    'arrears' => round($total - $paid, 2),
                ],
            ];
        }

        return $result;
    }

    /**
     * 汇总业主所有房间的金额和账单数
     */
    private function aggregateRoomsData($roomsData)
    {
        $grandTotal = 0;
        $grandPaid = 0;
        $totalBillCount = 0;
        $roomNames = [];
        foreach ($roomsData as $rd) {
            $grandTotal += $rd['amounts']['total'];
            $grandPaid += $rd['amounts']['paid'];
            $totalBillCount += count($rd['bills']);
            $roomNames[] = $rd['room']['room_number'];
        }
        return [$grandTotal, $grandPaid, $totalBillCount, $roomNames];
    }

    /**
     * 按业主记录催缴（事务：insert dunning + update bills）
     */
    private function recordOwnerDunning($roomsData, $ownerId, $remark, $channel)
    {
        $pdo = Db::name('bill')->getPdo();
        $pdo->beginTransaction();
        try {
            $now = date('Y-m-d H:i:s');
            foreach ($roomsData as $rd) {
                $bills = $rd['bills'];
                $amts = $rd['amounts'];
                $room = $rd['room'];

                Db::name('bill_dunning')->insert([
                    'community_id'   => $room['community_id'],
                    'room_id'        => $room['id'],
                    'owner_id'       => $ownerId,
                    'total_amount'   => $amts['total'],
                    'paid_amount'    => $amts['paid'],
                    'arrears_amount' => $amts['arrears'],
                    'bill_count'     => count($bills),
                    'remark'         => $remark,
                    'channel'        => $channel,
                    'admin_id'       => $this->adminId,
                    'create_time'    => $now,
                ]);

                foreach ($bills as $b) {
                    $stmt = $pdo->prepare("UPDATE ds_bill SET dunning_count = dunning_count + 1, dunning_time = ? WHERE id = ?");
                    $stmt->execute([$now, $b['id']]);
                }
            }
            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollback();
            throw $e;
        }
    }
}
