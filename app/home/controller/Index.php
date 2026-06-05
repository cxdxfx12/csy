<?php
namespace app\home\controller;

class Index
{
    public function index()
    {
        $homepage = dirname(__DIR__, 2) . '/public/index.html';
        if (file_exists($homepage)) {
            header('Content-Type: text/html; charset=utf-8');
            readfile($homepage);
            exit;
        }
        echo '系统首页';
    }
}
