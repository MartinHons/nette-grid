<?php

declare(strict_types=1);

namespace MartinHons\NetteGrid;

use PhpMyAdmin\SqlParser\Components\Expression;

class Column
{
	public function __construct(
		private Expression $expression,
		//private string $title,
		//private Filter $filter
	) {

	}


	public function getExpression(): Expression
	{
		return $this->expression;
	}
}
