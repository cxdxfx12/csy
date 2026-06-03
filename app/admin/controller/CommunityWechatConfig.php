<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class CommunityWechatConfig extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $total = Db::name('community_wechat_config')->count();
        $list = Db::name('community_wechat_config')
            ->page($page, $limit)->order('id', 'desc')->select();
        return $this->table($list, $total);
    }
}
