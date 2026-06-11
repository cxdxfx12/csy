<?php
// AI助手管理（报修AI对话配置 + 聊天记录 + 知识库）
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class AiAssistant extends BaseAdmin
{
    // ==================== 配置管理 ====================

    public function config()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $typeKeywords = $data['type_keywords'] ?? null;
            $urgentKeywords = $data['urgent_keywords'] ?? null;
            $greeting = $data['greeting'] ?? null;
            $welcomeTips = $data['welcome_tips'] ?? null;

            $cfg = [];
            if ($typeKeywords !== null) $cfg['ai_type_keywords'] = $typeKeywords;
            if ($urgentKeywords !== null) $cfg['ai_urgent_keywords'] = $urgentKeywords;
            if ($greeting !== null) $cfg['ai_greeting'] = $greeting;
            if ($welcomeTips !== null) $cfg['ai_welcome_tips'] = $welcomeTips;

            foreach ($cfg as $key => $val) {
                $exist = Db::name('config')->where('key', $key)->find();
                if ($exist) {
                    Db::name('config')->where('key', $key)->update(['value' => $val, 'update_time' => date('Y-m-d H:i:s')]);
                } else {
                    Db::name('config')->insert(['key' => $key, 'value' => $val, 'name' => $key, 'remark' => 'AI助手配置', 'create_time' => date('Y-m-d H:i:s')]);
                }
            }
            return $this->success([], '配置保存成功');
        }

        // GET：读取配置
        $typeKeywords = Db::name('config')->where('key', 'ai_type_keywords')->value('value');
        $urgentKeywords = Db::name('config')->where('key', 'ai_urgent_keywords')->value('value');
        $greeting = Db::name('config')->where('key', 'ai_greeting')->value('value');
        $welcomeTips = Db::name('config')->where('key', 'ai_welcome_tips')->value('value');

        // 默认值
        if (empty($typeKeywords)) {
            $typeKeywords = json_encode([
                '水电' => ['漏水','水管','水龙头','下水道','马桶','堵塞','停水','跳闸','停电','灯泡','灯','开关','插座','电线','短路','电路','电表'],
                '空调' => ['空调','制冷','不热','不冷','暖风','通风','出风口','空调管','外机','压缩机'],
                '门窗' => ['门','窗','门锁','把手','钥匙','门禁','窗户','玻璃','铝合金','推拉','防盗门','门卡'],
                '墙面' => ['墙','裂缝','漏水','发霉','起皮','掉皮','墙纸','油漆','渗水','返潮'],
                '燃气' => ['煤气','天然气','燃气','灶','打不着','漏气','燃气表','气味'],
                '电梯' => ['电梯','困人','停运','抖动','异响','超时'],
                '安保' => ['监控','摄像头','门禁','可视','对讲','报警','消防','烟雾','灭火器'],
                '卫生' => ['垃圾','清扫','保洁','卫生','异味','蟑螂','老鼠','虫害'],
                '停车' => ['车位','停车','车库','道闸','车牌','充电桩'],
                '其他' => [],
            ], JSON_UNESCAPED_UNICODE);
        }
        if (empty($urgentKeywords)) {
            $urgentKeywords = json_encode(['紧急','马上','立刻','赶紧','快','爆','着火','漏气','触电','困人','坍塌','危机','严重'], JSON_UNESCAPED_UNICODE);
        }
        if (empty($greeting)) {
            $greeting = '您好！我是大圣智慧物业的AI报修助手 🤖\n请直接告诉我您遇到的问题，比如：\n• "厨房水龙头漏水"\n• "客厅空调不制冷"\n• "门锁打不开了"\n\n我会自动帮您生成报修单！';
        }

        return $this->success([
            'type_keywords' => json_decode($typeKeywords, true),
            'urgent_keywords' => json_decode($urgentKeywords, true),
            'greeting' => $greeting,
            'welcome_tips' => $welcomeTips ?? '',
        ]);
    }

    // ==================== 聊天记录 ====================

    public function chatHistory()
    {
        [$page, $limit] = $this->getPage();
        $keyword = $this->request->param('keyword', '');
        $dateFrom = $this->request->param('date_from', '');
        $dateTo = $this->request->param('date_to', '');

        $query = Db::name('ai_chat_log')->alias('l')
            ->leftJoin('owner o', 'o.id = l.owner_id')
            ->field('l.*, o.realname as owner_name, o.phone as owner_phone');

        if ($keyword) {
            $query->where(function($q) use ($keyword) {
                $q->where('l.message', 'like', "%{$keyword}%")
                  ->whereOr('l.reply', 'like', "%{$keyword}%")
                  ->whereOr('o.realname', 'like', "%{$keyword}%");
            });
        }
        if ($dateFrom) $query->where('l.create_time', '>=', $dateFrom);
        if ($dateTo) $query->where('l.create_time', '<=', $dateTo . ' 23:59:59');

        $total = $query->count();
        $list = $query->order('l.id', 'desc')->page($page, $limit)->select();

        return $this->success(['list' => $list, 'total' => $total]);
    }

    // ==================== 统计 ====================

    public function stats()
    {
        $today = date('Y-m-d');
        $weekStart = date('Y-m-d', strtotime('-7 days'));

        $totalChats = Db::name('ai_chat_log')->count();
        $todayChats = Db::name('ai_chat_log')->where('create_time', '>=', $today)->count();
        $weekChats = Db::name('ai_chat_log')->where('create_time', '>=', $weekStart)->count();

        // 每日对话趋势
        $trend = Db::name('ai_chat_log')
            ->field("DATE(create_time) as date, count(*) as cnt")
            ->where('create_time', '>=', $weekStart)
            ->group('DATE(create_time)')
            ->order('date', 'asc')
            ->select();

        // 报修类型名称映射
        $typeMap = [1 => '水电-水', 2 => '水电-电', 3 => '燃气', 4 => '门窗', 5 => '管道/墙面', 6 => '家电/空调', 7 => '网络', 8 => '其他'];

        // 报修类型统计（按 repair_order.type 分组）
        $byTypeRows = Db::name('repair_order')
            ->field("IFNULL(type,8) as type_id, count(*) as cnt")
            ->group('type_id')
            ->order('cnt', 'desc')
            ->select();

        // 组装带名称的类型统计 + 各状态数量
        $byType = [];
        foreach ($byTypeRows as $row) {
            $tid = (int)$row['type_id'];
            $name = $typeMap[$tid] ?? ('类型' . $tid);
            $byType[] = [
                'type_id'   => $tid,
                'type_name' => $name,
                'count'     => (int)$row['cnt'],
            ];
        }

        // 总报修单数 & 按状态分布
        $repairTotal = Db::name('repair_order')->count();
        $statusRows = Db::name('repair_order')
            ->field("status, count(*) as cnt")
            ->group('status')
            ->select();
        $statusNames = [
            0 => '待派单', 1 => '已接单', 2 => '维修中', 3 => '已完成',
            4 => '已评价', 5 => '已取消', -1 => '已关闭',
        ];
        $statusList = [];
        foreach ($statusRows as $row) {
            $sid = (int)$row['status'];
            $statusList[] = [
                'status'     => $sid,
                'status_name'=> $statusNames[$sid] ?? ('状态' . $sid),
                'count'      => (int)$row['cnt'],
            ];
        }

        // AI识别的报修类型（从 ai_chat_log 的 repair_type 字段）
        $aiTypeRows = Db::name('ai_chat_log')
            ->field("repair_type, count(*) as cnt")
            ->where('action', 'in', ['submit','confirm'])
            ->where('repair_type', '<>', '')
            ->whereNotNull('repair_type')
            ->group('repair_type')
            ->order('cnt', 'desc')
            ->select();
        $aiTypes = [];
        foreach ($aiTypeRows as $row) {
            $aiTypes[] = [
                'type'  => $row['repair_type'],
                'count' => (int)$row['cnt'],
            ];
        }

        return $this->success([
            'total_chats'   => $totalChats,
            'today_chats'   => $todayChats,
            'week_chats'    => $weekChats,
            'trend'         => $trend,
            'by_type'       => $byType,
            'repair_total'  => $repairTotal,
            'status_list'   => $statusList,
            'ai_types'      => $aiTypes,
        ]);
    }
}
