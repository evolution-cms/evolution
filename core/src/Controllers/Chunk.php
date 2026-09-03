<?php namespace EvolutionCMS\Controllers;

use EvolutionCMS\Models;
use EvolutionCMS\Interfaces\ManagerTheme;
use EvolutionCMS\Support\ChunkFileStore;
use Illuminate\Support\Collection;

class Chunk extends AbstractController implements ManagerTheme\PageControllerInterface
{
    protected $view = 'page.chunk';

    /** A chunk is not this big; half a megabyte does not belong in a form. */
    private const MAX_EDITABLE_FILE_BYTES = 524288;

    protected $events = [
        'OnChunkFormPrerender',
        'OnChunkFormRender',
        'OnRichTextEditorRegister',
        'OnRichTextEditorInit'
    ];

    /** @var Models\SiteHtmlsnippet|null */
    private $object;

    protected $which_editor;

    /**
     * {@inheritdoc}
     */
    public function checkLocked(): ?string
    {
        $out = Models\ActiveUser::locked(78, $this->getElementId())
            ->first();
        if ($out !== null) {
            return sprintf($this->managerTheme->getLexicon('error_no_privileges'), $out->username);
        }

        return $out;
    }

    /**
     * {@inheritdoc}
     */
    public function canView(): bool
    {
        if($this->getIndex() == 77) {
            return $this->managerTheme->getCore()->hasPermission('new_chunk');
        }
        if($this->getIndex() == 78) {
            return $this->managerTheme->getCore()->hasPermission('edit_chunk');
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function process() : bool
    {
        $this->object = $this->parameterData();
        $store = ChunkFileStore::make();
        $name = (string) $this->object->name;

        // Same rule the front end renders by: the file, else the column.
        //
        // @deprecated since 3.5.8 falling back to the column
        // @todo [remove@3.7] Remove in Evolution CMS 3.7
        $extension = $store->writeExtension($name);
        $fileContent = $store->read($name, $store->firstExisting($name));

        if ($fileContent !== null) {
            $this->object->snippet = $fileContent;
        }

        $this->parameters = [
            'data'          => $this->object,
            'categories'    => $this->parameterCategories(),
            'which_editor'  => $this->which_editor,
            'action'        => $this->getIndex(),
            'events'        => $this->parameterEvents(),
            'actionButtons' => $this->parameterActionButtons(),
            'chunkFormats'       => $store->all(),
            'chunkFormatDefault' => $extension,
            'chunkFileExisting'  => $store->existing($name),
            'chunkFileDirectory' => $store->displayDirectory()
        ];

        return true;
    }

    /**
     * @return Models\SiteHtmlsnippet
     */
    protected function parameterData()
    {
        $id = $this->getElementId();

        /** @var Models\SiteHtmlsnippet $data */
        $data = Models\SiteHtmlsnippet::firstOrNew(['id' => $id]);

        if ($data->exists) {
            if (empty($data->count())) {
                $this->managerTheme->alertAndQuit('Chunk not found for id ' . $id . '.', false);
            }

            $_SESSION['itemname'] = $data->name;
            if ($data->locked === 1 && $_SESSION['mgrRole'] != 1) {
                $this->managerTheme->alertAndQuit('error_no_privileges');
            }
        } elseif (isset($_REQUEST['itemname'])) {
            $data->name = $_REQUEST['itemname'];
        } else {
            $_SESSION['itemname'] = $this->managerTheme->getLexicon("new_htmlsnippet");
        }

        $values = $this->managerTheme->loadValuesFromSession($_POST);

        if (!empty($values)) {
            $data->fill($values);
            if (isset($values['which_editor'])) {
                $this->which_editor = $values['which_editor'];
            } else {
                $this->which_editor = 'none';
            }
        }


        return $data;
    }

    protected function parameterCategories(): Collection
    {
        return Models\Category::orderBy('rank', 'ASC')
            ->orderBy('category', 'ASC')
            ->get();
    }

    protected function parameterEvents(): array
    {
        $out = [];

        foreach ($this->events as $event) {
            $out[$event] = $this->callEvent($event);
        }

        return $out;
    }

    private function callEvent($name)
    {
        switch ($name) {
            case 'OnRichTextEditorRegister':
                $out = $this->callEventOnRichTextEditorRegister();
                break;

            case 'OnRichTextEditorInit':
                $out = $this->callEventOnRichTextEditorInit();
                break;

            default:
                $out = $this->callEventDefault($name);
        }

        return $out;
    }

    protected function callEventOnRichTextEditorRegister()
    {
        $out = $this->managerTheme->getCore()->invokeEvent('OnRichTextEditorRegister', [
            'controller' => $this
        ]);
        if (empty($out) && !is_array($out)) {
            $out = [];
        }

        return (array)$out;
    }

    protected function callEventOnRichTextEditorInit()
    {
        if ($this->which_editor) {
            $editor = $this->which_editor;
        } else if ($this->object->editor_name !== 'none') {
            $editor = $this->object->editor_name;
        } else {
            $editor = 'none';
        }

        $out = $this->managerTheme->getCore()->invokeEvent('OnRichTextEditorInit', [
            'editor' => $editor,
            'elements' => ['post'],
            'controller' => $this
        ]);

        if (\is_array($out)) {
            return implode('', $out);
        }

        return (string)$out;
    }

    protected function callEventDefault($name)
    {
        $out = $this->managerTheme->getCore()->invokeEvent($name, [
            'id' => $this->getElementId(),
            'controller' => $this
        ]);
        if (\is_array($out)) {
            $out = implode('', $out);
        }

        return (string)$out;
    }

    protected function parameterActionButtons()
    {
        return [
            'select' => 1,
            'save' => $this->managerTheme->getCore()->hasPermission('save_chunk'),
            'new' => $this->managerTheme->getCore()->hasPermission('new_chunk'),
            'duplicate' => !empty($this->object->getKey()) && $this->managerTheme->getCore()->hasPermission('new_chunk'),
            'delete' => !empty($this->object->getKey()) && $this->managerTheme->getCore()->hasPermission('delete_chunk'),
            'cancel' => 1
        ];
    }
}
