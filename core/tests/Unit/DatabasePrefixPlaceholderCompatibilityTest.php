<?php

use EvolutionCMS\Database;

test('select replaces legacy prefix placeholders in fields and from clauses', function () {
    $database = new class extends Database {
        public array $queries = [];

        public function replacePrefixPlaceholderInTableName($sql)
        {
            return str_replace('[+prefix+]', 'evo_', $sql);
        }

        public function query($sql, $watchError = true)
        {
            $this->queries[] = $sql;

            return false;
        }
    };

    $database->select(
        [
            'name' => '[+prefix+]site_templates.templatename',
            'id' => '[+prefix+]site_templates.id',
            'category' => "if(isnull([+prefix+]categories.category),'No category',[+prefix+]categories.category)",
        ],
        [
            '[+prefix+]site_templates',
            'left join [+prefix+]categories on [+prefix+]site_templates.category=[+prefix+]categories.id',
        ],
        '',
        '5,1'
    );

    expect($database->queries)->toHaveCount(1);

    $sql = $database->queries[0];

    expect($sql)->not->toContain('[+prefix+]')
        ->and($sql)->toContain('evo_site_templates.templatename as `name`')
        ->and($sql)->toContain('evo_site_templates.id as `id`')
        ->and($sql)->toContain("if(isnull(evo_categories.category),'No category',evo_categories.category) as `category`")
        ->and($sql)->toContain('FROM evo_site_templates left join evo_categories on evo_site_templates.category=evo_categories.id')
        ->and($sql)->toContain('ORDER BY 5,1');
});
