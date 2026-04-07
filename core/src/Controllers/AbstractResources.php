<?php namespace EvolutionCMS\Controllers;

use Illuminate\Support\Arr;

abstract class AbstractResources extends AbstractController
{
    protected $noData = false;

    public function setNoData(): self
    {
        $this->noData = true;
        return $this;
    }

    public function isNoData(): bool
    {
        return $this->noData;
    }

    /**
     * {@inheritdoc}
     */
    public function checkLocked(): ?string
    {
        return null;
    }

    public function getParameters(array $params = []): array
    {
        return array_merge([
            'index' => $this->getIndex(),
            'mraTranslations' => $this->parameterMraTranslations(),
            'unlockTranslations' => $this->parameterUnLockTranslations()
        ], $params);
    }

    protected function parameterMraTranslations(): array
    {
        return $this->makeTranslations([
            'create_new', 'edit', 'duplicate', 'remove', 'confirm_duplicate_record', 'confirm_delete_template',
            'confirm_delete_tmplvars', 'confirm_delete_htmlsnippet', 'confirm_delete_snippet', 'confirm_delete_plugin',
            'confirm_delete_module'
        ]);
    }

    protected function parameterUnLockTranslations(): array
    {
        $out = $this->makeTranslations([
            'unlock_element_id_warning', 'lock_element_type_1', 'lock_element_type_2', 'lock_element_type_3',
            'lock_element_type_4', 'lock_element_type_5', 'lock_element_type_6', 'lock_element_type_7',
            'lock_element_type_8'
        ]);
        return [
            'msg' => get_by_key($out, 'unlock_element_id_warning'),
            'type1' => get_by_key($out, 'lock_element_type_1'),
            'type2' => get_by_key($out, 'lock_element_type_2'),
            'type3' => get_by_key($out, 'lock_element_type_3'),
            'type4' => get_by_key($out, 'lock_element_type_4'),
            'type5' => get_by_key($out, 'lock_element_type_5'),
            'type6' => get_by_key($out, 'lock_element_type_6'),
            'type7' => get_by_key($out, 'lock_element_type_7'),
            'type8' => get_by_key($out, 'lock_element_type_8'),
        ];
    }

    private function makeTranslations(array $keys)
    {
        $out = Arr::only($this->managerTheme->getLexicon(), $keys);

        foreach ($out as &$value) {
            $value = iconv(
                $this->managerTheme->getCore()->getConfig('evo_charset', 'utf-8')
                , 'utf-8'
                , $value
            );
        }

        return $out;
    }
}
