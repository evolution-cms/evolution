<?php
if (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}

/**
 *    Secure Manager Documents
 *    This script will mark manager documents as private
 *
 *    A document will be marked as private only if a manager user group
 *    is assigned to the document group that the document belongs to.
 *
 * @param string $docid
 */
function secureMgrDocument($docid = '')
{
    $modx = evolutionCMS();

    $modx->getDatabase()->update('privatemgr = 0', $modx->getDatabase()->getFullTableName("site_content"),
        ($docid > 0 ? "id='$docid'" : "privatemgr = 1"));
    $rs = $modx->getDatabase()->select(
        'DISTINCT sc.id',
        $modx->getDatabase()->getFullTableName("site_content") . " sc
			LEFT JOIN " . $modx->getDatabase()->getFullTableName("document_groups") . " dg ON dg.document = sc.id
			LEFT JOIN " . $modx->getDatabase()->getFullTableName("membergroup_access") . " mga ON mga.documentgroup = dg.document_group",
        ($docid > 0 ? " sc.id='{$docid}' AND " : "") . "mga.id>0"
    );
    $ids = $modx->getDatabase()->getColumn("id", $rs);
    if (count($ids) > 0) {
        $modx->getDatabase()->update('privatemgr = 1', $modx->getDatabase()->getFullTableName("site_content"),
            "id IN (" . implode(", ", $ids) . ")");
    }
}
