<?php

declare(strict_types=1);

use MartinHons\NetteGrid\QueryHelper;
use PhpMyAdmin\SqlParser\Components\Condition;
use Tester\Assert;
use Tester\TestCase;

include __DIR__ . '/../Bootstrap.php';


class MergeConditions extends TestCase
{
    public function testWrongperator(): void
    {
        Assert::exception(
            fn() => QueryHelper::mergeConditions([], 'nonsense'),
            InvalidArgumentException::class,
        );
    }


    public function testNullResult(): void
    {
        Assert::same(null, QueryHelper::mergeConditions([]));
        Assert::same(null, QueryHelper::mergeConditions([null]));
        Assert::same(null, QueryHelper::mergeConditions([null, null]));
        Assert::same(null, QueryHelper::mergeConditions([null, new Condition]));
    }


    public function testOneCondition(): void
    {
        Assert::same('1=1', QueryHelper::mergeConditions([new Condition('1=1')])->expr);
        Assert::same('1=1', QueryHelper::mergeConditions([null, new Condition('1=1')])->expr);
    }


    public function testTwoConditions(): void
    {
        Assert::same('1=1 AND 2=2', QueryHelper::mergeConditions([new Condition('1=1'), new Condition('2=2')])->expr);
        Assert::same('1=1 AND 2=2', QueryHelper::mergeConditions([null, new Condition('1=1'), new Condition('2=2')])->expr);
        Assert::same('1=1 OR 2=2', QueryHelper::mergeConditions([new Condition('1=1'), new Condition('2=2')], 'or')->expr);
        Assert::same('1=1 OR 2=2', QueryHelper::mergeConditions([null, new Condition('1=1'), new Condition('2=2')], 'OR')->expr);
    }
}

(new MergeConditions)->run();