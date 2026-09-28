<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent;

/**
 * EvolutionCMS\Models\SiteTmplvarTemplate
 *
 * @property int $tmplvarid
 * @property int $templateid
 * @property int $rank
 *
 * @mixin \Eloquent
 */
class SiteTmplvarTemplate extends Eloquent\Model
{
	protected $table = 'site_tmplvar_templates';

	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'tmplvarid' => 'int',
		'templateid' => 'int',
		'rank' => 'int'
	];

	protected $fillable = [
	    'tmplvarid',
        'templateid',
		'rank'
	];

    public function tmplvar()
    {
        return $this->belongsTo(SiteTmplvar::class, 'tmplvarid','id');
    }
}
