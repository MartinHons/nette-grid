<?php

declare(strict_types=1);

namespace MartinHons\NetteGrid;

use DateTime;
use DateTimeInterface;
use Exception;
use InvalidArgumentException;
use PDO;
use PDOStatement;
use PhpMyAdmin\SqlParser\Components\Condition;
use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Components\Limit;
use PhpMyAdmin\SqlParser\Components\OrderKeyword;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statement;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;

class QueryManager
{
    private Statement $statement;

    public function __construct(
        private PDO $pdo,
        string $query,
        array $queryParams = [],
    )
    {
        $buildedQuery = $this->buildQuery($query, $queryParams);
        $this->statement = $this->buildStatement($buildedQuery);
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
        Limit $limit
    ): PDOStatement
    {
        $this->statement->expr = $expressions;
        $this->statement->where = QueryHelper::mergeConditions([$this->statement->where[0] ?? new Condition, $where], 'AND'); // TODO tady se musí provést sloučení a prioritizace podmínek
        $this->statement->having = QueryHelper::mergeConditions([$this->statement->having[0] ?? new Condition, $having], 'AND'); // TODO tady se musí provést sloučení a prioritizace podmínek
        $this->statement->order = $order;
        $this->statement->limit = $limit;
        return $this->pdo->query($this->statement->build(), PDO::FETCH_ASSOC);
    }


    /**
     * Returns total column count and filtered column count.
     */
    public function getCountData(
        Condition $where,
        Condition $having
    ): array
    {
        $result = [];
        $statement = clone $this->statement;
        $statement->expr = [new Expression('COUNT(*) AS count')];
        $result['totalCount'] = $this->pdo->query($statement->build())->fetch()['count'];

        $statement->where = QueryHelper::mergeConditions([$statement->where[0] ?? new Condition, $where], 'AND'); // TODO tady se musí provést sloučení a prioritizace podmínek
        $statement->having = QueryHelper::mergeConditions([$statement->having[0] ?? new Condition, $having], 'AND'); // TODO tady se musí provést sloučení a prioritizace podmínek
        $result['filteredCount'] = $this->pdo->query($statement->build())->fetch()['count'];

        return $result;
    }


    /**
     * Returns expression searched by column alias, column name or fully qualified column name.
     */
    public function getExpr(string $column): Expression
    {
        if ($this->statement->expr[0]->expr === '*') {
            return new Expression(column: $column);
        }
        elseif (str_contains($column, '.')) {
            foreach($this->statement->expr as $statementColumn) {
                if ($column === $statementColumn->expr) {
                    return $statementColumn;
                }
            }
        }
        else {
            $aliasedColumns = array_filter($this->statement->expr, fn($expr) => $expr->alias);
            foreach($aliasedColumns as $aliasedColumn) {
                if ($column === $aliasedColumn->alias) {
                    return $aliasedColumn;
                }
            }

            $result = null;
            foreach($this->statement->expr as $statementColumn) {
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
    private function buildStatement(string $query): Statement
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
            else {
                return $this->pdo->quote((string)$param);
            }
        }, $params);

        foreach ($escapedParams as $param) {
            $query = preg_replace('/\?/', $param, $query, 1);
        }

        return $query;
    }
}
