<?php
namespace app;

use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
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

        // 其他异常
        if (env('APP_DEBUG', false)) {
            return parent::render($request, $e);
        }

        return json_error('服务器内部错误', 500);
    }
}
