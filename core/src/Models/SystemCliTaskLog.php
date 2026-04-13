<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent\Model;

class SystemCliTaskLog extends Model
{
    protected $table = 'system_cli_task_logs';

    public $timestamps = false;

    protected $fillable = [
        'task_id',
        'seq',
        'level',
        'step',
        'message',
        'context_json',
        'created_at',
    ];

    protected $casts = [
        'task_id' => 'integer',
        'seq' => 'integer',
        'context_json' => 'array',
        'created_at' => 'datetime',
    ];
}
