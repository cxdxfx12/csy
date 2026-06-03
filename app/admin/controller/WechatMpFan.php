<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class WechatMpFan extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['delete_time', 'null', '']];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['name', 'like', "%{$keyword}%"];
        $communityId = $this->request->param('community_id', 0);
        if ($communityId) $where[] = ['community_id', '=', $communityId];
        $total = Db::name('wechat_mp_fan')->where($where)->count();
        $list = Db::name('wechat_mp_fan')->where($where)->page($page, $limit)->order('id', 'desc')->select();
        return $this->table($list, $total);
    }

}
