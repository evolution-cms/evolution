<?php return [
    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */
    'default' => 'public',

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local"
    |
    */
    'disks' => [
        'core' => [
            'driver' => 'local',
            'root' => EVO_CORE_PATH,
        ],
        'storage' => [
            'driver' => 'local',
            'root' => EVO_STORAGE_PATH,
        ],
        'public' => [
            'driver' => 'local',
            'root' => EVO_BASE_PATH,
            'url' => EVO_SITE_URL,
            'visibility' => 'public',
        ],
        'manager' => [
            'driver' => 'local',
            'root' => EVO_MANAGER_PATH,
            'url' => EVO_SITE_URL,
            'visibility' => 'public',
        ],
    ],
];
