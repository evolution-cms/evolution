<?php

test('manager pings the session keep-alive before the session timeout runs out', function () {
    $evoJs = file_get_contents(dirname(__DIR__, 4) . '/manager/media/style/default/js/evo.js');

    // an interval equal to the timeout lets the session, and the resource locks shown in the tree, expire
    expect($evoJs)->toContain('w.setInterval(this.keepMeAlive, 1000 * 60 * this.config.session_timeout / 2);');
});
