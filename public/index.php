<?php
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', __DIR__ . DS . '..' . DS);
define('APP_PATH', ROOT_PATH . 'app' . DS);
define('RUNTIME_PATH', ROOT_PATH . 'runtime' . DS);
define('CONFIG_PATH', ROOT_PATH . 'config' . DS);

// 数据库和JWT配置从 .env 文件加载，此处不再硬编码
// 如果 .env 不存在，loadEnv() 会使用 config/database.php 中的默认值

require ROOT_PATH . 'vendor' . DS . 'autoload.php';
require ROOT_PATH . 'app' . DS . 'common.php';

function loadEnv($file) {
    if (!file_exists($file)) return;
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
    if (strpos($line, '[') === 0) { $section = strtolower(trim($line, '[]')); continue; }
    if (!isset($section)) $section = '';
    if (strpos($line, '=') === false) continue;
    list($key, $val) = explode('=', $line, 2);
    $_ENV[($section ? $section . '.' : '') . strtolower(trim($key))] = trim($val);
    }
}
loadEnv(ROOT_PATH . '.env');

function env($key, $default = null) { return $_ENV[$key] ?? $default; }
function config($key, $default = null) {
    static $configs = [];
    $parts = explode('.', $key);
    $file = CONFIG_PATH . $parts[0] . '.php';
    if (!isset($configs[$parts[0]]) && file_exists($file)) $configs[$parts[0]] = require $file;
    $val = $configs[$parts[0]] ?? [];
    for ($i = 1; $i < count($parts); $i++) {
        if (is_array($val) && isset($val[$parts[$i]])) $val = $val[$parts[$i]]; else return $default;
    }
    return $val;
}

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type,Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$req = new app\Request();
set_request($req);
$uri = $_SERVER['REQUEST_URI'];
$path = parse_url((string)$uri, PHP_URL_PATH) ?? '';
$path = trim($path, '/');
if (strpos($path, 'api.php/') === 0) $path = substr($path, 8);
if (strpos($path, 'index.php/') === 0) $path = substr($path, 10);
if (strpos($path, 'index.php') === 0) $path = substr($path, 9);
// admin Vue 端直接以 /api 作为 baseURL，路径如 /api/admin/login
// 仅在第二段是 admin/staff/manager 时剥离 api/ 前缀
// api 模块自身的路由（如 api/repair/list）保留
$knownShowModules = ['admin', 'staff', 'manager'];
if (strpos($path, 'api/') === 0) {
    $segments = explode('/', $path);
    if (count($segments) >= 2 && in_array($segments[1], $knownShowModules)) {
        $path = substr($path, 4); // 剥离前缀 'api/'
    }
}
$path = ltrim($path, '/');
$requestMethod = strtoupper($_SERVER['REQUEST_METHOD']);

// =========== 加载路由定义（带缓存） ===========
function loadRoutes() {
    $cacheFile = RUNTIME_PATH . 'routes.cache';
    $cacheTime = file_exists($cacheFile) ? filemtime($cacheFile) : 0;
    
    // 检查路由文件是否有更新
    $routeFiles = glob(ROOT_PATH . 'route' . DS . '*.php');
    $needRefresh = false;
    foreach ($routeFiles as $file) {
        if (filemtime($file) > $cacheTime) { $needRefresh = true; break; }
    }
    // 也检查 index.php 自身
    if (filemtime(__FILE__) > $cacheTime) { $needRefresh = true; }
    
    if (!$needRefresh && $cacheTime > 0) {
        $cached = @include $cacheFile;
        if (is_array($cached)) return $cached;
    }
    
    $routes = [];
    foreach ($routeFiles as $file) {
        $content = file_get_contents($file);
        // 逐行解析，处理 Route::group() 前缀
        $lines = explode("\n", $content);
        $groupStack = [];  // 栈: 每个元素是 'prefix/' 
        foreach ($lines as $line) {
            $trimmed = trim($line);
            // 检测 group 开始: Route::group('prefix', function () {
            if (preg_match("/Route::group\s*\(\s*['\"]([^'\"]+)['\"]/i", $trimmed, $gm)) {
                $groupStack[] = $gm[1] . '/';
                continue;
            }
            // 检测 group 结束: }); 或 })->... 
            if (preg_match("/^\}\);?\s*$/", $trimmed) || preg_match("/^\}\)->/i", $trimmed)) {
                if (!empty($groupStack)) array_pop($groupStack);
                continue;
            }
            // 匹配 Route::get|post|put|delete|patch('url', 'module/Controller/action')
            if (preg_match("/Route::(get|post|put|delete|patch)\s*\(\s*['\"]([^'\"]+)['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/i", $trimmed, $m)) {
                $method = strtoupper($m[1]);
                $url = $m[2];
                // 如果 URL 不以当前 group 前缀开头，则添加前缀
                // （兼容 admin.php 中已在 URL 中包含模块前缀的写法）
                if (!empty($groupStack)) {
                    $prefix = implode('', $groupStack);
                    $prefixClean = rtrim($prefix, '/');
                    if (strpos($url, $prefixClean . '/') !== 0) {
                        $url = $prefix . $url;
                    }
                }
                $handler = explode('/', $m[3]);
                if (count($handler) >= 3) {
                    $urlKey = $method . ':' . $url;
                    $routes[$urlKey] = [
                        'module' => $handler[0],
                        'ctrl' => $handler[1],
                        'action' => $handler[2],
                    ];
                }
            }
        }
    }
    // 写入缓存
    file_put_contents($cacheFile, '<?php return ' . var_export($routes, true) . ';');
    return $routes;
}

