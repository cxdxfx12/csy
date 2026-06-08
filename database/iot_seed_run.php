<?php
/**
 * IoT 数据种子脚本
 * 自动为所有小区创建设备实例并生成模拟数据
 * 运行: php database/iot_seed_run.php
 */
namespace think;

// 加载框架
require __DIR__ . '/../vendor/autoload.php';

// 手动初始化应用（CLI模式）
$appPath = __DIR__ . '/../app/';
define('DS', DIRECTORY_SEPARATOR);

// 简易引导
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($k, $v) = explode('=', $line, 2);
            $k = trim($k); $v = trim($v);
            if (strpos($k, 'DATABASE.') === 0) {
                $_ENV[$k] = $v;
                putenv("$k=$v");
            }
        }
    }
}

// 加载 ThinkPHP 配置和数据库
$config = require __DIR__ . '/../config/database.php';
$dbConf = $config['connections'][$config['default']];

$host = $_ENV['DATABASE.HOSTNAME'] ?? $dbConf['hostname'] ?? '127.0.0.1';
$port = $_ENV['DATABASE.HOSTPORT'] ?? $dbConf['hostport'] ?? '3306';
$dbname = $_ENV['DATABASE.DATABASE'] ?? $dbConf['database'] ?? 'dasheng';
$user = $_ENV['DATABASE.USERNAME'] ?? $dbConf['username'] ?? 'root';
$pass = $_ENV['DATABASE.PASSWORD'] ?? $dbConf['password'] ?? '';
$prefix = $_ENV['DATABASE.PREFIX'] ?? $dbConf['prefix'] ?? 'ds_';

// PDO连接
$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
$pdo = new \PDO($dsn, $user, $pass, [
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
]);
echo "✓ 数据库连接成功：{$dbname}\n";

