<?php
// 应用公共函数
if (!function_exists('env')) {
    function env($key, $default = null) { return $_ENV[$key] ?? $default; }
}
use think\facade\Db;
use think\facade\Cache;
use think\facade\Config;

if (!function_exists('request')) {
    /**
     * 获取当前请求对象
     */
    function request(): \app\Request
    {
        return $GLOBALS['__request__'] ?? new \app\Request();
    }
}

if (!function_exists('set_request')) {
    /**
     * 设置当前请求对象
     */
    function set_request(\app\Request $req): void
    {
        $GLOBALS['__request__'] = $req;
    }
}

if (!function_exists('json_success')) {
    function json_success($data = [], string $msg = '操作成功', int $code = 0): \think\response\Json
    {
        return \think\response\Json::create([
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
            'time' => time(),
        ]);
    }
}

if (!function_exists('json_error')) {
    function json_error(string $msg = '操作失败', int $code = 1, $data = []): \think\response\Json
    {
        return \think\response\Json::create([
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
            'time' => time(),
        ]);
    }
}

if (!function_exists('json_table')) {
    function json_table($list, int $total = 0, string $msg = '获取成功'): \think\response\Json
    {
        return \think\response\Json::create([
            'code'  => 0,
            'msg'   => $msg,
            'data'  => $list,
            'count' => $total,
            'time'  => time(),
        ]);
    }
}

if (!function_exists('get_admin_id')) {
    /**
     * 获取当前管理员ID
     */
    function get_admin_id(): int
    {
        return request()->adminInfo['id'] ?? 0;
    }
}

if (!function_exists('get_admin_info')) {
    /**
     * 获取当前管理员信息
     */
    function get_admin_info(): array
    {
        return request()->adminInfo ?? [];
    }
}

if (!function_exists('build_order_no')) {
    /**
     * 生成订单编号
     */
    function build_order_no(string $prefix = 'DS'): string
    {
        return $prefix . date('YmdHis') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('redirect')) {
    /**
     * URL 重定向
     */
    function redirect(string $url, int $code = 302)
    {
        header('Location: ' . $url, true, $code);
        exit;
    }
}

if (!function_exists('encrypt_password')) {
    /**
     * 密码加密（bcrypt）
     */
    function encrypt_password(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}

if (!function_exists('verify_password')) {
    /**
     * 密码验证（兼容旧版 md5 和 新版 bcrypt）
     * @param string $password 明文密码
     * @param string $hash 数据库中存储的哈希值
     * @return bool
     */
    function verify_password(string $password, string $hash): bool
    {
        // bcrypt 哈希以 $2y$ / $2b$ / $2a$ 开头
        if (strlen($hash) >= 4 && strpos($hash, '$2') === 0) {
            return password_verify($password, $hash);
        }
        // 兼容旧版 md5(md5($pwd).$salt)
        $salt = 'JUD6FCtZsqrmVXc2apev4TRn3O8gAhxbSlH9wfPN';
        return $hash === md5(md5($password) . $salt);
    }
}

if (!function_exists('tree_list')) {
    /**
     * 将数据转为树形结构
     */
    function tree_list(array $data, int $parentId = 0, string $idField = 'id', string $parentField = 'parent_id', string $childrenField = 'children'): array
    {
        $tree = [];
        foreach ($data as $item) {
            if ($item[$parentField] == $parentId) {
                $children = tree_list($data, $item[$idField], $idField, $parentField, $childrenField);
                if (!empty($children)) {
                    $item[$childrenField] = $children;
                }
                $tree[] = $item;
            }
        }
        return $tree;
    }
}

if (!function_exists('bytes_to_size')) {
    /**
     * 字节转可读大小
     */
    function bytes_to_size(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < 4) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . $units[$i];
    }
}

if (!function_exists('mask_phone')) {
    /**
     * 手机号脱敏
     */
    function mask_phone(string $phone): string
    {
        return substr($phone, 0, 3) . '****' . substr($phone, -4);
    }
}

if (!function_exists('mask_id_card')) {
    /**
     * 身份证脱敏
     */
    function mask_id_card(string $idCard): string
    {
        return substr($idCard, 0, 4) . '**********' . substr($idCard, -4);
    }
}

if (!function_exists('date_range')) {
    /**
     * 获取日期范围
     */
    function date_range(string $startDate, string $endDate): array
    {
        $dates = [];
        $current = strtotime($startDate);
        $end = strtotime($endDate);
        while ($current <= $end) {
            $dates[] = date('Y-m-d', $current);
            $current = strtotime('+1 day', $current);
        }
        return $dates;
    }
}

if (!function_exists('get_client_ip')) {
    /**
     * 获取客户端IP
     */
    function get_client_ip(): string
    {
        return request()->ip();
    }
}

if (!function_exists('array_to_keyval')) {
    /**
     * 数组转键值对
     */
    function array_to_keyval(array $list, string $keyField = 'id', string $valField = 'name'): array
    {
        $result = [];
        foreach ($list as $item) {
            $result[$item[$keyField]] = $item[$valField];
        }
        return $result;
    }
}
