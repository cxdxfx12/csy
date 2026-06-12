<?php
namespace think\facade;

use Closure;

class Db {
    protected static $instance = null;
    protected $connection = null;

    public static function name($table) {
        $prefix = 'ds_';
        return new Query($prefix . $table);
    }

    public static function table($table) { return new Query($table); }
}

class Query {
    protected $table;
    protected $where = [];
    protected $order = [];
    protected $limit = '';
    protected $page = 0;
    protected $pageSize = 15;
    protected $fields = '*';
    protected $joins = [];
    protected $alias = '';
    protected $groupField = '';

    public function __construct($table) {
        $this->table = $table;
    }

    public function getPdo() {
        static $pdo = null;
        if ($pdo === null) {
            $host = '127.0.0.1';
            $port = '3306';
            $db = 'dasheng';
            $user = 'root';
            $pass = 'cxdxfx12';
            $pdo = new \PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
        }
        return $pdo;
    }

    public function alias($alias) { $this->alias = " {$alias}"; return $this; }
    public function field($fields) { $this->fields = is_array($fields) ? implode(',', $fields) : $fields; return $this; }
    public function where($where, $op = null, $val = null) { 
        if ($op !== null && $val !== null) {
            $this->where[] = [$where, $op, $val];
        } elseif ($op === null && $val === null && is_string($where)) {
            // where('field', null) → IS NULL
            $this->whereNull($where);
        } elseif ($op !== null) {
            // where('field', 'value') 等价于 where('field', '=', 'value')
            $this->where[] = [$where, '=', $op];
        } elseif (is_array($where) && isset($where[0]) && is_array($where[0])) {
            // 多维数组: [['a','=',1], ['b','like','x']]
            foreach ($where as $w) {
                $this->where[] = $w;
            }
        } elseif (is_array($where) && !empty($where) && !isset($where[0])) {
            // 关联数组: where(['field1' => 'val1', 'field2' => 'val2'])
            foreach ($where as $field => $val) {
                if ($val === null) {
                    $this->whereNull($field);
                } else {
                    $this->where[] = [$field, '=', $val];
                }
            }
        } else {
            $this->where[] = $where; 
        }
        return $this; 
    }
    public function whereIn($field, $values) { $this->where[] = [$field, 'in', $values]; return $this; }
    public function whereNotIn($field, $values) { $this->where[] = [$field, 'not in', $values]; return $this; }
    public function whereBetween($field, $range) { $this->where[] = [$field, 'between', $range]; return $this; }
    public function whereNull($field) { $this->where[] = [$field, 'null', null]; return $this; }
    public function whereNotNull($field) { $this->where[] = [$field, 'not null', null]; return $this; }
    public function whereLike($field, $val) { $this->where[] = [$field, 'like', $val]; return $this; }
    public function when($condition, $callback) { if ($condition) $callback($this); return $this; }
    public function order($order, $dir = '') { $this->order[] = $dir ? "{$order} {$dir}" : $order; return $this; }
    public function limit($limit) { $this->limit = $limit; return $this; }
    public function page($page, $size = 15) { $this->page = $page; $this->pageSize = $size; return $this; }

