<?php
// 员工端基类
namespace app\staff;

use app\BaseController;
use think\facade\Db;

class BaseStaff extends BaseController
{
    protected $staffInfo = [];
    protected $staffId = 0;
    protected $noAuth = ['login', 'captcha'];

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
        $token = $this->request->header('Authorization', '');
        $token = str_replace('Bearer ', '', $token);

        if (empty($token)) {
            $this->throwError('请先登录');
        }

        try {
            $jwtConfig = config('jwt');
            $payload = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($jwtConfig['key'], $jwtConfig['algorithm']));
            $payload = (array) $payload;
            $this->staffId = $payload['sub'] ?? 0;
            $this->staffInfo = Db::name('staff')->where('id', $this->staffId)->find();

            if (!$this->staffInfo || $this->staffInfo['status'] != 1) {
                $this->throwError('账户异常');
            }

            $this->request->staffInfo = $this->staffInfo;

        } catch (\Exception $e) {
            $this->throwError('身份验证失败');
        }
    }
}
