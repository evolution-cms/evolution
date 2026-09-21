<?php

namespace EvolutionCMS\Services\DocumentSave;

/**
 * @since 3.5.9
 */
final class DocumentSaveResult
{
    /**
     * @param 'new'|'edit' $mode
     */
    public function __construct(
        public readonly int $id,
        public readonly string $mode,
        public readonly string $type,
        public readonly int $parent,
        public readonly string $pagetitle,
        public readonly string $alias,
    ) {
    }

    public function isNew(): bool
    {
        return $this->mode === 'new';
    }
}
