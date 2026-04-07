<?php namespace EvolutionCMS\Controllers;

use EvolutionCMS\Models;
use EvolutionCMS\Interfaces\ManagerTheme;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Finder;

class Tmplvar extends AbstractController implements ManagerTheme\PageControllerInterface
{
    protected $view = 'page.tmplvar';

    protected $standartTypes = [
        'text'             => 'Text',
        'textarea'         => 'Textarea',
        'textareamini'     => 'Textarea (Mini)',
        'richtext'         => 'RichText',
        'dropdown'         => 'DropDown List Menu',
        'listbox'          => 'Listbox (Single-Select)',
        'listbox-multiple' => 'Listbox (Multi-Select)',
        'option'           => 'Radio Options',
        'checkbox'         => 'Check Box',
        'image'            => 'Image',
        'file'             => 'File',
        'url'              => 'URL',
        'email'            => 'Email',
        'number'           => 'Number',
        'date'             => 'Date'
    ];

    protected $displayWidgets = [
        'datagrid'      => 'Data Grid',
        'richtext'      => 'RichText',
        'viewport'      => 'View Port',
        'custom_widget' => 'Custom Widget'
    ];

    protected $displayFormats = [
        'htmlentities' => 'HTML Entities',
        'date'         => 'Date Formatter',
        'unixtime'     => 'Unixtime',
        'delim'        => 'Delimited List',
        'htmltag'      => 'HTML Generic Tag',
        'hyperlink'    => 'Hyperlink',
        'image'        => 'Image',
        'string'       => 'String Formatter'
    ];

    protected $defaultProperties = [
        'text'             => [],
        'textarea'         => [],
        'textareamini'     => [],
        'richtext'         => [],
        'dropdown'         => [],
        'listbox'          => [],
        'listbox-multiple' => [],
        'option'           => [],
        'checkbox'         => [],
        'image'            => [
            'width' => [
                [
                    'label'   => 'Width',
                    'type'    => 'text',
                    'value'   => '120',
                    'default' => '120',
                    'desc'    => ''
                ]
            ],
            'height' => [
                [
                    'label'   => 'Height',
                    'type'    => 'text',
                    'value'   => '120',
                    'default' => '120',
                    'desc'    => ''
                ]
            ],
            'thumbnailer' => [
                [
                    'label'   => 'Thumbnailer',
                    'type'    => 'text',
                    'value'   => '',
                    'default' => '',
                    'desc'    => ''
                ]
            ],
        ],
        'file'             => [],
        'url'              => [],
        'email'            => [],
        'number'           => [
            'step' => [
                [
                    'label'   => 'step',
                    'type'    => 'text',
                    'value'   => '1',
                    'default' => '1',
                    'desc'    => ''
                ]
            ],
            'min'  => [
                [
                    'label'   => 'min',
                    'type'    => 'text',
                    'value'   => '',
                    'default' => '',
                    'desc'    => ''
                ]
            ],
            'max'  => [
                [
                    'label'   => 'max',
                    'type'    => 'text',
                    'value'   => '',
                    'default' => '',
                    'desc'    => ''
                ]
            ]
        ],
        'date'             => []
    ];

    protected $events = [
        'OnRichTextEditorRegister',
        'OnTVFormPrerender',
        'OnTVFormRender',
    ];

