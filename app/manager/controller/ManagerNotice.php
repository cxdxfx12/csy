<?php
namespace app\manager\controller;

use app\manager\BaseManager;
use think\facade\Db;

/**
 * 经理端 - 公告管理
 * 所有操作限定在当前管理的小区范围内
 */
class ManagerNotice extends BaseManager
{
    /** 公告列表 */
    public function lists()
    {
        [$page, $limit] = $this->getPageParams();
        $cid = $this->getCommunityId();
        $keyword = $this->request->param('keyword', '');
        $status  = $this->request->param('status', 0);

        $query = Db::name('notice')
            ->whereNull('delete_time')
            ->where('community_id', $cid);
        if ($keyword) $query->where('title', 'like', "%{$keyword}%");
        if ($status)  $query->where('status', intval($status));

        $total = $query->count();
        $list  = $query->page($page, $limit)->order('top_status desc, id desc')->select();

        return $this->success(['list' => $list, 'total' => $total]);
    }

    /** 新建公告 */
    public function add()
    {
        $data = $this->request->post();
        if (empty($data['title'])) return $this->error('请输入公告标题');
        if (empty($data['content'])) return $this->error('请输入公告内容');

        $data['community_id'] = $this->getCommunityId();
        $data['published_by'] = $this->managerInfo['nickname'] ?? $this->managerInfo['username'] ?? '';
        $data['create_time']  = date('Y-m-d H:i:s');
        $data['status']       = $data['status'] ?? 1;

        Db::name('notice')->insert($data);
        return $this->success([], '添加成功');
    }

    /** 编辑公告 */
    public function edit()
    {
        $data = $this->request->post();
        $id   = $data['id'] ?? 0;
        if (empty($id)) return $this->error('缺少公告ID');

        $notice = Db::name('notice')->where('id', $id)->where('community_id', $this->getCommunityId())->find();
        if (!$notice) return $this->error('公告不存在');

        unset($data['id']);
        Db::name('notice')->where('id', $id)->update($data);
        return $this->success([], '修改成功');
    }

    /** 删除公告（软删除） */
    public function delete()
    {
        $id = $this->request->post('id', 0);
        $notice = Db::name('notice')->where('id', $id)->where('community_id', $this->getCommunityId())->find();
        if (!$notice) return $this->error('公告不存在');
        Db::name('notice')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }

    /** 发布/撤回公告 */
    public function publish()
    {
        $id     = $this->request->post('id', 0);
        $status = $this->request->post('status', 2);
        $notice = Db::name('notice')->where('id', $id)->where('community_id', $this->getCommunityId())->find();
        if (!$notice) return $this->error('公告不存在');

        $update = ['status' => $status];
        if ($status == 2) {
            $update['publish_time'] = date('Y-m-d H:i:s');
        }
        Db::name('notice')->where('id', $id)->update($update);
        $msg = $status == 2 ? '发布成功' : '已撤回';
        return $this->success([], $msg);
    }

    private function getPageParams()
    {
        return [intval($this->request->param('page', 1)), intval($this->request->param('limit', 15))];
    }
}
