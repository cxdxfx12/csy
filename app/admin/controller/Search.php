<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

/**
 * 全局搜索 - 大圣导航助手
 */
class Search extends BaseAdmin
{
    /**
     * 全局搜索 - 同时搜索菜单+数据
     */
    public function global()
    {
        $keyword = trim($this->request->param('keyword', ''));
        if (empty($keyword)) {
            return $this->success(['results' => []]);
        }

        $results = [];
        $cid = $this->getFilteredCommunityId();
        $boundIds = $this->request->boundCommunityIds ?? [];

        // ========== 1. 搜索业主 ==========
        $this->searchOwner($keyword, $cid, $boundIds, $results);

        // ========== 2. 搜索房间 ==========
        $this->searchRoom($keyword, $cid, $boundIds, $results);

        // ========== 3. 搜索楼栋 ==========
        $this->searchBuilding($keyword, $cid, $boundIds, $results);

        // ========== 4. 搜索小区 ==========
        $this->searchCommunity($keyword, $results);

        // ========== 5. 搜索报修工单 ==========
        $this->searchRepairOrder($keyword, $cid, $boundIds, $results);

        // ========== 6. 搜索账单 ==========
        $this->searchBill($keyword, $cid, $boundIds, $results);

        // ========== 7. 搜索车辆 ==========
        $this->searchVehicle($keyword, $cid, $boundIds, $results);

        // ========== 8. 搜索员工 ==========
        $this->searchStaff($keyword, $results);

        // ========== 9. 搜索公告 ==========
        $this->searchNotice($keyword, $cid, $boundIds, $results);

        // ========== 10. 搜索供应商 ==========
        $this->searchSupplier($keyword, $results);

        // ========== 11. 搜索缴费记录 ==========
        $this->searchPayment($keyword, $cid, $boundIds, $results);

        // ========== 12. 搜索门禁卡 ==========
        $this->searchAccessCard($keyword, $cid, $boundIds, $results);

        // 按相关度排序（业主/房间优先）
        usort($results, function ($a, $b) {
            $order = ['owner' => 1, 'room' => 2, 'building' => 3, 'community' => 4,
                      'repair_order' => 5, 'bill' => 6, 'payment' => 7,
                      'vehicle' => 8, 'staff' => 9, 'notice' => 10,
                      'supplier' => 11, 'access_card' => 12];
            return ($order[$a['type']] ?? 99) <=> ($order[$b['type']] ?? 99);
        });

        // 限制总结果数
        $results = array_slice($results, 0, 50);

        return $this->success(['results' => $results, 'total' => count($results)]);
    }

    // ========== 业主搜索 ==========
    private function searchOwner($keyword, $cid, $boundIds, &$results)
    {
        $q = Db::name('owner')->alias('o')
            ->leftJoin('community c', 'c.id = o.community_id')
            ->field('o.id, o.realname, o.phone, o.id_card, o.status, o.avatar, c.name as community_name')
            ->whereNull('o.delete_time')
            ->where('o.realname|o.phone|o.id_card', 'like', "%{$keyword}%");

        if ($cid === -1) {
            $q->where('o.community_id', 'in', $boundIds);
        } elseif ($cid > 0) {
            $q->where('o.community_id', $cid);
        }

        $list = $q->limit(10)->order('o.id', 'desc')->select();
        foreach ($list as $item) {
            $results[] = [
                'type'    => 'owner',
                'typeName'=> '业主',
                'id'      => $item['id'],
                'title'   => $item['realname'],
                'subtitle'=> ($item['phone'] ? '电话: ' . $item['phone'] : '') .
                            ($item['community_name'] ? ' | ' . $item['community_name'] : ''),
                'route'   => '/owner/index',
                'extra'   => ['owner_id' => $item['id']],
                'icon'    => 'UserFilled',
                'status'  => $item['status'],
            ];
        }
    }

