<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent;

/**
 * EvolutionCMS\Models\DocumentgroupName
 *
 * @property int $id
 * @property string $name

 *
 * BelongsToMany
 * @property Eloquent\Collection $documents
 *
 * @mixin \Eloquent
 */
class DocumentgroupName extends Eloquent\Model
{
    public $timestamps = false;

    protected $casts = [
        'private_memgroup' => 'int',
        'private_webgroup' => 'int'
    ];

    protected $fillable = [
        'name',
        'private_memgroup',
        'private_webgroup',
    ];

    public function documents(): Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(SiteContent::class, 'document_groups', 'document_group', 'document')
            ->withTrashed();
    }

    public function fileGroups(): Eloquent\Relations\HasMany
    {
        return $this->hasMany(FileGroup::class, 'document_group');
    }
}
