<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class SmsLog extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) {
            $where = [['phone|content|template_id', 'like', "%{$keyword}%"]];
        }
        $status = $this->request->param('status', '');
        if ($status !== '') $where[] = ['status', '=', intval($status)];

        // 日期范围筛选
        $startDate = $this->request->param('start_date', '');
        $endDate = $this->request->param('end_date', '');
        if ($startDate) $where[] = ['create_time', '>=', $startDate . ' 00:00:00'];
        if ($endDate) $where[] = ['create_time', '<=', $endDate . ' 23:59:59'];

        $total = Db::name('sms_log')->where($where)->count();
        $list = Db::name('sms_log')->where($where)->page($page, $limit)->order('id', 'desc')->select();

        return $this->table($list, $total);
    }

    /**
     * 发送统计
     */
    public function stats()
    {
        $startDate = $this->request->param('start_date', '');
        $endDate = $this->request->param('end_date', '');
        $where = [];
        if ($startDate) $where[] = ['create_time', '>=', $startDate . ' 00:00:00'];
        if ($endDate) $where[] = ['create_time', '<=', $endDate . ' 23:59:59'];

        $total = Db::name('sms_log')->where($where)->count();
        $success = Db::name('sms_log')->where($where)->where('status', 1)->count();
        $fail = Db::name('sms_log')->where($where)->where('status', 2)->count();

        return $this->success([
            'total'   => $total,
            'success' => $success,
            'fail'    => $fail,
        ]);
    }
}