// 设备坐标配置（与 IotData.php 保持一致）
$deviceMap = [
    'feicui' => [
        'smoke' => [[-20,18,2.8],[0,22,2.8],[18,16,2.8],[-18,0,2.8],[0,4,2.8],[20,-2,2.8],[-22,-20,2.8],[-2,-24,2.8],[22,-18,2.8],[-10,10,2.8],[8,14,2.8],[-8,-10,2.8],[10,-14,2.8],[24,12,2.8]],
        'access' => [[-41,0,1.2],[-41,10,1.2],[-41,-10,1.2],[0,41,1.2],[15,41,1.2],[-15,41,1.2],[41,0,1.2],[41,-10,1.2],[0,-41,1.2]],
        'meter_w' => [[-18,18,0.8],[0,22,0.8],[18,16,0.8],[-18,0,0.8],[0,4,0.8],[20,-2,0.8],[-22,-20,0.8],[-2,-24,0.8],[22,-18,0.8]],
        'meter_e' => [[-18,18,1.6],[0,22,1.6],[18,16,1.6],[-18,0,1.6],[0,4,1.6],[20,-2,1.6],[-22,-20,1.6],[-2,-24,1.6],[22,-18,1.6]],
        'camera' => [[-42,-5,4.5],[-5,-42,4.5],[5,43,4.5],[43,8,4.5],[-42,15,4.5],[43,-15,4.5],[0,-42,4.5],[42,0,4.5]],
        'env'   => [[-25,25,2.0],[25,25,2.0],[0,0,2.0],[25,-25,2.0],[-25,-25,2.0]],
        'parking' => [[-35,-35,0.05],[-20,-35,0.05],[-5,-35,0.05],[10,-35,0.05],[25,-35,0.05],[35,-35,0.05],[-35,30,0.05],[-20,30,0.05],[-5,30,0.05],[10,30,0.05],[25,30,0.05],[35,30,0.05]],
        'streetlight' => [[-40,-30,6.0],[0,-30,6.0],[40,-30,6.0],[-40,0,6.0],[40,0,6.0],[-40,30,6.0],[0,30,6.0],[40,30,6.0]],
        'gas' => [[-20,18,0.6],[0,22,0.6],[18,16,0.6],[0,4,0.6],[20,-2,0.6],[-2,-24,0.6],[22,-18,0.6],[-10,10,0.6],[8,14,0.6]],
        'hydrant' => [[-41,-35,0.6],[41,-35,0.6],[-41,25,0.6],[41,25,0.6],[0,-35,0.6],[0,25,0.6]],
        'elevator' => [[-20,18,3.0],[0,22,3.0],[18,16,3.0],[20,-2,3.0],[-22,-20,3.0],[-2,-24,3.0],[22,-18,3.0]],
        'trash' => [[-38,-28,1.5],[20,-28,1.5],[-38,20,1.5],[20,20,1.5]],
    ],
    'yunqi' => [
        'smoke' => [[-44,-30,2.8],[-28,-30,2.8],[-12,-30,2.8],[4,-30,2.8],[20,-30,2.8],[36,-30,2.8],[-44,25,2.8],[-28,25,2.8],[-12,25,2.8],[4,25,2.8],[20,25,2.8],[36,25,2.8]],
        'access' => [[0,-47,1.2],[0,47,1.2],[-47,0,1.2],[47,0,1.2]],
        'meter_w' => [[-44,-30,0.8],[-28,-30,0.8],[-12,-30,0.8],[4,-30,0.8],[20,-30,0.8],[36,-30,0.8]],
        'meter_e' => [[-44,25,1.6],[-28,25,1.6],[-12,25,1.6],[4,25,1.6],[20,25,1.6],[36,25,1.6]],
        'camera' => [[-48,-10,4.5],[-48,15,4.5],[48,-10,4.5],[48,10,4.5],[0,-48,4.5],[0,48,4.5]],
        'env'   => [[-30,-20,2.0],[30,-20,2.0],[0,0,2.0],[-30,20,2.0],[30,20,2.0]],
        'parking' => [[-45,-40,0.05],[-25,-40,0.05],[0,-40,0.05],[25,-40,0.05],[45,-40,0.05],[-45,35,0.05],[-25,35,0.05],[0,35,0.05],[25,35,0.05],[45,35,0.05]],
        'streetlight' => [[-47,-35,6.0],[0,-35,6.0],[47,-35,6.0],[-47,35,6.0],[47,35,6.0]],
        'gas' => [[-44,-30,0.6],[-12,-30,0.6],[20,-30,0.6],[-44,25,0.6],[-12,25,0.6],[20,25,0.6]],
        'hydrant' => [[-47,-35,0.6],[47,-35,0.6],[-47,35,0.6],[47,35,0.6]],
        'elevator' => [[-44,-30,3.0],[-28,-30,3.0],[4,-30,3.0],[20,-30,3.0],[-44,25,3.0],[4,25,3.0]],
        'trash' => [[-45,-38,1.5],[25,-38,1.5],[-45,28,1.5],[25,28,1.5]],
    ],
    'zhongliang' => [
        'smoke' => [[-28,-15,2.8],[0,-12,2.8],[30,-18,2.8],[-28,10,2.8],[5,12,2.8],[30,8,2.8]],
        'access' => [[-40,-20,1.2],[-40,15,1.2],[43,-18,1.2],[43,12,1.2],[-5,-22,1.2],[-5,22,1.2]],
        'meter_w' => [[-28,-15,0.8],[0,-12,0.8],[30,-18,0.8],[-28,10,0.8],[5,12,0.8],[30,8,0.8]],
        'meter_e' => [[-28,-15,1.6],[0,-12,1.6],[30,-18,1.6],[-28,10,1.6],[5,12,1.6],[30,8,1.6]],
        'camera' => [[-42,-5,4.5],[-42,5,4.5],[45,0,4.5],[0,-24,4.5],[0,24,4.5]],
        'env'   => [[-15,-5,2.0],[15,-5,2.0],[0,0,2.0],[-15,10,2.0],[15,10,2.0]],
        'parking' => [[-35,-25,0.05],[-15,-25,0.05],[5,-25,0.05],[25,-25,0.05],[35,-25,0.05],[-35,20,0.05],[-15,20,0.05],[5,20,0.05],[25,20,0.05],[35,20,0.05]],
        'streetlight' => [[-38,-22,6.0],[0,-22,6.0],[40,-22,6.0],[-38,18,6.0],[40,18,6.0]],
        'gas' => [[-28,-15,0.6],[0,-12,0.6],[30,-18,0.6],[-28,10,0.6],[5,12,0.6],[30,8,0.6]],
        'hydrant' => [[-38,-22,0.6],[40,-22,0.6],[-38,18,0.6],[40,18,0.6]],
        'elevator' => [[-28,-15,3.0],[0,-12,3.0],[-28,10,3.0],[30,8,3.0]],
        'trash' => [[-35,-22,1.5],[20,-22,1.5],[-35,15,1.5],[20,15,1.5]],
    ],
    'shanshui' => [
        'smoke' => [[-30,-20,2.8],[-10,-18,2.8],[10,-22,2.8],[30,-15,2.8],[-20,10,2.8],[0,8,2.8],[20,12,2.8],[-30,25,2.8],[-10,28,2.8],[10,22,2.8],[30,20,2.8]],
        'access' => [[0,-41,1.2],[0,41,1.2],[-41,0,1.2]],
        'meter_w' => [[-30,-20,0.8],[-10,-18,0.8],[10,-22,0.8],[30,-15,0.8],[-20,10,0.8],[0,8,0.8],[20,12,0.8]],
        'meter_e' => [[-30,25,1.6],[-10,28,1.6],[10,22,1.6],[30,20,1.6]],
        'camera' => [[-42,0,4.5],[0,-42,4.5],[42,0,4.5],[0,42,4.5],[-20,-20,4.5],[20,20,4.5]],
        'env'   => [[-20,0,2.0],[20,0,2.0],[0,-20,2.0],[0,20,2.0],[0,0,2.0]],
        'parking' => [[-35,-35,0.05],[-15,-35,0.05],[5,-35,0.05],[25,-35,0.05],[-35,30,0.05],[-15,30,0.05],[5,30,0.05],[25,30,0.05]],
        'streetlight' => [[-40,-35,6.0],[0,-35,6.0],[40,-35,6.0],[-40,30,6.0],[40,30,6.0]],
        'gas' => [[-30,-20,0.6],[10,-22,0.6],[-20,10,0.6],[0,8,0.6],[20,12,0.6],[-30,25,0.6],[10,22,0.6]],
        'hydrant' => [[-40,-35,0.6],[40,-35,0.6],[-40,30,0.6],[40,30,0.6]],
        'elevator' => [[-30,-20,3.0],[-10,-18,3.0],[-20,10,3.0],[0,8,3.0],[-30,25,3.0],[-10,28,3.0]],
        'trash' => [[-38,-32,1.5],[18,-32,1.5],[-38,22,1.5],[18,22,1.5]],
    ],
    'yifeng' => [
        'smoke' => [[-32,-22,2.8],[-5,-20,2.8],[26,-18,2.8],[-36,2,2.8],[28,5,2.8],[44,0,2.8],[-34,28,2.8],[-5,30,2.8],[28,32,2.8]],
        'access' => [[-56,5,1.2],[46,5,1.2],[0,48,1.2],[0,-38,1.2]],
        'meter_w' => [[-32,-22,0.8],[-5,-20,0.8],[26,-18,0.8],[-36,2,0.8],[28,5,0.8],[44,0,0.8],[-34,28,0.8],[-5,30,0.8],[28,32,0.8]],
        'meter_e' => [[-32,-22,1.6],[-5,-20,1.6],[26,-18,1.6],[-36,2,1.6],[28,5,1.6],[44,0,1.6],[-34,28,1.6],[-5,30,1.6],[28,32,1.6]],
        'camera' => [[-58,5,4.5],[50,5,4.5],[-5,50,4.5],[-5,-42,4.5],[-5,5,4.5],[-30,5,4.5],[25,5,4.5]],
        'env'   => [[-30,-30,2.0],[30,-30,2.0],[0,0,2.0],[-30,35,2.0],[30,35,2.0],[-5,5,2.0]],
        'parking' => [[-40,-35,0.05],[-15,-35,0.05],[10,-35,0.05],[30,-35,0.05],[-40,32,0.05],[-15,32,0.05],[10,32,0.05],[30,32,0.05]],
        'streetlight' => [[-50,-35,6.0],[0,-35,6.0],[42,-35,6.0],[-50,32,6.0],[42,32,6.0]],
        'gas' => [[-32,-22,0.6],[-5,-20,0.6],[26,-18,0.6],[-36,2,0.6],[28,5,0.6],[-34,28,0.6],[-5,30,0.6],[28,32,0.6]],
        'hydrant' => [[-50,-35,0.6],[42,-35,0.6],[-50,32,0.6],[42,32,0.6]],
        'elevator' => [[-32,-22,3.0],[-5,-20,3.0],[-36,2,3.0],[28,5,3.0],[-34,28,3.0],[-5,30,3.0]],
        'trash' => [[-48,-32,1.5],[20,-32,1.5],[-48,25,1.5],[20,25,1.5]],
    ],
];

