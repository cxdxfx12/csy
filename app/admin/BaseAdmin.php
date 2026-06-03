<?php
// 后台基类控制器
namespace app\admin;

use app\BaseController;
use think\facade\Db;

class BaseAdmin extends BaseController
{
    protected $adminInfo = [];
    protected $adminId = 0;
    protected $noAuth = ['login', 'captcha', 'logout'];

    protected function initialize()
    {
        parent::initialize();

        // 自动认证
        $action = $this->request->action(true);
        if (!in_array($action, $this->noAuth)) {
            $this->auth();
        }
    }

    /**
     * 认证
     */
    protected function auth()
    {
        $token = $this->request->header('Authorization', '');
        if (empty($token)) {
            $token = $this->request->param('token', '');
        }
        $token = str_replace('Bearer ', '', $token);

        if (empty($token)) {
            $this->throwError('请先登录');
        }

        try {
            $jwtConfig = config('jwt');
            $payload = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($jwtConfig['key'], $jwtConfig['algorithm']));
            $payload = (array) $payload;
            $this->adminId = $payload['sub'] ?? 0;
            $this->adminInfo = Db::name('admin_user')->where('id', $this->adminId)->find();

            if (!$this->adminInfo || $this->adminInfo['status'] != 1) {
                $this->throwError('账户已被禁用');
            }

            // 绑定到请求
            $this->request->adminInfo = $this->adminInfo;
            $this->request->adminId = $this->adminId;

            // 权限检查
            $this->checkPermission();

        } catch (\Firebase\JWT\ExpiredException $e) {
            $this->throwError('登录已过期，请重新登录');
        } catch (\Exception $e) {
            $this->throwError('身份验证失败');
        }
    }

    /**
     * 权限检查
     */
    protected function checkPermission()
    {
        if ($this->adminInfo['role_id'] == 1) return; // 超管跳过

        $route = $this->request->controller() . '/' . $this->request->action();
        // 此处实现具体的权限校验逻辑
    }
}
