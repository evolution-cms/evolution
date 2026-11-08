<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent;

/**
 * EvolutionCMS\Models\SiteTmplvarAccess
 *
 * @property int $id
 * @property int $tmplvarid
 * @property int $documentgroup
 *
 * @mixin \Eloquent
 */
class SiteTmplvarAccess extends BaseModel
{
	protected $table = 'site_tmplvar_access';
	public $timestamps = false;

	protected $casts = [
		'tmplvarid' => 'int',
		'documentgroup' => 'int'
	];

	protected $fillable = [
		'tmplvarid',
		'documentgroup'
	];

    public function tmplvar()
    {
        return $this->belongsTo(SiteTmplvar::class, 'tmplvarid', 'id');
    }
}