// 设备类型到 protocol_id 的映射（随机搭配）
$typeProtocols = [
    'smoke' => [7,6,9],           // LoRaWAN, Zigbee, NB-IoT
    'access' => [3,11,4,12],       // HTTP, WiFi, Modbus, RS-485
    'meter_w' => [7,9,4,1],        // LoRaWAN, NB-IoT, Modbus, MQTT
    'meter_e' => [7,9,4,1,12],     // LoRaWAN, NB-IoT, Modbus, MQTT, RS-485
    'camera' => [3,11,13],         // HTTP, WiFi, 4G Cat.1
    'env' => [7,6,8,4],            // LoRaWAN, Zigbee, BLE, Modbus
    'parking' => [7,9,8],          // LoRaWAN, NB-IoT, BLE
    'streetlight' => [7,6,9,8],    // LoRaWAN, Zigbee, NB-IoT, BLE
    'gas' => [6,7,9],              // Zigbee, LoRaWAN, NB-IoT
    'hydrant' => [7,9],            // LoRaWAN, NB-IoT
    'elevator' => [3,1,4,10],      // HTTP, MQTT, Modbus, OPC UA
    'trash' => [7,9,6],            // LoRaWAN, NB-IoT, Zigbee
];

// 模拟值生成函数
function simValue($type, $i = 0) {
    $r = rand(0, 100);
    switch ($type) {
        case 'smoke': return rand(50, 200) . ' ppm';
        case 'access': return '正常 · ' . rand(120, 580) . '次通行';
        case 'meter_w': return number_format(rand(2350, 12800) / 100, 1) . ' 吨';
        case 'meter_e': return number_format(rand(4800, 32000) / 100, 1) . ' kWh';
        case 'camera': return '在线 · 人脸抓拍' . rand(420, 3200) . '次';
        case 'env': return rand(18, 35) . '°C / ' . rand(40, 85) . '%';
        case 'parking': return ($r < 65 ? '占用' : '空闲') . ' · ' . rand(1, 480) . '分钟';
        case 'streetlight': return '亮度' . rand(30, 100) . '% · ' . number_format(rand(50, 400) / 10, 1) . 'W';
        case 'gas': return number_format(rand(120, 850) / 10, 1) . ' m³';
        case 'hydrant': return number_format(rand(25, 75) / 100, 2) . ' MPa';
        case 'elevator': return rand(1, 32) . '层 · ' . ($r < 90 ? '运行正常' : '等待维护');
        case 'trash': return rand(15, 95) . '% · ' . ($r > 80 ? '即将满溢' : '正常');
        default: return '正常';
    }
}

