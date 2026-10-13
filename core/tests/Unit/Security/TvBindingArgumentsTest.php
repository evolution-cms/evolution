<?php

/*
|--------------------------------------------------------------------------
| [*field*] placeholders in @SELECT and @EVAL bindings
|--------------------------------------------------------------------------
|
| parseTvValues() replaced [*field*] with the field's text before the binding
| ran, so whoever could edit a page field could change the SQL or PHP an
| administrator wrote into a TV. TvBindingArguments turns a placeholder inside
| a string literal into a bound parameter or a variable read, writes integer
| values into plain SQL, and refuses every other position.
|
*/

use EvolutionCMS\Support\TvBindingArguments;

$fields = [
    'id' => '5',
    'alias' => 'news',
    'pagetitle' => "O'Brien; \"Jr.\" ?",
    'longtitle' => 'Back\\slash',
];

describe('@SELECT', function () use ($fields) {

    it('binds a placeholder that fills a quoted string', function () use ($fields) {
        expect(TvBindingArguments::sql("id FROM t WHERE pagetitle = '[*pagetitle*]'", $fields))
            ->toBe(['id FROM t WHERE pagetitle = ?', ["O'Brien; \"Jr.\" ?"]]);
    });

    it('binds the whole string when the placeholder is part of it', function () use ($fields) {
        expect(TvBindingArguments::sql("id FROM t WHERE pagetitle LIKE '%[*alias*]%' AND x = 'a-[*id*]-b'", $fields))
            ->toBe(['id FROM t WHERE pagetitle LIKE ? AND x = ?', ['%news%', 'a-5-b']]);
    });

    it('keeps the escapes of the string around the placeholder', function () use ($fields) {
        expect(TvBindingArguments::sql("x FROM t WHERE a = 'it''s [*alias*]' OR b = 'don\\'t [*alias*]'", $fields))
            ->toBe(['x FROM t WHERE a = ? OR b = ?', ["it's news", "don't news"]])
            ->and(TvBindingArguments::sql("x FROM t WHERE a LIKE '\\%[*alias*]'", $fields))
            ->toBe(['x FROM t WHERE a LIKE ?', ['\\%news']])
            ->and(TvBindingArguments::sql("x FROM t WHERE a = 'it''s [*alias*]'", $fields, 'pgsql'))
            ->toBe(['x FROM t WHERE a = ?', ["it's news"]]);
    });

    it('writes an integer value into plain SQL', function () use ($fields) {
        expect(TvBindingArguments::sql('id FROM t WHERE parent = [*id*] LIMIT [*id*]', $fields))
            ->toBe(['id FROM t WHERE parent = 5 LIMIT 5', []]);
    });

    it('refuses a text value in plain SQL', function () use ($fields) {
        expect(TvBindingArguments::sql('id FROM t WHERE pagetitle = [*pagetitle*]', $fields))->toBeNull()
            ->and(TvBindingArguments::sql('id FROM t WHERE parent = [*alias*]', $fields))->toBeNull();
    });

    it('refuses a placeholder in an identifier', function () use ($fields) {
        expect(TvBindingArguments::sql('`[*alias*]` FROM t', $fields))->toBeNull()
            ->and(TvBindingArguments::sql('"[*alias*]" FROM t', $fields, 'pgsql'))->toBeNull()
            ->and(TvBindingArguments::sql('[[*alias*]] FROM t', $fields, 'sqlsrv'))->toBeNull();
    });

    it('treats double quotes as a string on MySQL only', function () use ($fields) {
        expect(TvBindingArguments::sql('x FROM t WHERE a = "[*alias*]"', $fields))
            ->toBe(['x FROM t WHERE a = ?', ['news']]);
    });

    it('refuses a string that never closes', function () use ($fields) {
        expect(TvBindingArguments::sql("x FROM t WHERE a = '[*alias*]", $fields))->toBeNull();
    });

    it('leaves comments and unknown placeholders alone', function () use ($fields) {
        expect(TvBindingArguments::sql("x FROM t /* [*pagetitle*] */ WHERE a = '[*unknown*]' -- [*pagetitle*]", $fields))
            ->toBe(["x FROM t /* [*pagetitle*] */ WHERE a = '[*unknown*]' -- [*pagetitle*]", []]);
    });

    it('merges parser placeholders only after binding the fields', function () use ($fields) {
        // Core::mergePlaceholderContent() resolves [*field*] too, so merging first pasted values back in
        $merge = static function (string $sql) use ($fields): string {
            $sql = preg_replace_callback('/\[\*(\w+)\*\]/', fn ($m) => $fields[$m[1]] ?? $m[0], $sql);

            return str_replace('[+PREFIX+]', 'evo_', $sql);
        };

        expect(TvBindingArguments::prepareSelect("id FROM [+PREFIX+]site_content WHERE pagetitle = '[*pagetitle*]'", $fields, 'sqlite', $merge))
            ->toBe(['id FROM evo_site_content WHERE pagetitle = ?', ["O'Brien; \"Jr.\" ?"]]);
    });

    it('refuses a placeholder form it cannot bind, instead of letting the merge paste it in', function () use ($fields) {
        $merge = static fn (string $sql): string => $sql;

        expect(TvBindingArguments::prepareSelect("id FROM t WHERE a = '[*pagetitle:upper*]'", $fields, 'mysql', $merge))->toBeNull()
            ->and(TvBindingArguments::prepareSelect('id FROM t WHERE a = [*pagetitle@parent*]', $fields, 'mysql', $merge))->toBeNull()
            ->and(TvBindingArguments::prepareSelect('id FROM t WHERE parent = [*id*]', $fields, 'mysql', $merge))
            ->toBe(['id FROM t WHERE parent = 5', []]);
    });

    it('leaves a statement without placeholders as it is', function () use ($fields) {
        expect(TvBindingArguments::sql("pagetitle, id FROM evo_site_content WHERE parent = 2 AND alias != ''", $fields))
            ->toBe(["pagetitle, id FROM evo_site_content WHERE parent = 2 AND alias != ''", []]);
    });
});

