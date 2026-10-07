<?php

test('tree sort column accepts only allowlisted columns', function () {
    expect(normalizeTreeSortBy('pagetitle'))->toBe('pagetitle')
        ->and(normalizeTreeSortBy('publishedon'))->toBe('publishedon')
        ->and(normalizeTreeSortBy('id; DROP TABLE x'))->toBe('menuindex')
        ->and(normalizeTreeSortBy('(SELECT SLEEP(5))'))->toBe('menuindex')
        ->and(normalizeTreeSortBy('menuindex DESC, (SELECT 1)'))->toBe('menuindex')
        ->and(normalizeTreeSortBy(['id']))->toBe('menuindex')
        ->and(normalizeTreeSortBy(null))->toBe('menuindex');
});

test('tree sort direction accepts only ASC or DESC', function () {
    expect(normalizeTreeSortDir('DESC'))->toBe('DESC')
        ->and(normalizeTreeSortDir('desc'))->toBe('DESC')
        ->and(normalizeTreeSortDir('ASC'))->toBe('ASC')
        ->and(normalizeTreeSortDir('ASC, (SELECT 1)'))->toBe('ASC')
        ->and(normalizeTreeSortDir('DESC; --'))->toBe('ASC')
        ->and(normalizeTreeSortDir(null))->toBe('ASC');
});

function recordingTvQuery(): object
{
    defined('EVO_CLASS') || define('EVO_CLASS', \Illuminate\Container\Container::class);
    defined('IN_MANAGER_MODE') || define('IN_MANAGER_MODE', false);
    defined('IN_INSTALL_MODE') || define('IN_INSTALL_MODE', false);
    defined('EVO_API_MODE') || define('EVO_API_MODE', true);

    global $evo;
    $evo = new class {
        public function getDatabase()
        {
            return new class {
                public function getConfig($option = null)
                {
                    return $option === 'prefix' ? 'evo_' : 'mysql';
                }
            };
        }
    };

    return new class {
        public array $raw = [];

        public function whereRaw($sql)
        {
            $this->raw[] = $sql;

            return $this;
        }

        public function where(...$args)
        {
            $this->raw[] = 'where:' . json_encode($args);

            return $this;
        }
    };
}

test('a malformed tv filter matches nothing instead of being dropped', function () {
    $q = recordingTvQuery();
    (new \EvolutionCMS\Models\SiteContent())->scopeTvFilter($q, 'tv:price:>:5:UNSIGNED) OR 1=1 --');

    expect($q->raw)->toBe(['1 = 0']);
    $GLOBALS['evo'] = null;
});

test('a tv filter with a plain non-numeric cast is still applied', function () {
    $q = recordingTvQuery();
    (new \EvolutionCMS\Models\SiteContent())->scopeTvFilter($q, 'tv:name:=:abc:CHAR');

    expect($q->raw)->toBe(['where:' . json_encode(['tv_name.value', '=', 'abc'])]);
    $GLOBALS['evo'] = null;
});
