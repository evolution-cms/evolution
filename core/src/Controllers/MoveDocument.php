<?php namespace EvolutionCMS\Controllers;

use EvolutionCMS\Interfaces\ManagerTheme;
use EvolutionCMS\Models;
use EvolutionCMS\Legacy\Permissions;
use EvolutionCMS\Support\MoveDocumentTargetGuard;
use Exception;

class MoveDocument extends AbstractController implements ManagerTheme\PageControllerInterface
{
    protected $view = 'page.move_document';

    /**
     * {@inheritdoc}
     */
    public function checkLocked(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function canView(): bool
    {
        return $this->managerTheme->getCore()->hasPermission('save_document');
    }

    public function process() : bool
    {
        if ((int)$this->getIndex() === 52) {
            $this->handle();
            return false;
        }

        $this->processDisplay();
        return true;
    }

    protected function handle()
    {
        $newParentID = (int)get_by_key($_REQUEST, 'new_parent', 0, 'is_scalar');
        $documentID = $this->getElementId();

        // ok, two things to check.
        // first, document cannot be moved to itself
        // second, new parent must be a folder. If not, set it to folder.
        if ($documentID === $newParentID) {
            $this->managerTheme->alertAndQuit('error_movedocument1');
        }

        if ($documentID <= 0 || $newParentID < 0) {
            $this->managerTheme->alertAndQuit('error_movedocument2');
        }

        $document = $this->getDocument($documentID);

        $parents = $this->managerTheme->getCore()->getParentIds($newParentID);
        if (\in_array($document->getKey(), $parents, true)) {
            $this->managerTheme->alertAndQuit('error_movedocument2');
        }

        // check user has permission to move document to chosen location
        if ($this->managerTheme->getCore()->getConfig('use_udperms') && $document->parent !== $newParentID) {
            $this->checkNewParentPermission($newParentID);
        }

        $evtOut = $this->managerTheme->getCore()->invokeEvent('onBeforeMoveDocument', [
            'id_document' => $document->getKey(),
            'old_parent' => $document->parent,
            'new_parent' => $newParentID
        ]);

        if (\is_array($evtOut) && count($evtOut) > 0) {
            $newParent = (int)array_pop($evtOut);
            if ($newParent === $document->parent) {
                $this->managerTheme->alertAndQuit('error_movedocument2');
            } else {
                $newParentID = $newParent;
            }
        }
        if ($newParentID > 0) {
            $parentDocument = $this->getDocument($newParentID);
            if (MoveDocumentTargetGuard::blocksParent($parentDocument)) {
                $this->managerTheme->alertAndQuit('error_parent_deleted');
            };
            $children = allChildren($document->getKey());
            if (\in_array($parentDocument->getKey(), $children, true)) {
                $this->managerTheme->alertAndQuit('You cannot move a document to a child document!', false);
            }

            $parentDocument->isfolder = true;
            $parentDocument->save();
            if ($document->ancestor && $document->ancestor->children()->count() <= 1) {
                $document->ancestor->isfolder = false;
                $document->ancestor->save();
            }
            $document->parent = $parentDocument->getKey();
        } else {
            $document->parent = 0;
        }
        $document->save();

        // Set the item name for logger
        $_SESSION['itemname'] = $document->pagetitle;

        $this->managerTheme->getCore()->invokeEvent('onAfterMoveDocument', [
            'id_document' => $document->getKey(),
            'old_parent'  => $document->parent,
            'new_parent'  => $newParentID
        ]);

        // empty cache & sync site
        $this->managerTheme->getCore()->clearCache('full');

        header('Location: index.php?a=3&id=' . $document->getKey() . '&r=9');
    }

    protected function processDisplay() : bool
    {
        $id = $this->getElementId();
        $document = $this->getDocument($id);

        // check permissions on the document
        $udperms = new Permissions();
        $udperms->user     = $this->managerTheme->getCore()->getLoginUserID('mgr');
        $udperms->document = $document->getKey();
        $udperms->role     = $_SESSION['mgrRole'];

        if (!$udperms->checkPermissions()) {
            $this->managerTheme->alertAndQuit('access_permission_denied');
        }

        // Set the item name for logger
        $_SESSION['itemname'] = $document->pagetitle;
        $this->parameters['document'] = $document;

        return true;
    }

    /**
     * @param string|int $id
     * @return Models\SiteContent
     */
    protected function getDocument($id) : Models\SiteContent
    {
        try {
            if ($id <= 0) {
                throw new Exception();
            }
            /** @var Models\SiteContent $document */
            $document = Models\SiteContent::withTrashed()->findOrFail($id);
        } catch (Exception $exception) {
            $this->managerTheme->alertAndQuit('error_no_id');
        }

        return $document;
    }

    protected function checkNewParentPermission($id)
    {
        $udperms           = new Permissions;
        $udperms->user     = $this->managerTheme->getCore()->getLoginUserID('mgr');
        $udperms->document = $id;
        $udperms->role     = $_SESSION['mgrRole'];

        if ($udperms->checkPermissions()) {
            return;
        }

        $this->managerTheme->alertAndQuit('access_permission_parent_denied');
    }
}
