<?php
// AI智能报修助手 API（公开，无需登录）
namespace app\api\controller;

use app\BaseController;
use think\facade\Db;
use service\PushService;

class AiRepair extends BaseController
{
    protected $noAuth = ['chat', 'submit', 'quickTypes'];

    // 报修类型词库
    private $typeKeywords = [
        '水电' => ['漏水', '水管', '水龙头', '下水道', '马桶', '堵塞', '停水', '跳闸', '停电', '灯泡', '灯', '开关', '插座', '电线', '短路', '电路', '电表'],
        '空调' => ['空调', '制冷', '不热', '不冷', '暖风', '通风', '出风口', '空调管', '外机', '压缩机'],
        '门窗' => ['门', '窗', '门锁', '把手', '钥匙', '门禁', '窗户', '玻璃', '铝合金', '推拉', '防盗门', '门卡'],
        '墙面' => ['墙', '裂缝', '漏水', '发霉', '起皮', '掉皮', '墙纸', '油漆', '渗水', '返潮'],
        '燃气' => ['煤气', '天然气', '燃气', '灶', '打不着', '漏气', '燃气表', '气味'],
        '电梯' => ['电梯', '困人', '停运', '抖动', '异响', '超时'],
        '安保' => ['监控', '摄像头', '门禁', '可视', '对讲', '报警', '消防', '烟雾', '灭火器'],
        '卫生' => ['垃圾', '清扫', '保洁', '卫生', '异味', '蟑螂', '老鼠', '虫害'],
        '停车' => ['车位', '停车', '车库', '道闸', '车牌', '充电桩'],
        '其他' => [],
    ];

    // 紧急程度词库
    private $urgentKeywords = ['紧急', '马上', '立刻', '赶紧', '快', '爆', '着火', '漏气', '触电', '困人', '坍塌', '危机', '严重'];

    // 智能对话
    public function chat()
    {
        $msg = $this->request->post('message', '');
        $history = $this->request->post('history', []);

        if (empty(trim($msg))) {
            $reply = '您好！请描述您遇到的问题，我会帮您快速报修。比如："我家厨房水龙头漏水了"';
            $this->saveLog($msg, $reply, 'greet', '');
            return $this->success(['reply' => $reply, 'action' => 'greet']);
        }

        $msgLower = mb_strtolower($msg);

        // 问候/闲聊识别
        $greetings = ['你好', '您好', 'hi', 'hello', '在吗', '在不在'];
        foreach ($greetings as $g) {
            if (mb_strpos($msgLower, $g) !== false) {
                $reply = '您好！我是大圣智慧物业的AI报修助手 🤖\n请直接告诉我您遇到的问题，比如：\n• "厨房水龙头漏水"\n• "客厅空调不制冷"\n• "门锁打不开了"\n\n我会自动帮您生成报修单！';
                $this->saveLog($msg, $reply, 'greet', '');
                return $this->success([
                    'reply' => $reply,
                    'action' => 'greet'
                ]);
            }
        }

        // 分析报修类型
        $repairType = '其他';
        $maxScore = 0;
        foreach ($this->typeKeywords as $type => $keywords) {
            $score = 0;
            foreach ($keywords as $kw) {
                if (mb_strpos($msgLower, $kw) !== false) $score++;
            }
            if ($score > $maxScore) { $maxScore = $score; $repairType = $type; }
        }

        // 分析紧急程度
        $isUrgent = false;
        foreach ($this->urgentKeywords as $kw) {
            if (mb_strpos($msgLower, $kw) !== false) { $isUrgent = true; break; }
        }

        // 提取位置信息
        $location = '';
        $locKeywords = ['厨房', '卫生间', '客厅', '卧室', '阳台', '走廊', '电梯', '车库', '地下室', '楼道', '花园', '游泳池'];
        foreach ($locKeywords as $loc) {
            if (mb_strpos($msg, $loc) !== false) { $location = $loc; break; }
        }

        // 生成回复
        if ($maxScore >= 2 || ($maxScore >= 1 && $repairType !== '其他')) {
            $urgentTag = $isUrgent ? '【紧急】' : '';
            $locTag = $location ? '（' . $location . '）' : '';
            $typeHint = $repairType !== '其他' ? ' → 归类为【' . $repairType . '维修】' : '';

            $reply = '✅ 已识别您的报修需求：' . "\n" .
                '📋 类型：' . $urgentTag . $repairType . '维修' . $typeHint . "\n" .
                '📍 ' . ($location ?: '待确认') . "\n" .
                ($isUrgent ? '⚠️ 已标记为紧急工单，将优先处理！' . "\n" : '') .
                "\n" . '确认提交报修单吗？回复「确认」即可提交，或补充更多信息。';

            $this->saveLog($msg, $reply, 'confirm', $repairType);

            return $this->success([
                'reply'      => $reply,
                'action'     => 'confirm',
                'repairType' => $repairType,
                'isUrgent'   => $isUrgent,
                'location'   => $location,
            ]);
        }

        $reply = '我理解您遇到了问题。请再详细描述一下：\n• 哪里出了问题？（厨房/卫生间/客厅…）\n• 什么表现？（漏水/不亮/打不开…）\n• 紧急吗？\n\n描述越详细，我帮您处理越快！';
        $this->saveLog($msg, $reply, 'askMore', '');

        return $this->success([
            'reply' => $reply,
            'action' => 'askMore',
        ]);
    }

