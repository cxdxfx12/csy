<?php
namespace app\device\controller;

use app\BaseController;

class Gateway extends BaseController
{
    public function heartbeat()
    {
        return json_success([], 'ok');
    }

    public function data()
    {
        return json_success([], 'ok');
    }

    public function config()
    {
        return json_success(['interval' => 30, 'server' => '127.0.0.1']);
    }
}
