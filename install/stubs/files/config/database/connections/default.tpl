<?php
return [
    'driver' => env('DB_TYPE', '[+database_type+]'), //$database_type
    'host' => env('DB_HOST', '[+database_server+]'), //$database_server
    'port' => env('DB_PORT', '[+database_port+]'), //$database_port
    'database' => env('DB_DATABASE', '[+dbase+]'), //$dbase
    'username' => env('DB_USERNAME', '[+user_name+]'), //$database_user
    'password' => env('DB_PASSWORD', '[+password+]'), //$database_password
    'unix_socket' => env('DB_SOCKET', ''),
    'charset' => env('DB_CHARSET', '[+connection_charset+]'), // $database_connection_charset
    'collation' => env('DB_COLLATION', '[+connection_collation+]'), //$database_collation
    'prefix' => env('DB_PREFIX', '[+table_prefix+]'), //$table_prefix
    'strict' => env('DB_STRICT', false),
    'engine' => env('DB_ENGINE'[+database_engine+]),
    'options' => [
        PDO::ATTR_STRINGIFY_FETCHES => true,
    ]
];
