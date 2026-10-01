<?php
return [
    'driver' => env('DB_TYPE', '[+database_type+]'),
    'host' => env('DB_HOST', '[+database_server+]'),
    'port' => env('DB_PORT', '[+database_port+]'),
    'database' => env('DB_DATABASE', '[+database_name+]'),
    'username' => env('DB_USERNAME', '[+user_name+]'), //$database_user
    'password' => env('DB_PASSWORD', '[+password+]'), //$database_password
    'unix_socket' => env('DB_SOCKET', ''),
    'charset' => env('DB_CHARSET', '[+connection_charset+]'), // $database_connection_charset
    'collation' => env('DB_COLLATION', '[+connection_collation+]'), //$database_collation
    'prefix' => env('DB_PREFIX', '[+table_prefix+]'),
    'strict' => (bool) env('DB_STRICT', false),
    'engine' => env('DB_ENGINE'[+database_engine+]),
    // The DSN already names the database: a separate `use` would cost a round trip per request.
    'use_db_after_connecting' => false,
    'options' => [
        PDO::ATTR_STRINGIFY_FETCHES => true,
        PDO::ATTR_PERSISTENT => (bool) env('DB_PERSISTENT', false),
    ] + (in_array(env('DB_TYPE', '[+database_type+]'), ['mysql', 'mariadb'], true) ? [
        // Client-side prepares: one round trip per query instead of three (prepare, execute, close).
        // Values are strings either way (ATTR_STRINGIFY_FETCHES). Quoting follows the connection
        // charset, which is safe for utf8mb4/utf8/latin1; set DB_EMULATE_PREPARES=false for GBK-family charsets.
        PDO::ATTR_EMULATE_PREPARES => (bool) env('DB_EMULATE_PREPARES', true),
    ] : [])
];
