<?php

/*
 * Title: Language File
 * Purpose:
 *  	Default English language file for Ditto
 *  	
 * Note:
 * 		New language keys should added at the bottom of this page
*/

$_lang['language'] = "english";

$_lang['abbr_lang'] = "en";

$_lang['file_does_not_exist'] = "does not exist. Please check the file.";

$_lang['extender_does_not_exist'] = "extender does not exist. Please check it.";

$_lang['default_template'] = <<<TPL

    <div class="ditto_item" id="ditto_item_[+id+]">
        <h3 class="ditto_pageTitle"><a href="[~[+id+]~]">[+pagetitle+]</a></h3>
        <div class="ditto_documentInfo">by <strong>[+author+]</strong> on [+date+]</div>
        <div class="ditto_introText">[+introtext+]</div>
    </div>

TPL;

$_lang["bad_tpl"] = "<p>&[+tpl+] either does not contain any placeholders or is an invalid chunk name, code block, or filename. Please check it.</p>";
$_lang['missing_placeholders_tpl'] = 'One of your Ditto templates are missing placeholders, please check the template below:';

$_lang['no_documents'] = '<p>No documents found.</p>';

$_lang['resource_array_error'] = 'Resource Array Error';
 
$_lang['prev'] = "&lt; Previous";

$_lang['next'] = "Next &gt;";

$_lang['button_splitter'] = "|";

$_lang['default_copyright'] = "[(site_name)] 2006";

$_lang['invalid_class'] = "The Ditto class is invalid. Please check it.";

$_lang['none'] = "None";

$_lang['edit'] = "Edit";

$_lang['dateFormat'] = "%d-%b-%y %H:%M";

// Debug Tab Names

$_lang['info'] = "Info";

$_lang['modx'] = "MODX";

$_lang['fields'] = "Fields";

$_lang['templates'] = "Templates";

$_lang['filters'] = "Filters";

$_lang['prefetch_data'] = "Prefetch Data";

$_lang['retrieved_data'] = "Retreived Data";

// Debug Text

$_lang['placeholders'] = "Placeholders";

$_lang['params'] = "Parameters";

$_lang['basic_info'] = "Basic Info";

$_lang['document_info'] = "Document Info";

$_lang['debug'] = "Debug";

$_lang['version'] = "Version";

$_lang['summarize'] = "Summarize";

$_lang['total'] = "Total";	 

$_lang['sortBy'] = "Sort By";

$_lang['sortDir'] = "Sort Direction";

$_lang['start'] = "Start";
	 
$_lang['stop'] = "Stop";

$_lang['ditto_IDs'] = "IDs";

$_lang['ditto_IDs_selected'] = "Selected IDs";

$_lang['ditto_IDs_all'] = "All IDs";

$_lang['open_dbg_console'] = "Open Debug Console";

$_lang['save_dbg_console'] = "Save Debug Console";

?>