<?php namespace EvolutionCMS\Controllers;

use EvolutionCMS\Models;
use EvolutionCMS\Interfaces\ManagerTheme;
use Illuminate\Support\Collection;

class Plugin extends AbstractController implements ManagerTheme\PageControllerInterface
{
    protected $view = 'page.plugin';

    protected $events = [
        'OnPluginFormPrerender',
        'OnPluginFormRender'
    ];

    /** @var Models\SitePlugin|null */
    private $object;

    protected $internal;

    /**
     * {@inheritdoc}
     */
    public function checkLocked(): ?string
    {
        $out = Models\ActiveUser::locked(102, $this->getElementId())
            ->first();
        if ($out !== null) {
            $out = sprintf($this->managerTheme->getLexicon('error_no_privileges'), $out->username);
        }

        return $out;
    }

    /**
     * {@inheritdoc}
     */
    public function canView(): bool
    {
        switch ($this->getIndex()) {
            case 101:
                $out = $this->managerTheme->getCore()->hasPermission('new_plugin');
                break;

            case 102:
                $out = $this->managerTheme->getCore()->hasPermission('edit_plugin');
                break;

            default:
                $out = false;
        }

        return $out;
    }

    /**
     * {@inheritdoc}
     */
    public function process() : bool
    {
        $this->object = $this->parameterData();

        $this->parameters = [
            'data' => $this->object,
            'categories' => $this->parameterCategories(),
            'action' => $this->getIndex(),
            'importParams' => $this->parameterImportParams(),
            'docBlockList' => $this->parameterDocBlockList(),
            'internal' => $this->internal,
            'events' => $this->parameterEvents(),
            'actionButtons' => $this->parameterActionButtons()
        ];

        return true;
    }

    /**
     * @return Models\SitePlugin
     */
    protected function parameterData()
    {
        $id = $this->getElementId();

        /** @var Models\SitePlugin $data */
        $data = Models\SitePlugin::firstOrNew(['id' => $id]);

        if ($data->exists) {
            if (empty($data->count())) {
                $this->managerTheme->alertAndQuit('Plugin not found for id ' . $id . '.', false);
            }

            $_SESSION['itemname'] = $data->name;
            if ($data->locked === 1 && $_SESSION['mgrRole'] != 1) {
                $this->managerTheme->alertAndQuit('error_no_privileges');
            }
        } elseif (isset($_REQUEST['itemname'])) {
            $data->name = $_REQUEST['itemname'];
        } else {
            $_SESSION['itemname'] = $this->managerTheme->getLexicon("new_snippet");
        }

        $values = $this->managerTheme->loadValuesFromSession($_POST);

        if (!empty($values)) {
            $data->fill($values);
        }

        return $data;
    }

    protected function parameterCategories(): Collection
    {
        return Models\Category::orderBy('rank', 'ASC')
            ->orderBy('category', 'ASC')
            ->get();
    }

    protected function parameterImportParams(): array
    {

        return Models\SiteModule::query()->join('site_module_depobj', function ($join) {
            $join->on('site_module_depobj.module', '=', 'site_modules.id');
            $join->on('site_module_depobj.type', '=', \DB::raw(30));
        })->join('site_plugins', 'site_plugins.id', '=', 'site_module_depobj.resource')
            ->where('site_module_depobj.resource', $this->object->getKey())
            ->where('site_modules.enable_sharedparams', 1)->orderBy('site_modules.name', 'ASC')->get()
            ->pluck('name', 'guid')->toArray();
    }

    protected function parameterDocBlockList()
    {
        $out = '';
        $internal = [];
        if (isset($this->object->plugincode)) {
            $snippetcode = $this->object->plugincode;
            $parsed = $this->managerTheme->getCore()->parseDocBlockFromString($snippetcode);
            $out = $this->managerTheme->getCore()->convertDocBlockIntoList($parsed);
            $internal[0]['events'] = isset($parsed['events']) ? $parsed['events'] : '';
        }

        $this->internal = json_encode($internal);

        return $out;
    }

    protected function parameterEvents(): array
    {
        $out = [];

        foreach ($this->events as $event) {
            $out[$event] = $this->callEvent($event);
        }

        return $out;
    }

    private function callEvent($name): string
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
            'select'    => 1,
            'save'      => $this->managerTheme->getCore()->hasPermission('save_plugin'),
            'new'       => $this->managerTheme->getCore()->hasPermission('new_plugin'),
            'duplicate' => !empty($this->object->getKey()) && $this->managerTheme->getCore()->hasPermission('new_plugin'),
            'delete'    => !empty($this->object->getKey()) && $this->managerTheme->getCore()->hasPermission('delete_plugin'),
            'cancel'    => 1
        ];
    }
}
