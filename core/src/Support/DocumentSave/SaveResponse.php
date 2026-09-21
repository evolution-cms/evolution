<?php

namespace EvolutionCMS\Support\DocumentSave;

use EvolutionCMS\Services\DocumentSave\DocumentSaveResult;

/**
 * What the editor is told after a save: where the form flow goes next, and the same
 * facts as JSON for the in-place save.
 * @since 3.5.9
 */
final class SaveResponse
{
    /**
     * @param string $stay '' close, '1' new, '2' keep editing
     * @param string $listingPath sort/dir/page of the children listing, already escaped
     */
    public static function redirectUrl(DocumentSaveResult $saved, string $stay, bool $refreshPreview, string $listingPath, string $siteUrl): string
    {
        $id = $saved->id;
        if (!$saved->isNew() && $refreshPreview) {
            return $siteUrl . "index.php?id=$id&z=manprev";
        }

        if ($stay !== '') {
            $newAction = $saved->type === 'reference' ? '72' : '4';
            $url = $stay === '2'
                ? "index.php?a=27&id=$id&r=1&stay=2"
                : "index.php?a=$newAction&pid={$saved->parent}&r=1&stay=" . (int) $stay;
        } else {
            $url = "index.php?a=3&id=$id&r=1";
        }

        return $saved->isNew() ? $url : $url . $listingPath;
    }

    /**
     * @return array<string, mixed>
     */
    public static function payload(DocumentSaveResult $saved, string $redirect, string $token = ''): array
    {
        return [
            'success' => true,
            'id' => $saved->id,
            'mode' => $saved->mode,
            'type' => $saved->type,
            'parent' => $saved->parent,
            'pagetitle' => $saved->pagetitle,
            'alias' => $saved->alias,
            'editedon' => $saved->editedon,
            'redirect' => $redirect,
            'token' => $token,
        ];
    }
}
