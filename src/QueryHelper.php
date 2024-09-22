<?php

declare(strict_types=1);

namespace MartinHons\NetteGrid;

use InvalidArgumentException;
use PhpMyAdmin\SqlParser\Components\Condition;

class QueryHelper
{
	/**
	 * @param array<Condition|null> $conditions
	 * @param 'AND'|'OR' $operator
	 */
	public static function mergeConditions(array $conditions, string $operator = 'AND'): ?Condition
	{
		$operator = strtoupper($operator);
		if (!in_array($operator, ['AND', 'OR'], true)) {
			throw new InvalidArgumentException('$operator must be either AND or OR');
		}

		foreach ($conditions as $condition) {
			if ($condition !== null && !$condition instanceof Condition) {
				throw new InvalidArgumentException('Items of $conditions array must be null or instance of class PhpMyAdmin\SqlParser\Components\Condition.');
			}
		}

		$conditions = array_map(fn($condition) => $condition->expr ?? null, $conditions);
		$conditions = array_unique($conditions);
		$conditions = array_filter($conditions);
		$conditions = array_values($conditions);

		if (count($conditions) === 0) {
			return null;
		} elseif (count($conditions) === 1) {
			return new Condition($conditions[0]);
		} else {
			$conditions = self::sortConditions($conditions);
			return new Condition(implode(" $operator ", $conditions));
		}
	}


	/**
	 * It sorts conditions in query by their potential speed.
	 * @param array<string> $conditions
	 * @return array<string>
	 */
	private static function sortConditions(array $conditions): array
	{
		usort($conditions, function ($condition): int {
			if (preg_match('/^.+\s+LIKE\s+\S+$/i', $condition) !== false) {
				return -1;
			}
			return 0;
		});
		return $conditions;
	}
}
