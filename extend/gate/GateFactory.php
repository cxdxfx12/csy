<?php
/**
 * 道闸适配器工厂
 */
namespace gate;

class GateFactory
{
    private static $brands = [
        'jieshun'   => '捷顺',
        'hikvision' => '海康威视',
        'dahua'     => '大华',
        'fuji'      => '富士智能',
        'hongmen'   => '红门',
        'generic'   => '通用协议',
    ];
    
    /** 获取所有支持的品牌列表 */
    public static function brands(): array
    {
        return self::$brands;
    }
    
    /** 创建适配器实例 */
    public static function create(array $config): GateAdapter
    {
        $brand = $config['brand'] ?? 'generic';
        
        switch ($brand) {
            case 'jieshun':   return new JieshunAdapter($config);
            case 'hikvision': return new HikvisionAdapter($config);
            case 'dahua':     return new DahuaAdapter($config);
            case 'fuji':      return new FujiAdapter($config);
            case 'hongmen':   return new HongmenAdapter($config);
            default:          return new GenericAdapter($config);
        }
    }
}
