<?php
/**
 * 门禁适配器工厂
 */
namespace app\extend\access;

class AccessFactory
{
    private static $brands = [
        'zkteco'      => '中控智慧',
        'microengine' => '微耕',
        'hikvision'   => '海康门禁',
        'dahua'       => '大华门禁',
        'tongfang'    => '同方锐安',
        'peake'       => '披克',
        'generic'     => '通用协议',
    ];
    
    /** 获取所有支持的品牌列表 */
    public static function brands(): array
    {
        return self::$brands;
    }
    
    /** 创建适配器实例 */
    public static function create(array $config): AccessAdapter
    {
        $brand = $config['brand'] ?? 'generic';
        
        switch ($brand) {
            case 'zkteco':      return new ZktecoAdapter($config);
            case 'microengine': return new MicroengineAdapter($config);
            case 'hikvision':   return new HikvisionAccessAdapter($config);
            case 'dahua':       return new DahuaAccessAdapter($config);
            case 'tongfang':    return new TongfangAdapter($config);
            case 'peake':       return new PeakeAdapter($config);
            default:            return new GenericAccessAdapter($config);
        }
    }
}
