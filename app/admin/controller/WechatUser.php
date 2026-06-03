<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class WechatUser extends BaseAdmin
{
    /**
     * 统计概览
     */
    public function statistics()
    {
        $communityId = $this->request->param('community_id', 0);
        // 单表查询用不带前缀的条件
        $baseWhere = [['openid', '<>', ''], ['delete_time', 'null', '']];
        if ($communityId) $baseWhere[] = ['community_id', '=', $communityId];

        // 总数
        $total = Db::name('owner')->where($baseWhere)->count();

        // 已注册（有手机号）
        $registered = Db::name('owner')->where($baseWhere)->where('phone', '<>', '')->count();

        // 未注册（无手机号）
        $unregistered = $total - $registered;

        // 今日新增
        $today = date('Y-m-d');
        $todayNew = Db::name('owner')->where($baseWhere)
            ->where('create_time', '>=', $today . ' 00:00:00')->count();

        // 本周新增
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $weekNew = Db::name('owner')->where($baseWhere)
            ->where('create_time', '>=', $weekStart . ' 00:00:00')->count();

        // 本月新增
        $monthStart = date('Y-m-01');
        $monthNew = Db::name('owner')->where($baseWhere)
            ->where('create_time', '>=', $monthStart . ' 00:00:00')->count();

        // 各小区分布（带 JOIN 需要表别名）
        $joinWhere = [['o.openid', '<>', ''], ['o.delete_time', 'null', '']];
        if ($communityId) $joinWhere[] = ['o.community_id', '=', $communityId];

        $communityList = Db::name('owner')->alias('o')
            ->leftJoin('community c', 'c.id = o.community_id')
            ->where($joinWhere)
            ->field('o.community_id, c.name as community_name, count(*) as cnt')
            ->group('o.community_id, c.name')
            ->order('cnt', 'desc')
            ->select();

        return $this->success([
            'total'        => $total,
            'registered'   => $registered,
            'unregistered' => $unregistered,
            'today_new'    => $todayNew,
            'week_new'     => $weekNew,
            'month_new'    => $monthNew,
            'communities'  => $communityList,
        ]);
    }

    /**
     * 用户列表
     */
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['o.openid', '<>', ''], ['o.delete_time', 'null', '']];

        $communityId = $this->request->param('community_id', 0);
        if ($communityId) $where[] = ['o.community_id', '=', $communityId];

        $status = $this->request->param('status', '');
        if ($status === '1') {
            $where[] = ['o.phone', '<>', '']; // 已注册
        } elseif ($status === '0') {
            $where[] = ['o.phone', '=', ''];  // 未注册
        }

        $keyword = $this->request->param('keyword', '');
        if (!empty($keyword)) {
            $where[] = ['o.realname|o.phone|o.openid', 'like', "%{$keyword}%"];
        }

        // 统计已绑房间数
        $total = Db::name('owner')->alias('o')->where($where)->count();
        $list = Db::name('owner')->alias('o')
            ->leftJoin('community c', 'c.id = o.community_id')
            ->leftJoin('owner_room or2', 'or2.owner_id = o.id')
            ->leftJoin('room r', 'r.id = or2.room_id')
            ->field('o.id, o.community_id, o.realname, o.phone, o.openid, o.wechat_unionid,
                    o.gender, o.email, o.status, o.register_time, o.last_login_time, o.create_time,
                    c.name as community_name,
                    GROUP_CONCAT(DISTINCT CONCAT(r.building_name, \'-\', r.room_number) SEPARATOR \'，\') as rooms')
            ->group('o.id')
            ->where($where)
            ->page($page, $limit)
            ->order('o.create_time', 'desc')
            ->select();

        // 处理房间名（防止NULL）
        foreach ($list as &$item) {
            if (empty($item['rooms'])) $item['rooms'] = '';
        }

        return $this->table($list, $total);
    }

    /**
     * 用户详情
     */
    public function detail()
    {
        $id = $this->request->param('id', 0);
        $owner = Db::name('owner')->alias('o')
            ->leftJoin('community c', 'c.id = o.community_id')
            ->field('o.*, c.name as community_name')
            ->where('o.id', $id)
            ->find();

        if (!$owner) return $this->error('用户不存在');

        // 房间列表
        $rooms = Db::name('owner_room')->alias('or2')
            ->leftJoin('room r', 'r.id = or2.room_id')
            ->field('r.id, r.room_number, r.building_name, or2.relation')
            ->where('or2.owner_id', $id)
            ->select();

        // 最近缴费
        $payments = Db::name('bill_payment')
            ->where('owner_id', $id)
            ->order('create_time', 'desc')
            ->limit(10)
            ->select();

        // 最近报修
        $repairs = Db::name('repair_order')
            ->where('owner_id', $id)
            ->order('create_time', 'desc')
            ->limit(5)
            ->select();

        // 不暴露敏感信息
        unset($owner['password']);
        $owner['rooms'] = $rooms;
        $owner['payments'] = $payments;
        $owner['repairs'] = $repairs;

        return $this->success($owner);
    }

    /**
     * 导出CSV
     */
    public function export()
    {
        $where = [['o.openid', '<>', ''], ['o.delete_time', 'null', '']];

        $communityId = $this->request->param('community_id', 0);
        if ($communityId) $where[] = ['o.community_id', '=', $communityId];

        $status = $this->request->param('status', '');
        if ($status === '1') {
            $where[] = ['o.phone', '<>', ''];
        } elseif ($status === '0') {
            $where[] = ['o.phone', '=', ''];
        }

        $list = Db::name('owner')->alias('o')
            ->leftJoin('community c', 'c.id = o.community_id')
            ->field('o.realname, o.phone, o.openid, c.name as community_name,
                    o.register_time, o.last_login_time, o.create_time, o.status')
            ->where($where)
            ->order('o.create_time', 'desc')
            ->select();

        $filename = '微信用户_' . date('YmdHi') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        // BOM for Excel
        echo "\xEF\xBB\xBF";

        $fp = fopen('php://output', 'w');
        fputcsv($fp, ['姓名', '手机号', 'OpenID', '小区', '关注时间', '最近登录', '状态', '注册状态']);
        foreach ($list as $row) {
            fputcsv($fp, [
                $row['realname'],
                $row['phone'],
                $row['openid'],
                $row['community_name'],
                $row['create_time'],
                $row['last_login_time'],
                $row['status'] == 1 ? '正常' : '禁用',
                empty($row['phone']) ? '未注册' : '已注册',
            ]);
        }
        fclose($fp);
        exit;
    }
}
