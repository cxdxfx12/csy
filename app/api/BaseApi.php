<?php
// API基类（业主端）
namespace app\api;

use app\BaseController;
use think\facade\Db;

class BaseApi extends BaseController
{
    protected $ownerInfo = [];
    protected $ownerId = 0;
    protected $noAuth = ['login', 'register', 'sendSms', 'resetPassword', 'wechatNotify', 'alipayNotify'];

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
            $this->ownerId = $payload['sub'] ?? 0;
            $this->ownerInfo = Db::name('owner')->where('id', $this->ownerId)->find();

            if (!$this->ownerInfo || $this->ownerInfo['status'] != 1) {
                $this->throwError('账户异常');
            }

            $this->request->ownerInfo = $this->ownerInfo;

        } catch (\Exception $e) {
            $this->throwError('身份验证失败');
        }
    }
}
