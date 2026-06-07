<?php
/**
 * 硬件设备统一工厂 — 根据设备类型+品牌创建对应协议适配器
 */
namespace app\extend\device;

class DeviceFactory
{
    /** 支持的设备类型 */
    private static $types = [
        'gateway'      => ['label' => '智能网关',       'protocols' => ['mqtt', 'http', 'tcp', 'coap']],
        'access_ctrl'  => ['label' => '门禁控制器',     'protocols' => ['http', 'tcp', 'modbus', 'websocket']],
        'camera'       => ['label' => '监控摄像头',     'protocols' => ['rtsp', 'onvif', 'sdk', 'gb28181']],
        'sensor'       => ['label' => '环境传感器',     'protocols' => ['mqtt', 'modbus', 'zigbee', 'lorawan']],
        'fire'         => ['label' => '消防设备',       'protocols' => ['modbus', 'snmp', 'mqtt', 'http']],
        'gate'         => ['label' => '道闸设备',       'protocols' => ['http', 'tcp', 'modbus', 'sdk']],
        'charger'      => ['label' => '充电桩',         'protocols' => ['ocpp', 'modbus', 'http', 'tcp']],
        'water_meter'  => ['label' => '水表采集器',     'protocols' => ['modbus', 'nb-iot', 'lora', 'mqtt']],
        'power_meter'  => ['label' => '电表采集器',     'protocols' => ['modbus', 'dlms', 'mqtt', 'http']],
        'elevator'     => ['label' => '电梯控制器',     'protocols' => ['modbus', 'can', 'http', 'sdk']],
        'intercom'     => ['label' => '可视对讲',       'protocols' => ['sip', 'http', 'sdk', 'tcp']],
        'face_device'  => ['label' => '人脸识别终端',   'protocols' => ['http', 'sdk', 'tcp']],
    ];

    /**
     * 获取所有支持的设备类型列表
     * @return array [type_key => label]
     */
    public static function types(): array
    {
        $result = [];
        foreach (self::$types as $key => $info) {
            $result[$key] = $info['label'];
        }
        return $result;
    }

    /**
     * 获取某类型支持的协议列表
     */
    public static function protocols(string $type): array
    {
        return self::$types[$type]['protocols'] ?? [];
    }

    /**
     * 根据硬件设备记录创建对应协议适配器
     * @param array $deviceRow ds_device 表的一行记录（或关联配置）
     * @return DeviceAdapter
     */
    public static function create(array $deviceRow): DeviceAdapter
    {
        $deviceType = strtolower(trim($deviceRow['device_type'] ?? ''));
        // 兼容中文名映射到 key
        $typeMap = [
            '智能网关'     => 'gateway',
            '门禁控制器'   => 'access_ctrl',
            '监控摄像头'   => 'camera',
            '环境传感器'   => 'sensor',
            '消防设备'     => 'fire',
            '道闸设备'     => 'gate',
            '充电桩'       => 'charger',
            '水表采集器'   => 'water_meter',
            '电表采集器'   => 'power_meter',
            '电梯控制器'   => 'elevator',
            '电梯'         => 'elevator',
            '可视对讲'     => 'intercom',
            '人脸识别终端' => 'face_device',
        ];
        $typeKey = $typeMap[$deviceType] ?: $deviceType;

        switch ($typeKey) {
            case 'gateway':     return new GatewayAdapter($deviceRow);
            case 'access_ctrl': return new AccessCtrlAdapter($deviceRow);
            case 'camera':      return new CameraDeviceAdapter($deviceRow);
            case 'sensor':      return new SensorAdapter($deviceRow);
            case 'fire':        return new FireAdapter($deviceRow);
            case 'gate':        return new GateDeviceAdapter($deviceRow);
            case 'charger':     return new ChargerAdapter($deviceRow);
            case 'water_meter': return new WaterMeterAdapter($deviceRow);
            case 'power_meter': return new PowerMeterAdapter($deviceRow);
            case 'elevator':    return new ElevatorAdapter($deviceRow);
            case 'intercom':    return new IntercomAdapter($deviceRow);
            case 'face_device': return new FaceDeviceAdapter($deviceRow);
            default:            return new GenericDeviceAdapter($deviceRow);
        }
    }

    /**
     * 根据关联业务类型获取对应的业务适配器
     * 例如 ref_type='gate' 时返回 GateFactory 创建的门闸适配器
     */
    public static function createBusiness(string $refType, int $refId, int $communityId)
    {
        switch ($refType) {
            case 'gate':
                $config = Db::name('gate_device')->where('id', $refId)->where('community_id', $communityId)->find();
                if ($config && !empty($config['brand'])) {
                    $gc = Db::name('gate_config')->where('id', $config['config_id'] ?? 0)->find();
                    if ($gc) $config = array_merge($config, $gc);
                    return \app\extend\gate\GateFactory::create($config);
                }
                break;
            case 'access':
                $config = Db::name('access_device')->where('id', $refId)->where('community_id', $communityId)->find();
                if ($config && !empty($config['brand'])) {
                    $ac = Db::name('access_config')->where('id', $config['config_id'] ?? 0)->find();
                    if ($ac) $config = array_merge($config, $ac);
                    return \app\extend\access\AccessFactory::create($config);
                }
                break;
            case 'camera':
                $config = Db::name('surveillance_device')
                    ->alias('sd')
                    ->leftJoin('surveillance_channel sc', 'sc.device_id = sd.id')
                    ->where('sd.id', $refId)
                    ->field('sd.*, sc.channel_name, sc.brand, sc.api_url, sc.stream_url, sc.auth_token')
                    ->find();
                if (!empty($config)) {
                    return \app\extend\surveillance\SurveillanceFactory::create($config);
                }
                break;
        }
        return null;
    }

    /**
     * 设备类型中文 → 英文 key 映射
     */
    public static function typeToKey(string $cnName): string
    {
        $map = [
            '智能网关'=>'gateway','门禁控制器'=>'access_ctrl','监控摄像头'=>'camera',
            '环境传感器'=>'sensor','消防设备'=>'fire','道闸设备'=>'gate',
            '充电桩'=>'charger','水表采集器'=>'water_meter','电表采集器'=>'power_meter',
            '电梯'=>'elevator','电梯控制器'=>'elevator','可视对讲'=>'intercom','人脸识别终端'=>'face_device'
        ];
        return $map[$cnName] ?: $cnName;
    }

    /**
     * 设备类型英文 key → 中文
     */
    public static function keyToLabel(string $key): string
    {
        foreach (self::$types as $k => $info) {
            if ($k === $key || $info['label'] === $key) return $info['label'];
        }
        return $key;
    }
}
