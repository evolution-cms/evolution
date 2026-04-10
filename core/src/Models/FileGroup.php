<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent;

/**
 * EvolutionCMS\Models\FileGroup
 *
 * @property int $id
 * @property int $document_group
 * @property string $file  Relative path from filemanager root (forward slashes, no leading slash)
 *
 * @mixin \Eloquent
 */
class FileGroup extends Eloquent\Model
{
    public $timestamps = false;

    protected $casts = [
        'document_group' => 'int',
    ];

    protected $fillable = [
        'document_group',
        'file',
    ];
}