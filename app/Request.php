<?php
namespace app;

class Request {
    protected $data = [];
    protected $postData = [];
    protected $inputData = null;

    public function __construct() {
        $this->data = [
            'adminInfo' => [],
            'adminId' => 0,
            'currentAction' => '',
            'currentController' => '',
        ];
        // 解析 JSON body
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $json = json_decode($raw, true);
            if (is_array($json)) $this->postData = $json;
        }
    }

    protected function getInput() {
        if ($this->inputData === null) {
            $this->inputData = array_merge($_REQUEST, $this->postData);
        }
        return $this->inputData;
    }

    public function post($key = '', $default = '') {
        $data = !empty($this->postData) ? $this->postData : $_POST;
        return $this->input($data, $key, $default);
    }

    public function param($key = '', $default = '') {
        $data = $this->getInput();
        return $this->input($data, $key, $default);
    }

    /**
     * 统一处理 ThinkPHP 修饰符：/a /d /f /s
     *   /a → 强制数组   /d → 强制整数
     *   /f → 强制浮点    /s → 字符串过滤
     */
    protected function input($data, $key, $default) {
        if ($key === '') return $data;
        $modifier = '';
        // 检测 /a /d /f /s 后缀
        if (preg_match('/^(.+?)\/([adfs])$/i', $key, $m)) {
            $key = $m[1];
            $modifier = strtolower($m[2]);
        }
        $val = $data[$key] ?? $default;

        switch ($modifier) {
            case 'a': return is_array($val) ? $val : ($val !== '' && $val !== null ? [$val] : []);
            case 'd': return (int)$val;
            case 'f': return (float)$val;
            case 's': return is_string($val) ? htmlspecialchars(trim($val), ENT_QUOTES) : (string)$val;
            default: return $val;
        }
    }

    public function header($key = '', $default = '') {
        if ($key === '') return $_SERVER;
        $k = str_replace('-', '_', strtoupper($key));
        return $_SERVER['HTTP_' . $k] ?? $_SERVER[$k] ?? $default;
    }

    public function ip() { return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'; }
    public function url($absolute = false) { return $_SERVER['REQUEST_URI']; }
    public function domain() {
        // 优先检测代理转发协议（本地开发/内网穿透场景）
        $scheme = 'http';
        if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        ) {
            $scheme = 'https';
        }
        $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '127.0.0.1';
        return $scheme . '://' . $host;
    }
    public function module() { return 'admin'; }
    public function controller($convert = false) { return $this->data['currentController'] ?? ''; }
    public function action($convert = false) { return $this->data['currentAction'] ?? ''; }
    public function time() { return $_SERVER['REQUEST_TIME'] ?? time(); }
    public function method() { return $_SERVER['REQUEST_METHOD']; }
    public function pathinfo() { return trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'); }

    public function __get($name) { return $this->data[$name] ?? null; }
    public function __set($name, $value) { $this->data[$name] = $value; }
    public function __isset($name) { return isset($this->data[$name]); }
}