describe('@EVAL', function () use ($fields) {

    $evaluate = static function (string $code, array $tvFields) {
        return eval($code);
    };

    it('reads a placeholder in a string literal from $tvFields', function () use ($fields, $evaluate) {
        $code = TvBindingArguments::php("return 'Title: [*pagetitle*]!';", $fields);

        expect($code)->toBe("return ('Title: ' . (string) \$tvFields['pagetitle'] . '!');")
            ->and($evaluate($code, $fields))->toBe("Title: O'Brien; \"Jr.\" ?!");
    });

    it('keeps the field text as text', function () use ($evaluate) {
        $fields = ['pagetitle' => "'.strtoupper('x').'"];

        foreach (["return 'A[*pagetitle*]B';", 'return "A[*pagetitle*]B";'] as $source) {
            expect($evaluate(TvBindingArguments::php($source, $fields), $fields))
                ->toBe("A'.strtoupper('x').'B");
        }
    });

    it('works inside interpolated strings and heredocs', function () use ($fields, $evaluate) {
        $interpolated = TvBindingArguments::php('$n = 2; return "$n: [*alias*]";', $fields);
        $heredoc = TvBindingArguments::php("return <<<TXT\n[*alias*] [*id*]\nTXT;", $fields);

        expect($evaluate($interpolated, $fields))->toBe('2: news')
            ->and($evaluate($heredoc, $fields))->toBe('news 5');
    });

    it('refuses a placeholder outside every string literal', function () use ($fields) {
        expect(TvBindingArguments::php('return [*id*];', $fields))->toBeNull()
            ->and(TvBindingArguments::php("return 'a' . [*alias*];", $fields))->toBeNull();
    });

    it('refuses the positions a value could still escape from', function () use ($fields) {
        expect(TvBindingArguments::php("return <<<'TXT'\n[*alias*]\nTXT;", $fields))->toBeNull()
            ->and(TvBindingArguments::php("return 'a\\[*alias*]';", $fields))->toBeNull()
            ->and(TvBindingArguments::php('$n = 1; return "$n $[*alias*]";', $fields))->toBeNull();
    });

    it('leaves comments, unknown placeholders and plain code alone', function () use ($fields) {
        foreach ([
            "// [*alias*]\nreturn 1;",
            "return '[*unknown*]';",
            "return \$modx->runSnippet('Menu', ['parents' => 0]);",
        ] as $source) {
            expect(TvBindingArguments::php($source, $fields))->toBe($source);
        }
    });
});

it('resolves TV rows and document fields the way parseTvValues does', function () {
    expect(TvBindingArguments::fields(
        ['color' => ['value' => '', 'default_text' => 'red'], 'size' => ['value' => 'L', 'default_text' => 'M'], 'list' => [1, 2]],
        ['id' => 7, 'pagetitle' => 'Home', 'tags' => ['a']]
    ))->toBe(['color' => 'red', 'size' => 'L', 'list' => '', 'id' => '7', 'pagetitle' => 'Home', 'tags' => '']);
});

$copies = ['functions/tv.php', 'functions/actions/mutate_content.php'];

it('runs @SELECT with bound values in both copies of ProcessTVCommand', function (string $copy) {
    $run = json_decode(evoRunPhp(dirname(__DIR__, 2) . '/Fixtures/tv-binding-harness.php', [$copy], $exitCode, false), true);

    expect($exitCode)->toBe(0)
        ->and($run['queries']['selectQuoted'])->toBe([[
            'sql' => 'SELECT id FROM evo_site_content WHERE pagetitle = ?;',
            'bindings' => ["O'Brien; \"Jr.\" '.strtoupper('x').'"],
        ]])
        ->and($run['queries']['selectInteger'])->toBe([[
            'sql' => 'SELECT id FROM evo_site_content WHERE parent = 5;',
            'bindings' => [],
        ]])
        ->and($run['queries']['selectBareText'])->toBe([])
        ->and($run['results']['selectBareText'])->toBe('')
        ->and($run['queries']['selectModifier'])->toBe([])
        ->and($run['results']['selectModifier'])->toBe('');
})->with($copies);

it('runs @EVAL with field values as data in both copies of ProcessTVCommand', function (string $copy) {
    $run = json_decode(evoRunPhp(dirname(__DIR__, 2) . '/Fixtures/tv-binding-harness.php', [$copy], $exitCode, false), true);

    expect($exitCode)->toBe(0)
        ->and($run['results']['evalQuoted'])->toBe("Title: O'Brien; \"Jr.\" '.strtoupper('x').'")
        ->and($run['results']['evalInteger'])->toBe('Id 5')
        ->and($run['results']['evalBare'])->toBe('')
        ->and(implode("\n", array_column($run['events'], 'msg')))
        ->toContain('"tv_selectBareText": the @SELECT binding was not run')
        ->toContain('"tv_evalBare": the @EVAL binding was not run');
})->with($copies);
