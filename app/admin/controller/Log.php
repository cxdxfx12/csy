<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Log extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [];

        $keyword = $this->request->param('keyword', '');
        if ($keyword) {
            $where[] = ['admin_name|module|action', 'like', "%{$keyword}%"];
        }
        $adminId = $this->request->param('admin_id', 0);
        if ($adminId) $where[] = ['admin_id', '=', $adminId];
        $module = $this->request->param('module', '');
        if ($module) $where[] = ['module', '=', $module];
        $action = $this->request->param('action', '');
        if ($action) $where[] = ['action', 'like', "%{$action}%"];
        $startDate = $this->request->param('start_date', '');
        $endDate = $this->request->param('end_date', '');
        if ($startDate) $where[] = ['create_time', '>=', $startDate . ' 00:00:00'];
        if ($endDate) $where[] = ['create_time', '<=', $endDate . ' 23:59:59'];

        $total = Db::name('system_log')->where($where)->count();
        $list = Db::name('system_log')->where($where)
            ->field('id,admin_name,module,action,url,method,ip,duration,create_time')
            ->page($page, $limit)->order('id', 'desc')->select();

        return $this->table($list, $total);
    }

    public function detail()
    {
        $id = $this->request->param('id', 0);
        $info = Db::name('system_log')->where('id', $id)->find();
        return $this->success($info);
    }

    public function export()
    {
        // 导出日志为Excel
        return $this->success([], '导出功能需要在composer安装phpspreadsheet后启用');
    }
}
