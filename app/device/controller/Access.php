<?php
/**
 * 门禁设备回调接口 — 供门禁控制器主动推送刷卡事件
 * 路由: POST /device/access/notify
 */
namespace app\device\controller;

use app\BaseController;
use think\facade\Db;
use app\extend\access\AccessFactory;

class Access extends BaseController
{
    /**
     * 刷卡事件回调 — 门禁控制器推送刷卡/开门事件
     * 该接口不需要认证（由门禁硬件直接调用）
     * 安全校验：通过 device_sn + token 验证设备身份
     */
    public function notify()
    {
        $input = $this->request->post();

        // 设备身份校验
        $deviceSn = $input['device_sn'] ?? '';
        if (empty($deviceSn)) return json_error('缺少 device_sn');

        $config = Db::name('access_config')
            ->where('device_sn', $deviceSn)
            ->where('enabled', 1)
            ->whereNull('delete_time')
            ->find();

        if (!$config) return json_error('设备未注册');

        // 可选 token 校验
        if (!empty($config['api_token'])) {
            $reqToken = $this->request->header('X-Access-Token', '');
            if ($reqToken !== $config['api_token']) {
                return json_error('设备Token校验失败');
            }
        }

        // 注入配置信息
        $config['community_id'] = $config['community_id'] ?? 0;

        // 查找关联的设备记录
        $device = Db::name('access_device')
            ->where('device_sn', $deviceSn)
            ->whereNull('delete_time')
            ->find();
        if ($device) {
            $config['config_id'] = $config['id'];
            $config['device_id'] = $device['id'];
        }

        // 创建适配器处理
        $adapter = AccessFactory::create($config);
        $result = $adapter->onCardSwiped($input);

        // 更新设备心跳
        Db::name('access_device')
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

        Db::name('access_device')
            ->where('device_sn', $deviceSn)
            ->update([
                'last_heartbeat' => date('Y-m-d H:i:s'),
                'online'         => 1,
                'update_time'    => date('Y-m-d H:i:s'),
            ]);

        return json_success(['time' => date('Y-m-d H:i:s')], 'ok');
    }
}