    // 快速提交报修单
    public function submit()
    {
        $title    = $this->request->post('title', '');
        $content  = $this->request->post('content', '');
        $type     = $this->request->post('repair_type', '其他');
        $isUrgent = $this->request->post('is_urgent', false);
        $location = $this->request->post('location', '');
        $phone    = $this->request->post('phone', '');
        $name     = $this->request->post('name', 'AI报修用户');

        if (empty(trim($title))) {
            return $this->error('请填写报修标题');
        }

        // 查找怡丰城小区（默认社区ID=1）
        $communityId = Db::name('community')->where('status', 1)->value('id') ?? 1;

        $orderData = [
            'order_no'       => build_order_no('DSR'),
            'title'          => $title,
            'content'        => $content . ($location ? ' [位置: ' . $location . ']' : ''),
            'community_id'   => $communityId,
            'reporter'       => $name,
            'reporter_phone' => $phone ?: '未提供',
            'source'         => 3, // 3=AI智能报修
            'status'         => $isUrgent ? 1 : 1, // 紧急也先=1待处理，但标记
            'create_time'    => date('Y-m-d H:i:s'),
        ];

        // 尝试匹配房间
        if ($location) {
            $room = Db::name('room')->where('room_number', 'like', '%' . $location . '%')
                ->where('community_id', $communityId)->find();
            if ($room) $orderData['room_id'] = $room['id'];
        }

        // 关联维修类型工人（尝试自动派单）
        $worker = Db::name('repair_worker')->where('status', 1)->order('RAND()')->find();
        if ($worker) {
            $orderData['assignee_id'] = $worker['id'];
            $orderData['status'] = 2; // 自动派单
        }

        $orderId = Db::name('repair_order')->insertGetId($orderData);

        $reply = '🎉 报修单已生成！' . "\n" .
            '📋 工单号：' . $orderData['order_no'] . "\n" .
            '📝 标题：' . $title . "\n" .
            '🔧 类型：' . $type . '维修' . "\n" .
            ($isUrgent ? '⚠️ 紧急标记已生效' . "\n" : '') .
            ($worker ? '👷 已自动派单给维修师傅，请保持电话畅通！' : '📞 客服将尽快与您联系确认。') . "\n" .
            "\n💡 您可以凭工单号随时查询处理进度。";

        // 记录提交日志
        $this->saveLog($title, $reply, 'submit', $type);

        // 触发多渠道推送
        $pushOrderData = array_merge($orderData, [
            'is_urgent'    => $isUrgent,
            'worker_name'  => $worker['name'] ?? null,
        ]);
        PushService::pushNewRepair($orderId, $pushOrderData, $worker['id'] ?? 0, $communityId);

        return $this->success([
            'reply'      => $reply,
            'order_no'   => $orderData['order_no'],
            'order_id'   => $orderId,
            'status'     => $worker ? '已派单' : '待处理',
            'worker'     => $worker['name'] ?? null,
        ]);
    }

    // 快速报修类型列表
    public function quickTypes()
    {
        return $this->success([
            ['id' => '水电', 'name' => '水电维修', 'icon' => '🔧', 'examples' => '漏水、跳闸、灯不亮'],
            ['id' => '空调', 'name' => '空调维修', 'icon' => '❄️', 'examples' => '不制冷、异响、漏水'],
            ['id' => '门窗', 'name' => '门窗维修', 'icon' => '🚪', 'examples' => '门锁坏、窗户关不上'],
            ['id' => '墙面', 'name' => '墙面维修', 'icon' => '🧱', 'examples' => '裂缝、发霉、渗水'],
            ['id' => '燃气', 'name' => '燃气维修', 'icon' => '🔥', 'examples' => '打不着火、漏气'],
            ['id' => '电梯', 'name' => '电梯故障', 'icon' => '🛗', 'examples' => '停运、困人、异响'],
            ['id' => '安保', 'name' => '安保消防', 'icon' => '🛡️', 'examples' => '监控坏、消防故障'],
            ['id' => '卫生', 'name' => '卫生保洁', 'icon' => '🧹', 'examples' => '垃圾堆积、异味'],
            ['id' => '停车', 'name' => '停车问题', 'icon' => '🅿️', 'examples' => '车位被占、道闸故障'],
        ]);
    }

    // 保存对话记录
    private function saveLog($message, $reply, $action = 'chat', $repairType = '')
    {
        try {
            $ownerId = 0;
            // API场景下session可能未初始化，通过request获取
            $token = $this->request->header('Authorization', '');
            if ($token) {
                $token = str_replace('Bearer ', '', $token);
                $owner = Db::name('owner')->where('token', $token)->find();
                if ($owner) $ownerId = $owner['id'];
            }
            Db::name('ai_chat_log')->insert([
                'owner_id'    => $ownerId,
                'message'     => trim($message),
                'reply'       => $reply,
                'action'      => $action,
                'repair_type' => $repairType,
                'create_time' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            // 记录失败不阻断主流程
        }
    }
}
