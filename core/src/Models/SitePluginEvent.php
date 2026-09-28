<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent;
use EvolutionCMS\Traits;

/**
 * EvolutionCMS\Models\SitePluginEvent
 *
 * @property int $pluginid
 * @property int $evtid
 * @property int $priority
 *
 * @mixin \Eloquent
 */
class SitePluginEvent extends Eloquent\Model
{
    use Traits\Models\ManagerActions;

    protected $table = 'site_plugin_events';

	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'pluginid' => 'int',
		'evtid' => 'int',
		'priority' => 'int'
	];

	protected $fillable = [
		'pluginid',
		'evtid',
		'priority'
	];
}
