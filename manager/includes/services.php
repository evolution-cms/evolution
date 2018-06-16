<?php
global $database_server, $dbase, $database_user, $database_password, $table_prefix, $database_connection_charset, $database_connection_method;

return [
    EvolutionCMS\Interfaces\DatabaseInterface::class => array(
        'class' => EvolutionCMS\Database::class,
        'arguments' => array(
            $database_server,
            $dbase,
            $database_user,
            $database_password,
            $table_prefix,
            $database_connection_charset,
            $database_connection_method,
        ),
        'calls' => array(
            array(
                'method' => 'query',
                'arguments' => array(
                    "SET SQL_MODE = '';"
                )
            )
        )
    ),
    EvolutionCMS\Interfaces\PhpCompatInterface::class => array(
        'class' => EvolutionCMS\Legacy\PhpCompat::class
    ),
    EvolutionCMS\Interfaces\MailInterface::class => array(
        'class' => EvolutionCMS\Mail::class
    ),
    EvolutionCMS\Interfaces\PasswordHashInterface::class => array(
        'class' => EvolutionCMS\Legacy\PasswordHash::class
    ),
    EvolutionCMS\Interfaces\MakeTableInterface::class => array(
        'class' => EvolutionCMS\Support\MakeTable::class
    ),
    EvolutionCMS\Interfaces\ExportSiteInerface::class => array(
        'class' => EvolutionCMS\Legacy\ExportSite::class
    ),
    EvolutionCMS\Interfaces\ManagerApiInterface::class => array(
        'class' => EvolutionCMS\Legacy\ManagerApi::class
    ),
    EvolutionCMS\Interfaces\DeprecatedCoreInterface::class => array(
        'class'=> EvolutionCMS\Legacy\DeprecatedCore::class
    ),
    EvolutionCMS\Interfaces\ModifiersInterface::class => array(
        'class' => EvolutionCMS\Legacy\Modifiers::class
    )
];
