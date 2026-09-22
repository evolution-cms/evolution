<?php

namespace EvolutionCMS\Support\DocumentSave;

use EvolutionCMS\Models\SiteTmplvar;
use EvolutionCMS\Models\SiteTmplvarContentvalue;

/**
 * Reads and writes the TV values of one document with as few queries as the diff needs.
 * @since 3.5.9
 */
final class TemplateVariableValues
{
    /**
     * TVs of a template with the value stored for the document. Non-administrators only
     * get the TVs without access rules or whose document belongs to one of their groups.
     *
     * @return array<int, array{id:int, name:string, type:string, default_text:string, value_id:int|null, value:string|null}>
     */
    public static function forTemplate(int $template, int $documentId, bool $restrictToGroups, array $managerGroups): array
    {
        $query = SiteTmplvar::query()->distinct()
            ->select('site_tmplvars.id', 'site_tmplvars.name', 'site_tmplvars.type', 'site_tmplvars.default_text',
                'site_tmplvar_contentvalues.id as value_id', 'site_tmplvar_contentvalues.value')
            ->join('site_tmplvar_templates', 'site_tmplvar_templates.tmplvarid', '=', 'site_tmplvars.id')
            ->leftJoin('site_tmplvar_contentvalues', function ($join) use ($documentId) {
                $join->on('site_tmplvar_contentvalues.tmplvarid', '=', 'site_tmplvars.id')
                    ->where('site_tmplvar_contentvalues.contentid', '=', $documentId);
            })
            ->leftJoin('site_tmplvar_access', 'site_tmplvar_access.tmplvarid', '=', 'site_tmplvars.id')
            ->where('site_tmplvar_templates.templateid', $template)
            ->orderBy('site_tmplvars.rank');

        if ($restrictToGroups) {
            $query->leftJoin('document_groups', 'site_tmplvar_contentvalues.contentid', '=', 'document_groups.document')
                ->where(function ($q) use ($managerGroups) {
                    $q->whereNull('site_tmplvar_access.documentgroup')
                        ->orWhereIn('document_groups.document_group', $managerGroups);
                });
        }

        $rows = [];
        foreach ($query->get() as $row) {
            $rows[] = [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'type' => (string) $row->type,
                'default_text' => (string) $row->default_text,
                'value_id' => $row->value_id === null ? null : (int) $row->value_id,
                'value' => $row->value,
            ];
        }

        return $rows;
    }

    /**
     * One insert, one delete and an update per changed value; untouched rows cost nothing.
     *
     * @param array $tvs rows from forTemplate()
     * @param array<int, string|null> $desired TV id => value, null removes the row
     */
    public static function sync(int $documentId, array $tvs, array $desired): void
    {
        $insert = [];
        $delete = [];
        foreach ($tvs as $tv) {
            $value = $desired[$tv['id']] ?? null;
            if ($value === null) {
                if ($tv['value_id'] !== null) {
                    $delete[] = $tv['id'];
                }
            } elseif ($tv['value_id'] === null) {
                $insert[] = ['tmplvarid' => $tv['id'], 'contentid' => $documentId, 'value' => $value];
            } elseif ((string) $tv['value'] !== $value) {
                SiteTmplvarContentvalue::query()
                    ->where('contentid', $documentId)->where('tmplvarid', $tv['id'])
                    ->update(['value' => $value]);
            }
        }

        if ($insert) {
            SiteTmplvarContentvalue::query()->insert($insert);
        }
        if ($delete) {
            SiteTmplvarContentvalue::query()->where('contentid', $documentId)->whereIn('tmplvarid', $delete)->delete();
        }
    }
}
