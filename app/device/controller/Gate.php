<?php
/**
 * 道闸设备回调接口 — 供道闸硬件主动推送车牌识别结果
 * 路由: POST /device/gate/notify
 */
namespace app\device\controller;

use app\BaseController;
use think\facade\Db;
use app\extend\gate\GateFactory;

class Gate extends BaseController
{
    /**
     * 车牌识别回调 — 道闸设备推送识别到的车牌
     * 该接口不需要认证（由道闸硬件直接调用）
     * 安全校验：通过 device_sn + token 验证设备身份
     */
    public function notify()
    {
        $input = $this->request->post();

        // 设备身份校验
        $deviceSn = $input['device_sn'] ?? '';
        if (empty($deviceSn)) return json_error('缺少 device_sn');

        $config = Db::name('gate_config')
            ->where('device_sn', $deviceSn)
            ->where('enabled', 1)
            ->whereNull('delete_time')
            ->find();

        if (!$config) return json_error('设备未注册');

        // 可选 token 校验
        if (!empty($config['api_token'])) {
            $reqToken = $this->request->header('X-Gate-Token', '');
            if ($reqToken !== $config['api_token']) {
                return json_error('设备Token校验失败');
            }
        }

        // 注入配置信息
        $config['community_id'] = $config['community_id'] ?? 0;

        // 创建适配器处理
        $adapter = GateFactory::create($config);
        $result = $adapter->onPlateRecognized($input);

        // 更新设备心跳
        Db::name('gate_device')
            ->where('device_sn', $deviceSn)
            ->update([
                'last_heartbeat' => date('Y-m-d H:i:s'),
                'online'         => 1,
            ]);

        return json_success($result, 'ok');
    }

    /** 设备心跳 */
    public function heartbeat()
    {
        $deviceSn = $this->request->post('device_sn', '');
        if (empty($deviceSn)) return json_error('缺少 device_sn');

        Db::name('gate_device')
            ->where('device_sn', $deviceSn)
            ->update([
                'last_heartbeat' => date('Y-m-d H:i:s'),
                'online'         => 1,
                'update_time'    => date('Y-m-d H:i:s'),
            ]);

        return json_success(['time' => date('Y-m-d H:i:s')], 'ok');
    }
}
