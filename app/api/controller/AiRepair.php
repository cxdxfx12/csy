<?php
// AI智能报修助手 API（公开，无需登录）
namespace app\api\controller;

use app\BaseController;
use think\facade\Db;
use service\PushService;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AiRepair extends BaseController
{
    protected $noAuth = ['chat', 'submit', 'quickTypes', 'query'];

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

    // 工单状态标签
    private $statusLabels = [
        1 => '待派单',
        2 => '待接单',
        3 => '处理中',
        4 => '待验收',
        5 => '已完成',
        6 => '已关闭',
    ];

    // 进度步骤（用于可视化）
    private $progressSteps = ['待派单', '待接单', '处理中', '待验收', '已完成'];

    // 智能对话
    public function chat()
    {
        $msg = $this->request->post('message', '');
        $history = $this->request->post('history', []);

        if (empty(trim($msg))) {
            $reply = '您好！请描述您遇到的问题，我会帮您快速报修。比如："我家厨房水龙头漏水了"\n\n💡 输入工单号（DSR...）可查询处理进度。';
            $this->saveLog($msg, $reply, 'greet', '');
            return $this->success(['reply' => $reply, 'action' => 'greet']);
        }

        $msgLower = mb_strtolower($msg);

        // === 优先级1: 工单号查询（DSR开头） ===
        if (preg_match('/DSR\d+/', $msg, $matches)) {
            $orderNo = $matches[0];
            return $this->queryByOrderNo($orderNo, $msg);
        }

        // === 优先级2: 进度查询意图 ===
        $queryKeywords = ['查进度', '工单状态', '进度', '处理进度', '修好了吗', '怎么样了', '什么时候修', '报修进度', '我的报修', '工单查询', '查一下', '帮我查'];
        $isProgressQuery = false;
        foreach ($queryKeywords as $kw) {
            if (mb_strpos($msgLower, $kw) !== false) { $isProgressQuery = true; break; }
        }
        if ($isProgressQuery) {
            return $this->queryMyOrders($msg);
        }

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

        // 尝试关联登录业主，自动提取房产信息
        $ownerId = $this->getOwnerIdFromToken();
        $ownerData = [];
        $roomData = null;
        if ($ownerId > 0) {
            $owner = Db::name('owner')->where('id', $ownerId)->find();
            if ($owner) {
                $ownerData = $owner;
                $name = $owner['realname'] ?: $name;
                $phone = $phone ?: $owner['phone'] ?? '';
                // 自动提取业主绑定的房产信息
                $roomData = Db::name('room')
                    ->where('owner_id', $ownerId)
                    ->where('status', 1)
                    ->whereNull('delete_time')
                    ->find();
            }
        }

        $orderData = [
            'order_no'       => build_order_no('DSR'),
            'title'          => $title,
            'content'        => $content . ($location ? ' [位置: ' . $location . ']' : ''),
            'community_id'   => $ownerId > 0 ? ($ownerData['community_id'] ?? $communityId) : $communityId,
            'owner_id'       => $ownerId,
            'room_id'        => $roomData['id'] ?? 0,
            'reporter'       => $name,
            'reporter_phone' => $phone ?: '未提供',
            'source'         => 3, // 3=AI智能报修
            'status'         => 1,
            'create_time'    => date('Y-m-d H:i:s'),
        ];

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
            "\n💡 下次输入工单号或「查进度」即可跟踪处理状态，您也可以在「报修」页面查看全部工单。";


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

    // 查询工单进度（公开API）
    public function query()
    {
        $orderNo = $this->request->param('order_no', '');
        $phone   = $this->request->param('phone', '');

        if (empty($orderNo)) {
            return $this->error('请提供工单号');
        }

        $order = Db::name('repair_order')->where('order_no', $orderNo)->find();
        if (!$order) {
            return $this->error('工单不存在，请检查工单号');
        }

        // 可选：手机号验证归属
        if ($phone && $order['reporter_phone'] !== $phone) {
            return $this->error('手机号与工单不匹配');
        }

        $workerName = '';
        if ($order['assignee_id']) {
            $workerName = Db::name('repair_worker')->where('id', $order['assignee_id'])->value('name') ?? '';
        }

        return $this->success([
            'id'          => $order['id'],
            'order_no'    => $order['order_no'],
            'title'       => $order['title'] ?? '',
            'status'      => $order['status'],
            'status_text' => $this->statusLabels[$order['status']] ?? '未知',
            'worker_name' => $workerName,
            'reporter'    => $order['reporter'],
            'create_time' => $order['create_time'],
            'accept_time' => $order['accept_time'],
            'finish_time' => $order['finish_time'],
            'rating'      => $order['rating'] ?? 0,
            'content'     => $order['content'] ?? '',
        ]);
    }

    // 通过工单号查询并返回聊天回复
    private function queryByOrderNo($orderNo, $msg)
    {
        $order = Db::name('repair_order')->where('order_no', $orderNo)->find();
        if (!$order) {
            $reply = '❌ 未找到工单号 ' . $orderNo . '，请检查是否输入正确。' . "\n" . '💡 提示：工单号格式为 DSR...，提交成功时系统会自动生成。';
            $this->saveLog($msg, $reply, 'query_notfound', '');
            return $this->success(['reply' => $reply, 'action' => 'notfound']);
        }

        $workerName = '';
        if ($order['assignee_id']) {
            $workerName = Db::name('repair_worker')->where('id', $order['assignee_id'])->value('name') ?? '';
        }
        $statusText = $this->statusLabels[$order['status']] ?? '未知';

        // 可视化进度条
        $cur = max(0, min(4, (int)$order['status'] - 1));
        $bar = '';
        foreach ($this->progressSteps as $i => $s) {
            if ($i < $cur) $bar .= '🟢';
            elseif ($i == $cur) $bar .= '🔵';
            else $bar .= '⚪';
        }

        $reply = '📋 工单号：' . $order['order_no'] . "\n"
               . '📝 标题：' . ($order['title'] ?? '') . "\n"
               . '📊 状态：' . $statusText . "\n"
               . '➡️ 进度：' . $bar . "\n"
               . ($workerName ? '👷 维修师傅：' . $workerName . "\n" : '')
               . '🕐 提交时间：' . $order['create_time'] . "\n"
               . ($order['finish_time'] ? '✅ 完成时间：' . $order['finish_time'] . "\n" : '');

        if ((int)$order['status'] === 4) {
            $reply .= "\n💬 维修已完成，请验收并评价服务。";
        } elseif ((int)$order['status'] === 5) {
            $reply .= "\n⭐ 您已评价，感谢反馈！";
        }

        $this->saveLog($msg, $reply, 'query_order', '');
        return $this->success([
            'reply'   => $reply,
            'action'  => 'query_result',
            'tracked' => [
                'order_no'    => $order['order_no'],
                'title'       => $order['title'] ?? '',
                'status'      => $order['status'],
                'status_text' => $statusText,
                'worker_name' => $workerName,
            ]
        ]);
    }

    // 查询我的工单列表
    private function queryMyOrders($msg)
    {
        $ownerId = $this->getOwnerIdFromToken();

        if ($ownerId > 0) {
            $orders = Db::name('repair_order')
                ->where('owner_id', $ownerId)
                ->whereNull('delete_time')
                ->order('id', 'desc')
                ->limit(5)
                ->select()
                ->toArray();

            if (!empty($orders)) {
                $reply = '📋 您最近的报修工单：' . "\n\n";
                foreach ($orders as $o) {
                    $st = $this->statusLabels[$o['status']] ?? '未知';
                    $reply .= '🔹 ' . ($o['title'] ?? '无标题') . "\n"
                            . '   单号：' . $o['order_no'] . "\n"
                            . '   状态：' . $st . "\n"
                            . '   时间：' . $o['create_time'] . "\n\n";
                }
                $reply .= '💡 输入工单号可查看详细进度。';
                $this->saveLog($msg, $reply, 'my_orders', '');
                return $this->success([
                    'reply'  => $reply,
                    'action' => 'my_orders',
                    'orders' => array_map(function ($o) {
                        return [
                            'order_no'    => $o['order_no'],
                            'title'       => $o['title'] ?? '',
                            'status'      => $o['status'],
                            'status_text' => $this->statusLabels[$o['status']] ?? '未知',
                        ];
                    }, $orders),
                ]);
            }
        }

        if ($ownerId > 0) {
            $reply = '您目前没有报修记录。如需报修，请直接描述您遇到的问题，我会帮您生成报修单！';
        } else {
            $reply = '请提供您的工单号（如 DSR...），我帮您查询处理进度。' . "\n" . '💡 工单号在报修成功时会自动生成，您也可以登录后查看"我的报修"。';
        }
        $this->saveLog($msg, $reply, 'no_orders', '');
        return $this->success(['reply' => $reply, 'action' => 'no_orders']);
    }

    // 从JWT token获取owner_id（与BaseApi::auth保持一致）
    private function getOwnerIdFromToken()
    {
        $ownerId = 0;
        $token = $this->request->header('Authorization', '');
        if ($token) {
            $token = str_replace('Bearer ', '', $token);
            try {
                $jwtConfig = config('jwt');
                $payload = JWT::decode($token, new Key($jwtConfig['key'], $jwtConfig['algorithm']));
                $payload = (array)$payload;
                $ownerId = (int)($payload['sub'] ?? 0);
            } catch (\Exception $e) {
                // token无效，返回0
            }
        }
        return $ownerId;
    }

    // 保存对话记录
    private function saveLog($message, $reply, $action = 'chat', $repairType = '')
    {
        try {
            $ownerId = $this->getOwnerIdFromToken();
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
