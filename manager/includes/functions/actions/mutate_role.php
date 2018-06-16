<?php

if(!function_exists('render_form')) {
    /**
     * @param string $name
     * @param string $label
     * @param string $status
     * @return string
     */
    function render_form($name, $label, $status = '')
    {
        $modx = evolutionCMS();
        global $roledata;

        $tpl = '<label class="d-block" for="[+name+]check">
		<input name="[+name+]check" id="[+name+]check" class="click" type="checkbox" onchange="changestate(document.userform.[+name+])" [+checked+] [+status+]>
		<input type="hidden" class="[+set+]" name="[+name+]" value="[+value+]">
		[+label+]
	</label>';

        $checked = ($roledata[$name] == 1) ? 'checked' : '';
        $value = ($roledata[$name] == 1) ? 1 : 0;
        if ($status == 'disabled') {
            $checked = 'checked';
            $value = 1;
            $set = 'fix';
        } else {
            $set = 'set';
        }

        $ph = array(
            'name'    => $name,
            'checked' => $checked,
            'status'  => $status,
            'value'   => $value,
            'label'   => $label,
            'set'     => $set
        );

        return $modx->parseText($tpl, $ph);
    }
}
