(function($){
if ($.fn.pagination){
	$.fn.pagination.defaults.beforePageText = 'Strana';
	$.fn.pagination.defaults.afterPageText = 'z {pages}';
	$.fn.pagination.defaults.displayMsg = 'Zobrazené {from} až {to} z {total} záznamov';
}
if ($.fn.datagrid){
	$.fn.datagrid.defaults.loadMsg = 'Spracováva sa, čakajte prosím ...';
}
if ($.fn.treegrid && $.fn.datagrid){
	$.fn.treegrid.defaults.loadMsg = $.fn.datagrid.defaults.loadMsg;
}
if ($.messager){
	$.messager.defaults.ok = 'OK';
	$.messager.defaults.cancel = 'Zavrieť';
}
$.map(['validatebox','textbox','filebox','searchbox',
		'combo','combobox','combogrid','combotree',
		'datebox','datetimebox','numberbox',
		'spinner','numberspinner','timespinner','datetimespinner'], function(plugin){
	if ($.fn[plugin]){
		$.fn[plugin].defaults.missingMessage = 'Toto pole je povinné.';
	}
});
if ($.fn.validatebox){
	$.fn.validatebox.defaults.rules.email.message = 'Zadajte prosím platnú e-mailovú adresu.';
	$.fn.validatebox.defaults.rules.url.message = 'Zadajte prosím platnú URL adresu.';
	$.fn.validatebox.defaults.rules.length.message = 'Zadajte prosím hodnotu medzi {0} a {1}.';
	$.fn.validatebox.defaults.rules.remote.message = 'Opravte prosím toto pole.';
}
if ($.fn.calendar){
	$.fn.calendar.defaults.firstDay = 1;
	$.fn.calendar.defaults.weeks  = ['N','P','U','S','Š','P','S'];
	$.fn.calendar.defaults.months = ['Jan', 'Feb', 'Mar', 'Apr', 'Máj', 'Jún', 'Júl', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec'];
}
if ($.fn.datebox){
	$.fn.datebox.defaults.currentText = 'Dnes';
	$.fn.datebox.defaults.closeText = 'Zavrieť';
	$.fn.datebox.defaults.okText = 'OK';
}
if ($.fn.datetimebox && $.fn.datebox){
	$.extend($.fn.datetimebox.defaults,{
		currentText: $.fn.datebox.defaults.currentText,
		closeText: $.fn.datebox.defaults.closeText,
		okText: $.fn.datebox.defaults.okText
	});
}
})(jQuery)
