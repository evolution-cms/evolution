<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent;

/**
 * EvolutionCMS\Models\DocumentGroup
 *
 * @property int $id
 * @property int $document_group
 * @property int $document
 *
 * @mixin \Eloquent
 */
class DocumentGroup extends Eloquent\Model
{
	protected $table = 'document_groups';

	public $timestamps = false;

	protected $casts = [
		'document_group' => 'int',
		'document' => 'int'
	];

	protected $fillable = [
		'document_group',
		'document'
	];
}
