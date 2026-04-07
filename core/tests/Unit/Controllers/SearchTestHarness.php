<?php

namespace Tests\Unit\Controllers;

use EvolutionCMS\Controllers\Search;
use EvolutionCMS\Interfaces\ManagerThemeInterface;

class SearchTestHarness extends Search
{
    public function __construct(ManagerThemeInterface $managerTheme, array $data = [])
    {
    }

    public function exposeApplyContainsConditionGroup($query, array $columns, string $search): void
    {
        $this->ciLikeConditions($query, $columns, $search);
    }
}