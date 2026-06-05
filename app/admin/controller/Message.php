<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Message extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['title|content', 'like', "%{$keyword}%"];
        $messageType = $this->request->param('type', '');
        if ($messageType) $where[] = ['type', '=', $messageType];
        $receiverType = $this->request->param('receiver_type', '');
        if ($receiverType) $where[] = ['receiver_type', '=', $receiverType];

        $total = Db::name('message')->where($where)->count();
        $list = Db::name('message')->where($where)->page($page, $limit)->order('id', 'desc')->select();

        // 返回 {list, total} 格式，匹配前端 res.data.list / res.data.total
        return $this->success(['list' => $list, 'total' => $total], '获取成功');
    }

    public function add()
    {
        $data = $this->request->post();

        // 只接受白名单字段，防止任意数据注入
        $allowFields = ['type', 'receiver_type', 'receiver_id', 'title', 'content'];
        $insertData = [];
        foreach ($allowFields as $f) {
            $insertData[$f] = $data[$f] ?? '';
        }

        if (empty(trim($insertData['title']))) return $this->error('标题不能为空');
        if (empty(trim($insertData['content']))) return $this->error('内容不能为空');

        // 从当前登录管理员信息自动填充
        $communityIds = array_filter(array_map('intval', explode(',', $this->adminInfo['community_ids'] ?? '')));
        $insertData['community_id'] = !empty($communityIds) ? $communityIds[0] : 0;
        $insertData['sender_id'] = intval($this->adminId ?? 0);
        $insertData['sender_type'] = 'admin';
        $insertData['is_read'] = 0;
        $insertData['status'] = 1;
        $insertData['receiver_id'] = intval($insertData['receiver_id'] ?? 0);
        $insertData['create_time'] = date('Y-m-d H:i:s');

        Db::name('message')->insert($insertData);
        return $this->success([], '发送成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('message')->where('id', $id)->delete();
        return $this->success([], '删除成功');
    }
}
