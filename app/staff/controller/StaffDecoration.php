<?php
namespace app\staff\controller;

use app\BaseController;
use think\facade\Db;

/**
 * 装修管理 - 物业员工移动端
 */
class StaffDecoration extends BaseController
{
    /**
     * 我负责的装修申请列表
     */
    public function applyList()
    {
        $staffInfo = $this->request->staffInfo ?? [];
        $communityIds = $staffInfo['community_ids'] ?? '';
        $communityIds = $communityIds ? array_filter(array_map('intval', explode(',', $communityIds))) : [];

        $where = [['da.delete_time', 'null', '']];
        if (!empty($communityIds)) {
            $where[] = ['da.community_id', 'in', $communityIds];
        }

        $status = $this->request->param('status', '');
        if ($status !== '') $where[] = ['da.status', '=', intval($status)];

        $keyword = $this->request->param('keyword', '');
        if ($keyword) {
            $where[] = ['da.apply_no|r.room_number|o.realname', 'like', "%{$keyword}%"];
        }

        [$page, $limit] = [intval($this->request->param('page', 1)), intval($this->request->param('limit', 15))];

        $total = Db::name('decoration_apply')->alias('da')
            ->leftJoin('room r', 'r.id = da.room_id')
            ->leftJoin('owner o', 'o.id = da.owner_id')
            ->where($where)->count();

        $list = Db::name('decoration_apply')->alias('da')
            ->leftJoin('room r', 'r.id = da.room_id')
            ->leftJoin('owner o', 'o.id = da.owner_id')
            ->leftJoin('community c', 'c.id = da.community_id')
            ->field('da.id, da.apply_no, da.status, da.start_date, da.end_date, da.company_name, da.leader_name, da.leader_phone, r.room_number, o.realname as owner_name, c.name as community_name')
            ->where($where)->page($page, $limit)->order('da.id', 'desc')->select();

        $statusMap = [0=>'待审核',1=>'待缴费',2=>'施工中',3=>'待验收',4=>'已完成',5=>'已驳回',6=>'已取消'];
        foreach ($list as &$row) {
            $row['status_name'] = $statusMap[$row['status']] ?? '未知';
        }

        return $this->table($list, $total);
    }

    /**
     * 装修申请详情
     */
    public function applyDetail()
    {
        $id = $this->request->param('id', 0);
        $info = Db::name('decoration_apply')->alias('da')
            ->leftJoin('room r', 'r.id = da.room_id')
            ->leftJoin('owner o', 'o.id = da.owner_id')
            ->leftJoin('community c', 'c.id = da.community_id')
            ->field('da.*, r.room_number, r.building_name, r.area as room_area, o.realname as owner_name, o.phone as owner_phone, c.name as community_name')
            ->where('da.id', $id)->find();

        if (!$info) return $this->error('记录不存在');

        $statusMap = [0=>'待审核',1=>'待缴费',2=>'施工中',3=>'待验收',4=>'已完成',5=>'已驳回',6=>'已取消'];
        $info['status_name'] = $statusMap[$info['status']] ?? '未知';

        // 关联施工人员
        $info['workers'] = Db::name('decoration_worker')->where('apply_id', $id)->whereNull('delete_time')->select();
        // 最近巡查
        $info['recent_inspects'] = Db::name('decoration_inspect')->where('apply_id', $id)->whereNull('delete_time')->order('id', 'desc')->limit(10)->select();
        // 违规记录
        $info['violations'] = Db::name('decoration_violation')->where('apply_id', $id)->whereNull('delete_time')->order('id', 'desc')->select();

        return $this->success($info);
    }

    // ============ 巡查（移动端） ============

