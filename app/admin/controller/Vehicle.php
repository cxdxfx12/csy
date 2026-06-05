<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Vehicle extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['v.delete_time', 'null', '']];
        $cid = $this->getFilteredCommunityId();
        if ($cid === -1) $where[] = ['v.community_id', 'in', $this->request->boundCommunityIds];
        elseif ($cid > 0) $where[] = ['v.community_id', '=', $cid];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['v.plate_number|v.brand|v.model', 'like', "%{$keyword}%"];
        $total = Db::name('vehicle')->alias('v')->where($where)->count();
        $list = Db::name('vehicle')->alias('v')
            ->leftJoin('owner o', 'o.id = v.owner_id')
            ->leftJoin('parking_space ps', 'ps.id = v.parking_space_id')
            ->leftJoin('community com', 'com.id = v.community_id')
            ->field('v.*, o.realname as owner_name, o.phone as owner_phone, ps.space_no, com.name as community_name')
            ->where($where)->page($page, $limit)->order('v.id', 'desc')->select();
        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        $this->validateCommunityAccess($data['community_id'] ?? 0);
        // 检查同一小区下车牌号唯一性
        $exist = Db::name('vehicle')->where('community_id', $data['community_id'] ?? 0)
            ->where('plate_number', $data['plate_number'])->where('delete_time', null)->find();
        if ($exist) {
            return $this->error('该小区下已存在相同车牌号的车辆');
        }
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('vehicle')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        $vehicle = Db::name('vehicle')->where('id', $data['id'])->find();
        if ($vehicle) $this->validateCommunityAccess($vehicle['community_id'] ?? 0);
        // 检查同一小区下车牌号唯一性（排除自身）
        $exist = Db::name('vehicle')->where('community_id', $data['community_id'] ?? 0)
            ->where('plate_number', $data['plate_number'])->where('id', '<>', $data['id'])
            ->where('delete_time', null)->find();
        if ($exist) {
            return $this->error('该小区下已存在相同车牌号的车辆');
        }
        Db::name('vehicle')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        $vehicle = Db::name('vehicle')->where('id', $id)->find();
        if ($vehicle) $this->validateCommunityAccess($vehicle['community_id'] ?? 0);
        Db::name('vehicle')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }
}
