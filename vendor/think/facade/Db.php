<?php
/**
 * Db 门面 —— 代理到标准 think-orm（topthink/think-orm v3.0）
 */
namespace think\facade;

use think\DbManager;

class Db
{
    protected static ?DbManager $manager = null;
    protected static bool $inited = false;

    protected static function init(): void
    {
        if (self::$inited) return;
        self::$inited = true;

        $root = dirname(__DIR__, 3);
        self::$manager = new DbManager();

        // 直接从 .env 读取数据库配置（不依赖 env() 的大小写匹配）
        $env = self::readEnv($root . '/.env');

        $config = [
            'default' => 'mysql',
            'connections' => [
                'mysql' => [
                    'type'     => 'mysql',
                    'hostname' => $env['database.hostname'] ?? '127.0.0.1',
                    'database' => $env['database.database'] ?? 'dasheng',
                    'username' => $env['database.username'] ?? 'root',
                    'password' => $env['database.password'] ?? '',
                    'hostport' => $env['database.hostport'] ?? '3306',
                    'charset'  => $env['database.charset'] ?? 'utf8mb4',
                    'prefix'   => $env['database.prefix'] ?? 'ds_',
                    'auto_timestamp'  => 'datetime',
                    'datetime_format' => 'Y-m-d H:i:s',
                ],
            ],
        ];

        // 也合并 config/database.php 中的非连接配置
        $cfgFile = $root . '/config/database.php';
        if (file_exists($cfgFile)) {
            $cfg = require $cfgFile;
            if (isset($cfg['default'])) $config['default'] = $cfg['default'];
            // 合并其他顶层配置
            foreach (['auto_timestamp', 'datetime_format', 'break_reconnect', 'trigger_sql'] as $k) {
                if (isset($cfg[$k])) $config[$k] = $cfg[$k];
            }
        }

        self::$manager->setConfig($config);
    }

    protected static function readEnv(string $path): array
    {
        $env = [];
        if (!file_exists($path)) return $env;
        $section = '';
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            if ($line[0] === '[') { $section = strtolower(trim($line, '[]')); continue; }
            $pos = strpos($line, '=');
            if ($pos === false) continue;
            $k = trim(substr($line, 0, $pos));
            $v = trim(substr($line, $pos + 1));
            $env[($section ? $section . '.' : '') . strtolower($k)] = $v;
        }
        return $env;
    }

    protected static function manager(): DbManager
    {
        self::init();
        return self::$manager;
    }

    public static function name(string $table): \think\db\Query
    {
        return self::manager()->name($table);
    }

    public static function table(string $table): \think\db\Query
    {
        return self::manager()->table($table);
    }

    public static function startTrans(): void { self::manager()->startTrans(); }
    public static function commit(): void       { self::manager()->commit(); }
    public static function rollback(): void     { self::manager()->rollback(); }

    public static function connect(?string $name = null, bool $force = false)
    {
        return self::manager()->connect($name, $force);
    }

    public static function __callStatic(string $method, array $args)
    {
        return self::manager()->$method(...$args);
    }
}