// 控制器名映射（处理模块特定的命名差异）
$ctrlMap = [
    'staff'   => ['login' => 'StaffLogin', 'repair' => 'StaffRepair', 'meter' => 'StaffMeter', 'charge' => 'StaffCharge', 'patrol' => 'StaffPatrol', 'visitor' => 'StaffVisitor', 'order' => 'StaffOrder', 'profile' => 'StaffProfile'],
    'manager' => ['login' => 'ManagerLogin', 'dashboard' => 'Dashboard'],
    'device'  => ['gateway' => 'Gateway', 'camera' => 'Camera'],
];
// 三段路由控制器名映射 /staff/repair/list → StaffRepair
$ctrl3Map = [
    'staff' => ['repair' => 'StaffRepair', 'meter' => 'StaffMeter', 'charge' => 'StaffCharge', 'patrol' => 'StaffPatrol', 'visitor' => 'StaffVisitor', 'order' => 'StaffOrder', 'profile' => 'StaffProfile'],
];

try {
    // 先尝试匹配已定义的路由
    $routes = loadRoutes();
    $routeKey = $requestMethod . ':' . $path;
    $matched = $routes[$routeKey] ?? null;

    if ($matched) {
        $module = $matched['module'];
        $ctrlName = $matched['ctrl'];
        $actionName = $matched['action'];
    } else {
        // 路由未匹配，回退到原有的 URI 解析逻辑
        $parts = array_values(array_filter(explode('/', $path)));
        if (count($parts) >= 2) {
            $module = $parts[0];
            if (count($parts) >= 3) {
                $ctrlName = $ctrl3Map[$module][$parts[1]] ?? ucfirst($parts[1]);
                $actionName = $parts[2];
            } else {
                $ctrlName = $ctrlMap[$module][$parts[1]] ?? 'Login';
                $actionName = $parts[1];
            }
        }
    }

    if (isset($module) && isset($ctrlName) && isset($actionName)) {
        $req->currentAction = $actionName;
        $req->currentController = $ctrlName;

        $className = "app\\{$module}\\controller\\{$ctrlName}";
        if (class_exists($className)) {
            $controller = new $className($req);
            $tryNames = [$actionName];
            if ($actionName === 'list') $tryNames[] = 'lists';
            foreach ($tryNames as $tryName) {
                if (method_exists($controller, $tryName)) {
                    $response = $controller->{$tryName}();
                    if (is_object($response) && method_exists($response, 'send')) {
                        $response->send();
                    } elseif (is_string($response)) {
                        echo $response;
                    }
                    exit;
                }
            }
        }
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['code' => 404, 'msg' => '未找到该路由: ' . $path], JSON_UNESCAPED_UNICODE);

} catch (\think\exception\HttpResponseException $e) {
    $e->getResponse()->send();
} catch (\Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    $msg = $e->getMessage();
    // 友好化常见数据库错误
    if (strpos($msg, 'Duplicate entry') !== false) {
        $msg = '数据已存在，请勿重复添加';
    } elseif (strpos($msg, 'Integrity constraint violation') !== false) {
        $msg = '数据冲突，请检查输入';
    }
    echo json_encode(['code' => 1, 'msg' => $msg], JSON_UNESCAPED_UNICODE);
}
