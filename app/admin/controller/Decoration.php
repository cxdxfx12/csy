<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

/**
 * 装修管理 - 后台控制器
 * 流程: 申请→审核→缴费→施工→巡查(循环)→验收→退押金→完成
 */
class Decoration extends BaseAdmin
{
    // ============ 装修申请 ============

    public function applyList()
    {
        [$page, $limit] = $this->getPage();
        $where = [['da.delete_time', 'null', '']];

        $communityId = $this->request->param('community_id', 0);
        if ($communityId) {
            $where[] = ['da.community_id', '=', $communityId];
        } else {
            $filter = $this->getCommunityFilter('da.community_id');
            if (!empty($filter)) $where = array_merge($where, $filter);
        }

        $status = $this->request->param('status', '');
        if ($status !== '') $where[] = ['da.status', '=', intval($status)];

        $keyword = $this->request->param('keyword', '');
        if ($keyword) {
            $where[] = ['da.apply_no|da.leader_name|da.company_name|r.room_number|o.realname', 'like', "%{$keyword}%"];
        }

        $total = Db::name('decoration_apply')->alias('da')
            ->leftJoin('room r', 'r.id = da.room_id')
            ->leftJoin('owner o', 'o.id = da.owner_id')
            ->where($where)->count();

        $list = Db::name('decoration_apply')->alias('da')
            ->leftJoin('room r', 'r.id = da.room_id')
            ->leftJoin('owner o', 'o.id = da.owner_id')
            ->leftJoin('community c', 'c.id = da.community_id')
            ->field('da.*, r.room_number, r.building_name, o.realname as owner_name, o.phone as owner_phone, c.name as community_name')
            ->where($where)->page($page, $limit)->order('da.id', 'desc')->select();

        $statusMap = [0=>'待审核',1=>'待缴费',2=>'施工中',3=>'待验收',4=>'已完成',5=>'已驳回',6=>'已取消'];
        foreach ($list as &$row) {
            $row['status_name'] = $statusMap[$row['status']] ?? '未知';
        }

        return $this->table($list, $total);
    }

