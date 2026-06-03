<?php
namespace app\staff\controller;

use app\staff\BaseStaff;
use think\facade\Db;

class StaffMeter extends BaseStaff
{
    /**
     * 抄表房间列表：按表类型列出小区房间，含上次读数
     * GET /api/staff/meter/list?type=1&page=1&limit=20
     * type: 1水表 2电表 3燃气表
     */
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $type = (int)$this->request->param('type', 1);
        $communityId = $this->staffInfo['community_id'] ?? 0;
        if (!$communityId) return $this->error('未绑定小区');

        // 子查询：该房间+该类型的最近一次读数
        $sub = fn($f) => "(SELECT mr.{$f} FROM ds_meter_reading mr WHERE mr.room_id = r.id AND mr.type = {$type} AND mr.delete_time IS NULL ORDER BY mr.id DESC LIMIT 1)";

        $fields = "r.id, r.room_number, r.building_name, r.unit, r.floor, o.realname as owner_name,"
            . "{$sub('current_reading')} as last_reading,"
            . "{$sub('reading_date')} as last_date,"
            . "{$sub('meter_no')} as meter_no";

        $total = Db::name('room')->alias('r')
            ->where('r.community_id', $communityId)
            ->whereNull('r.delete_time')
            ->where('r.status', 1)
            ->count();

        $list = Db::name('room')->alias('r')
            ->leftJoin('owner o', 'o.id = r.owner_id')
            ->field($fields)
            ->where('r.community_id', $communityId)
            ->whereNull('r.delete_time')
            ->where('r.status', 1)
            ->page($page, $limit)
            ->order('r.building_name', 'asc')
            ->order('LENGTH(r.unit), r.unit', 'asc')
            ->order('LENGTH(r.room_number), r.room_number', 'asc')
            ->select();

        return $this->success(['list' => $list, 'total' => $total]);
    }

    /**
     * 提交抄表：自动查上次读数并计算用量
     * POST /api/staff/meter/read
     * body: { room_id, type, current_reading, reading_date?, photo?, remark? }
     */
    public function read()
    {
        $data   = $this->request->post();
        $roomId = (int)($data['room_id'] ?? 0);
        $type   = (int)($data['type'] ?? 1);
        $current = floatval($data['current_reading'] ?? 0);
        $meterNo = trim($data['meter_no'] ?? '');
        $readingDate = $data['reading_date'] ?? date('Y-m-d');
        $photo  = $data['photo'] ?? '';
        $remark = $data['remark'] ?? '';

        if (!$roomId)          return $this->error('房间ID不能为空');
        if ($current <= 0)     return $this->error('请输入有效读数');

        $communityId = $this->staffInfo['community_id'] ?? 0;

        // 查找该房间+该类型最近一次抄表记录
        $last = Db::name('meter_reading')
            ->where('room_id', $roomId)
            ->where('type', $type)
            ->whereNull('delete_time')
            ->order('id', 'desc')
            ->find();

        $previous = $last ? floatval($last['current_reading']) : 0;
        if (empty($meterNo) && $last) $meterNo = $last['meter_no'];

        $usage = round($current - $previous, 2);
        $isAbnormal = $usage < 0 || ($last && $current < floatval($last['current_reading']));

        Db::name('meter_reading')->insert([
            'community_id'     => $communityId,
            'room_id'          => $roomId,
            'type'             => $type,
            'meter_no'         => $meterNo,
            'previous_reading' => $previous,
            'current_reading'  => $current,
            'usage_amount'     => max(0, $usage),
            'reading_date'     => $readingDate,
            'reading_by'       => $this->staffInfo['realname'] ?? '',
            'operator_id'      => $this->staffId,
            'photo'            => $photo,
            'status'           => $isAbnormal ? 2 : 1,
            'remark'           => $remark,
            'create_time'      => date('Y-m-d H:i:s'),
        ]);

        return $this->success([
            'previous_reading' => $previous,
            'usage_amount'     => max(0, $usage),
        ], '录入成功');
    }

    /**
     * 抄表历史：按类型+社区筛选，可选按房间过滤
     * GET /api/staff/meter/history?type=1&room_id=0
     */
    public function history()
    {
        $type = (int)$this->request->param('type', 1);
        $roomId = (int)$this->request->param('room_id', 0);
        $communityId = $this->staffInfo['community_id'] ?? 0;
        if (!$communityId) return $this->error('未绑定小区');

        $query = Db::name('meter_reading')->alias('m')
            ->leftJoin('room r', 'r.id = m.room_id')
            ->field('m.*, r.room_number, r.building_name')
            ->where('m.community_id', $communityId)
            ->where('m.type', $type)
            ->whereNull('m.delete_time');

        if ($roomId) $query->where('m.room_id', $roomId);

        $list = $query->order('m.id', 'desc')->limit(20)->select();
        return $this->success($list);
    }
}
