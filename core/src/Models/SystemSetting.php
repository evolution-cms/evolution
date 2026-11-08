<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent;
use EvolutionCMS\Traits;

/**
 * EvolutionCMS\Models\SystemSetting
 *
 * @property string $setting_name
 * @property string $setting_value
 *
 * @mixin \Eloquent
 */
class SystemSetting extends BaseModel
{
    use Traits\Models\ManagerActions;

    protected $table = 'system_settings';

	protected $primaryKey = 'setting_name';
	public $incrementing = false;
	public $timestamps = false;

	protected $fillable = [
	    'setting_name',
		'setting_value'
	];
}
