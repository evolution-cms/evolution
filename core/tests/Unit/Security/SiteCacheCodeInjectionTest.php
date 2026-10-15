<?php

/*
| siteCache.idx.php is PHP that every request includes. Cache::writeSiteCache() escaped the values
| but pasted chunk names, setting names, aliases, content types, snippet/plugin names and event
| names raw between single quotes, so a chunk named  x');<php code>//  - saved by anyone with
| save_chunk, no code permission needed - ran as code on the next request after a cache refresh
| (or broke every page with a parse error when the name merely held a quote).
*/

function buildSiteCacheWithHostileNames(): array
{
    $output = evoRunPhp(dirname(__DIR__, 2) . '/Fixtures/site-cache-harness.php', [], $exitCode, false);
    $data = json_decode($output, true);

    expect($exitCode)->toBe(0)
        ->and($data)->toBeArray();

    return $data;
}

it('writes a cache file that parses and runs nothing from the names', function () {
    $cache = buildSiteCacheWithHostileNames();

    expect($cache['parsed'])->toBeTrue($cache['error'] ?? '')
        ->and($cache['injected'])->toBeNull();
});

it('keeps every name exactly as stored', function () {
    $cache = buildSiteCacheWithHostileNames();

    expect($cache['chunks'])->toBe(["x');\$GLOBALS['site_cache_injected']=1;//", 'back\\slash'])
        ->and($cache['chunkValues']['back\\slash'])->toBe("it's")
        ->and($cache['settings'])->toContain("set'ting\\")
        ->and($cache['aliases'][1]['alias'])->toBe("home'\\")
        ->and($cache['documents'])->toHaveKey("home'\\")
        ->and($cache['contentTypes'][1])->toBe("text/plain');\$GLOBALS['site_cache_injected']=2;//")
        ->and($cache['snippets'])->toBe(["sn'ip"])
        ->and($cache['plugins'])->toBe(["pl'ug"])
        ->and($cache['events'])->toBe(["On'Event" => ["pl'ug"]]);
});
