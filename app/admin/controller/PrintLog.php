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
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['title', 'like', "%{$keyword}%"];
        $communityId = $this->request->param('community_id', 0);
        if ($communityId) $where[] = ['community_id', '=', $communityId];
        $total = Db::name('print_log')->where($where)->count();
        $list = Db::name('print_log')->where($where)->page($page, $limit)->order('id', 'desc')->select();
        return $this->table($list, $total);
    }

}