// 告警消息
function alarmMsg($type) {
    $msgs = [
        'smoke' => ['烟雾浓度超标', '疑似火警', '探测器触发预警'],
        'access' => ['门禁异常开启', '多次验证失败', '陌生人尾随告警'],
        'meter_w' => ['用水量异常激增', '管道泄漏疑似'],
        'meter_e' => ['用电负荷过载', '线路异常'],
        'camera' => ['移动侦测告警', '区域入侵检测', '异常行为识别'],
        'env' => ['温度异常偏高', '湿度超标', '空气质量差'],
        'parking' => ['车位占用异常', '车牌识别失败'],
        'streetlight' => ['灯具故障', '电路异常'],
        'gas' => ['燃气浓度超标', '疑似泄漏告警'],
        'hydrant' => ['水压过低告警', '消防栓异常位移'],
        'elevator' => ['电梯困人告警', '平层异常', '变频器故障'],
        'trash' => ['垃圾桶满溢告警', '异味超标'],
    ];
    $pool = $msgs[$type] ?? ['设备告警'];
    return $pool[array_rand($pool)];
}

// ============================================================
// 主流程
// ============================================================
echo "========== IoT 种子数据导入 ==========\n\n";

// 1. 执行建表 SQL
echo "[1/4] 创建数据表...\n";
$sql = file_get_contents(__DIR__ . '/iot_tables.sql');
$statements = array_filter(array_map('trim', explode(';', $sql)));
foreach ($statements as $stmt) {
    if (!empty($stmt) && strpos($stmt, '--') !== 0) {
        try { $pdo->exec($stmt); } catch (\Exception $e) {}
    }
}
echo "  ✓ 表结构已就绪\n\n";

