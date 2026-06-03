<?php
namespace app\device\controller;

use app\BaseController;
use think\facade\Db;

class Camera extends BaseController
{
    public function recognize()
    {
        $plateNumber = $this->request->post('plate_number', '');
        $communityId = $this->request->post('community_id', 0);

        if (empty($plateNumber)) return json_error('未识别到车牌');

        $vehicle = Db::name('vehicle')->where('plate_number', $plateNumber)->find();
        if ($vehicle) {
            Db::name('parking_record')->insert([
                'community_id' => $communityId,
                'plate_number' => $plateNumber,
                'space_id' => $vehicle['parking_space_id'] ?? 0,
                'enter_time' => date('Y-m-d H:i:s'),
                'create_time' => date('Y-m-d H:i:s'),
            ]);
            return json_success(['type' => 'registered', 'owner_id' => $vehicle['owner_id']], '已登记车辆');
        }

        return json_success(['type' => 'visitor', 'plate_number' => $plateNumber], '访客车辆');
    }
}
