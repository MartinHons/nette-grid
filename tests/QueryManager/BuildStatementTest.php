<?php

declare(strict_types=1);

use MartinHons\NetteGrid\QueryManager;
use Tester\Assert;
use Tester\TestCase;

include __DIR__ . '/../Bootstrap.php';


class BuildStatementTest extends TestCase
{
    private PDO $mockPDO;

    public function setUp(): void
    {
        $mockPDO = Mockery::mock(PDO::class);
        $mockPDO->shouldReceive('quote')->with('a')->andReturn("'a'");
        $this->mockPDO = $mockPDO;
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

    public function testWrongQuery(): void
    {
        Assert::exception(fn() => new QueryManager($this->mockPDO, ''), InvalidArgumentException::class);
        Assert::exception(fn() => new QueryManager($this->mockPDO, 'xyz'), InvalidArgumentException::class);
        Assert::exception(fn() => new QueryManager($this->mockPDO, 'SELECT * FROM table;SELECT * FROM table'), InvalidArgumentException::class);
        Assert::exception(fn() => new QueryManager($this->mockPDO, 'SELECT * FROM table JOIN table2 ON table2.id = table.id_table_1'), InvalidArgumentException::class);
    }

    public function testRightQuery(): void
    {
    }

}

(new BuildStatementTest)->run();