    protected function buildWhere($params = []) {
        $where = $params ?: $this->where;
        $sql = '';
        $binds = [];
        foreach ($where as $w) {
            if (is_string($w)) { $sql .= ($sql ? ' AND ' : '') . $w; continue; }
            if (is_array($w) && count($w) >= 2) {
                $op = strtolower($w[1] ?? '=');
                $field = $w[0];
                $val = $w[2] ?? null;
                // 通用：字段名加反引号保护（避免MySQL保留字如group,key等问题）
                // 支持 alias.field 格式 → `alias`.`field`
                $fieldQuoted = (strpos($field, '`') === false) ? ('`' . str_replace('.', '`.`', $field) . '`') : $field;

                // 处理多字段|分隔 like: ['name|phone', 'like', '%kw%'] → (`name` LIKE ? OR `phone` LIKE ?)
                if (strpos($field, '|') !== false && in_array($op, ['like', 'not like'])) {
                    $fields = explode('|', $field);
                    $parts = [];
                    foreach ($fields as $f) {
                        $fQuoted = (strpos($f, '`') === false) ? ('`' . str_replace('.', '`.`', $f) . '`') : $f;
                        $parts[] = "{$fQuoted} {$op} ?";
                        $binds[] = $val;
                    }
                    $sql .= ($sql ? ' AND ' : '') . '(' . implode(' OR ', $parts) . ')';
                    continue;
                }
                if ($op === 'in' || $op === 'not in') {
                    if (is_array($val) && !empty($val)) {
                        $placeholders = implode(',', array_fill(0, count($val), '?'));
                        $sql .= ($sql ? ' AND ' : '') . "{$fieldQuoted} {$op} ({$placeholders})";
                        $binds = array_merge($binds, array_values($val));
                    }
                } elseif ($op === 'between') {
                    $sql .= ($sql ? ' AND ' : '') . "{$fieldQuoted} BETWEEN ? AND ?";
                    $binds = array_merge($binds, array_values($val));
                } elseif ($op === 'null' || ($op === '=' && $val === null)) {
                    $sql .= ($sql ? ' AND ' : '') . "{$fieldQuoted} IS NULL";
                } elseif ($op === 'not null' || ($op === '<>' && $val === null)) {
                    $sql .= ($sql ? ' AND ' : '') . "{$fieldQuoted} IS NOT NULL";
                } else {
                    $sql .= ($sql ? ' AND ' : '') . "{$fieldQuoted} {$op} ?";
                    $binds[] = $val;
                }
            }
        }
        return [$sql, $binds];
    }

    protected function buildJoin() {
        return $this->joins ? ' ' . implode(' ', $this->joins) : '';
    }

    public function select() {
        $pdo = $this->getPdo();
        $table = $this->table;
        $alias = $this->alias;
        $fields = $this->fields;
        
        $from = "`{$table}`{$alias}";
        $joins = $this->buildJoin();
        list($where, $binds) = $this->buildWhere();
        $sql = "SELECT {$fields} FROM {$from}{$joins}" . ($where ? " WHERE {$where}" : '');
        
        if ($this->groupField) $sql .= ' GROUP BY ' . $this->groupField;
        
        if ($this->order) {
            $orders = array_filter($this->order);
            if (!empty($orders)) $sql .= ' ORDER BY ' . implode(',', $orders);
        }
        
        if ($this->page) {
            $offset = ($this->page - 1) * $this->pageSize;
            $sql .= " LIMIT {$this->pageSize} OFFSET {$offset}";
        } elseif ($this->limit) {
            $sql .= " LIMIT {$this->limit}";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($binds);
        return $stmt->fetchAll();
    }

    public function find($id = null) {
        if ($id !== null) {
            return $this->where('id', '=', $id)->limit(1)->select()[0] ?? null;
        }
        $rows = $this->limit(1)->select();
        return $rows[0] ?? null;
    }

    public function count($field = '*') {
        $pdo = $this->getPdo();
        $table = $this->table;
        $alias = $this->alias;
        $from = "`{$table}`{$alias}";
        $joins = $this->buildJoin();
        list($where, $binds) = $this->buildWhere();
        $countExpr = $field === '*' ? '*' : "`{$field}`";
        $sql = "SELECT COUNT({$countExpr}) FROM {$from}{$joins}" . ($where ? " WHERE {$where}" : '');
        $stmt = $pdo->prepare($sql);
        $stmt->execute($binds);
        return (int) $stmt->fetchColumn();
    }

    public function sum($field) {
        $pdo = $this->getPdo();
        $table = $this->table;
        $alias = $this->alias;
        $from = "`{$table}`{$alias}";
        $joins = $this->buildJoin();
        list($where, $binds) = $this->buildWhere();
        $sql = "SELECT COALESCE(SUM({$field}),0) FROM {$from}{$joins}" . ($where ? " WHERE {$where}" : '');
        $stmt = $pdo->prepare($sql);
        $stmt->execute($binds);
        return (float) $stmt->fetchColumn();
    }

    public function column($field, $key = null) {
        $rows = $this->select();
        if ($key !== null) {
            $result = [];
            foreach ($rows as $r) $result[$r[$key]] = $r[$field];
            return $result;
        }
        return array_map(function($r) use ($field) { return $r[$field]; }, $rows);
    }

    public function insert($data, $replace = false) {
        $pdo = $this->getPdo();
        $fields = implode('`,`', array_keys($data));
        $placeholders = implode(',', array_fill(0, count($data), '?'));
        $sql = ($replace ? 'REPLACE' : 'INSERT') . " INTO `{$this->table}` (`{$fields}`) VALUES ({$placeholders})";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($data));
        return $pdo->lastInsertId();
    }

