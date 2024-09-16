<?php

declare(strict_types=1);

use MartinHons\NetteGrid\QueryManager;
use PhpMyAdmin\SqlParser\Components\Condition;
use Tester\Assert;
use Tester\TestCase;

include __DIR__ . '/../Bootstrap.php';


class GetCountDataTest extends TestCase
{
    private PDO $pdo;

    public function setUp(): void
    {
        $this->pdo = new PDO('mysql:host=mariadb;dbname=test_db', 'root', 'root');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->query("
            DROP TABLE person;
            CREATE TABLE `person` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `first_name` varchar(100) NOT NULL,
                `surname` varchar(100) NOT NULL,
                `birthdate` date NOT NULL,
                PRIMARY KEY (`id`)
            );
            INSERT INTO test_db.person (first_name,surname,birthdate) VALUES
                ('Johnny','Cash','1932-02-26'),
                ('Ferdinand','Porsche','1875-09-03'),
                ('Emmy','Destinn','1878-02-26'),
                ('Quentin','Tarantino','1963-03-27'),
                ('Winston','Churchill','1874-11-30'),
                ('Marie','Curie','1867-11-07'),
                ('Nikola','Tesla','1856-07-10'),
                ('Queen','Elizabeth','1952-02-06'),
                ('Bill','Gates','1955-10-28'),
                ('Kiichiro','Toyoda','1894-06-11');
        ");
    }

    public function tearDown(): void
    {
    }


    public function testEmptyWhere(): void
    {
        $qm = new QueryManager($this->pdo, 'SELECT * FROM person');
        Assert::same(['totalCount' => 10, 'filteredCount' => 10], $qm->getCountData(new Condition, new Condition));
    }


    public function testWhereInQuery(): void
    {
        $qm = new QueryManager($this->pdo, 'SELECT * FROM person WHERE birthdate > "1900-01-01"');
        Assert::same(['totalCount' => 4, 'filteredCount' => 4], $qm->getCountData(new Condition, new Condition));
    }


    public function testWhereInFilter(): void
    {
        $qm = new QueryManager($this->pdo, 'SELECT * FROM person');
        Assert::same(['totalCount' => 10, 'filteredCount' => 4], $qm->getCountData(new Condition('birthdate > "1900-01-01"'), new Condition));
    }


    public function testWhereInQueryAndFilter(): void
    {
        $qm = new QueryManager($this->pdo, 'SELECT * FROM person WHERE birthdate > "1900-01-01"');
        Assert::same(['totalCount' => 4, 'filteredCount' => 2], $qm->getCountData(new Condition('first_name LIKE "Q%"'), new Condition));
    }
    /*


    public function testBuildQuery(): void
    {
        Assert::same('XYZ 123', $this->qm->testBuildQuery('XYZ ?', [123]));
        Assert::same('XYZ 123.123', $this->qm->testBuildQuery('XYZ ?', [123.123]));
        Assert::same('XYZ \'a\'', $this->qm->testBuildQuery('XYZ ?', ['a']));

        Assert::same('XYZ 1 0', $this->qm->testBuildQuery('XYZ ? ?', [true, false]));
        Assert::same('XYZ NULL', $this->qm->testBuildQuery('XYZ ?', [null]));
        Assert::same('XYZ \'2024-10-20T20:35:00+02:00\'', $this->qm->testBuildQuery('XYZ ?', [new DateTimeImmutable('2024-10-20 20:35')]));

    }*/
}

(new GetCountDataTest)->run();