// 2. 清空并插入协议和设备类型
echo "[2/4] 导入协议和设备类型...\n";
$pdo->exec("DELETE FROM `{$prefix}iot_device_data`");
$pdo->exec("DELETE FROM `{$prefix}iot_device`");
$pdo->exec("DELETE FROM `{$prefix}iot_device_type`");
$pdo->exec("DELETE FROM `{$prefix}iot_protocol`");

$seedSql = file_get_contents(__DIR__ . '/iot_seed.sql');
$seedStmts = array_filter(array_map('trim', explode(';', $seedSql)));
foreach ($seedStmts as $stmt) {
    if (!empty($stmt) && strpos($stmt, '--') !== 0) {
        try { $pdo->exec($stmt); } catch (\Exception $e) {
            echo "  ⚠ 跳过: " . substr($e->getMessage(), 0, 80) . "\n";
        }
    }
}
echo "  ✓ 协议和设备类型已导入\n\n";

// 3. 创建设备实例
echo "[3/4] 创建设备实例...\n";
$pdo->exec("DELETE FROM `{$prefix}iot_device`");

// 获取所有小区
$communities = $pdo->query("SELECT id, code, name FROM `{$prefix}community` WHERE status=1")->fetchAll();
$communityIds = [];
foreach ($communities as $c) {
    $communityIds[$c['code']] = $c['id'];
}

// 获取所有设备类型
$types = $pdo->query("SELECT id, code, y_height FROM `{$prefix}iot_device_type` WHERE status=1")->fetchAll();
$typeIds = [];
$typeHeights = [];
foreach ($types as $t) {
    $typeIds[$t['code']] = $t['id'];
    $typeHeights[$t['code']] = $t['y_height'];
}

$insert = $pdo->prepare(
    "INSERT INTO `{$prefix}iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW(),?,?,?)"
);

$totalDevices = 0;
$deviceIds = [];

foreach ($deviceMap as $cid => $typePositions) {
    if (!isset($communityIds[$cid])) {
        echo "  ⚠ 小区编码 '{$cid}' 在数据库中不存在，跳过\n";
        continue;
    }
    $comId = $communityIds[$cid];
    $comName = '';
    foreach ($communities as $c) { if ($c['code'] === $cid) { $comName = $c['name']; break; } }
    echo "  处理: {$comName} ({$cid})\n";

    foreach ($typePositions as $type => $positions) {
        if (!isset($typeIds[$type])) continue;
        $dtId = $typeIds[$type];
        $protocols = $typeProtocols[$type] ?? [7];
        
        foreach ($positions as $i => $pos) {
            $x = $pos[0]; $z = $pos[1];
            $y = $typeHeights[$type] ?? ($pos[2] ?? 1.5);
            $code = 'DEV_' . strtoupper($cid) . '_' . strtoupper($type) . '_' . sprintf('%03d', $i+1);
            $name = '[' . $comName . '] ' . ucfirst($type) . ' #' . ($i+1);
            $protoId = $protocols[array_rand($protocols)];
            $floor = rand(1, 32);
            $building = chr(65 + rand(0, 25)) . '栋';
            $location = $building . ' ' . $floor . '层';
            $fw = 'v' . rand(1, 3) . '.' . rand(0, 9) . '.' . rand(0, 9);
            $bat = rand(65, 100);

            try {
                $insert->execute([$comId, $dtId, $protoId, $name, $code, $x, $y, $z, $location, $floor, $building, '2024-0'.rand(1,9).'-'.rand(10,28), $fw, $bat, 1]);
                $devId = $pdo->lastInsertId();
                $deviceIds[$code] = $devId;
                $totalDevices++;
            } catch (\Exception $e) {
                // 重复跳过
            }
        }
    }
}
echo "  ✓ 共创建 {$totalDevices} 个设备实例\n\n";

