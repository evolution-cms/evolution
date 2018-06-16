<?php namespace EvolutionCMS\Legacy;

Class TemplateParser
{

    /**
     * @param array $config [action, tabs, toArray]
     * @param array $data
     * @return string
     */
    public function output($config = array(), $data = array()) {
        $modx = evolutionCMS();

        $output = '';
        $action = !empty($config['action']) ? $config['action'] : (!empty($_REQUEST['a']) ? $_REQUEST['a'] : '');
        $tab = isset($config['tab']) ? ' AND tab IN(' . $config['tab'] . ')' : '';

        if($action) {
            $sql = $modx->getDatabase()->query('SELECT t1.*, IF(t1.alias=\'\',t1.name,t1.alias) AS alias, t2.category AS category_name
			FROM ' . $modx->getDatabase()->getFullTableName('system_templates') . ' AS t1
			INNER JOIN ' . $modx->getDatabase()->getFullTableName('categories') . ' AS t2 ON t2.id=t1.category
			WHERE t1.action IN(' . $action . ') ' . $tab . '
			ORDER BY t1.tab ASC, t1.rank ASC');

            if($modx->getDatabase()->getRecordCount($sql)) {
                $tabs = array();
                while($row = $modx->getDatabase()->getRow($sql)) {
                    if(!$row['value'] && !empty($data[$row['name']])) {
                        $row['value'] = $data[$row['name']];
                    }
                    $tabs[$row['tab']]['category_name'] = $row['category_name'];
                    $tabs[$row['tab']][$row['name']] = $this->render($row);
                }

                if(!empty($config['toArray'])) {
                    $output = $tabs;
                } else {
                    $output .= '<div class="tab-pane" id="pane_' . $action . '">';
                    $output .= '
					<script type="text/javascript">
						var pane_' . $action . ' = new WebFXTabPane(document.getElementById("pane_' . $action . '"), ' . ($modx->config['remember_last_tab'] == 1 ? 'true' : 'false') . ');
					</script>';

                    foreach($tabs as $idTab => $tab) {
                        $output .= '<div class="tab-page" id="tab_' . $action . '_' . $idTab . '">';
                        $output .= '
						<h2 class="tab">' . (!empty($config['tabs'][$idTab]) ? $config['tabs'][$idTab] : $tab['category_name']) . '</h2>
						<script type="text/javascript">pane_' . $action . '.addTabPage(document.getElementById("tab_' . $action . '_' . $idTab . '"));</script>';
                        unset($tab['category_name']);
                        foreach($tab as $item) {
                            $output .= $item;
                        }
                        $output .= '</div>';
                    }
                    $output .= '</div>';
                }
            }
        }

        return $output;
    }

    /**
     * @param array $data
     * @return string
     */
    private function render($data) {
        $modx = evolutionCMS(); global $_lang, $_country_lang;

        $data['lang.name'] = (isset($_lang[$data['alias']]) ? $_lang[$data['alias']] : $data['alias']);
        $data['value'] = (isset($_POST[$data['name']][$data['value']]) ? $_POST[$data['name']][$data['value']] : (isset($data['value']) ? $modx->getPhpCompat()->htmlspecialchars($data['value']) : ''));
        $data['readonly'] = ($data['readonly'] ? ' readonly' : '');

        $output = '';
        $output .= '<div class="form-group row">';

        switch($data['type']) {

            case 'text':
                $output .= '<label class="col-sm-3" for="[+name+]">[+lang.name+]</label>
					<div class="col-sm-7">
					<input type="text" name="[+name+]" class="form-control" id="[+name+]" value="[+value+]" onChange="documentDirty=true;"[+readonly+] />';
                $output .= $data['content'];
                $output .= '</div>';

                break;

            case 'textarea':
                $output .= '<label class="col-sm-3" for="[+name+]">[+lang.name+]</label>
					<div class="col-sm-7">
					<textarea name="[+name+]" class="form-control" id="[+name+]" onChange="documentDirty=true;"[+readonly+]>[+value+]</textarea>';
                $output .= $data['content'];
                $output .= '</div>';

                break;

            case 'date':
                $data['value'] = (isset($_POST[$data['name']][$data['value']]) ? $modx->toDateFormat($_POST[$data['name']][$data['value']]) : (isset($data['value']) ? $modx->toDateFormat($data['value']) : ''));
                $output .= '<label class="col-sm-3" for="[+name+]">[+lang.name+]</label>
					<div class="col-sm-7">
					<input type="text" name="[+name+]" class="form-control DatePicker" id="[+name+]" value="[+value+]" onChange="documentDirty=true;"[+readonly+] />';
                $output .= $data['content'];
                $output .= '</div>';

                break;

            case 'select':
                $output .= '<label class="col-sm-3" for="[+name+]">[+lang.name+]</label>';
                $output .= '<div class="col-sm-7">';
                $output .= '<select name="[+name+]" class="form-control" id="[+name+]" onChange="documentDirty=true;">';
                if($data['name'] == 'country' && isset($_country_lang)) {
                    $chosenCountry = isset($_POST['country']) ? $_POST['country'] : $data['country'];
                    $output .= '<option value=""' . (!isset($chosenCountry) ? ' selected' : '') . '>&nbsp;</option>';
                    foreach($_country_lang as $key => $value) {
                        $output .= '<option value="' . $key . '"' . (isset($chosenCountry) && $chosenCountry == $key ? ' selected' : '') . '>' . $value . '</option>';
                    }
                } else {
                    if($data['elements']) {
                        $elements = explode('||', $data['elements']);
                        foreach($elements as $key => $value) {
                            $value = explode('==', $value);
                            $output .= '<option value="' . $value[1] . '">' . (isset($_lang[$value[0]]) ? $_lang[$value[0]] : $value[0]) . '</option>';
                        }
                    }
                }
                $output .= '</select>';
                $output .= $data['content'];
                $output .= '</div>';

                break;

            case 'checkbox':
                $output .= '<label class="col-sm-3" for="[+name+]">[+lang.name+]</label>';
                $output .= '<div class="col-sm-7">';
                $output .= '<input type="checkbox" name="[+name+]" class="form-control" id="[+name+]" value="[+value+]" onChange="documentDirty=true;"[+readonly+] />';
                if($data['elements']) {
                    $elements = explode('||', $data['elements']);
                    foreach($elements as $key => $value) {
                        $value = explode('==', $value);
                        $output .= '<br /><input type="checkbox" name="' . $value[0] . '" class="form-control" id="' . $value[0] . '" value="' . $value[1] . '" onChange="documentDirty=true;"[+readonly+] /> ' . (isset($_lang[$value[0]]) ? $_lang[$value[0]] : $value[0]);
                    }
                }
                $output .= $data['content'];
                $output .= '</div>';

                break;

            case 'radio':
                $output .= '<label class="col-sm-3" for="[+name+]">[+lang.name+]</label>';
                $output .= '<div class="col-sm-7">';
                $output .= '<input type="radio" name="[+name+]" class="form-control" id="[+name+]" value="[+value+]" onChange="documentDirty=true;"[+readonly+] />';
                if($data['elements']) {
                    $elements = explode('||', $data['elements']);
                    foreach($elements as $key => $value) {
                        $value = explode('==', $value);
                        $output .= '<br /><input type="radio" name="[+name+]" class="form-control" id="[+name+]_' . $key . '" value="' . $value[1] . '" onChange="documentDirty=true;"[+readonly+] /> ' . (isset($_lang[$value[0]]) ? $_lang[$value[0]] : $value[0]);
                    }
                }
                $output .= $data['content'];
                $output .= '</div>';

                break;

            case 'custom':
                $output .= '<label class="col-sm-3" for="[+name+]">[+lang.name+]</label>';
                $output .= '<div class="col-sm-7">';
                $output .= $data['content'];
                $output .= '</div>';

                break;
        }

        $output .= '</div>';

        $output = $modx->parseText($output, $data);

        return $output;
    }

}
