<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    'commands' => [
        'auto:bill'    => \app\command\AutoBill::class,
        'auto:dunning' => \app\command\AutoDunning::class,
    ],
];