// 4. 生成模拟数据
echo "[4/4] 生成模拟数据...\n";
$pdo->exec("DELETE FROM `{$prefix}iot_device_data`");

// 生成每小时1条的历史数据（最近24小时）
$now = time();
$dataInsert = $pdo->prepare(
    "INSERT INTO `{$prefix}iot_device_data` (device_id, value, raw_value, unit, is_online, device_status, alarm_msg, data_source, data_time) VALUES (?,?,?,?,?,?,?,?,?)"
);

// 从 device 表获取所有设备
$allDevices = $pdo->query(
    "SELECT d.id, d.code as device_code, d.community_id, dt.code as type_code 
     FROM `{$prefix}iot_device` d 
     JOIN `{$prefix}iot_device_type` dt ON d.device_type_id = dt.id
     WHERE d.status = 1"
)->fetchAll();

$dataCount = 0;
// 对每个设备生成最近24小时 + 当前模拟数据
foreach ($allDevices as $dev) {
    $devId = $dev['id'];
    $type = $dev['type_code'];
    $online = rand(1, 100) <= 95 ? 1 : 0;
    
    for ($h = 0; $h <= 24; $h++) {
        $dt = date('Y-m-d H:i:s', $now - $h * 3600);
        $status = 'normal';
        if ($online == 0) {
            $status = 'offline';
        } elseif (rand(1, 100) <= 3) {
            $status = 'alarm';
        } elseif (rand(1, 100) <= 8) {
            $status = 'warning';
        }
        $raw = simValue($type, $devId + $h);
        $alarm = '';
        if ($status === 'alarm') {
            $alarm = alarmMsg($type);
        }
        $val = null;
        preg_match('/[\d.]+/', $raw, $m);
        if ($m) $val = (float)$m[0];

        $unit = '';
        if (strpos($raw, 'ppm') !== false) $unit = 'ppm';
        elseif (strpos($raw, '°C') !== false) $unit = '°C/%';
        elseif (strpos($raw, 'MPa') !== false) $unit = 'MPa';
        elseif (strpos($raw, 'm³') !== false) $unit = 'm³';
        elseif (strpos($raw, 'kWh') !== false) $unit = 'kWh';
        elseif (strpos($raw, '吨') !== false) $unit = '吨';
        elseif (strpos($raw, '%') !== false) $unit = '%';

        try {
            $dataInsert->execute([$devId, $val, $raw, $unit, $online, $status, $alarm, ($h==0)?'realtime':'history', $dt]);
            $dataCount++;
        } catch (\Exception $e) {}
    }
}
echo "  ✓ 共生成 {$dataCount} 条数据记录\n\n";

echo "========== 全部完成! ==========\n";
echo "设备表: " . $totalDevices . " 个设备\n";
echo "数据表: " . $dataCount . " 条记录\n";
echo "协议表: " . $pdo->query("SELECT COUNT(*) FROM `{$prefix}iot_protocol`")->fetchColumn() . " 种协议\n";
echo "设备类型表: " . $pdo->query("SELECT COUNT(*) FROM `{$prefix}iot_device_type`")->fetchColumn() . " 种类型\n";
