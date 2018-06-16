//<?php
/**
 * Quick Manager+
 * 
 * Enables QuickManager+ front end content editing support
 *
 * @category 	plugin
 * @version 	1.5.10
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL v3)
 * @internal    @properties &jqpath=Path to jQuery;text;assets/js/jquery.min.js &loadmanagerjq=Load jQuery in manager;list;true,false;false &loadfrontendjq=Load jQuery in front-end;list;true,false;false &noconflictjq=jQuery noConflict mode in front-end;list;true,false;false &loadfa=Load Font Awesome css in front-end;list;true,false;true &loadtb=Load modal box in front-end;list;true,false;true &tbwidth=Modal box window width;text;80% &tbheight=Modal box window height;text;90% &hidefields=Hide document fields from front-end editors;text;parent &hidetabs=Hide document tabs from front-end editors;text; &hidesections=Hide document sections from front-end editors;text; &addbutton=Show add document here button;list;true,false;true &tpltype=New document template type;list;parent,id,selected;parent &tplid=New document template id;int;3 &custombutton=Custom buttons;textarea; &managerbutton=Show go to manager button;list;true,false;true &logout=Logout to;list;manager,front-end;manager &disabled=Plugin disabled on documents;text; &autohide=Autohide toolbar;list;true,false;true &position= Toolbar position;list;top,right,bottom,left,before;top &editbuttons=Inline edit buttons;list;true,false;false &editbclass=Edit button CSS class;text;qm-edit &newbuttons=Inline new resource buttons;list;true,false;false &newbclass=New resource button CSS class;text;qm-new &tvbuttons=Inline template variable buttons;list;true,false;false &tvbclass=Template variable button CSS class;text;qm-tv &removeBg=Remove toolbar background;list;yes,no;no &buttonStyle=QuickManager buttons CSS stylesheet;list;actionButtons,navButtons;navButtons  
 * @internal	@events OnParseDocument,OnWebPagePrerender,OnDocFormPrerender,OnDocFormSave,OnManagerLogout 
 * @internal	@modx_category Manager and Admin
 * @internal    @legacy_names QM+,QuickEdit
 * @internal    @installset base, sample
 * @internal    @disabled 1
 * @reportissues https://github.com/modxcms/evolution
 * @documentation Official docs [+site_url+]assets/plugins/qm/readme.html
 * @link        http://www.maagit.fi/modx/quickmanager-plus
 * @author      Mikko Lammi
 * @author      Since 2011: yama, dmi3yy, segr, Nicola1971.
 * @lastupdate  02/02/2018 
 */

// In manager
if (!$modx->checkSession()) return;

$show = TRUE;

if ($disabled  != '') {
    $arr = array_filter(array_map('intval', explode(',', $disabled)));
    if (in_array($modx->documentIdentifier, $arr)) {
        $show = FALSE;
    }
}

if ($show) {
    // Replace [*#tv*] with QM+ edit TV button placeholders
    if ($tvbuttons == 'true') {
        if ($modx->event->name == 'OnParseDocument') {
             $output = &$modx->documentOutput;
             $output = preg_replace('~\[\*#(.*?)\*\]~', '<!-- '.$tvbclass.' $1 -->[*$1*]', $output);
             $modx->documentOutput = $output;
         }
     }
    include_once($modx->config['base_path'].'assets/plugins/qm/qm.inc.php');
    $qm = new Qm($modx, $jqpath, $loadmanagerjq, $loadfrontendjq, $noconflictjq, $loadfa, $loadtb, $tbwidth, $tbheight, $hidefields, $hidetabs, $hidesections, $addbutton, $tpltype, $tplid, $custombutton, $managerbutton, $logout, $autohide, $position, $editbuttons, $editbclass, $newbuttons, $newbclass, $tvbuttons, $tvbclass, $buttonStyle, $removeBg);
}
