<?php

declare(strict_types=1);

use MartinHons\NetteGrid\QueryManager;
use Tester\Assert;
use Tester\TestCase;

include __DIR__ . '/../Bootstrap.php';


class BuildQueryTest extends TestCase
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


    public function testWrongPlaceholderCount(): void
    {
        Assert::exception(
            fn() => new QueryManager($this->mockPDO, 'XYZ', ['a']),
            InvalidArgumentException::class,
        );
        Assert::exception(
            fn() => new QueryManager($this->mockPDO, 'XYZ ?', []),
            InvalidArgumentException::class,
        );
    }
}

(new BuildQueryTest)->run();