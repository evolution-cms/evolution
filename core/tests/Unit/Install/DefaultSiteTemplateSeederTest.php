<?php

test('default site template seeders use the database source for the minimal template', function () {
    $worker = dirname(__DIR__, 2) . '/Mocks/default_site_template_seeder_worker.php';
    $root = dirname(__DIR__, 4);
    $seeders = [
        [
            $root . '/core/database/seeders/SiteTemplatesTableSeeder.php',
            'Database\\Seeders\\SiteTemplatesTableSeeder',
        ],
        [
            $root . '/install/stubs/seeds/install/SiteTemplatesTableSeeder.php',
            'EvolutionCMS\\Installer\\Install\\SiteTemplatesTableSeeder',
        ],
    ];

    foreach ($seeders as [$seeder, $class]) {
        $exitCode = null;
        $output = evoRunPhp($worker, [$seeder, $class], $exitCode);
        $template = json_decode($output, true, flags: JSON_THROW_ON_ERROR);

        expect($exitCode)->toBe(0)
            ->and($template['templatesource'])->toBe('db');
    }
});
