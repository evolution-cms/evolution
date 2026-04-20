<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent\Model;

class SystemWorkerHealth extends Model
{
    protected $table = 'system_worker_health';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'last_worker_run_at',
        'last_worker_pick_at',
        'last_worker_success_at',
        'last_worker_failed_at',
        'last_worker_error_code',
        'last_worker_host',
        'last_worker_pid',
        'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'last_worker_run_at' => 'datetime',
        'last_worker_pick_at' => 'datetime',
        'last_worker_success_at' => 'datetime',
        'last_worker_failed_at' => 'datetime',
        'last_worker_pid' => 'integer',
        'updated_at' => 'datetime',
    ];
}
