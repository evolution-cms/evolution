<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent\Model;

class SystemCliTask extends Model
{
    protected $table = 'system_cli_tasks';

    protected $fillable = [
        'uuid',
        'type',
        'target',
        'requested_version',
        'status',
        'step',
        'progress',
        'message',
        'payload_json',
        'result_json',
        'created_by',
        'locked_by',
        'attempt_count',
        'lease_expires_at',
        'worker_host',
        'worker_pid',
        'error_code',
        'catalog_snapshot_hash',
        'requested_by_snapshot',
        'started_at',
        'heartbeat_at',
        'cancellation_requested_at',
        'finished_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'result_json' => 'array',
        'requested_by_snapshot' => 'array',
        'progress' => 'integer',
        'attempt_count' => 'integer',
        'worker_pid' => 'integer',
        'created_by' => 'integer',
        'lease_expires_at' => 'datetime',
        'started_at' => 'datetime',
        'heartbeat_at' => 'datetime',
        'cancellation_requested_at' => 'datetime',
        'finished_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
