<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent;

/**
 * EvolutionCMS\Models\SystemEventname
 *
 * @property int $id
 * @property string $name
 * @property int $service
 * @property string $groupname
 *
 * @mixin \Eloquent
 *
 * BelongsToMany
 * @property Eloquent\Collection $plugins
 */
class SystemEventname extends Eloquent\Model
{
	protected $table = 'system_eventnames';

	public $timestamps = false;

	protected $casts = [
		'service' => 'int'
	];

	protected $fillable = [
		'name',
		'service',
		'groupname'
	];

    public function plugins() : Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            SitePlugin::class,
            (new SitePluginEvent)->getTable(),
            'evtid',
            'pluginid'
        )->withPivot('priority');
    }
}
