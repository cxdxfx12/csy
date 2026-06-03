<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class ParkingRecord extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['pr.delete_time', '=', null]];
        $communityId = $this->request->param('community_id', 0);
        if ($communityId) $where[] = ['pr.community_id', '=', $communityId];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['pr.plate_number', 'like', "%{$keyword}%"];
        $startDate = $this->request->param('start_date', '');
        $endDate = $this->request->param('end_date', '');
        if ($startDate) $where[] = ['pr.enter_time', '>=', $startDate . ' 00:00:00'];
        if ($endDate) $where[] = ['pr.enter_time', '<=', $endDate . ' 23:59:59'];

        $total = Db::name('parking_record')->alias('pr')->where($where)->count();
        $list = Db::name('parking_record')->alias('pr')
            ->leftJoin('parking_space ps', 'ps.id = pr.space_id')
            ->field('pr.*, ps.space_no')
            ->where($where)->page($page, $limit)->order('pr.id', 'desc')->select();
        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        $data['create_time'] = date('Y-m-d H:i:s');
        // 如果有出场时间，计算时长和费用
        if (!empty($data['exit_time']) && !empty($data['enter_time'])) {
            $data['duration'] = (strtotime($data['exit_time']) - strtotime($data['enter_time'])) / 60;
        }
        Db::name('parking_record')->insert($data);
        return $this->success([], '添加成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('parking_record')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }
}
