<?php

declare(strict_types=1);

use MartinHons\NetteGrid\QueryManager;
use Tester\Assert;
use Tester\TestCase;

include __DIR__ . '/../Bootstrap.php';


class GetExprTest extends TestCase
{
    private PDO $mockPDO;


    public function setUp(): void
    {
        $mockPDO = Mockery::mock(PDO::class);
        $mockPDO->shouldReceive('quote')->with('a')->andReturn("'a'");
        $mockPDO->shouldReceive('quote')->with('2024-10-20T20:35:00+02:00')->andReturn("'2024-10-20T20:35:00+02:00'");
        $this->mockPDO = $mockPDO;
    }


    public function tearDown(): void
    {
        Mockery::close();
    }


    public function testWithStar(): void
    {
        $qm = new QueryManager($this->mockPDO, 'SELECT * FROM table');
        Assert::same('xyz', $qm->getExpr('xyz')->column);
    }


    public function testWithJoin(): void
    {
        $qm = new QueryManager($this->mockPDO,
            'SELECT
                table1.xyz,
                table2.abc,
                table3.xyz as def,
                (
                    SELECT
                        abc
                    FROM table4
                    WHERE table4.id_table1 = table1.id
                ) as ghi
            FROM table1
            JOIN table2 = table2.id_table1 = table1.id
            JOIN table3 = table3.id_table1 = table1.id
        ');

        Assert::same('table2.abc', $qm->getExpr('abc')->expr);
        Assert::same('table3.xyz', $qm->getExpr('def')->expr);
        Assert::same('table3.xyz', $qm->getExpr('table3.xyz')->expr);
        Assert::same('SELECT', $qm->getExpr('ghi')->subquery);

        Assert::exception(
            fn() => $qm->getExpr('xyz')->expr,
            InvalidArgumentException::class,
        );
    }


    public function testFunction(): void
    {
        $qm = new QueryManager($this->mockPDO, 'SELECT SUM(a) as abc FROM table');

        Assert::same('SUM', $qm->getExpr('abc')->function);

        Assert::exception(
            fn() => $qm->getExpr('xyz')->expr,
            InvalidArgumentException::class,
        );
    }
}

(new GetExprTest)->run();