    /**
     * 今日待巡查列表
     */
    public function inspectTodoList()
    {
        $staffInfo = $this->request->staffInfo ?? [];
        $communityIds = $staffInfo['community_ids'] ?? '';
        $communityIds = $communityIds ? array_filter(array_map('intval', explode(',', $communityIds))) : [];

        $where = [
            ['da.delete_time', 'null', ''],
            ['da.status', '=', 2], // 只查施工中的
        ];
        if (!empty($communityIds)) {
            $where[] = ['da.community_id', 'in', $communityIds];
        }

        // 取今天还未巡查过的
        $today = date('Y-m-d');
        $list = Db::name('decoration_apply')->alias('da')
            ->leftJoin('room r', 'r.id = da.room_id')
            ->leftJoin('community c', 'c.id = da.community_id')
            ->field('da.id, da.apply_no, da.leader_name, da.leader_phone, r.room_number, c.name as community_name')
            ->where($where)
            ->order('da.id', 'asc')
            ->select();

        // 标记今天是否已巡查
        foreach ($list as &$row) {
            $todayCount = Db::name('decoration_inspect')
                ->where('apply_id', $row['id'])
                ->where('create_time', '>=', $today . ' 00:00:00')
                ->count();
            $row['today_inspected'] = $todayCount > 0;
            $row['today_inspect_count'] = $todayCount;
        }

        return $this->success($list);
    }

    /**
     * 提交巡查记录
     */
    public function inspectSubmit()
    {
        $staffInfo = $this->request->staffInfo ?? [];
        $data = $this->request->post();

        $applyId = $data['apply_id'] ?? 0;
        $apply = Db::name('decoration_apply')->where('id', $applyId)->find();
        if (!$apply) return $this->error('装修申请不存在');
        if ($apply['status'] != 2) return $this->error('只有施工中的可以巡查');

        $record = [
            'apply_id' => $applyId,
            'community_id' => $apply['community_id'],
            'inspector_id' => $staffInfo['id'] ?? 0,
            'inspector_name' => $staffInfo['realname'] ?? $staffInfo['username'] ?? '',
            'inspect_time' => date('Y-m-d H:i:s'),
            'result' => intval($data['result'] ?? 0),
            'content' => $data['content'] ?? '',
            'photos' => $data['photos'] ?? '',
            'create_time' => date('Y-m-d H:i:s'),
        ];

        Db::name('decoration_inspect')->insert($record);

        return $this->success([], '巡查记录已提交');
    }

    /**
     * 我的巡查历史
     */
    public function inspectMyHistory()
    {
        $staffInfo = $this->request->staffInfo ?? [];
        $inspectorId = $staffInfo['id'] ?? 0;
        [$page, $limit] = [intval($this->request->param('page', 1)), intval($this->request->param('limit', 15))];

        $where = [
            ['di.delete_time', 'null', ''],
            ['di.inspector_id', '=', $inspectorId],
        ];

        $total = Db::name('decoration_inspect')->alias('di')
            ->leftJoin('decoration_apply da', 'da.id = di.apply_id')
            ->leftJoin('room r', 'r.id = da.room_id')
            ->where($where)->count();

        $list = Db::name('decoration_inspect')->alias('di')
            ->leftJoin('decoration_apply da', 'da.id = di.apply_id')
            ->leftJoin('room r', 'r.id = da.room_id')
            ->field('di.*, da.apply_no, r.room_number')
            ->where($where)->page($page, $limit)->order('di.id', 'desc')->select();

        return $this->table($list, $total);
    }

    /**
     * 上报违规
     */
    public function violationReport()
    {
        $staffInfo = $this->request->staffInfo ?? [];
        $data = $this->request->post();

        $applyId = $data['apply_id'] ?? 0;
        $apply = Db::name('decoration_apply')->where('id', $applyId)->find();
        if (!$apply) return $this->error('装修申请不存在');

        $record = [
            'apply_id' => $applyId,
            'community_id' => $apply['community_id'],
            'violation_type' => $data['violation_type'] ?? '',
            'description' => $data['description'] ?? '',
            'status' => 0,
            'photos' => $data['photos'] ?? '',
            'create_admin_id' => $staffInfo['id'] ?? 0,
            'create_time' => date('Y-m-d H:i:s'),
        ];

        if (empty($record['description'])) return $this->error('违规描述不能为空');

        Db::name('decoration_violation')->insert($record);
        return $this->success([], '违规已上报');
    }
}
