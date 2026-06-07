<?php
/**
 * 监控适配器工厂
 */
namespace app\extend\surveillance;

class SurveillanceFactory
{
    private static $brands = [
        'hikvision' => ['name' => '海康威视', 'protocol' => 'ISAPI', 'port' => 80],
        'dahua'     => ['name' => '大华',     'protocol' => 'HTTP API', 'port' => 80],
        'uniview'   => ['name' => '宇视',     'protocol' => 'HTTP API', 'port' => 80],
        'tiandy'    => ['name' => '天地伟业', 'protocol' => 'ONVIF/HTTP', 'port' => 80],
        'xiongmai'  => ['name' => '雄迈',     'protocol' => 'HTTP API', 'port' => 34567],
        'generic'   => ['name' => '通用ONVIF', 'protocol' => 'ONVIF', 'port' => 80],
    ];

    /** 获取所有支持的品牌列表 */
    public static function brands(): array
    {
        return self::$brands;
    }

    /** 创建适配器实例 */
    public static function create(array $config): SurveillanceAdapter
    {
        $brand = $config['brand'] ?? 'generic';

        return match ($brand) {
            'hikvision' => new HikvisionNvrAdapter($config),
            'dahua'     => new DahuaNvrAdapter($config),
            'uniview'   => new UniviewNvrAdapter($config),
            'tiandy'    => new TiandyNvrAdapter($config),
            'xiongmai'  => new XiongmaiNvrAdapter($config),
            default     => new GenericNvrAdapter($config),
        };
    }
}