    public function insertGetId($data) { return $this->insert($data); }

    public function insertAll($data) {
        if (empty($data)) return 0;
        $count = 0;
        foreach ($data as $row) { $this->insert($row); $count++; }
        return $count;
    }

    public function update($data = []) {
        $pdo = $this->getPdo();
        $sets = [];
        $binds = [];
        foreach ($data as $k => $v) {
            $sets[] = "`{$k}` = ?";
            $binds[] = $v;
        }
        // inc/dec 生成的 SET 子句（以 `field` = `field` +/- N 开头）应移到 SET 而非 WHERE
        $normalWhere = [];
        foreach ($this->where as $w) {
            $str = is_array($w) && isset($w[0]) ? $w[0] : $w;
            if (is_string($str) && preg_match('/^`\w+`\s*=\s*`\w+`\s*[\+\-]/', $str)) {
                $sets[] = $str;
            } else {
                $normalWhere[] = $w;
            }
        }
        list($where, $wBinds) = $this->buildWhere($normalWhere);
        $sql = "UPDATE `{$this->table}` SET " . implode(',', $sets) . ($where ? " WHERE {$where}" : '');
        $binds = array_merge($binds, $wBinds);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($binds);
        return $stmt->rowCount();
    }

    public function delete() {
        $pdo = $this->getPdo();
        list($where, $binds) = $this->buildWhere();
        $sql = "DELETE FROM `{$this->table}`" . ($where ? " WHERE {$where}" : '');
        $stmt = $pdo->prepare($sql);
        $stmt->execute($binds);
        return $stmt->rowCount();
    }

    public function inc($field, $step = 1) { $this->where[] = ["`{$field}` = `{$field}` + {$step}"]; return $this; }
    public function dec($field, $step = 1) { $this->where[] = ["`{$field}` = `{$field}` - {$step}"]; return $this; }

    protected function prefixTable($tableRef) {
        $prefix = 'ds_';
        $parts = preg_split('/\s+/', trim($tableRef), 2);
        $table = $parts[0];
        $alias = $parts[1] ?? '';
        // 已有前缀就不重复添加
        if (strpos($table, $prefix) !== 0) {
            $table = $prefix . $table;
        }
        return $alias ? "`{$table}` {$alias}" : "`{$table}`";
    }

    public function leftJoin($table, $on) {
        $this->joins[] = "LEFT JOIN " . $this->prefixTable($table) . " ON {$on}";
        return $this;
    }

    public function rightJoin($table, $on) {
        $this->joins[] = "RIGHT JOIN " . $this->prefixTable($table) . " ON {$on}";
        return $this;
    }

    public function innerJoin($table, $on) {
        $this->joins[] = "INNER JOIN " . $this->prefixTable($table) . " ON {$on}";
        return $this;
    }

    public function join($table, $on, $type = 'LEFT') {
        $typeUpper = strtoupper($type);
        $this->joins[] = "{$typeUpper} JOIN " . $this->prefixTable($table) . " ON {$on}";
        return $this;
    }

    public function value($field) {
        $this->fields = "`{$field}`";
        $this->limit = 1;
        $rows = $this->select();
        return $rows[0][$field] ?? null;
    }

    public function avg($field) {
        $pdo = $this->getPdo();
        $table = $this->table;
        $from = "`{$table}`";
        $joins = $this->buildJoin();
        list($where, $binds) = $this->buildWhere();
        $sql = "SELECT COALESCE(AVG(`{$field}`),0) FROM {$from}{$joins}" . ($where ? " WHERE {$where}" : '');
        $stmt = $pdo->prepare($sql);
        $stmt->execute($binds);
        return (float) $stmt->fetchColumn();
    }

    public function group($field) {
        $this->groupField = $field;
        return $this;
    }

    public function toArray() { return $this->select(); }
}