    public function applyAdd()
    {
        try {
            $data = $this->request->post();
            $this->validateCommunityAccess($data['community_id'] ?? 0);

            // 校验房间归属：room_id 必须属于选中的 community_id
            if (!empty($data['room_id']) && !empty($data['community_id'])) {
                $room = Db::name('room')->where('id', $data['room_id'])->where('community_id', $data['community_id'])->find();
                if (!$room) {
                    return $this->error('所选房间不属于该小区，请重新选择');
                }
            }

            // 移除前端可能多传的字段（id 由数据库自增生成）
            unset($data['id']);

            $data['apply_no'] = build_order_no('DSZ');
            $data['status'] = 0;
            $data['create_time'] = date('Y-m-d H:i:s');

            // 计算总费用
            $data['deposit_amount'] = floatval($data['deposit_amount'] ?? 0);
            $data['manage_fee'] = floatval($data['manage_fee'] ?? 0);
            $data['trash_fee'] = floatval($data['trash_fee'] ?? 0);
            $data['other_fee'] = floatval($data['other_fee'] ?? 0);
            $data['total_fee'] = $data['deposit_amount'] + $data['manage_fee'] + $data['trash_fee'] + $data['other_fee'];

            // 清洗日期字段：将字符串 'null'/空字符串 转为 SQL NULL
            $dateFields = ['start_date', 'end_date'];
            foreach ($dateFields as $f) {
                if (isset($data[$f]) && ($data[$f] === 'null' || $data[$f] === '' || $data[$f] === null)) {
                    $data[$f] = null;
                }
            }

            // 只保留表中实际存在的字段，避免 extra fields 报错
            $allowedFields = [
                'community_id', 'room_id', 'owner_id', 'apply_no',
                'company_name', 'company_phone', 'leader_name', 'leader_phone',
                'start_date', 'end_date', 'content', 'decoration_type', 'drawing_urls',
                'status', 'deposit_amount', 'manage_fee', 'trash_fee', 'other_fee',
                'total_fee', 'remark', 'create_time',
            ];
            $insertData = [];
            foreach ($allowedFields as $f) {
                if (array_key_exists($f, $data)) {
                    $insertData[$f] = $data[$f];
                }
            }

            $applyId = Db::name('decoration_apply')->insert($insertData);

            // 保存施工人员
            $workers = $this->request->post('workers/a', []);
            if (!empty($workers)) {
                $workerList = [];
                foreach ($workers as $w) {
                    if (empty($w['name'])) continue;
                    $workerList[] = [
                        'apply_id' => $applyId,
                        'name' => $w['name'],
                        'id_card' => $w['id_card'] ?? '',
                        'phone' => $w['phone'] ?? '',
                        'job_type' => $w['job_type'] ?? '',
                        'create_time' => date('Y-m-d H:i:s'),
                    ];
                }
                if (!empty($workerList)) {
                    Db::name('decoration_worker')->insertAll($workerList);
                }
            }

            return $this->success(['id' => $applyId], '提交成功');

        } catch (\Throwable $e) {
            $msg = '操作失败: [' . get_class($e) . '] ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine();
            file_put_contents(__DIR__ . '/../../../runtime/log/deco_debug.log', date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
            return $this->error($msg);
        }
    }

    public function applyEdit()
    {
        $data = $this->request->post();
        $id = $data['id'] ?? 0;
        $record = Db::name('decoration_apply')->where('id', $id)->find();
        if (!$record) return $this->error('记录不存在');
        $this->validateCommunityAccess($data['community_id'] ?? $record['community_id']);

        // 校验房间归属：room_id 必须属于选中的 community_id
        if (!empty($data['room_id']) && !empty($data['community_id'])) {
            $room = Db::name('room')->where('id', $data['room_id'])->where('community_id', $data['community_id'])->find();
            if (!$room) {
                return $this->error('所选房间不属于该小区，请重新选择');
            }
        }

        // 只允许编辑待审核/驳回/取消状态的申请
        if (!in_array($record['status'], [0, 5, 6])) {
            return $this->error('当前状态不允许编辑');
        }

        $data['deposit_amount'] = floatval($data['deposit_amount'] ?? 0);
        $data['manage_fee'] = floatval($data['manage_fee'] ?? 0);
        $data['trash_fee'] = floatval($data['trash_fee'] ?? 0);
        $data['other_fee'] = floatval($data['other_fee'] ?? 0);
        $data['total_fee'] = $data['deposit_amount'] + $data['manage_fee'] + $data['trash_fee'] + $data['other_fee'];
        $data['update_time'] = date('Y-m-d H:i:s');
        unset($data['id']);

        // 清洗日期字段：将字符串 'null'/空字符串 转为 SQL NULL
        foreach (['start_date', 'end_date'] as $f) {
            if (isset($data[$f]) && ($data[$f] === 'null' || $data[$f] === '' || $data[$f] === null)) {
                $data[$f] = null;
            }
        }

        Db::name('decoration_apply')->where('id', $id)->update($data);
        return $this->success([], '修改成功');
    }

    /**
     * 审核(通过/驳回)
     */
    public function applyAudit()
    {
        $id = $this->request->post('id', 0);
        $action = $this->request->post('action', ''); // pass / reject
        $remark = $this->request->post('remark', '');

        $record = Db::name('decoration_apply')->where('id', $id)->find();
        if (!$record) return $this->error('记录不存在');
        $this->validateCommunityAccess($record['community_id']);

        if ($record['status'] != 0) {
            return $this->error('当前状态不可审核');
        }

        $update = ['audit_time' => date('Y-m-d H:i:s'), 'audit_admin_id' => $this->adminId, 'audit_remark' => $remark];

        if ($action === 'pass') {
            $update['status'] = 1; // 审核通过→待缴费
        } elseif ($action === 'reject') {
            if (empty($remark)) return $this->error('驳回必须填写原因');
            $update['status'] = 5; // 驳回
        } else {
            return $this->error('无效操作');
        }

        Db::name('decoration_apply')->where('id', $id)->update($update);
        return $this->success([], $action === 'pass' ? '审核通过' : '已驳回');
    }

    /**
     * 确认缴费
     */
    public function applyCharge()
    {
        $id = $this->request->post('id', 0);
        $paidAmount = floatval($this->request->post('paid_amount', 0));

        $record = Db::name('decoration_apply')->where('id', $id)->find();
        if (!$record) return $this->error('记录不存在');
        $this->validateCommunityAccess($record['community_id']);

        if ($record['status'] != 1) {
            return $this->error('当前状态不可缴费(需审核通过)');
        }

        Db::name('decoration_apply')->where('id', $id)->update([
            'status' => 2,
            'paid_amount' => $paidAmount > 0 ? $paidAmount : $record['total_fee'],
            'paid_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ]);

        // 如果配置了收费项目，自动生成缴费记录
        // 这里记录到财务流水
        if ($paidAmount > 0) {
            Db::name('finance_flow')->insert([
                'flow_no' => build_order_no('DSF'),
                'community_id' => $record['community_id'],
                'type' => 1, // 收入
                'category' => '装修收费',
                'amount' => $paidAmount,
                'balance' => 0,
                'source_type' => 'decoration',
                'source_id' => $id,
                'description' => '装修费用 - ' . $record['apply_no'],
                'operator_id' => $this->adminId,
                'operator_name' => $this->adminInfo['username'] ?? '',
                'status' => 1,
                'create_time' => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->success([], '缴费确认成功，已转为施工中');
    }

    /**
     * 申请验收
     */
    public function applyRequestAccept()
    {
        $id = $this->request->post('id', 0);
        $record = Db::name('decoration_apply')->where('id', $id)->find();
        if (!$record) return $this->error('记录不存在');
        $this->validateCommunityAccess($record['community_id']);

        if ($record['status'] != 2) {
            return $this->error('只有施工中的申请可以提请验收');
        }

        Db::name('decoration_apply')->where('id', $id)->update([
            'status' => 3,
            'update_time' => date('Y-m-d H:i:s'),
        ]);

        return $this->success([], '已提交验收申请');
    }

    /**
     * 竣工验收（只改状态，不处理退款）
     */
    public function applyAccept()
    {
        $id = $this->request->post('id', 0);
        $result = $this->request->post('accept_result', '');
        $isPass = $this->request->post('is_pass', 1);

        $record = Db::name('decoration_apply')->where('id', $id)->find();
        if (!$record) return $this->error('记录不存在');
        $this->validateCommunityAccess($record['community_id']);

        if ($record['status'] != 3) {
            return $this->error('当前状态不可验收');
        }

        // 有违规未处理的不允许验收
        $pendingViolation = Db::name('decoration_violation')
            ->where('apply_id', $id)->where('status', 0)->count();
        if ($pendingViolation > 0) {
            return $this->error("有 {$pendingViolation} 条违规未整改，请先处理");
        }

        $update = [
            'accept_result' => $result,
            'accept_time' => date('Y-m-d H:i:s'),
            'accept_admin_id' => $this->adminId,
            'update_time' => date('Y-m-d H:i:s'),
            'status' => $isPass ? 4 : 2,
        ];

        Db::name('decoration_apply')->where('id', $id)->update($update);

        return $this->success([], $isPass ? '验收通过，请及时处理押金退还' : '验收不通过，已退回施工');
    }

    public function applyCancel()
    {
        $id = $this->request->post('id', 0);
        $remark = $this->request->post('remark', '');

        $record = Db::name('decoration_apply')->where('id', $id)->find();
        if (!$record) return $this->error('记录不存在');
        $this->validateCommunityAccess($record['community_id']);

        if (in_array($record['status'], [4, 6])) {
            return $this->error('已完成或已取消的申请不可再取消');
        }

        Db::name('decoration_apply')->where('id', $id)->update([
            'status' => 6,
            'remark' => $remark,
            'update_time' => date('Y-m-d H:i:s'),
        ]);

        return $this->success([], '已取消');
    }

    /**
     * 退还押金（计算违规扣款后剩余金额）
     */
    public function applyRefund()
    {
        $id = $this->request->post('id', 0);
        $refundMethod = $this->request->post('refund_method', '现金');
        $refundRemark = $this->request->post('refund_remark', '');

        $record = Db::name('decoration_apply')->where('id', $id)->find();
        if (!$record) return $this->error('记录不存在');
        $this->validateCommunityAccess($record['community_id']);

        if ($record['status'] != 4) {
            return $this->error('只有已完成的申请可退押金');
        }
        if (floatval($record['deposit_amount'] ?? 0) <= 0) {
            return $this->error('该申请无押金可退');
        }
        if (floatval($record['refund_amount'] ?? 0) > 0 || !empty($record['refund_time'])) {
            return $this->error('押金已退还，不可重复操作');
        }

        // 违规扣款总额
        $totalPenalty = floatval(Db::name('decoration_violation')
            ->where('apply_id', $id)->where('status', 2)->sum('penalty_amount'));

        // 实际退款 = 押金 - 违规扣款
        $refundAmount = max(0, floatval($record['deposit_amount']) - $totalPenalty);

        $desc = '装修押金退还 - ' . $record['apply_no'];
        if ($totalPenalty > 0) {
            $desc .= '（押金¥' . number_format($record['deposit_amount'], 2) . ' - 违规扣款¥' . number_format($totalPenalty, 2) . '）';
        }
        if ($refundMethod) $desc .= ' [' . $refundMethod . ']';
        if ($refundRemark) $desc .= ' 备注：' . $refundRemark;

        // 记录财务流水 (退款)
        Db::name('finance_flow')->insert([
            'flow_no' => build_order_no('DSF'),
            'community_id' => $record['community_id'],
            'type' => 3, // 退款
            'category' => '装修押金退款',
            'amount' => $refundAmount,
            'balance' => 0,
            'source_type' => 'decoration_refund',
            'source_id' => $id,
            'description' => $desc,
            'operator_id' => $this->adminId,
            'operator_name' => $this->adminInfo['username'] ?? '',
            'status' => 1,
            'create_time' => date('Y-m-d H:i:s'),
        ]);

        // 更新申请记录
        Db::name('decoration_apply')->where('id', $id)->update([
            'refund_amount' => $refundAmount,
            'refund_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ]);

        $msg = '押金 ¥' . number_format($refundAmount, 2) . ' 已退还';
        if ($totalPenalty > 0) $msg .= '（已扣除违规罚金 ¥' . number_format($totalPenalty, 2) . '）';
        return $this->success([], $msg);
    }

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

        // 关联施工人员
        $info['workers'] = Db::name('decoration_worker')->where('apply_id', $id)->whereNull('delete_time')->select();
        // 关联巡查记录
        $info['inspects'] = Db::name('decoration_inspect')->where('apply_id', $id)->whereNull('delete_time')->order('id', 'desc')->limit(20)->select();
        // 关联违规记录
        $info['violations'] = Db::name('decoration_violation')->where('apply_id', $id)->whereNull('delete_time')->order('id', 'desc')->select();

        $statusMap = [0=>'待审核',1=>'待缴费',2=>'施工中',3=>'待验收',4=>'已完成',5=>'已驳回',6=>'已取消'];
        $info['status_name'] = $statusMap[$info['status']] ?? '未知';

        return $this->success($info);
    }

    public function applyDelete()
    {
        $id = $this->request->post('id', 0);
        $record = Db::name('decoration_apply')->where('id', $id)->find();
        if (!$record) return $this->error('记录不存在');
        $this->validateCommunityAccess($record['community_id']);

        Db::name('decoration_apply')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }

    // ============ 施工人员 ============

    public function workerList()
    {
        [$page, $limit] = $this->getPage();

        $applyId = $this->request->param('apply_id', 0);
        $keyword = $this->request->param('keyword', '');

        $where = [['dw.delete_time', 'null', '']];
        if ($applyId) $where[] = ['dw.apply_id', '=', $applyId];
        if ($keyword) $where[] = ['dw.name|dw.phone|dw.id_card|dw.card_no', 'like', "%{$keyword}%"];

        $total = Db::name('decoration_worker')->alias('dw')
            ->leftJoin('decoration_apply da', 'da.id = dw.apply_id')
            ->where($where)->count();

        $list = Db::name('decoration_worker')->alias('dw')
            ->leftJoin('decoration_apply da', 'da.id = dw.apply_id')
            ->leftJoin('room r', 'r.id = da.room_id')
            ->field('dw.*, da.apply_no, da.status as apply_status, r.room_number')
            ->where($where)->page($page, $limit)->order('dw.id', 'desc')->select();

        return $this->table($list, $total);
    }

    public function workerAdd()
    {
        $data = $this->request->post();

        // 验证申请存在
        $apply = Db::name('decoration_apply')->where('id', $data['apply_id'])->find();
        if (!$apply) return $this->error('装修申请不存在');

        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('decoration_worker')->insert($data);
        return $this->success([], '添加成功');
    }

    public function workerEdit()
    {
        $data = $this->request->post();
        $id = $data['id'] ?? 0;
        unset($data['id']);

        $worker = Db::name('decoration_worker')->where('id', $id)->find();
        if (!$worker) return $this->error('记录不存在');

        Db::name('decoration_worker')->where('id', $id)->update($data);
        return $this->success([], '修改成功');
    }

    public function workerDelete()
    {
        $id = $this->request->post('id', 0);
        Db::name('decoration_worker')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }

    public function workerIssueCard()
    {
        $id = $this->request->post('id', 0);
        $cardNo = $this->request->post('card_no', '');
        $expireDate = $this->request->post('card_expire_date', '');

        $worker = Db::name('decoration_worker')->where('id', $id)->find();
        if (!$worker) return $this->error('施工人员不存在');

        // 获取关联申请的竣工日期作为默认有效期
        if (empty($expireDate)) {
            $apply = Db::name('decoration_apply')->where('id', $worker['apply_id'])->find();
            $expireDate = $apply['end_date'] ?? date('Y-m-d', strtotime('+3 months'));
        }

        Db::name('decoration_worker')->where('id', $id)->update([
            'card_no' => $cardNo,
            'card_issue_date' => date('Y-m-d'),
            'card_expire_date' => $expireDate,
        ]);

        return $this->success([], '发证成功');
    }

    // ============ 巡查记录 ============

    public function inspectList()
    {
        [$page, $limit] = $this->getPage();
        $where = [['di.delete_time', 'null', '']];

        $communityId = $this->request->param('community_id', 0);
        if ($communityId) {
            $where[] = ['di.community_id', '=', $communityId];
        } else {
            $filter = $this->getCommunityFilter('di.community_id');
            if (!empty($filter)) $where = array_merge($where, $filter);
        }

        $applyId = $this->request->param('apply_id', 0);
        if ($applyId) $where[] = ['di.apply_id', '=', $applyId];

        $result = $this->request->param('result', '');
        if ($result !== '') $where[] = ['di.result', '=', intval($result)];

        $total = Db::name('decoration_inspect')->alias('di')
            ->leftJoin('decoration_apply da', 'da.id = di.apply_id')
            ->leftJoin('room r', 'r.id = da.room_id')
            ->where($where)->count();

        $list = Db::name('decoration_inspect')->alias('di')
            ->leftJoin('decoration_apply da', 'da.id = di.apply_id')
            ->leftJoin('room r', 'r.id = da.room_id')
            ->leftJoin('community c', 'c.id = di.community_id')
            ->field('di.*, da.apply_no, da.status as apply_status, r.room_number, c.name as community_name')
            ->where($where)->page($page, $limit)->order('di.id', 'desc')->select();

        return $this->table($list, $total);
    }

    public function inspectAdd()
    {
        $data = $this->request->post();

        $apply = Db::name('decoration_apply')->where('id', $data['apply_id'])->find();
        if (!$apply) return $this->error('装修申请不存在');
        if ($apply['status'] != 2) return $this->error('只有施工中的申请可以巡查');

        $data['community_id'] = $apply['community_id'];
        $data['inspector_id'] = $this->adminId;
        $data['inspector_name'] = $this->adminInfo['username'] ?? '';
        $data['inspect_time'] = date('Y-m-d H:i:s');
        $data['create_time'] = date('Y-m-d H:i:s');

        // 移除不属于 decoration_inspect 表的字段
        $autoViolation = intval($data['auto_violation'] ?? 0);
        $violationType = $data['violation_type'] ?? '其他违规';
        unset($data['auto_violation'], $data['violation_type'], $data['violation_desc']);

        Db::name('decoration_inspect')->insert($data);

        // 如果巡查发现异常(result=1)，可快速创建违规记录
        if ($autoViolation && intval($data['result']) === 1) {
            $violationDesc = $data['content'] ?? '巡查发现异常';
            Db::name('decoration_violation')->insert([
                'apply_id' => $data['apply_id'],
                'community_id' => $apply['community_id'],
                'violation_type' => $violationType,
                'description' => $violationDesc,
                'status' => 0,
                'photos' => $data['photos'] ?? '',
                'create_admin_id' => $this->adminId,
                'create_time' => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->success([], '巡查记录已保存');
    }

    public function inspectDelete()
    {
        $id = $this->request->post('id', 0);
        Db::name('decoration_inspect')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }

    // ============ 违规记录 ============

    public function violationList()
    {
        [$page, $limit] = $this->getPage();
        $where = [['dv.delete_time', 'null', '']];

        $communityId = $this->request->param('community_id', 0);
        if ($communityId) {
            $where[] = ['dv.community_id', '=', $communityId];
        } else {
            $filter = $this->getCommunityFilter('dv.community_id');
            if (!empty($filter)) $where = array_merge($where, $filter);
        }

        $applyId = $this->request->param('apply_id', 0);
        if ($applyId) $where[] = ['dv.apply_id', '=', $applyId];

        $status = $this->request->param('status', '');
        if ($status !== '') $where[] = ['dv.status', '=', intval($status)];

        $type = $this->request->param('violation_type', '');
        if ($type) $where[] = ['dv.violation_type', '=', $type];

        $total = Db::name('decoration_violation')->alias('dv')
            ->leftJoin('decoration_apply da', 'da.id = dv.apply_id')
            ->leftJoin('room r', 'r.id = da.room_id')
            ->where($where)->count();

        $list = Db::name('decoration_violation')->alias('dv')
            ->leftJoin('decoration_apply da', 'da.id = dv.apply_id')
            ->leftJoin('room r', 'r.id = da.room_id')
            ->leftJoin('community c', 'c.id = dv.community_id')
            ->field('dv.*, da.apply_no, r.room_number, c.name as community_name')
            ->where($where)->page($page, $limit)->order('dv.id', 'desc')->select();

        $statusNames = [0=>'待整改',1=>'已整改',2=>'已扣款'];
        foreach ($list as &$row) {
            $row['status_name'] = $statusNames[$row['status']] ?? '未知';
        }

        return $this->table($list, $total);
    }

    public function violationAdd()
    {
        $data = $this->request->post();

        $apply = Db::name('decoration_apply')->where('id', $data['apply_id'])->find();
        if (!$apply) return $this->error('装修申请不存在');

        $data['community_id'] = $apply['community_id'];
        $data['create_admin_id'] = $this->adminId;
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['status'] = 0;

        Db::name('decoration_violation')->insert($data);
        return $this->success([], '违规记录已添加');
    }

    public function violationEdit()
    {
        $data = $this->request->post();
        $id = $data['id'] ?? 0;
        unset($data['id']);

        $record = Db::name('decoration_violation')->where('id', $id)->find();
        if (!$record) return $this->error('记录不存在');

        $data['update_time'] = date('Y-m-d H:i:s');
        Db::name('decoration_violation')->where('id', $id)->update($data);
        return $this->success([], '修改成功');
    }

    /**
     * 整改完成
     */
    public function violationRectify()
    {
        $id = $this->request->post('id', 0);
        $rectifyResult = $this->request->post('rectify_result', '');
        $penaltyAmount = floatval($this->request->post('penalty_amount', 0));
        $doPenalty = $this->request->post('do_penalty', 0); // 是否扣款

        $record = Db::name('decoration_violation')->where('id', $id)->find();
        if (!$record) return $this->error('记录不存在');

        $update = [
            'rectify_result' => $rectifyResult,
            'update_time' => date('Y-m-d H:i:s'),
        ];

        if ($doPenalty && $penaltyAmount > 0) {
            $update['status'] = 2; // 已扣款
            $update['penalty_amount'] = $penaltyAmount;

            // 财务流水记录
            $apply = Db::name('decoration_apply')->where('id', $record['apply_id'])->find();
            if ($apply) {
                Db::name('finance_flow')->insert([
                    'flow_no' => build_order_no('DSF'),
                    'community_id' => $apply['community_id'],
                    'type' => 1, // 收入
                    'category' => '装修罚金',
                    'amount' => $penaltyAmount,
                    'balance' => 0,
                    'source_type' => 'decoration_violation',
                    'source_id' => $id,
                    'description' => "装修违规罚金 - {$record['violation_type']} - {$apply['apply_no']}",
                    'operator_id' => $this->adminId,
                    'operator_name' => $this->adminInfo['username'] ?? '',
                    'status' => 1,
                    'create_time' => date('Y-m-d H:i:s'),
                ]);
            }
        } else {
            $update['status'] = 1; // 已整改(不扣款)
        }

        Db::name('decoration_violation')->where('id', $id)->update($update);
        return $this->success([], '处理完成');
    }

    public function violationDelete()
    {
        $id = $this->request->post('id', 0);
        Db::name('decoration_violation')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }

    // ============ 统计接口 ============

    public function statistics()
    {
        $communityId = $this->request->param('community_id', 0);
        $where = [['delete_time', 'null', '']];
        if ($communityId) $where[] = ['community_id', '=', $communityId];

        $stats = [];
        $stats['total'] = Db::name('decoration_apply')->where($where)->count();
        $stats['pending_audit'] = Db::name('decoration_apply')->where($where)->where('status', 0)->count();
        $stats['pending_pay'] = Db::name('decoration_apply')->where($where)->where('status', 1)->count();
        $stats['in_progress'] = Db::name('decoration_apply')->where($where)->where('status', 2)->count();
        $stats['pending_accept'] = Db::name('decoration_apply')->where($where)->where('status', 3)->count();
        $stats['completed'] = Db::name('decoration_apply')->where($where)->where('status', 4)->count();

        // 待整改违规数
        $violationWhere = [['delete_time', 'null', '']];
        if ($communityId) $violationWhere[] = ['community_id', '=', $communityId];
        $stats['pending_violation'] = Db::name('decoration_violation')->where($violationWhere)->where('status', 0)->count();

        // 今日巡查数
        $today = date('Y-m-d');
        $inspectWhere = [['delete_time', 'null', ''], ['create_time', '>=', $today . ' 00:00:00']];
        if ($communityId) $inspectWhere[] = ['community_id', '=', $communityId];
        $stats['today_inspect'] = Db::name('decoration_inspect')->where($inspectWhere)->count();

        return $this->success($stats);
    }
}
