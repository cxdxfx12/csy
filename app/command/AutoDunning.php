<?php
namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class AutoDunning extends Command
{
    protected function configure()
    {
        $this->setName('auto:dunning')
            ->setDescription('超期6天自动催缴');
    }

    protected function execute(Input $input, Output $output)
    {
        $cutoff = date('Y-m-d', strtotime('-6 days'));
        $output->info("[催缴] 截止日期 <= {$cutoff}");

        $pdo = Db::connect()->getPdo();

        $stmt = $pdo->prepare("
            SELECT b.owner_id, b.room_id, b.id AS bill_id,
                   o.realname AS owner_name, o.phone AS owner_phone,
                   r.community_id, r.room_number, r.building_name,
                   c.name AS community_name
            FROM ds_bill b
            INNER JOIN ds_room r ON r.id = b.room_id AND r.delete_time IS NULL
            LEFT JOIN ds_owner o ON o.id = b.owner_id AND o.delete_time IS NULL
            LEFT JOIN ds_community c ON c.id = r.community_id
            WHERE b.delete_time IS NULL
              AND b.status IN (1, 2)
              AND b.due_date IS NOT NULL
              AND b.due_date <= ?
              AND (
                  b.dunning_time IS NULL
                  OR b.dunning_time <= ?
              )
            ORDER BY b.owner_id, b.room_id
        ");
        $stmt->execute([$cutoff, $cutoff]);
        $overdueBills = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($overdueBills)) {
            $output->info("[催缴] 无超期欠费，跳过");
            return;
        }

        // 按 owner_id 分组
        $ownerBills = [];
        foreach ($overdueBills as $b) {
            $oid = $b['owner_id'] ?: 0;
            if (!isset($ownerBills[$oid])) {
                $ownerBills[$oid] = [
                    'name'  => $b['owner_name'] ?: '未知业主',
                    'rooms' => [],
                ];
            }
            $rid = $b['room_id'];
            if (!isset($ownerBills[$oid]['rooms'][$rid])) {
                $ownerBills[$oid]['rooms'][$rid] = [
                    'community_id'   => $b['community_id'],
                    'community_name' => $b['community_name'],
                    'room_number'    => $b['room_number'],
                    'building_name'  => $b['building_name'],
                    'bill_ids'       => [],
                ];
            }
            $ownerBills[$oid]['rooms'][$rid]['bill_ids'][] = $b['bill_id'];
        }

        $totalOwners = count($ownerBills);
        $totalDunning = 0;
        $now = date('Y-m-d H:i:s');

        $output->info("[催缴] 涉及 {$totalOwners} 位业主");

        foreach ($ownerBills as $oid => $oData) {
            $rooms = $oData['rooms'];
            Db::startTrans();
            try {
                foreach ($rooms as $rid => $rData) {
                    if (empty($rData['bill_ids'])) continue;

                    $bills = Db::name('bill')
                        ->whereIn('id', $rData['bill_ids'])
                        ->where('delete_time', null)
                        ->select();
                    if ($bills->isEmpty()) continue;

                    $total = 0; $paid = 0;
                    foreach ($bills as $bill) {
                        $total += $bill['total_amount'];
                        $paid += $bill['paid_amount'];
                    }
                    $arrears = round($total - $paid, 2);
                    if ($arrears <= 0) continue;

                    Db::name('bill_dunning')->insert([
                        'community_id'   => $rData['community_id'],
                        'room_id'        => $rid,
                        'owner_id'       => $oid,
                        'total_amount'   => $total,
                        'paid_amount'    => $paid,
                        'arrears_amount' => $arrears,
                        'bill_count'     => count($bills),
                        'remark'         => '系统自动催缴（超期6天）',
                        'channel'        => 'auto',
                        'admin_id'       => 0,
                        'create_time'    => $now,
                    ]);

                    $pdo2 = Db::connect()->getPdo();
                    $phs = implode(',', array_fill(0, count($rData['bill_ids']), '?'));
                    $stmt2 = $pdo2->prepare("UPDATE ds_bill SET dunning_count = dunning_count + 1, dunning_time = ? WHERE id IN ({$phs})");
                    $stmt2->execute(array_merge([$now], $rData['bill_ids']));

                    $totalDunning++;
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $output->error("[催缴] 失败: {$oData['name']} - {$e->getMessage()}");
            }
        }

        $output->info("[催缴] 完成：{$totalOwners} 位业主，{$totalDunning} 笔记录");
    }
}
