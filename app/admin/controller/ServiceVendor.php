<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class ServiceVendor extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['delete_time', 'null', '']];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['company_name|mobile|phone|contact_person|remark', 'like', "%{$keyword}%"];
        $vendorType = $this->request->param('vendor_type', '');
        if ($vendorType) $where[] = ['vendor_type', '=', $vendorType];
        $status = $this->request->param('status', '');
        if ($status !== '') $where[] = ['status', '=', intval($status)];
        $communityId = $this->request->param('community_id', 0);
        if ($communityId) $where[] = ['community_id', '=', $communityId];
        $total = Db::name('service_vendor')->where($where)->count();
        $list = Db::name('service_vendor')->where($where)->page($page, $limit)->order('id', 'desc')->select();
        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('service_vendor')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        Db::name('service_vendor')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('service_vendor')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }
}
