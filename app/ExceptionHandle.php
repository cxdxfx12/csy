<?php
namespace app;

use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\facade\Db;
use think\Response;
use Throwable;

class ExceptionHandle extends Handle
{
    public function render($request, Throwable $e): Response
    {
        // 参数验证错误
        if ($e instanceof ValidateException) {
            return json_error($e->getError(), 422);
        }

        // 请求异常
        if ($e instanceof HttpException && !$request->isAjax()) {
            return json_error($e->getMessage(), $e->getStatusCode());
        }

        // 其他异常：生产环境隐藏详情，记录日志
        if (env('APP_DEBUG', false)) {
            return parent::render($request, $e);
        }

        // 记录异常日志但不暴露详情给客户端
        $logData = [
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'message' => $e->getMessage(),
            'code'    => $e->getCode(),
            'url'     => $request->url(true),
            'method'  => $request->method(),
            'ip'      => $request->ip(),
            'time'    => date('Y-m-d H:i:s'),
        ];
        try {
            Db::name('system_error_log')->insert($logData);
        } catch (\Throwable $ignore) {
            // 记录异常日志失败，静默处理
        }

        return json_error('服务器内部错误', 500);
    }
}