    // ========== 房间搜索 ==========
    private function searchRoom($keyword, $cid, $boundIds, &$results)
    {
        $q = Db::name('room')->alias('r')
            ->leftJoin('community c', 'c.id = r.community_id')
            ->leftJoin('building b', 'b.id = r.building_id')
            ->leftJoin('owner o', 'o.id = r.owner_id')
            ->field('r.id, r.room_number, r.area, r.status, r.community_id,
                     c.name as community_name, b.name as building_name, o.realname as owner_name')
            ->whereNull('r.delete_time')
            ->where('r.room_number', 'like', "%{$keyword}%");

        if ($cid === -1) {
            $q->where('r.community_id', 'in', $boundIds);
        } elseif ($cid > 0) {
            $q->where('r.community_id', $cid);
        }

        $list = $q->limit(10)->order('r.id', 'desc')->select();
        foreach ($list as $item) {
            $results[] = [
                'type'    => 'room',
                'typeName'=> '房间',
                'id'      => $item['id'],
                'title'   => $item['room_number'],
                'subtitle'=> ($item['building_name'] ? $item['building_name'] . ' | ' : '') .
                            ($item['community_name'] ?? '') .
                            ($item['owner_name'] ? ' | 业主: ' . $item['owner_name'] : ' | 空置'),
                'route'   => '/property/room',
                'extra'   => ['room_id' => $item['id'], 'community_id' => $item['community_id']],
                'icon'    => 'House',
                'status'  => $item['status'],
            ];
        }
    }

    // ========== 楼栋搜索 ==========
    private function searchBuilding($keyword, $cid, $boundIds, &$results)
    {
        $q = Db::name('building')->alias('b')
            ->leftJoin('community c', 'c.id = b.community_id')
            ->field('b.id, b.name, b.floor_count, b.unit_count, c.name as community_name')
            ->whereNull('b.delete_time')
            ->where('b.name', 'like', "%{$keyword}%");

        if ($cid === -1) {
            $q->where('b.community_id', 'in', $boundIds);
        } elseif ($cid > 0) {
            $q->where('b.community_id', $cid);
        }

        $list = $q->limit(8)->order('b.id', 'desc')->select();
        foreach ($list as $item) {
            $results[] = [
                'type'    => 'building',
                'typeName'=> '楼栋',
                'id'      => $item['id'],
                'title'   => $item['name'],
                'subtitle'=> ($item['community_name'] ?? '') .
                            ($item['floor_count'] ? ' | ' . $item['floor_count'] . '层' : ''),
                'route'   => '/property/building',
                'extra'   => ['building_id' => $item['id']],
                'icon'    => 'School',
            ];
        }
    }

    // ========== 小区搜索 ==========
    private function searchCommunity($keyword, &$results)
    {
        $list = Db::name('community')
            ->field('id, name, address')
            ->whereNull('delete_time')
            ->where('name|address', 'like', "%{$keyword}%")
            ->limit(5)->order('id', 'desc')->select();

        foreach ($list as $item) {
            $results[] = [
                'type'    => 'community',
                'typeName'=> '小区',
                'id'      => $item['id'],
                'title'   => $item['name'],
                'subtitle'=> $item['address'] ?? '',
                'route'   => '/property/community',
                'extra'   => ['community_id' => $item['id']],
                'icon'    => 'OfficeBuilding',
            ];
        }
    }

    // ========== 报修工单搜索 ==========
    private function searchRepairOrder($keyword, $cid, $boundIds, &$results)
    {
        $q = Db::name('repair_order')->alias('ro')
            ->leftJoin('room r', 'r.id = ro.room_id')
            ->leftJoin('community c', 'c.id = ro.community_id')
            ->field('ro.id, ro.order_no, ro.reporter, ro.reporter_phone, ro.status, ro.type,
                     r.room_number, c.name as community_name')
            ->whereNull('ro.delete_time')
            ->where('ro.order_no|ro.reporter|ro.reporter_phone|r.room_number', 'like', "%{$keyword}%");

        if ($cid === -1) {
            $q->where('ro.community_id', 'in', $boundIds);
        } elseif ($cid > 0) {
            $q->where('ro.community_id', $cid);
        }

        $list = $q->limit(8)->order('ro.id', 'desc')->select();
        $typeMap = [1=>'水',2=>'电',3=>'气',4=>'门窗',5=>'管道',6=>'家电',7=>'网络',8=>'其他'];
        foreach ($list as $item) {
            $results[] = [
                'type'    => 'repair_order',
                'typeName'=> '报修工单',
                'id'      => $item['id'],
                'title'   => $item['order_no'],
                'subtitle'=> ($item['reporter'] ? '报修人: ' . $item['reporter'] . ' | ' : '') .
                            ($item['room_number'] ? $item['room_number'] . ' | ' : '') .
                            ($typeMap[$item['type']] ?? '其他') .
                            ($item['community_name'] ? ' | ' . $item['community_name'] : ''),
                'route'   => '/repair/order',
                'extra'   => ['order_id' => $item['id']],
                'icon'    => 'Tools',
                'status'  => $item['status'],
            ];
        }
    }

    // ========== 账单搜索 ==========
    private function searchBill($keyword, $cid, $boundIds, &$results)
    {
        $q = Db::name('bill')->alias('b')
            ->leftJoin('owner o', 'o.id = b.owner_id')
            ->leftJoin('room r', 'r.id = b.room_id')
            ->leftJoin('community c', 'c.id = b.community_id')
            ->field('b.id, b.bill_no, b.amount, b.status, b.bill_period,
                     o.realname as owner_name, r.room_number, c.name as community_name')
            ->whereNull('b.delete_time')
            ->where('b.bill_no|o.realname|o.phone|r.room_number', 'like', "%{$keyword}%");

        if ($cid === -1) {
            $q->where('b.community_id', 'in', $boundIds);
        } elseif ($cid > 0) {
            $q->where('b.community_id', $cid);
        }

        $list = $q->limit(8)->order('b.id', 'desc')->select();
        foreach ($list as $item) {
            $results[] = [
                'type'    => 'bill',
                'typeName'=> '账单',
                'id'      => $item['id'],
                'title'   => $item['bill_no'],
                'subtitle'=> ($item['owner_name'] ? $item['owner_name'] . ' | ' : '') .
                            ($item['room_number'] ? $item['room_number'] . ' | ' : '') .
                            '¥' . $item['amount'] .
                            ($item['bill_period'] ? ' | ' . $item['bill_period'] : '') .
                            ($item['community_name'] ? ' | ' . $item['community_name'] : ''),
                'route'   => '/charge/bill',
                'extra'   => ['bill_id' => $item['id']],
                'icon'    => 'Document',
                'status'  => $item['status'],
            ];
        }
    }

    // ========== 缴费记录搜索 ==========
    private function searchPayment($keyword, $cid, $boundIds, &$results)
    {
        $q = Db::name('payment')->alias('p')
            ->leftJoin('owner o', 'o.id = p.owner_id')
            ->leftJoin('community c', 'c.id = p.community_id')
            ->field('p.id, p.payment_no, p.amount, p.pay_method, p.create_time,
                     o.realname as owner_name, c.name as community_name')
            ->where('p.payment_no|o.realname|o.phone', 'like', "%{$keyword}%");

        if ($cid === -1) {
            $q->where('p.community_id', 'in', $boundIds);
        } elseif ($cid > 0) {
            $q->where('p.community_id', $cid);
        }

        $list = $q->limit(8)->order('p.id', 'desc')->select();
        foreach ($list as $item) {
            $results[] = [
                'type'    => 'payment',
                'typeName'=> '缴费记录',
                'id'      => $item['id'],
                'title'   => $item['payment_no'] ?: ('缴费 #' . $item['id']),
                'subtitle'=> ($item['owner_name'] ? $item['owner_name'] . ' | ' : '') .
                            '¥' . $item['amount'] .
                            ($item['community_name'] ? ' | ' . $item['community_name'] : ''),
                'route'   => '/charge/payment',
                'extra'   => ['payment_id' => $item['id']],
                'icon'    => 'Wallet',
            ];
        }
    }

    // ========== 车辆搜索 ==========
    private function searchVehicle($keyword, $cid, $boundIds, &$results)
    {
        $q = Db::name('vehicle')->alias('v')
            ->leftJoin('owner o', 'o.id = v.owner_id')
            ->leftJoin('community c', 'c.id = v.community_id')
            ->field('v.id, v.plate_number, v.vehicle_type, v.brand, v.color, v.status,
                     o.realname as owner_name, c.name as community_name')
            ->whereNull('v.delete_time')
            ->where('v.plate_number|v.color|v.brand', 'like', "%{$keyword}%");

        if ($cid === -1) {
            $q->where('v.community_id', 'in', $boundIds);
        } elseif ($cid > 0) {
            $q->where('v.community_id', $cid);
        }

        $list = $q->limit(8)->order('v.id', 'desc')->select();
        foreach ($list as $item) {
            $results[] = [
                'type'    => 'vehicle',
                'typeName'=> '车辆',
                'id'      => $item['id'],
                'title'   => $item['plate_number'],
                'subtitle'=> ($item['owner_name'] ? '车主: ' . $item['owner_name'] . ' | ' : '') .
                            ($item['brand'] ? $item['brand'] . ' | ' : '') .
                            ($item['community_name'] ?? ''),
                'route'   => '/parking/vehicle',
                'extra'   => ['vehicle_id' => $item['id']],
                'icon'    => 'Van',
                'status'  => $item['status'],
            ];
        }
    }

    // ========== 员工搜索 ==========
    private function searchStaff($keyword, &$results)
    {
        $list = Db::name('staff')
            ->field('id, realname, phone, department, position, status')
            ->whereNull('delete_time')
            ->where('realname|phone|department|position', 'like', "%{$keyword}%")
            ->limit(8)->order('id', 'desc')->select();

        foreach ($list as $item) {
            $results[] = [
                'type'    => 'staff',
                'typeName'=> '员工',
                'id'      => $item['id'],
                'title'   => $item['realname'],
                'subtitle'=> ($item['department'] ? $item['department'] . ' | ' : '') .
                            ($item['position'] ? $item['position'] . ' | ' : '') .
                            ($item['phone'] ?? ''),
                'route'   => '/staff/index',
                'extra'   => ['staff_id' => $item['id']],
                'icon'    => 'User',
                'status'  => $item['status'],
            ];
        }
    }

    // ========== 公告搜索 ==========
    private function searchNotice($keyword, $cid, $boundIds, &$results)
    {
        $q = Db::name('notice')->alias('n')
            ->leftJoin('community c', 'c.id = n.community_id')
            ->field('n.id, n.title, n.type, n.status, n.create_time, c.name as community_name')
            ->whereNull('n.delete_time')
            ->where('n.title', 'like', "%{$keyword}%");

        if ($cid === -1) {
            $q->where('n.community_id', 'in', $boundIds);
        } elseif ($cid > 0) {
            $q->where('n.community_id', $cid);
        }

        $list = $q->limit(8)->order('n.id', 'desc')->select();
        $typeMap = [1=>'通知',2=>'活动',3=>'紧急'];
        foreach ($list as $item) {
            $results[] = [
                'type'    => 'notice',
                'typeName'=> '公告',
                'id'      => $item['id'],
                'title'   => $item['title'],
                'subtitle'=> ($typeMap[$item['type']] ?? '通知') .
                            ($item['community_name'] ? ' | ' . $item['community_name'] : '') .
                            ' | ' . date('Y-m-d', strtotime($item['create_time'])),
                'route'   => '/notice/index',
                'extra'   => ['notice_id' => $item['id']],
                'icon'    => 'Bell',
                'status'  => $item['status'],
            ];
        }
    }

    // ========== 供应商搜索 ==========
    private function searchSupplier($keyword, &$results)
    {
        $list = Db::name('supplier')
            ->field('id, name, contact_person, contact_phone, category, status')
            ->whereNull('delete_time')
            ->where('name|contact_person|contact_phone|category', 'like', "%{$keyword}%")
            ->limit(8)->order('id', 'desc')->select();

        foreach ($list as $item) {
            $results[] = [
                'type'    => 'supplier',
                'typeName'=> '供应商',
                'id'      => $item['id'],
                'title'   => $item['name'],
                'subtitle'=> ($item['category'] ? $item['category'] . ' | ' : '') .
                            ($item['contact_person'] ? '联系人: ' . $item['contact_person'] . ' | ' : '') .
                            ($item['contact_phone'] ?? ''),
                'route'   => '/supplier/index',
                'extra'   => ['supplier_id' => $item['id']],
                'icon'    => 'Shop',
                'status'  => $item['status'],
            ];
        }
    }

    // ========== 门禁卡搜索 ==========
    private function searchAccessCard($keyword, $cid, $boundIds, &$results)
    {
        $q = Db::name('access_card')->alias('ac')
            ->leftJoin('owner o', 'o.id = ac.owner_id')
            ->leftJoin('community c', 'c.id = ac.community_id')
            ->field('ac.id, ac.card_no, ac.status, o.realname as owner_name, c.name as community_name')
            ->whereNull('ac.delete_time')
            ->where('ac.card_no|o.realname|o.phone', 'like', "%{$keyword}%");

        if ($cid === -1) {
            $q->where('ac.community_id', 'in', $boundIds);
        } elseif ($cid > 0) {
            $q->where('ac.community_id', $cid);
        }

        $list = $q->limit(8)->order('ac.id', 'desc')->select();
        foreach ($list as $item) {
            $results[] = [
                'type'    => 'access_card',
                'typeName'=> '门禁卡',
                'id'      => $item['id'],
                'title'   => $item['card_no'],
                'subtitle'=> ($item['owner_name'] ? '业主: ' . $item['owner_name'] . ' | ' : '') .
                            ($item['community_name'] ?? ''),
                'route'   => '/security/access-card',
                'extra'   => ['card_id' => $item['id']],
                'icon'    => 'Postcard',
                'status'  => $item['status'],
            ];
        }
    }
}
