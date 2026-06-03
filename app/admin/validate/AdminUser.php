<?php
namespace app\admin\validate;

use think\Validate;

class AdminUser extends Validate
{
    protected $rule = [
        'username' => 'require',
        'password' => 'require|min:6',
        'role_id'  => 'require|number',
    ];

    protected $message = [
        'username.require' => '用户名不能为空',
        'password.require' => '密码不能为空',
        'password.min'     => '密码至少6位',
        'role_id.require'  => '请选择角色',
    ];

    protected $scene = [
        'add'  => ['username', 'password', 'role_id'],
        'edit' => ['username', 'role_id'],
    ];
}
