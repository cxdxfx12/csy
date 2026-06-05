<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Invoice extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['delete_time', 'null', '']];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['content|remark|invoice_no|title_name', 'like', "%{$keyword}%"];
        $communityId = $this->request->param('community_id', 0);
        if ($communityId) {
            $where[] = ['community_id', '=', $communityId];
        } else {
            $filter = $this->getCommunityFilter('community_id');
            if (!empty($filter)) $where = array_merge($where, $filter);
        }
        $total = Db::name('invoice')->where($where)->count();
        $list = Db::name('invoice')->where($where)->page($page, $limit)->order('id', 'desc')->select();
        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        $this->validateCommunityAccess($data['community_id'] ?? 0);
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('invoice')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        $record = Db::name('invoice')->where('id', $data['id'])->find();
        if ($record) {
            $this->validateCommunityAccess($record['community_id'] ?? 0);
            if (!empty($data['community_id'])) $this->validateCommunityAccess($data['community_id']);
        }
        Db::name('invoice')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        $record = Db::name('invoice')->where('id', $id)->find();
        if ($record) $this->validateCommunityAccess($record['community_id'] ?? 0);
        Db::name('invoice')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }
}
