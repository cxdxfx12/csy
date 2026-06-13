<?php
namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class AutoBill extends Command
{
    protected function configure()
    {
        $this->setName('auto:bill')
            ->setDescription('自动生成月度帐单');
    }

    protected function execute(Input $input, Output $output)
    {
        $period = date('Y-m');
        $output->info("[帐单] 目标账期: {$period}");

        $items = Db::name('charge_item')
            ->where('status', 1)
            ->where('type', 1)
            ->where('delete_time', null)
            ->select();

        if ($items->isEmpty()) {
            $output->info("[帐单] 无周期性收费项目，跳过");
            return;
        }

        $totalGen = 0;
        $totalSkip = 0;

        foreach ($items as $item) {
            $output->info("[帐单] 项目: {$item['name']} (id={$item['id']})");

            $communityIds = [];
            if (!empty($item['community_id']) && $item['community_id'] > 0) {
                $communityIds = [$item['community_id']];
            } else {
                $communityIds = Db::name('community')->where('delete_time', null)->column('id');
            }

            foreach ($communityIds as $cid) {
                $r = $this->generateBillsForItem($item, $cid, $period);
                $totalGen += $r['generated'];
                $totalSkip += $r['skipped'];
                if ($r['generated'] > 0 || $r['skipped'] > 0) {
                    $name = Db::name('community')->where('id', $cid)->value('name') ?: "小区#{$cid}";
                    $output->info("  ├─ {$name}: 生成{$r['generated']}条, 跳过{$r['skipped']}间(空置), 已有{$r['existed']}条");
                }
            }
        }

        $output->info("[帐单] 完成：生成 {$totalGen} 条，跳过 {$totalSkip} 间空置房");
    }

    private function generateBillsForItem(array $item, int $cid, string $period): array
    {
        $allRooms = Db::name('room')
            ->where('community_id', $cid)
            ->where('delete_time', null)
            ->select();

        $rooms = [];
        $skipped = 0;
        foreach ($allRooms as $r) {
            if (in_array($r['status'], [2, 3])) {
                $rooms[] = $r;
            } else {
                $skipped++;
            }
        }

        $insertData = [];
        $existed = 0;

        foreach ($rooms as $room) {
            $exists = Db::name('bill')->where([
                'room_id'        => $room['id'],
                'charge_item_id' => $item['id'],
                'bill_period'    => $period,
            ])->find();
            if ($exists) { $existed++; continue; }

            $ownerRoom = Db::name('owner_room')
                ->where('room_id', $room['id'])
                ->where('delete_time', null)
                ->order('is_primary', 'desc')
                ->find();
            $ownerId = $ownerRoom['owner_id'] ?? 0;

            $amount = 0;
            $mode = $item['billing_mode'] ?? 5;
            if ($mode == 1) {
                $amount = $room['area'] * $item['unit_price'];
            } else {
                $amount = $item['unit_price'];
            }
            $amount = round($amount, 2);

            $insertData[] = [
                'bill_no'          => build_order_no('DSB'),
                'community_id'     => $cid,
                'owner_id'         => $ownerId,
                'room_id'          => $room['id'],
                'charge_item_id'   => $item['id'],
                'charge_item_name' => $item['name'],
                'bill_period'      => $period,
                'bill_year'        => substr($period, 0, 4),
                'bill_month'       => substr($period, 5, 2),
                'amount'           => $amount,
                'paid_amount'      => 0,
                'total_amount'     => $amount,
                'status'           => 1,
                'due_date'         => date('Y-m-d', strtotime('+30 days')),
                'create_time'      => date('Y-m-d H:i:s'),
            ];
        }

        if (!empty($insertData)) {
            Db::name('bill')->insertAll($insertData);
        }

        return [
            'generated' => count($insertData),
            'skipped'   => $skipped,
            'existed'   => $existed,
        ];
    }
}
