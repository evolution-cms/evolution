<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent;

/**
 * EvolutionCMS\Models\MembergroupName
 *
 * @property int $id
 * @property string $name
 *
 * BelongsToMany
 * @property Eloquent\Collection $users
 * @property Eloquent\Collection $documentGroups
 *
 * @mixin \Eloquent
 */
class MembergroupName extends Eloquent\Model
{
    protected $table = 'membergroup_names';

    public $timestamps = false;

    protected $fillable = [
        'name'
    ];

    public function users(): Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'member_groups', 'user_group', 'member');
    }

    public function documentGroups(): Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(DocumentgroupName::class, 'membergroup_access', 'membergroup', 'documentgroup')->withPivot(['id', 'context']);
    }
}
