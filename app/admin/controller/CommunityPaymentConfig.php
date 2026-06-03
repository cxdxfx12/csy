<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class CommunityPaymentConfig extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $total = Db::name('community_payment_config')->count();
        $list = Db::name('community_payment_config')
            ->page($page, $limit)->order('id', 'desc')->select();
        return $this->table($list, $total);
    }
}
