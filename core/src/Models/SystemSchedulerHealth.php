<?php namespace EvolutionCMS\Models;

class SystemSchedulerHealth extends BaseModel
{
    protected $table = 'system_scheduler_health';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'last_heartbeat_at',
        'last_heartbeat_host',
        'last_heartbeat_mode',
        'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'last_heartbeat_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
