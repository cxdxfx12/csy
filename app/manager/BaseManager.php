<?php
// 经理端基类
namespace app\manager;

use app\BaseController;

class BaseManager extends BaseController
{
    protected $noAuth = ['login'];

    protected function initialize()
    {
        parent::initialize();
        $action = $this->request->action(true);
        if (!in_array($action, $this->noAuth)) {
            $this->auth();
        }
    }

    protected function auth()
    {
        // 复用员工端认证
    }
}
