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
        if ($key === '') return $data;
        return $data[$key] ?? $default;
    }

    public function param($key = '', $default = '') {
        $data = $this->getInput();
        if ($key === '') return $data;
        return $data[$key] ?? $default;
    }

    public function header($key = '', $default = '') {
        if ($key === '') return $_SERVER;
        $k = str_replace('-', '_', strtoupper($key));
        return $_SERVER['HTTP_' . $k] ?? $_SERVER[$k] ?? $default;
    }

    public function ip() { return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'; }
    public function url($absolute = false) { return $_SERVER['REQUEST_URI']; }
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
