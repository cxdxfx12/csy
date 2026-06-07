<?php
/**
 * 门禁品牌适配器 - 抽象基类
 * 所有门禁品牌驱动必须继承此类并实现对应方法
 */
namespace app\extend\access;

use think\facade\Db;

abstract class AccessAdapter
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
     * 刷卡事件回调 — 门禁控制器推送刷卡事件
     * @param array $data { card_no, direction:'in'|'out', open_method, door_no, photo_url, event_time }
     * @return array { action:'open'|'deny', reason, holder_info }
     */
    abstract public function onCardSwiped(array $data): array;
    
    /**
     * 远程开门
     * @param int $doorNo 门序号
     * @return bool
     */
    abstract public function remoteOpen(int $doorNo = 1): bool;
    
    /**
     * 查询门禁控制器状态
     * @return array { online, door_status, last_heartbeat, total_users }
     */
    abstract public function getStatus(): array;
    
    /**
     * 同步白名单到门禁控制器（推送有效卡号列表）
     * @param array $cards [{card_no, holder_name, expire_date}]
     * @return bool
     */
    abstract public function syncWhitelist(array $cards): bool;
    
    /**
     * 远程锁门
     * @param int $doorNo
     * @return bool
     */
    abstract public function lockDoor(int $doorNo = 1): bool;
    
    // ========== 通用工具方法 ==========
    
    /** 记录心跳 */
    protected function recordHeartbeat(): void
    {
        Db::name('access_device')
            ->where('device_sn', $this->config['device_sn'] ?? '')
            ->update(['last_heartbeat' => date('Y-m-d H:i:s'), 'online' => 1]);
    }
    
    /** 记录刷卡通行事件 */
    protected function recordEvent(array $data): void
    {
        Db::name('access_event')->insert([
            'config_id'     => $this->config['config_id'] ?? 0,
            'device_id'     => $this->config['device_id'] ?? 0,
            'community_id'  => $this->config['community_id'] ?? 0,
            'card_no'       => $data['card_no'] ?? '',
            'holder_name'   => $data['holder_name'] ?? '',
            'door_name'     => $this->config['door_name'] ?? '',
            'direction'     => $data['direction'] ?? 'in',
            'open_method'   => $data['open_method'] ?? 'card',
            'action'        => $data['action'] ?? 'unknown',
            'reason'        => $data['reason'] ?? '',
            'photo_url'     => $data['photo_url'] ?? '',
            'event_time'    => $data['event_time'] ?? date('Y-m-d H:i:s'),
            'create_time'   => date('Y-m-d H:i:s'),
        ]);
    }
    
    /** 在系统中匹配门禁卡信息 */
    protected function matchCard(string $cardNo, int $communityId): ?array
    {
        return Db::name('access_card')
            ->where('card_no', $cardNo)
            ->where('community_id', $communityId)
            ->where('status', 1)
            ->whereNull('delete_time')
            ->find();
    }
    
    /** 校验卡是否有效 */
    protected function validateCard(?array $card): string
    {
        if (!$card) return '未登记卡号';
        if ($card['status'] != 1) {
            return $card['status'] == 0 ? '卡已挂失' : '卡已注销';
        }
        $now = date('Y-m-d');
        if (!empty($card['effective_date']) && $card['effective_date'] > $now) return '卡尚未生效';
        if (!empty($card['expire_date']) && $card['expire_date'] < $now) return '卡已过期';
        return 'ok';
    }
}
