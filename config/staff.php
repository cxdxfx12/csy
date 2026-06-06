<?php
// 员工端配置
return [
    // 允许通过 staff 端登录的 admin_user 角色 ID 列表
    // 防止超管(role=1)等高权限账号混入员工端
    'allowed_roles' => [2, 3, 4, 5, 6, 7, 8],
];
