<?php
namespace app\api\controller;

use app\api\BaseApi;
use think\facade\Db;

class Notice extends BaseApi
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $owner = Db::name('owner')->where('id', $this->ownerId)->find();
        $where = [
            ['community_id', 'in', [0, $owner['community_id'] ?? 0]],
            ['status', '=', 2],
            ['delete_time', '=', null],
        ];
        $total = Db::name('notice')->where($where)->count();
        $list = Db::name('notice')->where($where)
            ->field('id,title,type,level,cover_image,published_by,publish_time,top_status,read_count')
            ->page($page, $limit)->order('top_status desc, id desc')->select()->toArray();
        return $this->success(['list' => $list, 'total' => $total]);
    }

    public function detail()
    {
        $id = $this->request->param('id', 0);
        $info = Db::name('notice')->where('id', $id)->find();
        if ($info) {
            Db::name('notice')->where('id', $id)->inc('read_count')->update();
        }
        return $this->success($info);
    }
}
