<?php

use EvolutionCMS\Support\MakeTable;

/**
 * MakeTable::handleSorting() returns an ORDER BY clause for extras that page through a table on a
 * public page; orderby and orderdir come straight from the query string.
 */
function makeTableSortingWithGet(array $get): string
{
    $saved = $_GET;
    $_GET = $get;
    try {
        return (new MakeTable())->handleSorting();
    } finally {
        $_GET = $saved;
    }
}

it('keeps a plain or table-qualified sort column and a valid direction', function (array $get, string $clause) {
    expect(makeTableSortingWithGet($get))->toBe($clause);
})->with([
    'defaults' => [[], ' ORDER BY id DESC '],
    'column' => [['orderby' => 'username', 'orderdir' => 'asc'], ' ORDER BY username ASC '],
    'qualified' => [['orderby' => 'u.createdon', 'orderdir' => 'DESC'], ' ORDER BY u.createdon DESC '],
]);

it('refuses SQL in the sort column or direction', function (array $get) {
    expect(makeTableSortingWithGet($get))->toBe(' ORDER BY id DESC ');
})->with([
    'subquery' => [['orderby' => '(SELECT SLEEP(5))']],
    'stacked' => [['orderby' => 'id; DROP TABLE users']],
    'second term' => [['orderby' => 'id, (SELECT 1)']],
    'comment' => [['orderby' => 'id--']],
    'array' => [['orderby' => ['id']]],
    'direction' => [['orderdir' => 'DESC, (SELECT 1)']],
    'direction injection' => [['orderdir' => 'ASC; --']],
]);

it('returns no clause in natural order', function () {
    expect((new MakeTable())->handleSorting(true))->toBe('');
});

it('pages with an integer offset only', function () {
    defined('MAX_DISPLAY_RECORDS_NUM') || define('MAX_DISPLAY_RECORDS_NUM', 10);
    $saved = $_GET;
    try {
        $_GET = ['page' => '3.7'];
        $three = (new MakeTable())->handlePaging();
        $_GET = ['page' => '1 OR 1=1'];
        $bad = (new MakeTable())->handlePaging();
    } finally {
        $_GET = $saved;
    }

    expect($three)->toBe(' LIMIT ' . (2 * MAX_DISPLAY_RECORDS_NUM) . ', ' . MAX_DISPLAY_RECORDS_NUM)
        ->and($bad)->toBe(' LIMIT 0, ' . MAX_DISPLAY_RECORDS_NUM);
});
