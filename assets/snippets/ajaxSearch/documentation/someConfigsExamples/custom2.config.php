<?php
/*
 *  categConfig :   To define the display of categories (output)
 *  Add a category as a switch item. 'uncategorized' item describe the results outside of any category
 *  Add a switch for a new site. The default site is named 'defsite'.
 *  Allowed config parameters : grpLabel, tplResult, tplAjaxResult, display, extract, rank ...
 */
if(!function_exists('categConfig')) {
    function categConfig($site='defsite',$category){
        $config = array();
        $site = strtolower($site);
        $category = strtolower($category);
        switch($site) {
            case 'defsite':
            switch($category){
                case 'arts':
                    $config['grpLabel'] = 'Arts';
                    $config['tplAjaxResult'] = 'imgAjaxResult';      // allow the display of an image
                    break;
                case 'music':
                    $config['grpLabel'] = 'Music';
                    $config['tplAjaxResult'] = 'imgAjaxResult';      // allow the display of an image
                    break;
                case 'geography':
                    $config['grpLabel'] = 'Geography';
                    $config['tplAjaxResult'] = 'imgAjaxResult';		// allow the display of an image
                    break;
                case '':
                    $config['grpLabel'] = 'Site wide';
                    break;
            }
        }
        return $config;
    }
}
?>