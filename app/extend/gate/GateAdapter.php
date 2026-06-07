<?php
/**
 * 道闸品牌适配器 - 抽象基类
 * 所有品牌驱动必须继承此类并实现对应方法
 */
namespace app\extend\gate;

use think\facade\Db;

abstract class GateAdapter
{
    /** @var array 设备配置 */
    protected $config = [];
    
    /** @var string 品牌标识 */
    protected $brand = '';
    
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    /** 获取品牌名称 */
    abstract public function name(): string;
    
    /**
     * 车牌识别回调 — 道闸设备推送识别到的车牌
     * @param array $data { plate_number, direction:'in'|'out', image_url, timestamp }
     * @return array { action:'open'|'deny', reason, vehicle_info }
     */
    abstract public function onPlateRecognized(array $data): array;
    
    /**
     * 远程开闸
     * @return bool
     */
    abstract public function openGate(string $direction = 'in'): bool;
    
    /**
     * 查询道闸状态
     * @return array { online:bool, status:'open'|'closed', last_heartbeat }
     */
    abstract public function getStatus(): array;
    
    /**
     * 同步白名单到道闸设备（推送已登记车牌）
     * @param array $plates 车牌号列表
     * @return bool
     */
    abstract public function syncWhitelist(array $plates): bool;
    
    /**
     * 心跳检测 — 设备定期上报健康状态
     */
    protected function recordHeartbeat(): void
    {
        Db::name('gate_device')
            ->where('device_sn', $this->config['device_sn'] ?? '')
            ->update(['last_heartbeat' => date('Y-m-d H:i:s'), 'online' => 1]);
    }
    
    /**
     * 记录通行事件
     */
    protected function recordEvent(array $data): void
    {
        Db::name('gate_event')->insert([
            'config_id'     => $this->config['config_id'] ?? 0,
            'device_id'     => $this->config['device_id'] ?? 0,
            'community_id'  => $this->config['community_id'] ?? 0,
            'plate_number'  => $data['plate_number'] ?? '',
            'direction'     => $data['direction'] ?? 'in',
            'action'        => $data['action'] ?? 'unknown',
            'image_url'     => $data['image_url'] ?? '',
            'recognize_time' => $data['recognize_time'] ?? date('Y-m-d H:i:s'),
            'create_time'   => date('Y-m-d H:i:s'),
        ]);
    }
    
    /**
     * 在系统中匹配车辆信息
     */
    protected function matchVehicle(string $plateNumber, int $communityId): ?array
    {
        return Db::name('vehicle')
            ->where('plate_number', $plateNumber)
            ->where('community_id', $communityId)
            ->where('status', 1)
            ->whereNull('delete_time')
            ->find();
    }
    
    /**
     * 创建停车记录
     */
    protected function createParkingRecord(array $event): void
    {
        if ($event['direction'] === 'in') {
            Db::name('parking_record')->insert([
                'community_id'  => $this->config['community_id'] ?? 0,
                'plate_number'  => $event['plate_number'] ?? '',
                'space_id'      => $event['space_id'] ?? 0,
                'enter_time'    => $event['recognize_time'] ?? date('Y-m-d H:i:s'),
                'enter_image'   => $event['image_url'] ?? '',
                'create_time'   => date('Y-m-d H:i:s'),
            ]);
        } else {
            // 出场：更新最近的入场记录
            $record = Db::name('parking_record')
                ->where('plate_number', $event['plate_number'] ?? '')
                ->where('community_id', $this->config['community_id'] ?? 0)
                ->whereNull('exit_time')
                ->order('id', 'desc')
                ->find();
            if ($record) {
                $enterTime = strtotime($record['enter_time']);
                $exitTime = strtotime($event['recognize_time'] ?? 'now');
                $duration = max(0, ($exitTime - $enterTime) / 60);
                
                $fee = $this->calcFee($duration, $record['community_id'] ?? 0);
                
                Db::name('parking_record')
                    ->where('id', $record['id'])
                    ->update([
                        'exit_time'  => $event['recognize_time'] ?? date('Y-m-d H:i:s'),
                        'exit_image' => $event['image_url'] ?? '',
                        'duration'   => $duration,
                        'fee'        => $fee,
                        'update_time'=> date('Y-m-d H:i:s'),
                    ]);
            }
        }
    }
    
    /**
     * 计算停车费
     */
    protected function calcFee(float $durationMinutes, int $communityId): float
    {
        // 从费率规则表获取匹配规则
        $rule = Db::name('parking_fee_rule')
            ->where('community_id', $communityId)
            ->whereNull('delete_time')
            ->order('id', 'asc')
            ->find();
        
        if (empty($rule)) return 0;
        
        // 免费时长
        $freeMinutes = floatval($rule['free_minutes'] ?? 30);
        if ($durationMinutes <= $freeMinutes) return 0;
        
        $chargeMinutes = $durationMinutes - $freeMinutes;
        $unitPrice = floatval($rule['unit_price'] ?? 2);
        $unitDuration = intval($rule['unit_duration'] ?? 60);
        $dailyMax = floatval($rule['daily_max'] ?? 20);
        
        $fee = ceil($chargeMinutes / $unitDuration) * $unitPrice;
        
        // 日封顶
        if ($dailyMax > 0) {
            $days = max(1, ceil($durationMinutes / (24 * 60)));
            $fee = min($fee, $dailyMax * $days);
        }
        
        return $fee;
    }
}
