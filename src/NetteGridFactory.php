<?php

declare(strict_types=1);

namespace MartinHons\NetteGrid;

use MartinHons\NetteGrid\Components\NetteGrid\NetteGrid;

interface NetteGridFactory
{
    public function create(string $sqlQuery, array $sqlParams): NetteGrid;
}
