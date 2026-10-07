<?php

use EvolutionCMS\Database;

function sqlCapturingDatabase(string $driver = 'mysql'): Database
{
    return new class($driver) extends Database {
        public array $queries = [];

        public function __construct(private string $driverName)
        {
        }

        public function getConfig($option = null)
        {
            return $option === 'driver' ? $this->driverName : null;
        }

        public function replacePrefixPlaceholderInTableName($sql)
        {
            return $sql;
        }

        public function query($sql, $watchError = true)
        {
            $this->queries[] = $sql;

            return false;
        }

        public function getInsertId($conn = null)
        {
            return 1;
        }
    };
}

test('update quotes hostile array keys as identifiers', function () {
    $db = sqlCapturingDatabase();
    $db->update(['a` = (SELECT 1), `b' => 'x'], 't', 'id=1');

    expect($db->queries[0])->toBe("UPDATE t SET `a`` = (SELECT 1), ``b` = 'x' WHERE id=1");
});

test('insert quotes hostile array keys as identifiers', function () {
    $db = sqlCapturingDatabase();
    $db->insert(['a`) VALUES (1); -- ' => 'x'], 't');

    expect($db->queries[0])->toBe("INSERT INTO t (`a``) VALUES (1); -- `) VALUES('x')");
});

test('insert-select quotes only non-plain column names', function () {
    $db = sqlCapturingDatabase();
    $db->insert(['id' => 1, 'x`,(SELECT 1)' => 2], 't', '*', 'src');

    expect($db->queries[0])->toContain('(id,`x``,(SELECT 1)`)');
});

test('pgsql identifiers double the double quote', function () {
    $db = sqlCapturingDatabase('pgsql');
    $db->update(['a" = 1, "b' => 'x'], 't');

    expect($db->queries[0])->toBe("UPDATE t SET \"a\"\" = 1, \"\"b\" = 'x' ");
});

test('select quotes hostile aliases', function () {
    $db = sqlCapturingDatabase();
    $db->select(['x` , (SELECT 1) as `y' => 'col'], 't');

    expect($db->queries[0])->toContain('col as `x`` , (SELECT 1) as ``y`');
});

test('orderByDate only accepts asc or desc', function () {
    $builder = Mockery::mock(Illuminate\Database\Eloquent\Builder::class);
    $builder->shouldReceive('orderByRaw')->once()->with('CASE WHEN pub_date != 0 THEN pub_date ELSE createdon END DESC')->andReturnSelf();
    $builder->shouldReceive('orderByRaw')->once()->with('CASE WHEN pub_date != 0 THEN pub_date ELSE createdon END ASC')->andReturnSelf();

    $model = new EvolutionCMS\Models\SiteContent();
    $model->scopeOrderByDate($builder, 'desc, (SELECT SLEEP(5))');
    $model->scopeOrderByDate($builder, 'ASC');
});
