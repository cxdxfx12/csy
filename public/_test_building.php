<?php
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', __DIR__ . DS . '..' . DS);
define('APP_PATH', ROOT_PATH . 'app' . DS);
define('RUNTIME_PATH', ROOT_PATH . 'runtime' . DS);
define('CONFIG_PATH', ROOT_PATH . 'config' . DS);

$_ENV['DATABASE.HOSTNAME'] = '127.0.0.1';
$_ENV['DATABASE.DATABASE'] = 'dasheng';
$_ENV['DATABASE.USERNAME'] = 'root';
$_ENV['DATABASE.PASSWORD'] = 'cxdxfx12';
$_ENV['DATABASE.HOSTPORT'] = '3306';
$_ENV['DATABASE.PREFIX'] = 'ds_';

function env($k, $d = null) { return $_ENV[$k] ?? $d; }
function config($k, $d = null) {
    static $c = []; $p = explode('.', $k); $f = CONFIG_PATH . $p[0] . '.php';
    if (!isset($c[$p[0]]) && file_exists($f)) $c[$p[0]] = require $f;
    $v = $c[$p[0]] ?? []; for ($i = 1; $i < count($p); $i++) { if (is_array($v) && isset($v[$p[$i]])) $v = $v[$p[$i]]; else return $d; }
    return $v;
}

require ROOT_PATH . 'vendor' . DS . 'autoload.php';
require ROOT_PATH . 'app' . DS . 'common.php';

use think\facade\Db;

echo "<pre>";

// Test 1: count without join
echo "=== Test 1: count without join ===\n";
$c = Db::name('building')->alias('b')->whereRaw('b.delete_time IS NULL')->count();
echo "Result: $c\n\n";

// Test 2: count with join  
echo "=== Test 2: count with join ===\n";
$c = Db::name('building')->alias('b')
    ->leftJoin('community c', 'c.id = b.community_id')
    ->whereRaw('b.delete_time IS NULL')
    ->count();
echo "Result: $c\n\n";

// Test 3: select
echo "=== Test 3: select ===\n";
$r = Db::name('building')->alias('b')
    ->leftJoin('community c', 'c.id = b.community_id')
    ->field('b.*, c.name as community_name')
    ->whereRaw('b.delete_time IS NULL')
    ->order('b.sort', 'asc')
    ->page(1, 15)
    ->select();
echo "Rows: " . count($r) . "\n";
print_r($r);

// Test 4: where('b.delete_time', null) count WITHOUT join
echo "\n=== Test 4: where('b.delete_time', null) no join ===\n";
$c = Db::name('building')->alias('b')->where('b.delete_time', null)->count();
echo "Result: $c\n";

// Test 5: where('b.delete_time', null) count WITH join
echo "\n=== Test 5: where('b.delete_time', null) with join ===\n";
$c = Db::name('building')->alias('b')
    ->leftJoin('community c', 'c.id = b.community_id')
    ->where('b.delete_time', null)
    ->count();
echo "Result: $c\n";

// Test 6: fetchSql
echo "\n=== Test 6: SQL with whereRaw ===\n";
$sql = Db::name('building')->alias('b')
    ->leftJoin('community c', 'c.id = b.community_id')
    ->whereRaw('b.delete_time IS NULL')
    ->fetchSql(true)->select();
echo "$sql\n";

echo "\n=== Test 7: SQL with where(field, null) ===\n";
$sql = Db::name('building')->alias('b')
    ->leftJoin('community c', 'c.id = b.community_id')
    ->where('b.delete_time', null)
    ->fetchSql(true)->select();
echo "$sql\n";
