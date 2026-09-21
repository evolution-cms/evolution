(function($){
	$.extend($.fn.datagrid.defaults, {
		dragSelection: false,
		onBeforeDrag: function(row){},	// return false to deny drag
		onStartDrag: function(row){},
		onStopDrag: function(row){},
		onDragEnter: function(targetRow, sourceRow){},	// return false to deny drop
		onDragOver: function(targetRow, sourceRow){},	// return false to deny drop
		onDragLeave: function(targetRow, sourceRow){},
		onBeforeDrop: function(targetRow, sourceRow, point){},
		onDrop: function(targetRow, sourceRow, point){},	// point:'append','top','bottom'
	});
	$.extend($.fn.datagrid.methods, {
		_appendRow: function(jq, row){
			return jq.each(function(){
				var dg = $(this);
				var rows = $.isArray(row) ? row : [row];
				$.map(rows, function(row){
					dg.datagrid('appendRow', row).datagrid('enableDnd', dg.datagrid('getRows').length-1);
					if (row._selected){
						dg.datagrid('selectRow', dg.datagrid('getRows').length-1);
					}
				});
			});
		},
		_insertRow: function(jq, param){
			return jq.each(function(){
				var dg = $(this);
				var index = param.index;
				var row = param.row;
				var rows = $.isArray(row) ? row : [row];
				$.map(rows, function(row, i){
					dg.datagrid('insertRow', {
						index: (index+i),
						row: row
					}).datagrid('enableDnd', index+i);
					if (row._selected){
						dg.datagrid('selectRow', index+i);
					}
				});
			});
		},
		_deleteRow: function(jq, row){
			return jq.each(function(){
				var dg = $(this);
				var rows = $.isArray(row) ? row : [row];
				$.map(rows, function(row){
					var index = dg.datagrid('getRowIndex', row);
					dg.datagrid('deleteRow', index);
				});
			});
		}
	});
	
	var disabledDroppingRows = [];
	
	$.extend($.fn.datagrid.methods, {
		enableDnd: function(jq, index){
			return jq.each(function(){
				var target = this;
				var state = $.data(this, 'datagrid');
				var dg = $(this);
				var opts = state.options;
				
				var draggableOptions = {
					disabled: false,
					revert: true,
					cursor: 'pointer',
					proxy: function(source) {
						var p = $('<div style="z-index:9999999999999"></div>').appendTo('body');
						var draggingRow = getDraggingRow(source);
						var rows = $.isArray(draggingRow) ? draggingRow : [draggingRow];
						$.map(rows, function(row,i){
							var index = dg.datagrid('getRowIndex', row);
							var tr1 = opts.finder.getTr(target, index, 'body', 1);
							var tr2 = opts.finder.getTr(target, index, 'body', 2);
							tr2.clone().removeAttr('id').removeClass('droppable').appendTo(p);
							tr1.clone().removeAttr('id').removeClass('droppable').find('td').insertBefore(p.find('tr:eq('+i+') td:first'));
							$('<td><span class="tree-dnd-icon tree-dnd-no" style="position:static">&nbsp;</span></td>').insertBefore(p.find('tr:eq('+i+') td:first'));
						});
						p.find('td').css('vertical-align','middle');
						p.hide();
						return p;
					},
					deltaX: 15,
					deltaY: 15,
					onBeforeDrag:function(e){
						var draggingRow = getDraggingRow(this);
						if (opts.onBeforeDrag.call(target, draggingRow) == false){return false;}
						if ($(e.target).parent().hasClass('datagrid-cell-check')){return false;}
						if (e.which != 1){return false;}
						$.map($.isArray(draggingRow)?draggingRow:[draggingRow], function(row){
							var index = $(target).datagrid('getRowIndex', row);
							opts.finder.getTr(target, index).droppable({accept:'no-accept'});
						});
					},
					onStartDrag: function() {
						$(this).draggable('proxy').css({
							left: -10000,
							top: -10000
						});
						var draggingRow = getDraggingRow(this);
						opts.onStartDrag.call(target, draggingRow);
						state.draggingRow = draggingRow;
					},
					onDrag: function(e) {
						var x1=e.pageX,y1=e.pageY,x2=e.data.startX,y2=e.data.startY;
						var d = Math.sqrt((x1-x2)*(x1-x2)+(y1-y2)*(y1-y2));
						if (d>3){	// when drag a little distance, show the proxy object
							$(this).draggable('proxy').show();
							var tr = opts.finder.getTr(target, parseInt($(this).attr('datagrid-row-index')), 'body');
							$.extend(e.data, {
								startX: tr.offset().left,
								startY: tr.offset().top,
								offsetWidth: 0,
								offsetHeight: 0
							});
						}
						this.pageY = e.pageY;
					},
					onStopDrag:function(){
						$.map(disabledDroppingRows, function(row){
							var r = $(row);
							if (r.hasClass('datagrid-row')){
								r.droppable('enable');
							} else if (r.find('tr.datagrid-row:first').length == 0){
								r.droppable('enable');
							}
						});
						disabledDroppingRows = [];
						
						$.map($.isArray(state.draggingRow) ? state.draggingRow : [state.draggingRow], function(row){
							var index = dg.datagrid('getRowIndex', row);
							dg.datagrid('enableDnd', index);
						});
						opts.onStopDrag.call(target, state.draggingRow);
					}
				};
				var droppableOptions = {
					accept: 'tr.datagrid-row',
					onDragEnter: function(e, source){
						if ($(this).droppable('options').disabled){return;}
						if (opts.onDragEnter.call(target, getRow(this), getDraggingRow(source)) == false){
							setProxyFlag(source, false);
							var tr = opts.finder.getTr(target, $(this).attr('datagrid-row-index'));
							tr.find('td').css('border', '');
							tr.droppable('disable');
							$(this).droppable('disable');
							disabledDroppingRows.push(this);
						}
					},
					onDragOver: function(e, source) {
						if ($(this).droppable('options').disabled){
							return;
						}
						if ($.inArray(this, disabledDroppingRows) >= 0){
							return;
						}
						var pageY = source.pageY;
						var top = $(this).offset().top;
						var bottom = top + $(this).outerHeight();
						
						setProxyFlag(source, true);
						var tr = opts.finder.getTr(target, $(this).attr('datagrid-row-index'));
						tr.children('td').css('border','');
						if (pageY > top + (bottom - top) / 2) {
							tr.children('td').css('border-bottom','1px solid red');
						} else {
							tr.children('td').css('border-top','1px solid red');
						}
						
						if (opts.onDragOver.call(target, getRow(this), getDraggingRow(source)) == false){
							setProxyFlag(source, false);
							tr.find('td').css('border', '');
							tr.droppable('disable');
							$(this).droppable('disable');
							disabledDroppingRows.push(this);
						}
					},
					onDragLeave: function(e, source) {
						if ($(this).droppable('options').disabled){
							return;
						}
						setProxyFlag(source, false);
						var tr = opts.finder.getTr(target, $(this).attr('datagrid-row-index'));
						tr.children('td').css('border','');
						opts.onDragLeave.call(target, getRow(this), getDraggingRow(source));
					},
					onDrop: function(e, source) {
						if ($(this).droppable('options').disabled){
							return;
						}
						
						var tr = opts.finder.getTr(target, $(this).attr('datagrid-row-index'));
						var td = tr.children('td');
						var point =  parseInt(td.css('border-top-width')) ? 'top' : 'bottom';
						td.css('border','');
						var dRow = getRow(this);
						var sRow = getDraggingRow(source);
						
						if (opts.onBeforeDrop.call(target, dRow, sRow, point) == false){
							return;
						}
						insert.call(this);
						opts.onDrop.call(target, dRow, sRow, point);
						
						function insert(){
							var sourceIndex = parseInt($(source).attr('datagrid-row-index'));
							var destIndex = parseInt($(this).attr('datagrid-row-index'));
							var sourceTarget = $(source).closest('div.datagrid-view').children('table')[0];
							var target = $(this).closest('div.datagrid-view').children('table')[0];
							
							if ($(this).hasClass('datagrid-view')){
								$(target).datagrid('_appendRow', sRow);
								$(sourceTarget).datagrid('_deleteRow', sRow);
								if ($(sourceTarget).datagrid('getRows').length == 0){
									$(sourceTarget).datagrid('enableDnd');
								}
							} else if (target != sourceTarget){
								var index = point == 'top' ? destIndex : (destIndex+1);
								if (index >= 0){
									$(sourceTarget).datagrid('_deleteRow', sRow);
									$(target).datagrid('_insertRow', {
										index: index,
										row: sRow
									});
								}
							} else {
								var dg = $(target);
								var index = point == 'top' ? destIndex : (destIndex+1);
								if (index >= 0){
									dg.datagrid('_deleteRow', sRow);
									var destIndex = parseInt($(this).attr('datagrid-row-index'));
									var index = point == 'top' ? destIndex : (destIndex+1);
									if (index >= 0){
										dg.datagrid('_insertRow', {
											index: index,
											row: sRow
										});
									}
								}
							}
						}
					}
				}
				
				if (index != undefined){
					var trs = opts.finder.getTr(this, index);
				} else {
					var trs = opts.finder.getTr(this, 0, 'allbody');
				}
				trs.draggable(draggableOptions);
				trs.droppable(droppableOptions);
				setDroppable(target);
				
				function setProxyFlag(source, allowed){
					var icon = $(source).draggable('proxy').find('span.tree-dnd-icon');
					icon.removeClass('tree-dnd-yes tree-dnd-no').addClass(allowed ? 'tree-dnd-yes' : 'tree-dnd-no');
				}
				function getRow(tr){
					if (!$(tr).hasClass('datagrid-row')){return null}
					var target = $(tr).closest('div.datagrid-view').children('table')[0];
					var opts = $(target).datagrid('options');
					return opts.finder.getRow(target, $(tr));
				}
				function getDraggingRow(tr){
					if (!$(tr).hasClass('datagrid-row')){return null}
					var target = $(tr).closest('div.datagrid-view').children('table')[0];
					var opts = $(target).datagrid('options');
					if (opts.dragSelection){
						var rows = $(target).datagrid('getSelections');
						$.map(rows, function(row){
							row._selected = true;
						});
						if (!rows.length){
							var row = opts.finder.getRow(target, $(tr));
							row._selected = false;
							return row;
						}
						return rows;
					} else {
						var row = opts.finder.getRow(target, $(tr));
						if ($(tr).hasClass('datagrid-row-selected')){
							row._selected = true;
						}
						return row;
					}
				}
				function setDroppable(target){
					var c = $(target).datagrid('getPanel').find('div.datagrid-view');
					c.droppable(droppableOptions);
					if (c.find('tr.datagrid-row:first').length){
						c.droppable('disable');
					} else {
						c.droppable('enable');
					}
				}
			});
		}
		
	});
})(jQuery);
