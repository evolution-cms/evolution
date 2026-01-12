<?php
/**
 * @see: https://tracy.nette.org
 */
return [
    /**
     * true - Activate tracy handler all errors contains at the /core/storage/logs/
     * false - Default Evolution CMS Error handler. All errors contains at the /manager/index.php?a=114
     * 'manager' - Activate tracy for users who authorize on the admin panel
     *
     * IMPORTANT! Tracy ignore the "error_reporting" EvolutionCMS setting
     */
    'active' => false,
    'panels' => [
        EvolutionCMS\Tracy\Panels\Database\Panel::class,
        EvolutionCMS\Tracy\Panels\Routing\Panel::class,
        EvolutionCMS\Tracy\Panels\Request\Panel::class,
        EvolutionCMS\Tracy\Panels\Session\Panel::class,
        EvolutionCMS\Tracy\Panels\Event\Panel::class,
        EvolutionCMS\Tracy\Panels\View\Panel::class,
        EvolutionCMS\Tracy\Panels\Auth\Panel::class
    ],
    /**
     * false - Activate tracy for all
     * true - Only handle errors
     * array - list of IP
     * string - IP separated with ","
     *
     * You can add more security if use IP as format SECRET@IP
     *  - SECRET is a value of the $_COOKIE['tracy-debug']
     *  - IP is you ip address
     */
    'hidden' => false
];
