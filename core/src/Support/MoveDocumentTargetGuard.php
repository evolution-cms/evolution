<?php

namespace EvolutionCMS\Support;

use EvolutionCMS\Models\SiteContent;

class MoveDocumentTargetGuard
{
    public static function blocksParent(?SiteContent $parentDocument): bool
    {
        return $parentDocument === null || (int)$parentDocument->deleted === 1;
    }
}
