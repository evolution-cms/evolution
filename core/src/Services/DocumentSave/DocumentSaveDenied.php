<?php

namespace EvolutionCMS\Services\DocumentSave;

use RuntimeException;

/**
 * The save was refused; the message is already translated for the user.
 * @since 3.5.9
 */
final class DocumentSaveDenied extends RuntimeException
{
    /**
     * @param bool $restoreForm send the user back to the editor with the posted values kept
     */
    public function __construct(string $message, public readonly bool $restoreForm = true)
    {
        parent::__construct($message);
    }
}
