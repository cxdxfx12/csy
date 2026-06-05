<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class PrintLog extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['delete_time', 'null', '']];

        // 关键词搜索：标题或模板编码
        $keyword = $this->request->param('keyword', '');
        if ($keyword) {
            $where[] = ['title|template_code', 'like', "%{$keyword}%"];
        }

        // 小区筛选（结合权限）
        $cid = $this->getFilteredCommunityId();
        if ($cid === -1) $where[] = ['community_id', 'in', $this->request->boundCommunityIds];
        elseif ($cid > 0) $where[] = ['community_id', '=', $cid];

        // 业务类型筛选
        $businessType = $this->request->param('business_type', '');
        if ($businessType) $where[] = ['business_type', '=', $businessType];

        // 日期范围筛选
        $startDate = $this->request->param('start_date', '');
        $endDate = $this->request->param('end_date', '');
        if ($startDate) $where[] = ['create_time', '>=', $startDate . ' 00:00:00'];
        if ($endDate) $where[] = ['create_time', '<=', $endDate . ' 23:59:59'];

        $total = Db::name('print_log')->where($where)->count();
        $list = Db::name('print_log')->where($where)->page($page, $limit)->order('id', 'desc')->select();
        return $this->table($list, $total);
    }

}
