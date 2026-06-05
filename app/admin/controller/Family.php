<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Family extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['f.delete_time', 'null', '']];
        $ownerId = $this->request->param('owner_id', 0);
        if ($ownerId) $where[] = ['f.owner_id', '=', $ownerId];

        // 小区隔离：通过 owner 的 community_id 过滤
        $filter = $this->getCommunityFilter('o.community_id');
        if (!empty($filter)) $where = array_merge($where, $filter);

        $total = Db::name('owner_family')->alias('f')
            ->leftJoin('owner o', 'o.id = f.owner_id')
            ->where($where)->count();
        $list = Db::name('owner_family')->alias('f')
            ->leftJoin('owner o', 'o.id = f.owner_id')
            ->leftJoin('room r', 'r.id = f.room_id')
            ->field('f.*, o.realname as owner_name, o.community_id, r.room_number')
            ->where($where)->page($page, $limit)->order('f.id', 'desc')->select();
        foreach ($list as &$item) {
            $item['wx_bound'] = !empty($item['openid']) ? 1 : 0;
            if (!empty($item['openid'])) {
                $item['openid_masked'] = substr($item['openid'], 0, 6) . '****' . substr($item['openid'], -4);
            }
        }
        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        // 通过 owner 校验社区权限
        if (!empty($data['owner_id'])) {
            $owner = Db::name('owner')->where('id', $data['owner_id'])->find();
            if ($owner) $this->validateCommunityAccess($owner['community_id'] ?? 0);
        }
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('owner_family')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        $family = Db::name('owner_family')->where('id', $data['id'])->find();
        if ($family) {
            $owner = Db::name('owner')->where('id', $family['owner_id'])->find();
            if ($owner) $this->validateCommunityAccess($owner['community_id'] ?? 0);
        }
        Db::name('owner_family')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        $family = Db::name('owner_family')->where('id', $id)->find();
        if ($family) {
            $owner = Db::name('owner')->where('id', $family['owner_id'])->find();
            if ($owner) $this->validateCommunityAccess($owner['community_id'] ?? 0);
        }
        Db::name('owner_family')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }

    /**
     * 管理员解绑家庭成员微信
     */
    public function unbindWechat()
    {
        $id = intval($this->request->post('id', 0));
        if ($id <= 0) return $this->error('参数错误');

        $family = Db::name('owner_family')->where('id', $id)->find();
        if (!$family) return $this->error('家庭成员不存在');

        Db::name('owner_family')->where('id', $id)->update([
            'openid' => '',
        ]);
        return $this->success([], '微信已解绑');
    }
}
