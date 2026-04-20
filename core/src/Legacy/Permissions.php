<?php namespace EvolutionCMS\Legacy;

use EvolutionCMS\Models\SiteContent;

/**
 * @class: udperms
 */
class Permissions
{
    /**
     * @var int
     */
    public $user;
    /**
     * @var int
     */
    public $document;
    /**
     * @var int
     */
    public $role;
    /**
     * @var bool
     */
    public $duplicateDoc = false;

    /**
     * @return bool
     */
    public function checkPermissions()
    {

        global $udperms_allowroot;
        $modx = evo();

        $document = $this->document;
        $role = $this->role;

        if ($role == 1) {
            return true;  // administrator - grant all document permissions
        }

        if ($modx->getConfig('use_udperms') == 0 || $modx->getConfig('use_udperms') == "" || !isset($modx->config['use_udperms'])) {
            return true; // permissions aren't in use
        }
        $parent = SiteContent::query()->find($this->document);
        $parent = $parent->parent ?? null;
        if ($document == 0 && $parent == null && $udperms_allowroot == 1) {
            return true;
        } // User is allowed to create new document in root
        if (($this->duplicateDoc == true || $document == 0) && $parent == 0 && $udperms_allowroot == 0) {
            return false; // deny duplicate || create new document at root if Allow Root is No
        }

        // get document groups for current user
        $docgrp = empty($_SESSION['mgrDocgroups']) ? '' : implode(' || dg.document_group = ',
            $_SESSION['mgrDocgroups']);

        /* Note:
            A document is flagged as private whenever the document group that it
            belongs to is assigned or links to a user group. In other words if
            the document is assigned to a document group that is not yet linked
            to a user group then that document will be made public. Documents that
            are private to the manager users will not be private to web users if the
            document group is not assigned to a web user group and visa versa.
         */
        $permissionsok = false;  // set permissions to false

        $query = SiteContent::query()->select('id');
        if(!empty($docgrp)){
            $query = $query->leftJoin('document_groups', 'site_content.id','=', 'document_groups.document')
                ->where(function($q) use ($docgrp) {
                    $q->where('document_groups.document_group', $docgrp)
                        ->orWhere('site_content.privatemgr', 0);
                });
        }else {
            $query->where('privatemgr', 0);
        }
        if ($query->count() > 0) {
            $permissionsok = true;
        }

        return $permissionsok;
    }
}