    /** @var Models\SiteTmplvar|null */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function checkLocked(): ?string
    {
        $out = Models\ActiveUser::locked(301, $this->getElementId())
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
        if ($this->getIndex() == 300) {
            return $this->managerTheme->getCore()->hasPermission('new_template');
        }
        if ($this->getIndex() == 301) {
            return $this->managerTheme->getCore()->hasPermission('edit_template');
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function process(): bool
    {
        $this->object = $this->parameterData();
        $this->parameters = [
            'data'              => $this->object,
            'categories'        => $this->parameterCategories(),
            'types'             => $this->parameterTypes(),
            'display'           => $this->parameterDisplay(),
            'categoriesWithTpl' => $this->parameterCategoriesWithTpl(),
            'tplOutCategory'    => $this->parameterTplOutCategory(),
            'roles'             => $this->parameterRoles(),
            'action'            => $this->getIndex(),
            'events'            => $this->parameterEvents(),
            'actionButtons'     => $this->parameterActionButtons(),
            'groupsArray'       => $this->getGroupsArray(),
            'defaultProperties' => json_encode($this->defaultProperties),
            // :TODO delete
            'origin'            => isset($_REQUEST['or']) ? (int) $_REQUEST['or'] : 76,
            'originId'          => isset($_REQUEST['oid']) ? (int) $_REQUEST['oid'] : null
        ];

        return true;
    }

    /**
     * @return Models\SiteTmplvar
     */
    protected function parameterData()
    {
        $id = $this->getElementId();

        /** @var Models\SiteTmplvar $data */
        $data = Models\SiteTmplvar::with('templates')
            ->firstOrNew(['id' => $id], [
                'category' => (int) get_by_key($_REQUEST, 'catid', 0)
            ]);

        if ($id > 0) {
            $_SESSION['itemname'] = $data->caption;
            if ($data->locked === 1 && $_SESSION['mgrRole'] != 1) {
                $this->managerTheme->alertAndQuit('error_no_privileges');
            }
        } elseif (isset($_REQUEST['itemname'])) {
            $data->name = $_REQUEST['itemname'];
        } else {
            $_SESSION['itemname'] = $this->managerTheme->getLexicon('new_template');
            $data->category = isset($_REQUEST['catid']) ? (int) $_REQUEST['catid'] : 0;
        }
        $data->properties = json_decode($data->properties ?? '', true) ?? [];
        $values = $this->managerTheme->loadValuesFromSession($_POST);
        if ($values) {
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

    protected function parameterTypes(): array
    {
        return [
            0 => ['optgroup' => $this->parameterStandartTypes()],
            1 => ['optgroup' => $this->parameterCustomTypes()]
        ];
    }

    protected function parameterStandartTypes(): array
    {
        return [
            'name'    => 'Standard Type',
            'options' => $this->standartTypes
        ];
    }

    protected function parameterCustomTypes(): array
    {
        $customTvs = [
            'custom_tv' => 'Custom Input'
        ];

        $finder = Finder\Finder::create()
            ->in(EVO_BASE_PATH . 'assets/tvs')
            ->depth(0)
            ->notName('/^index\.html$/')
            ->sortByName();

        /** @var Finder\SplFileInfo $ctv */
        foreach ($finder as $ctv) {
            $filename = $ctv->getFilename();
            $customTvs['custom_tv:' . $filename] = $filename;
            $propertiesFile = 'assets/tvs/' . $filename . '/' . $filename . '.properties.php';
            if (Storage::exists($propertiesFile)) {
                $cfg = require EVO_BASE_PATH . $propertiesFile;
                if (is_array($cfg)) {
                    $this->defaultProperties['custom_tv:' . $filename] = $cfg;
                }
            }
        }

        return [
            'name'    => 'Custom Type',
            'options' => $customTvs
        ];
    }

    protected function parameterDisplay(): array
    {
        return [
            0 => ['optgroup' => $this->parameterDisplayWidgets()],
            1 => ['optgroup' => $this->parameterDisplayFormats()]
        ];
    }

    protected function parameterDisplayWidgets(): array
    {
        return [
            'name'    => 'Widgets',
            'options' => $this->displayWidgets
        ];
    }

    protected function parameterDisplayFormats(): array
    {
        return [
            'name'    => 'Formats',
            'options' => $this->displayFormats
        ];
    }

    protected function parameterTplOutCategory(): Collection
    {
        return Models\SiteTemplate::with('tvs')
            ->where('category', '=', 0)
            ->orderBy('templatename', 'ASC')
            ->get();
    }

    protected function parameterCategoriesWithTpl(): Collection
    {
        return Models\Category::with('templates.tvs')
            ->whereHas('templates')
            ->orderBy('rank', 'ASC')
            ->get();
    }

    protected function parameterRoles(): Collection
    {
        return Models\UserRole::with('tvs')->orderBy('name', 'ASC')->get();
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
            'id'          => $this->getElementId(),
            'controller'  => $this,
            'forfrontend' => 1
        ]);
        if (\is_array($out)) {
            $out = implode('', $out);
        }

        return (string) $out;
    }

    protected function parameterActionButtons()
    {
        return [
            'select'    => 1,
            'save'      => $this->managerTheme->getCore()->hasPermission('save_template'),
            'new'       => $this->managerTheme->getCore()->hasPermission('new_template'),
            'duplicate' => $this->object->getKey() && $this->managerTheme->getCore()->hasPermission('new_template'),
            'delete'    => $this->object->getKey() && $this->managerTheme->getCore()->hasPermission('delete_template'),
            'cancel'    => 1
        ];
    }

    private function getSelectedTplFromRequest(): array
    {
        return array_unique(array_map('intval', get_by_key($_POST, 'template', [], 'is_array')));
    }

    private function getSelectedRoleFromRequest(): array
    {
        return array_unique(array_map('intval', get_by_key($_POST, 'role', [], 'is_array')));
    }

    private function getGroupsArray()
    {
        $id = $this->getElementId();
        return Models\SiteTmplvarAccess::where('tmplvarid', $id)->get()->pluck('documentgroup')->toArray();
    }

    public function isSelectedTemplate(Models\SiteTemplate $item)
    {
        return (
            $this->object->templates->contains('id', $item->getKey())
            || \in_array(
                $item->getKey(),
                $this->getSelectedTplFromRequest(), true
            )
        );
    }

    public function isSelectedRole(Models\UserRole $item)
    {
        return (
            $this->object->userRoles->contains('id', $item->getKey())
            || \in_array(
                $item->getKey(),
                $this->getSelectedRoleFromRequest(), true
            )
        );
    }


}
