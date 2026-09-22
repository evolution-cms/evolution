<?php
if (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}

use EvolutionCMS\Support\DocumentPrivacy;

/**
 *    Secure Web Documents
 *    This script will mark web documents as private
 *
 *    A document will be marked as private only if a web user group
 *    is assigned to the document group that the document belongs to.
 *
 * @param string $docid
 * @deprecated since 3.5.9 use EvolutionCMS\Support\DocumentPrivacy
 * @todo [remove@3.7] Remove in Evolution CMS 3.7
 */
function secureWebDocument($docid = '', $context = 1)
{
    $context = $context == 0 ? DocumentPrivacy::MANAGER : DocumentPrivacy::WEB;
    if (is_numeric($docid) && $docid > 0) {
        DocumentPrivacy::refresh((int) $docid, $context);
    } else {
        DocumentPrivacy::refreshAll($context);
    }
}

/**
 * @deprecated since 3.5.9 use EvolutionCMS\Support\DocumentPrivacy
 * @todo [remove@3.7] Remove in Evolution CMS 3.7
 */
function secureMgrDocument($docid = '', $context = 0)
{
    secureWebDocument($docid, $context);
}
