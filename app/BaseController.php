<?php
namespace app;

use think\exception\HttpResponseException;
use think\response\Json;

abstract class BaseController
{
    protected $request;
    protected $middleware = [];

    public function __construct($request = null)
    {
        $this->request = $request ?: new Request();
        $this->initialize();
    }

    protected function initialize() {}

    protected function success($data = [], string $msg = '操作成功', int $code = 0): Json
    {
        return new Json(['code' => $code, 'msg' => $msg, 'data' => $data, 'time' => time()]);
    }

    protected function error(string $msg = '操作失败', int $code = 1, $data = []): Json
    {
        return new Json(['code' => $code, 'msg' => $msg, 'data' => $data, 'time' => time()]);
    }

    protected function table($list, int $total = 0): Json
    {
        return new Json(['code' => 0, 'msg' => '获取成功', 'data' => $list, 'count' => $total, 'time' => time()]);
    }

    protected function getPage(): array
    {
        $page = (int) ($_REQUEST['page'] ?? 1);
        $limit = (int) ($_REQUEST['limit'] ?? 15);
        if ($limit > 100) $limit = 100;
        return [$page, $limit];
    }

    protected function buildWhere(array $fields): array
    {
        $where = [];
        foreach ($fields as $field => $condition) {
            $value = $_REQUEST[$field] ?? null;
            if ($value === '' || $value === null) continue;
            if (is_string($condition)) {
                switch ($condition) {
                    case 'like': $where[] = [$field, 'like', "%{$value}%"]; break;
                    case 'eq': $where[] = [$field, '=', $value]; break;
                    case 'in': if (is_array($value)) $where[] = [$field, 'in', $value]; break;
                    default: $where[] = [$field, '=', $value];
                }
            }
        }
        return $where;
    }

    protected function throwSuccess($data = [], string $msg = '操作成功') {
        throw new HttpResponseException($this->success($data, $msg));
    }

    protected function throwError(string $msg = '操作失败') {
        throw new HttpResponseException($this->error($msg));
    }
}
