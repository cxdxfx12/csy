<?php
// IoT设备管理（连接3D大屏 ds_iot_device / ds_iot_device_type / ds_iot_protocol / ds_iot_device_data）
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Iot extends BaseAdmin
{
    // ==================== 设备管理 ====================

    public function deviceList()
    {
        [$page, $limit] = $this->getPage();
        $cid = $this->getFilteredCommunityId();
        $keyword = $this->request->param('keyword', '');
        $typeId = (int)$this->request->param('type_id', 0);
        $status = $this->request->param('status', '');

        $query = Db::name('iot_device')->alias('d')
            ->leftJoin('iot_device_type dt', 'dt.id = d.device_type_id')
            ->leftJoin('iot_protocol p', 'p.id = d.protocol_id')
            ->leftJoin('community c', 'c.id = d.community_id')
            ->field('d.*, dt.code as type_code, dt.name as type_name, dt.category, dt.unit,
                     p.name as protocol_name, p.code as protocol_code,
                     c.name as community_name')
            ->whereNull('d.delete_time');

        // 小区过滤
        if ($cid === -1 && !empty($this->request->boundCommunityIds)) {
            $query->whereIn('d.community_id', $this->request->boundCommunityIds);
        } elseif ($cid > 0) {
            $query->where('d.community_id', $cid);
        }

        if ($keyword) {
            $query->where(function($q) use ($keyword) {
                $q->where('d.name', 'like', "%{$keyword}%")
                  ->whereOr('d.code', 'like', "%{$keyword}%")
                  ->whereOr('d.install_location', 'like', "%{$keyword}%");
            });
        }
        if ($typeId > 0) $query->where('d.device_type_id', $typeId);
        if ($status !== '' && $status !== null) $query->where('d.status', (int)$status);

        $total = $query->count();
        $list = $query->order('d.id', 'desc')->page($page, $limit)->select();

        return $this->success(['list' => $list, 'total' => $total]);
    }

    public function deviceAdd()
    {
        $data = $this->request->post();
        if (empty($data['name']) || empty($data['code'])) {
            return $this->error('设备名称/编号不能为空');
        }
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['update_time'] = date('Y-m-d H:i:s');
        $data['install_date'] = $data['install_date'] ?? date('Y-m-d');
        $data['last_online'] = date('Y-m-d H:i:s');
        $data['status'] = $data['status'] ?? 1;

        Db::name('iot_device')->insert($data);
        return $this->success([], '添加成功');
    }

    public function deviceEdit()
    {
        $id = (int)$this->request->post('id', 0);
        if ($id <= 0) return $this->error('参数错误');

        $data = $this->request->post();
        unset($data['id'], $data['create_time']);
        $data['update_time'] = date('Y-m-d H:i:s');

        Db::name('iot_device')->where('id', $id)->update($data);
        return $this->success([], '修改成功');
    }

    public function deviceDelete()
    {
        $id = (int)$this->request->post('id', 0);
        if ($id <= 0) return $this->error('参数错误');
        Db::name('iot_device')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }

    // ==================== 统计数据 ====================

    public function stats()
    {
        $cid = $this->getFilteredCommunityId();
        $query = Db::name('iot_device')->whereNull('delete_time');

        if ($cid === -1 && !empty($this->request->boundCommunityIds)) {
            $query->whereIn('community_id', $this->request->boundCommunityIds);
        } elseif ($cid > 0) {
            $query->where('community_id', $cid);
        }

        $total = $query->count();
        $online = (clone $query)->where('status', 1)->count();
        $offline = (clone $query)->where('status', 0)->count();
        $typeCount = (clone $query)->group('device_type_id')->count();

        // 各小区设备数
        $byCommunity = Db::name('iot_device')->alias('d')
            ->leftJoin('community c', 'c.id = d.community_id')
            ->whereNull('d.delete_time')
            ->field('c.name, count(*) as cnt')
            ->group('d.community_id')
            ->select();

        return $this->success([
            'total' => $total,
            'online' => $online,
            'offline' => $offline,
            'types' => $typeCount,
            'by_community' => $byCommunity,
        ]);
    }

    // ==================== 设备类型管理 ====================

    public function typeList()
    {
        [$page, $limit] = $this->getPage();
        $query = Db::name('iot_device_type');
        $total = $query->count();
        $list = $query->order('sort', 'asc')->page($page, $limit)->select();
        return $this->success(['list' => $list, 'total' => $total]);
    }

    public function typeAll()
    {
        $list = Db::name('iot_device_type')->where('status', 1)->order('sort', 'asc')->select();
        return $this->success($list);
    }

    public function typeAdd()
    {
        $data = $this->request->post();
        if (empty($data['name']) || empty($data['code'])) {
            return $this->error('名称/编码不能为空');
        }
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['status'] = $data['status'] ?? 1;
        $data['sort'] = $data['sort'] ?? 99;
        Db::name('iot_device_type')->insert($data);
        return $this->success([], '添加成功');
    }

    public function typeEdit()
    {
        $id = (int)$this->request->post('id', 0);
        if ($id <= 0) return $this->error('参数错误');
        $data = $this->request->post();
        unset($data['id'], $data['create_time']);
        Db::name('iot_device_type')->where('id', $id)->update($data);
        return $this->success([], '修改成功');
    }

    public function typeDelete()
    {
        $id = (int)$this->request->post('id', 0);
        if ($id <= 0) return $this->error('参数错误');
        // 检查是否有设备使用该类型
        $cnt = Db::name('iot_device')->where('device_type_id', $id)->whereNull('delete_time')->count();
        if ($cnt > 0) return $this->error("该类型下有 {$cnt} 台设备，无法删除");
        Db::name('iot_device_type')->where('id', $id)->delete();
        return $this->success([], '删除成功');
    }

    // ==================== 协议管理 ====================

    public function protocolList()
    {
        [$page, $limit] = $this->getPage();
        $query = Db::name('iot_protocol');
        $total = $query->count();
        $list = $query->order('sort', 'asc')->page($page, $limit)->select();
        return $this->success(['list' => $list, 'total' => $total]);
    }

    public function protocolAll()
    {
        $list = Db::name('iot_protocol')->where('status', 1)->order('sort', 'asc')->select();
        return $this->success($list);
    }

    public function protocolAdd()
    {
        $data = $this->request->post();
        if (empty($data['name']) || empty($data['code'])) {
            return $this->error('名称/编码不能为空');
        }
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['status'] = $data['status'] ?? 1;
        $data['sort'] = $data['sort'] ?? 99;
        Db::name('iot_protocol')->insert($data);
        return $this->success([], '添加成功');
    }

    public function protocolEdit()
    {
        $id = (int)$this->request->post('id', 0);
        if ($id <= 0) return $this->error('参数错误');
        $data = $this->request->post();
        unset($data['id'], $data['create_time']);
        Db::name('iot_protocol')->where('id', $id)->update($data);
        return $this->success([], '修改成功');
    }

    public function protocolDelete()
    {
        $id = (int)$this->request->post('id', 0);
        if ($id <= 0) return $this->error('参数错误');
        $cnt = Db::name('iot_device')->where('protocol_id', $id)->whereNull('delete_time')->count();
        if ($cnt > 0) return $this->error("该协议下有 {$cnt} 台设备，无法删除");
        Db::name('iot_protocol')->where('id', $id)->delete();
        return $this->success([], '删除成功');
    }

    // ==================== 数据查看 ====================

    public function dataList()
    {
        [$page, $limit] = $this->getPage();
        $deviceId = (int)$this->request->param('device_id', 0);
        $dateFrom = $this->request->param('date_from', '');
        $dateTo = $this->request->param('date_to', '');

        $query = Db::name('iot_device_data')->alias('dd')
            ->leftJoin('iot_device d', 'd.id = dd.device_id')
            ->leftJoin('iot_device_type dt', 'dt.id = d.device_type_id')
            ->field('dd.*, d.name as device_name, d.code as device_code, dt.name as type_name, dt.unit');

        if ($deviceId > 0) $query->where('dd.device_id', $deviceId);
        if ($dateFrom) $query->where('dd.data_time', '>=', $dateFrom);
        if ($dateTo) $query->where('dd.data_time', '<=', $dateTo . ' 23:59:59');

        $total = $query->count();
        $list = $query->order('dd.id', 'desc')->page($page, $limit)->select();

        return $this->success(['list' => $list, 'total' => $total]);
    }
}
