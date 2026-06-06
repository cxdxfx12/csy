<?php
// 设备端认证配置
return [
    // 允许的设备密钥列表（生产环境通过 .env DEVICE_KEYS 覆盖）
    'allowed_keys' => explode(',', env('DEVICE.KEYS', '')),
];
