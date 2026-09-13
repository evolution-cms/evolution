<?php
class DATEPICKER {
    /**
     * @return string
     */
    public function getDP() {
        $modx = evolutionCMS();

        $load_script = file_get_contents(__DIR__.'/datepicker.tpl');
        if(!isset(EvolutionCMS()->config['lang_code'])) EvolutionCMS()->config['lang_code'] = $this->getLangCode();
		EvolutionCMS()->config['datetime_format_lc'] = isset(EvolutionCMS()->config['datetime_format']) ? strtolower(EvolutionCMS()->config['datetime_format']) : 'dd-mm-yyyy';
        return EvolutionCMS()->mergeSettingsContent($load_script);
    }

    /**
     * @return string
     */
    public function getLangCode() {
        $lang = evolutionCMS()->get('ManagerTheme')->getLang();

        $dp_path = str_replace('\\', '/', __DIR__);

        return is_file("{$dp_path}/i18n/datepicker.{$lang}.js") ? $lang : 'en';
    }
}
