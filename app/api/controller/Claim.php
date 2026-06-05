<?php
namespace app\api\controller;

use app\api\BaseApi;
use think\facade\Db;

/**
 * 自助认领房产 —— 微信用户通过手机号认领已登记的业主房产
 */
class Claim extends BaseApi
{
    /**
     * 输入手机号，匹配同小区业主，转移房产绑定到当前微信账号
     * POST /api/claimProperty
     */
    public function claim()
    {
        $phone = request()->param('phone', '');
        if (empty($phone)) {
            return json(['code' => 1, 'msg' => '请输入手机号']);
        }
        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return json(['code' => 1, 'msg' => '手机号格式不正确']);
        }

        $ownerId    = $this->ownerId;                        // 当前微信用户
        $ownerInfo  = $this->ownerInfo;
        $communityId = $ownerInfo['community_id'] ?? 0;

        // 是否已经绑定过房产
        $myRoomCount = Db::name('owner_room')
            ->where('owner_id', $ownerId)
            ->where('delete_time', null)
            ->count();
        if ($myRoomCount > 0) {
            return json(['code' => 1, 'msg' => '您已有绑定房产，无需重复认领']);
        }

        // 当前微信用户的手机号不能和目标一致（占位手机号除外）
        $myPhone = $ownerInfo['phone'] ?? '';
        if ($myPhone === $phone) {
            return json(['code' => 1, 'msg' => '该手机号已是您当前账号']);
        }

        // 查找目标业主（同小区、相同手机号、未删除）
        $target = Db::name('owner')
            ->where('phone', $phone)
            ->where('community_id', $communityId)
            ->where('delete_time', 0)
            ->find();

        if (!$target) {
            return json(['code' => 1, 'msg' => '未找到该手机号对应的业主信息，请联系物业管理人员核实']);
        }

        if ($target['id'] == $ownerId) {
            return json(['code' => 1, 'msg' => '该手机号已是您当前账号']);
        }

        // 目标业主已经绑定了其他微信，不允许抢绑
        if (!empty($target['openid']) && $target['openid'] !== $ownerInfo['openid']) {
            return json(['code' => 1, 'msg' => '该手机号已绑定其他微信账号']);
        }

        // 查询目标业主的房产
        $rooms = Db::name('owner_room')
            ->where('owner_id', $target['id'])
            ->where('delete_time', null)
            ->select();

        if (empty($rooms)) {
            return json(['code' => 1, 'msg' => '该手机号对应的业主暂无登记房产，请联系物业']);
        }

        Db::startTrans();
        try {
            // 将目标业主的房产归属转移到当前微信用户
            foreach ($rooms as $room) {
                // 防止重复绑定同一房间
                $exists = Db::name('owner_room')
                    ->where('owner_id', $ownerId)
                    ->where('room_id', $room['room_id'])
                    ->where('delete_time', null)
                    ->find();
                if (!$exists) {
                    Db::name('owner_room')
                        ->where('id', $room['id'])
                        ->update(['owner_id' => $ownerId]);
                }
                // 同步 ds_room.owner_id
                if (!empty($room['room_id'])) {
                    $roomOwner = Db::name('room')->where('id', $room['room_id'])->value('owner_id');
                    if ($roomOwner == $target['id'] || empty($roomOwner)) {
                        Db::name('room')->where('id', $room['room_id'])->update(['owner_id' => $ownerId]);
                    }
                }
            }

            // 更新当前微信用户为真实手机号和真实姓名
            $updateData = ['phone' => $phone];
            if (!empty($target['realname']) && $target['realname'] !== '微信用户') {
                $updateData['realname'] = $target['realname'];
            }
            Db::name('owner')->where('id', $ownerId)->update($updateData);

            // 将目标业主标记为已归档（避免重复认领）
            Db::name('owner')->where('id', $target['id'])->update([
                'phone'  => 'ARCHIVED_' . $target['id'],
                'openid' => '',
                'status' => 0,
            ]);

            // 转移账单
            Db::name('bill')
                ->where('owner_id', $target['id'])
                ->where('delete_time', null)
                ->update(['owner_id' => $ownerId]);

            // 转移报修
            Db::name('repair_order')
                ->where('owner_id', $target['id'])
                ->where('delete_time', null)
                ->update(['owner_id' => $ownerId]);

            // 转移投诉
            Db::name('complaint')
                ->where('owner_id', $target['id'])
                ->where('delete_time', null)
                ->update(['owner_id' => $ownerId]);

            // 转移车辆
            Db::name('vehicle')
                ->where('owner_id', $target['id'])
                ->where('delete_time', null)
                ->update(['owner_id' => $ownerId]);

            Db::commit();

            return json([
                'code' => 0,
                'msg'  => '认领成功！已绑定 ' . count($rooms) . ' 套房产',
                'data' => ['room_count' => count($rooms), 'realname' => $updateData['realname'] ?? '微信用户'],
            ]);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 1, 'msg' => '系统异常，请稍后重试']);
        }
    }
}
