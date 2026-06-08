<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Consultation extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) {
            $where[] = ['name|phone|type|content', 'like', "%$keyword%"];
        }
        $status = $this->request->param('status', '');
        if ($status !== '') {
            $where[] = ['status', '=', (int)$status];
        }

        $total = Db::name('consultation')->where($where)->count();
        $list = Db::name('consultation')
            ->where($where)->page($page, $limit)->order('id', 'desc')->select();

        return $this->table($list, $total);
    }

    public function detail()
    {
        $id = $this->request->param('id', 0);
        $record = Db::name('consultation')->where('id', $id)->find();
        if (!$record) {
            return $this->error('记录不存在');
        }
        // 标记已读
        if ($record['status'] == 0) {
            Db::name('consultation')->where('id', $id)->update([
                'status' => 1,
                'update_time' => date('Y-m-d H:i:s')
            ]);
            $record['status'] = 1;
        }
        return $this->success($record);
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('consultation')->where('id', $id)->delete();
        return $this->success([], '删除成功');
    }

    public function batchDelete()
    {
        $ids = $this->request->post('ids', []);
        if (empty($ids)) {
            return $this->error('请选择要删除的记录');
        }
        Db::name('consultation')->whereIn('id', $ids)->delete();
        return $this->success([], '批量删除成功');
    }

    public function markRead()
    {
        $id = $this->request->post('id', 0);
        Db::name('consultation')->where('id', $id)->update([
            'status' => 1,
            'update_time' => date('Y-m-d H:i:s')
        ]);
        return $this->success([], '已标记为已读');
    }

    /**
     * 未读数量
     */
    public function unreadCount()
    {
        $count = Db::name('consultation')->where('status', 0)->count();
        return $this->success(['count' => $count]);
    }
}
