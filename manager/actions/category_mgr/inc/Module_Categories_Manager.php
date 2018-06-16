<?php
/**
 * Class for MODx Categories Manager
 */
if (!is_object($modx)
    || $modx->isBackend() === false) {
    die('Please use the MODx Backend.');
}

/**
 * @deprecated use EvolutionCMS\Legacy\ModuleCategoriesManager
 */
class Module_Categories_Manager extends EvolutionCMS\Legacy\ModuleCategoriesManager
{
}
