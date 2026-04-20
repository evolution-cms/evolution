<?php

use EvolutionCMS\Support\MysqlDumper;

test('mysql dumper keeps row batch size above zero for small tables with many parts', function () {
    $dumper = new MysqlDumper('test');
    $method = new ReflectionMethod(MysqlDumper::class, 'calculateRowBatchSize');
    $method->setAccessible(true);

    expect($method->invoke($dumper, 1, 15))->toBe(1)
        ->and($method->invoke($dumper, 2, 20))->toBe(1)
        ->and($method->invoke($dumper, 10, 5))->toBe(10);
});
