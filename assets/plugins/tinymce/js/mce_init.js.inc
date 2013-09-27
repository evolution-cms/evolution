<script language="javascript" type="text/javascript" src="[+mce_url+]tiny_mce/tiny_mce.js?[+refresh_seed+]"></script>
<script language="javascript" type="text/javascript" src="[+mce_url+]js/xconfig.js"></script>
<script language="javascript" type="text/javascript">
tinyMCE.init({
	theme                            : 'advanced',
	skin                             : '[+skin+]',
	skin_variant                     : '[+skin_variant+]',
	mode                             : 'exact',
	elements                         : '[+elmList+]',
	width                            : '[+width+]',
	height                           : '[+height+]',
	language                         : '[+language+]',
	element_format                   : '[+element_format+]',
	schema                           : '[+schema+]',
	paste_text_use_dialog            : true,
	document_base_url                : '[+document_base_url+]',
	relative_urls                    : [+relative_urls+],
	remove_script_host               : [+remove_script_host+],
	convert_urls                     : [+convert_urls+],
	force_br_newlines                : [+force_br_newlines+],
	force_p_newlines                 : [+force_p_newlines+],
	forced_root_block                : '[+forced_root_block+]',
	valid_elements                   : mce_valid_elements,
	popup_css_add                    : '[+mce_url+]style/popup_add.css',
	theme_advanced_source_editor_height : 400,
	accessibility_warnings : false,
	theme_advanced_toolbar_location  : 'top',
	theme_advanced_statusbar_location: 'bottom',
	theme_advanced_toolbar_align     : '[+toolbar_align+]',
	theme_advanced_font_sizes        : '80%,90%,100%,120%,140%,160%,180%,220%,260%,320%,400%,500%,700%',
	content_css                      : '[+content_css+]',
	formats : {
		alignleft   : {selector : 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img', classes : 'justifyleft'},
		alignright  : {selector : 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img', classes : 'justifyright'},
		alignfull   : {selector : 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img', classes : 'justifyfull'}
	},
	apply_source_formatting          : true,
	remove_linebreaks                : false,
	convert_fonts_to_spans           : true,
	plugins                          : '[+plugins+]',
	theme_advanced_buttons1          : '[+buttons1+]',
	theme_advanced_buttons2          : '[+buttons2+]',
	theme_advanced_buttons3          : '[+buttons3+]',
	theme_advanced_buttons4          : '[+buttons4+]',
	theme_advanced_resize_horizontal :  false,
	external_link_list_url           : [+link_list+],
	template_external_list_url       : '[+tpl_list+]',
	template_popup_width             : 550,
	template_popup_height            : 350,
	theme_advanced_blockformats      : '[+mce_formats+]',
	theme_advanced_styles            : '[+css_selectors+]',
	theme_advanced_disable           : '[+disabledButtons+]',
	theme_advanced_resizing          : [+mce_resizing+],
	fullscreen_settings : {
		theme_advanced_buttons1_add_before : 'save'
	},
	plugin_insertdate_dateFormat     : '[+date_format+]',
	plugin_insertdate_timeFormat     : '[+time_format+]',
	entity_encoding                  : '[+entity_encoding+]',
	file_browser_callback            : '[+file_browser_callback+]',
	paste_text_sticky                : true,
	setup : function(ed)
	{
		ed.onPostProcess.add(function(ed, o)
		{
			// State get is set when contents is extracted from editor
			if (o.get)
			{
				o.content = o.content.replace('<p>{{', '{{');
				o.content = o.content.replace('}}</p>', '}}');
				o.content = o.content.replace(/<p>\[([\[\!\~\^])/g, '[$1');
				o.content = o.content.replace(/([\]\!\~\^])\]<\/p>/g, '$1]');
			}
		});
	},
	onchange_callback                : [+onchange_callback+][+terminate+]
	[+customparams+]
})
</script>

<style type="text/css">
.clearlooks2_modalBlocker {background-color:#333;}
</style>
