<?php
/*
 * All available config-params of TinyMCE4
 * https://www.tinymce.com/docs/configure/
 *
 * Belows default configuration setup assures all editor-params have a fallback-value, and type per key is known
 * $this->set( $editorParam, $value, $type, $emptyAllowed=false )
 *
 * $editorParam     = param to set
 * $value           = value to set
 * $type            = string, number, bool, json (array or string)
 * $emptyAllowed    = true, false (allows param:'' instead of falling back to default)
 * If $editorParam is empty and $emptyAllowed is true, $defaultValue will be ignored
 *
 * $this->modxParams holds an array of actual Modx- / user-settings
 *
 * */

if( !empty( $this->modxParams['custom_plugins'])) {
    $this->set('plugins', $this->modxParams['custom_plugins'], 'string' );
};
$this->appendSet('plugins', 'template', ' '); // Assure plugin is loaded / in plugins-list

$this->set('menubar', false, 'bool' ); // https://www.tinymce.com/docs/configure/editor-appearance/#menubar

// Take over global values for each of the 4 rows
if(!empty($this->modxParams['custom_buttons_useglobal'])) {
    $i=1;
    while($i<=4) {
        $this->modxParams['custom_buttons'.$i] = $modx->configGlobal[$this->editorKey.'_custom_buttons'.$i]; 
        $i++;
    }
}

$this->set('toolbar1', $this->modxParams['custom_buttons1'], 'string', false );
$this->set('toolbar2', $this->modxParams['custom_buttons2'], 'string', true );
$this->set('toolbar3', $this->modxParams['custom_buttons3'], 'string', true );
$this->set('toolbar4', $this->modxParams['custom_buttons4'], 'string', true );