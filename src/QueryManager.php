<?php

declare(strict_types=1);

namespace MartinHons\NetteGrid;

use DateTimeInterface;
use InvalidArgumentException;
use PDO;
use PDOStatement;
use PhpMyAdmin\SqlParser\Components\Condition;
use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Components\Limit;
use PhpMyAdmin\SqlParser\Components\OrderKeyword;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;

class QueryManager
{
    private SelectStatement $select;


    /**
     * @param array<mixed> $queryParams
     */
    public function __construct(
        private PDO $pdo,
        string $query,
        array $queryParams = [],
    )
    {
        $buildedQuery = $this->buildQuery($query, $queryParams);
        $this->select = $this->buildStatement($buildedQuery);
    }


    /**
     * Returns data according to the specified parameters.
     * @param array<Expression> $expressions
     */
    public function getRowData(
        array $expressions,
        Condition $where,
        Condition $having,
        OrderKeyword $order,
        Limit $limit,
    ): PDOStatement|false
    {
        $this->select->expr = $expressions;

        $where = QueryHelper::mergeConditions([$this->select->where[0] ?? new Condition, $where], 'AND');
        if ($where) {
            $this->select->where = [$where];
        }

        $having = QueryHelper::mergeConditions([$this->select->having[0] ?? new Condition, $having], 'AND');
        if ($having) {
            $this->select->having = [$having];
        }

        $this->select->order = [$order];
        $this->select->limit = $limit;
        return $this->pdo->query($this->select->build(), PDO::FETCH_ASSOC);
    }


    /**
     * Returns total column count and filtered column count.
     * @return array<int>
     */
    public function getCountData(
        Condition $where,
        Condition $having,
    ): array
    {
        $result = [];
        $statement = clone $this->select;
        $statement->expr = [new Expression('COUNT(*) AS count')];

        $queryRes = $this->pdo->query($statement->build());
        $result['totalCount'] = 0;
        if ($queryRes !== false) {
            $row = $queryRes->fetch();
            if (is_array($row) && isset($row['count'])) {
                $result['totalCount'] = $row['count'];
            }
        }

        $where = QueryHelper::mergeConditions([$statement->where[0] ?? new Condition, $where], 'AND');
        if ($where) {
            $statement->where = [$where];
        }

        $having = QueryHelper::mergeConditions([$statement->having[0] ?? new Condition, $having], 'AND'); // TODO tady se musí provést sloučení a prioritizace podmínek
        if ($having) {
            $statement->having = [$having];
        }

        $queryRes = $this->pdo->query($statement->build());
        $result['filteredCount'] = 0;
        if ($queryRes !== false) {
            $row = $queryRes->fetch();
            if (is_array($row) && isset($row['count'])) {
                $result['filteredCount'] = $row['count'];
            }
        }

        return $result;
    }


    /**
     * Returns expression searched by column alias, column name or fully qualified column name.
     */
    public function getExpr(string $column): Expression
    {
        if ($this->select->expr[0]->expr === '*') {
            return new Expression(column: $column);
        }
        elseif (str_contains($column, '.')) {
            foreach($this->select->expr as $statementColumn) {
                if ($column === $statementColumn->expr) {
                    return $statementColumn;
                }
            }
        }
        else {
            $aliasedColumns = array_filter($this->select->expr, fn($expr) => $expr->alias !== null);
            foreach($aliasedColumns as $aliasedColumn) {
                if ($column === $aliasedColumn->alias) {
                    return $aliasedColumn;
                }
            }

            $result = null;
            foreach($this->select->expr as $statementColumn) {
                if ($column === $statementColumn->column) {
                    if ($result) {
                        throw new InvalidArgumentException(sprintf('Column "%s" in query is ambiguous', $column));
                    }
                    $result = $statementColumn;
                }
            }
            if ($result) {
                return $result;
            }
        }

        throw new InvalidArgumentException(sprintf('Column %s is not in the database query', $column));
    }


    /**
     * It builds PhpMyAdmin\SqlParser\Statement from query string.
     */
    private function buildStatement(string $query): SelectStatement
    {
        $parser = new Parser($query);
        if(count($parser->statements) !== 1 || !$parser->statements[0] instanceof SelectStatement) {
            throw new InvalidArgumentException('Query string error. Query must have exactly one SELECT statement.');
        }
        $statement = $parser->statements[0];

        if($statement->expr[0]->expr === '*' && $statement->join) {
            throw new InvalidArgumentException('Star in the query string with joins is not allowed.');
        }

        return $statement;
    }


    /**
     * It replaces params in the query string and returns the result.
     * @param array<mixed> $params
     */
    private function buildQuery(string $query, array $params): string
    {
        if (substr_count($query, '?') !== count($params)) {
            throw new InvalidArgumentException('Count of given parameters isn\'t match count of placeholders in the query string.');
        }

        $escapedParams = array_map(function($param) {
            if ($param instanceof DateTimeInterface) {
                $param = $param->format('c');
            }

            if (is_null($param)) {
                return 'NULL';
            }
            elseif (is_bool($param)) {
                return $param ? '1' : '0';
            }
            elseif (is_int($param) || is_float($param)) {
                return (string)$param;
            }
            elseif (is_string($param) || (is_object($param) && method_exists($param, '__toString'))) {
                return $this->pdo->quote((string)$param);
            }
            else {
                throw new InvalidArgumentException('Unsupported parameter type: ' . gettype($param));
            }
        }, $params);

        foreach ($escapedParams as $param) {
            $query = (string)preg_replace('/\?/', $param, $query, 1);
        }

        return $query;
    }
}
