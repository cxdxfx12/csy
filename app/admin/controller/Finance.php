<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Finance extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [];
        $communityId = $this->request->param('community_id', 0);
        if ($communityId) $where[] = ['community_id', '=', $communityId];
        $type = $this->request->param('type', 0);
        if ($type) $where[] = ['type', '=', $type];
        $startDate = $this->request->param('start_date', '');
        $endDate = $this->request->param('end_date', '');
        if ($startDate) $where[] = ['create_time', '>=', $startDate . ' 00:00:00'];
        if ($endDate) $where[] = ['create_time', '<=', $endDate . ' 23:59:59'];

        $total = Db::name('finance_flow')->where($where)->count();
        $list = Db::name('finance_flow')->alias('f')
            ->leftJoin('community com', 'com.id = f.community_id')
            ->field('f.*, com.name as community_name')
            ->where($where)->page($page, $limit)->order('f.id', 'desc')->select();

        $incomeTotal = Db::name('finance_flow')->where($where)->where('type', 1)->sum('amount');
        $expenseTotal = Db::name('finance_flow')->where($where)->where('type', 2)->sum('amount');

        return $this->success([
            'list' => $list,
            'total' => $total,
            'income_total' => $incomeTotal,
            'expense_total' => $expenseTotal,
        ]);
    }
}
