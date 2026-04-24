var url = decodeURIComponent(window.location.href);
var _GET = decodeURIComponent(window.location.search.slice(1))
        .split('&')
        .reduce(function _reduce (a,b) {
          b = b.split('=');
          if (a[b[0]]) {
            if (is_array(a[b[0]])) {a[b[0]].push(b[1])}
            else {var arr=[];arr.push(a[b[0]]);arr.push( b[1]);a[b[0]]=arr;}
          } else {a[b[0]] = b[1];}
          return a;
        }, {});

function link(){
	mass = location.href.split('?');
	return mass[0]+'?id='+_GET['id']+'&a='+_GET['a'];
}

function store_search(val){
	$('.item_list .catalog_item').each(function(){
		var search_name = $(this).find('h3').text() || '';
		search_name = search_name.toLowerCase();
		if ( search_name.indexOf( val.toLowerCase() ) < 0 ) {
			$(this).hide();
		} else {
			$(this).show();
		}
	})
}

store = {
	categories:{},
	types:{},
	installedState:{},
	installedCatalog:[],
	currentList:[],
	currentTemplate:'list',
	consoleCatalog:[],
	catalogBootLoading:false,
	systemTaskHealth:{
		scheduler:null,
		worker:null
	},
	systemTaskUiFlags:{
		can_view:0,
		can_manage_packages:0,
		can_site_update:0
	},
	systemTaskPollTimer:null,
	systemTaskPollInFlight:false,
	systemTaskLastPollAt:0,
	systemTaskTerminal:false,
	systemTaskElapsedTimer:null,
	systemTaskPollTaskId:0,
	systemTaskPollTaskTitle:'',
	systemTaskPollToken:0,
	systemTaskRefreshTaskId:0,
	systemTaskRefreshPermissionsTaskId:0,
	systemTaskCancelInFlight:false,
	systemTaskWarnings:[],
	allCategoryId:null,
	extend:function(obj1){
		hash = '';
		if ($('[name="hash"]').val() != '') {
			res = eval('('+$('[name="hash"]').val()+')');
			hash = res.hash;
		};
		param = {
			hash:hash,
			lang:$('[name="language"]').val()
		};
		return $.extend(obj1,param);
	},
	verifyUser: function(){
		if ($('[name="hash"]').val() !='') {
			store.query('verifyuser',{'verify':'1'},function(data){
				if ( data.result ) {
					store.updateUserCategory( data );
				};


				store.showUserForms( data.result );
			});
		}
	},
	showUserForms: function(bool){
		if (bool){
			res = eval('('+$('[name="hash"]').val()+')');
			$('#username').html( res.username );
			$('#login').hide();
			$('.logined').show();
		}
	},
	logout: function(){
		$.ajax({url:link()+"&action=exituser",type:'POST',data:{res:$('[name="hash"]').val()},success:function(){
		window.location.href = window.location.href
		}});
	},
	login:function(){
		$('.cart_list .error').hide();
		var res ={};
		store.query('login',{name:$('[name="name"]').val(),password:$('[name="password"]').val()},function(data){
			if (data.result) {
				res.hash = data.hash;
				res.username = data.username;
				$('[name="hash"]').val( JSON.stringify(res) );
				//switch user forms enter/exit
				store.updateUserCategory(data);
				store.showUserForms(true);
				//remember user
				$.ajax({url:link()+"&action=saveuser",type:'POST',data:{res:$('[name="hash"]').val()}});
			} else {
				$('.cart_list .error').fadeIn();
			}
		});
	},
	init:function(){
		store.syncTheme();
		store.observeParentTheme();
		store.query('start',{'user':'1'},function(data){
			store.category = data.allcategory;
			store.catalog = data.category;
			store.catalogBootLoading = true;
			store.update_category(data.category);
			/*Show firdt category*/
			var id = $('.category_list').find('li').first().find('a').attr('data-id');
			store.allCategoryId = id;
			$('[name=parent]').val(id);
			store.startCatalogBoot();
			store.loadConsoleCatalog(function(){
				store.finishCatalogBoot();
			});

			if (data.user) {
				store.showUserForms( data.user.result );
				store.updateUserCategory( data.user );
			}
		});

		store.types =  eval('('+$('[name="types"]').val()+')');
		store.installedState = store.parseInstalledState();
		store.systemTaskUiFlags = store.parseSystemTaskUiFlags();

		$('a.item-install').live('click',function(){
			store.install(this);
			return false;
		});
		$('.item-delete').live('click', function(){
			if (($(this).attr('data-source-kind') || '') === 'console') {
				store.handleConsoleUninstall(this);
				return false;
			}
			store.previewLegacyDelete(this);
			return false;
		});
		$('.store-task-start').live('click', function(){
			store.queueConsoleInstallTask(this);
			return false;
		});
		$('.item-more').live('click', function(){
			store.showItemMore(this);
			return false;
		});
		$('.store-task-cancel-queued').live('click', function(){
			store.cancelQueuedSystemTask($(this).attr('data-task-id'));
			return false;
		});
		$('.store-task-open-existing').live('click', function(){
			store.openExistingSystemTask($(this).attr('data-task-id'));
			return false;
		});

		$('.item-install2').live('click',function(){
			tpl = '<li data-id="'+$(this).attr('data-id')+'">'+$(this).parent().find('.row-category').text()+'<a href="#">X</a></li>';
			$('.cart_list ul').append(tpl);
			return false;
		});

		$('.category_list a').live('click',function(){
			if ($(this).attr('data-source') === 'console') {
				$('[name=parent]').val('console-extras');
				if (store.catalogBootLoading) {
					store.renderCatalogLoadingState();
					return false;
				}
				store.update_list(store.consoleCatalog, 'list');
				return false;
			}
			if ($(this).attr('data-source') === 'installed') {
				$('[name=parent]').val('installed-extras');
				if (store.catalogBootLoading) {
					store.renderCatalogLoadingState();
					return false;
				}
				store.update_list(store.installedCatalog, 'list');
				return false;
			}
			$('[name=parent]').val($(this).attr('data-id'));
			//store.get_list({}, store.update_list );

			if (store.catalogBootLoading) {
				store.renderCatalogLoadingState();
				return false;
			}
			store.update_list( store.category[$(this).attr('data-id')] , $(this).attr('data-tpl') );
			return false;
		});

		$('.category_list2 a').live('click',function(){
			$('[name=parent]').val($(this).html());
			store.get_own_list({}, store.updateUserPack );
			return false;
		});

		$('#store_sort').change(function(){
			store.renderCurrentList();
		});
		$(document).on('change', '.store-select-wrap select', function(){
			store.syncSelectDisplay($(this).closest('.store-select-wrap'));
		});

		$(window).on('focus', function(){
			store.syncTheme();
		});

		$(document).on('visibilitychange', function(){
			if (!document.hidden) {
				store.syncTheme();
			}
		});

		var file;
		$('#install_file').on('change', function() {
            file = this.files[0];
		    console.log(file);
        });

        $('#install_file_btn').on('click', function() {
            if($.isEmptyObject( file )) return;
            $('#install_file_resp').html('');
            $('#install_file_prg').fadeIn();
            $.ajax({
                url: link()+'&method=fast',
                type: 'POST',
                data: new FormData($('#install_file_form')[0]),
                cache: false, contentType: false, processData: false,

                // Custom XMLHttpRequest
                xhr: function() {
                    var myXhr = $.ajaxSettings.xhr();
                    if (myXhr.upload) {
                        // For handling the progress of the upload
                        myXhr.upload.addEventListener('progress', function(e) {
                            if (e.lengthComputable) {
                                $('progress').attr({
                                    value: e.loaded,
                                    max: e.total,
                                });
                            }
                        } , false);
                    }
                    return myXhr;
                },
            }).done(function(resp){
                $('#install_file_resp').html(resp);
                $('#install_file_prg').fadeOut();
                console.log("Success: File sent!");
            }).fail(function(resp){
                $('#install_file_resp').html(resp);
                $('#install_file_prg').fadeOut();
                console.log("Error: File couldn't be sent!");
            });
        });
	},
	install:function(elm, skipConfirm){
		if ($(elm).attr('data-method') == 'console-extra') {
			store.handleConsoleInstall(elm);
			return false;
		}

		store.resetSystemTaskUiState();

		var installedState = parseInt($(elm).attr('data-installed-state') || '0', 10);
		if (!skipConfirm && installedState && !confirm($(elm).attr('data-text'))) {
			return false;
		}

		var el = $(elm).closest('.catalog_item').find('.informer');
		var file = $(elm).closest('.catalog_item').find('[name="link"]').val();
		store.query('download',{id:$(elm).attr('data-id')},function(data){
			//el.find('.download').html( parseInt(el.find('.download').html())+1 );
		});

		if ($(elm).attr('data-method') == "package"){
			var install_url = link() + "&action=install&cid="+$(elm).attr('data-id')+"&name="+$(elm).attr('data-name')+"&dependencies="+$(elm).attr('data-dependencies')+"&file="+file;
			$.fancybox.open({
				href : install_url,
				type: 'iframe',
				afterClose: function(){
					store.refreshInstalledState();
				}
			});
		} else {
			$('.item_list .catalog_item').addClass('blocked');
			$(elm).closest('.catalog_item').find('.loader').show();
			$.ajax({
				url:link()+"&method=fast&action=install&cid="+$(elm).attr('data-id')+"&name="+$(elm).attr('data-name')+"&dependencies="+$(elm).attr('data-dependencies'),
				type:'POST',
				data:{method:'fast',file:file},
				success:function(data){
					console.log(data);

					el.closest('.catalog_item').find('.loader').hide();
                    if (data.result == 'error') {
                        $.fancybox.open(data.data);
					} else {
                        el.css('display', 'block').animate({opacity: 1}, 500, function () {
                            el.delay(2000).animate({opacity: 0}, 3000, function () {
                                el.css('display', 'none')
                            });
                        });
						store.refreshInstalledState();
                    }

					$('.item_list .catalog_item').removeClass('blocked');
				}

			})

		}

	},
	previewLegacyDelete: function(elm){
		store.resetSystemTaskUiState();

		var $button = $(elm);
		var $card = $button.closest('.catalog_item');
		var installedVersion = $.trim($button.attr('data-current-version') || '');
		var fileValue = store.resolveLegacyInstalledFileValue($card, installedVersion);

		var legacyDeleteTitle = ($('[name="delete_label"]').val() || 'Delete') + ': ' + ($button.attr('data-title') || '');
		var legacySourceLabel = $button.attr('data-source-label') || ($('[name="source_label_legacy"]').val() || 'Legacy');
		if (legacySourceLabel) {
			legacyDeleteTitle += ' (' + legacySourceLabel + ')';
		}

		store.openPopup(
			legacyDeleteTitle,
			'<div class="store-popup-shell ' + store.getPopupThemeClass() + '"><div class="store-popup-empty">' + store.escapeHtml($('[name="delete_preview_loading"]').val() || 'Preparing delete preview...') + '</div></div>',
			'wide'
		);

		$.ajax({
			url: link() + '&action=legacy_delete_preview',
			cache: false,
			dataType: 'json',
			type: 'post',
			data: {
				cid: $button.attr('data-id') || '',
				file: fileValue,
				name: $button.attr('data-title') || '',
				version: installedVersion
			},
			success: function(response){
				if (!response || !response.ok) {
					store.openPopup(
						legacyDeleteTitle,
						'<div class="store-popup-shell ' + store.getPopupThemeClass() + '"><div class="store-popup-empty">' + store.escapeHtml((response && response.message) || ($('[name="delete_preview_error"]').val() || 'Unable to delete this package.')) + '</div></div>',
						'wide'
					);
					return;
				}

				store.openPopup(
					legacyDeleteTitle,
					store.buildLegacyDeletePopupContent(response),
					'wide',
					function(){
						store.bindLegacyDeletePopup();
					}
				);
			},
			error: function(){
				store.openPopup(
					legacyDeleteTitle,
					'<div class="store-popup-shell ' + store.getPopupThemeClass() + '"><div class="store-popup-empty">' + store.escapeHtml($('[name="delete_preview_error"]').val() || 'Unable to delete this package.') + '</div></div>',
					'wide'
				);
			}
		});
	},
	resolveLegacyInstalledFileValue: function($card, installedVersion){
		var $select = $card.find('[name="link"]').first();
		var normalizedInstalled = $.trim(String(installedVersion || ''));
		var value = '';

		if ($select.length && normalizedInstalled) {
			$select.find('option').each(function(){
				var $option = $(this);
				var optionText = $.trim($option.text() || '');
				var optionValue = $.trim($option.val() || '');
				if (optionText === normalizedInstalled || optionValue === normalizedInstalled) {
					value = optionValue;
					return false;
				}
			});
		}

		if (!value && $select.length) {
			value = $.trim($select.val() || '');
		}

		return value;
	},
	buildLegacyDeletePopupContent: function(response){
		var html = '<div class="store-popup-shell store-popup-shell-delete ' + store.getPopupThemeClass() + '" data-delete-token="' + store.escapeHtml(response.token || '') + '">';
		html += '<p class="store-delete-intro">' + store.escapeHtml($('[name="delete_preview_intro"]').val() || 'Select what should be removed from files and manager records for this legacy package.') + '</p>';

		if ((!response.files || !response.files.length) && !store.hasLegacyDeleteDbEntries(response.db || {})) {
			html += '<div class="store-popup-empty">' + store.escapeHtml($('[name="delete_preview_empty"]').val() || 'Nothing from this package was found for deletion.') + '</div>';
		} else {
			html += store.buildLegacyDeleteDbSection(response.db || {});
			html += store.buildLegacyDeleteFileSection(response.files || []);
			html += '<div class="store-delete-actions">';
			html += '<button type="button" class="btn btn-default store-delete-cancel">' + store.escapeHtml($('[name="delete_preview_cancel"]').val() || 'Cancel') + '</button>';
			html += '<button type="button" class="btn btn-danger store-delete-confirm">' + store.escapeHtml($('[name="delete_preview_confirm"]').val() || 'Delete selected') + '</button>';
			html += '</div>';
		}

		html += '</div>';
		return html;
	},
	buildLegacyDeleteFileSection: function(files){
		if (!files || !files.length) {
			return '';
		}

		files = $.grep(files, function(entry){
			return !store.isLegacyDeleteIgnoredPath(entry && entry.label ? entry.label : '');
		});

		if (!files.length) {
			return '';
		}

		var groups = {};
		$.each(files, function(index, entry){
			var group = entry.group || 'files';
			if (!groups[group]) {
				groups[group] = [];
			}
			groups[group].push(entry);
		});

		var orderedGroups = [];
		var preferredOrder = ['snippets', 'modules', 'plugins', 'tvs', 'files'];

		$.each(preferredOrder, function(index, group){
			if (groups[group] && groups[group].length) {
				orderedGroups.push({
					name: group,
					entries: groups[group]
				});
				delete groups[group];
			}
		});

		$.each(Object.keys(groups).sort(function(a, b){
			return a.localeCompare(b);
		}), function(index, group){
			orderedGroups.push({
				name: group,
				entries: groups[group]
			});
		});

		var html = '<div class="store-delete-section"><h3>' + store.escapeHtml($('[name="delete_preview_files_label"]').val() || 'Files') + '</h3>';
		$.each(orderedGroups, function(index, groupData){
			var group = groupData.name;
			var entries = groupData.entries || [];
			html += '<div class="store-delete-group">';
			html += '<h4>' + store.escapeHtml(store.getLegacyDeleteGroupLabel(group)) + '</h4>';
			if (store.isLegacyDeleteFlatGroup(group)) {
				html += '<div class="store-delete-checklist">';
				$.each(entries, function(index, entry){
					html += store.buildLegacyDeleteCheckbox(entry.key, entry.label, '', []);
				});
				html += '</div>';
			} else {
				html += store.buildLegacyDeleteFileTree(entries);
			}
			html += '</div>';
		});
		html += '</div>';
		return html;
	},
	isLegacyDeleteFlatGroup: function(group){
		return $.inArray(group, ['plugins', 'snippets', 'modules', 'tvs']) !== -1;
	},
	isLegacyDeleteIgnoredPath: function(path){
		var normalized = String(path || '').replace(/\\/g, '/').replace(/^\/+/, '');
		if (!normalized) {
			return false;
		}

		if (normalized === 'assets/images' || normalized.indexOf('assets/images/') === 0) {
			return true;
		}

		var segments = normalized.split('/');
		for (var i = 0; i < segments.length; i++) {
			if ((segments[i] || '').toLowerCase() === '.htaccess') {
				return true;
			}
		}

		return false;
	},
	buildLegacyDeleteFileTree: function(entries){
		var root = {};

		$.each(entries || [], function(index, entry){
			if (store.isLegacyDeleteIgnoredPath(entry && entry.label ? entry.label : '')) {
				return;
			}
			var path = String(entry.label || '').split('/');
			var cursor = root;
			$.each(path, function(segmentIndex, segment){
				if (!segment) {
					return;
				}
				if (!cursor[segment]) {
					cursor[segment] = {
						name: segment,
						children: {}
					};
				}
				if (segmentIndex === path.length - 1) {
					cursor[segment].entry = entry;
				}
				cursor = cursor[segment].children;
			});
		});

		return '<div class="store-delete-tree">' + store.renderLegacyDeleteTreeNodes(root, 0) + '</div>';
	},
	renderLegacyDeleteTreeNodes: function(nodes, depth){
		var html = '';
		var names = Object.keys(nodes || {}).sort(function(a, b){
			return a.localeCompare(b);
		});

		$.each(names, function(index, name){
			var node = nodes[name];
			var hasChildren = node && node.children && Object.keys(node.children).length > 0;
			var hasEntry = node && node.entry;

			if (hasEntry) {
				html += '<div class="store-delete-tree-entry">';
				html += store.buildLegacyDeleteCheckbox(node.entry.key, node.entry.label, '', node.entry.children || []);
				if (hasChildren) {
					html += '<div class="store-delete-tree-children">';
					html += store.renderLegacyDeleteTreeNodes(node.children, depth + 1);
					html += '</div>';
				}
				html += '</div>';
				return;
			}

			html += '<details class="store-delete-tree-folder" open>';
			html += '<summary>' + store.escapeHtml(name) + '</summary>';
			html += '<div class="store-delete-tree-children">';
			if (hasEntry) {
				html += store.buildLegacyDeleteCheckbox(node.entry.key, node.entry.label, '', node.entry.children || []);
			}
			if (hasChildren) {
				html += store.renderLegacyDeleteTreeNodes(node.children, depth + 1);
			}
			html += '</div></details>';
		});

		return html;
	},
	buildLegacyDeleteDbSection: function(groups){
		if (!store.hasLegacyDeleteDbEntries(groups)) {
			return '';
		}

		var html = '<div class="store-delete-section"><h3>' + store.escapeHtml($('[name="delete_preview_components_label"]').val() || 'Components') + '</h3>';
		$.each(groups, function(group, entries){
			if (!entries || !entries.length) {
				return;
			}
			html += '<div class="store-delete-group">';
			html += '<h4>' + store.escapeHtml(store.getLegacyDeleteGroupLabel(group)) + '</h4>';
			html += '<div class="store-delete-checklist">';
			$.each(entries, function(index, entry){
				html += store.buildLegacyDeleteCheckbox(entry.key, entry.label, entry.meta || entry.version || '', []);
			});
			html += '</div></div>';
		});
		html += '</div>';
		return html;
	},
	buildLegacyDeleteCheckbox: function(key, label, meta, children){
		var hasChildren = children && children.length;
		var html = '<label class="store-delete-check' + (hasChildren ? ' store-delete-check-parent' : '') + '">';
		html += '<input type="checkbox" class="store-delete-checkbox" value="' + store.escapeHtml(key) + '" checked="checked">';
		html += '<span class="store-delete-check-body">';
		html += '<span class="store-delete-check-mainline">';
		html += '<span class="store-delete-check-label">' + store.escapeHtml(label) + '</span>';
		if (meta) {
			html += '<span class="store-delete-check-meta">' + store.escapeHtml(meta) + '</span>';
		}
		html += '</span>';
		if (hasChildren) {
			html += '<details class="store-delete-check-details">';
			html += '<summary>' + store.escapeHtml('files (' + children.length + ')') + '</summary>';
			html += '<div class="store-delete-check-files">';
			$.each(children, function(index, child){
				html += '<label class="store-delete-check store-delete-check-child">';
				html += '<input type="checkbox" class="store-delete-checkbox store-delete-checkbox-child" value="' + store.escapeHtml(child.key || '') + '" checked="checked">';
				html += '<span class="store-delete-check-body">';
				html += '<span class="store-delete-check-mainline">';
				html += '<span class="store-delete-check-file">' + store.escapeHtml(child.label || '') + '</span>';
				html += '</span></span></label>';
			});
			html += '</div></details>';
		}
		html += '</span></label>';
		return html;
	},
	hasLegacyDeleteDbEntries: function(groups){
		var found = false;
		$.each(groups || {}, function(group, entries){
			if (entries && entries.length) {
				found = true;
				return false;
			}
		});
		return found;
	},
	getLegacyDeleteGroupLabel: function(group){
		var labels = {
			files: 'Files',
			plugins: 'Plugins',
			snippets: 'Snippets',
			modules: 'Modules',
			chunks: 'Chunks',
			templates: 'Templates',
			tvs: 'TVs'
		};
		return labels[group] || group;
	},
	bindLegacyDeletePopup: function(){
		var $popup = store.getActivePopup();
		if (!$popup.length) {
			return;
		}

		$popup.find('.store-delete-check-parent > .store-delete-checkbox').off('change.storeDeleteParent').on('change.storeDeleteParent', function(){
			var checked = $(this).is(':checked');
			var $parent = $(this).closest('.store-delete-check-parent');
			$parent.find('.store-delete-checkbox-child').prop('checked', checked);
		});

		$popup.find('.store-delete-checkbox-child').off('change.storeDeleteChild').on('change.storeDeleteChild', function(){
			var $details = $(this).closest('.store-delete-check-details');
			var $parent = $details.closest('.store-delete-check-parent');
			var $children = $details.find('.store-delete-checkbox-child');
			var $checked = $children.filter(':checked');
			$parent.children('.store-delete-checkbox').prop('checked', $children.length > 0 && $checked.length === $children.length);
		});

		$popup.find('.store-delete-cancel').off('click').on('click', function(){
			store.closeActivePopup();
		});

		$popup.find('.store-delete-confirm').off('click').on('click', function(){
			store.runLegacyDelete($(this));
		});
	},
	runLegacyDelete: function($button){
		var $popup = store.getActivePopup();
		if (!$popup.length) {
			return;
		}

		var token = $popup.find('.store-popup-shell-delete').attr('data-delete-token') || '';
		var selection = [];
		$popup.find('.store-delete-checkbox:checked').each(function(){
			selection.push($(this).val());
		});

		if (!selection.length) {
			return;
		}

		$button.prop('disabled', true);
		$.ajax({
			url: link() + '&action=legacy_delete_run',
			cache: false,
			dataType: 'json',
			type: 'post',
			data: {
				token: token,
				selection: selection
			},
			success: function(response){
				if (!response || !response.ok) {
					alert((response && response.message) || ($('[name="delete_preview_error"]').val() || 'Unable to delete this package.'));
					$button.prop('disabled', false);
					return;
				}

				store.closeActivePopup();
				store.refreshInstalledState();
			},
			error: function(){
				alert($('[name="delete_preview_error"]').val() || 'Unable to delete this package.');
				$button.prop('disabled', false);
			}
		});
	},
	closeActivePopup: function(){
		var $popup = store.getActivePopup();
		if (!$popup.length) {
			store.resetSystemTaskUiState();
			store.cleanupPopupArtifacts();
			return;
		}

		var $close = $popup.find('.evo-popup-close, .close').first();
		if ($close.length) {
			store.resetSystemTaskUiState();
			$close.trigger('click');
			setTimeout(store.cleanupPopupArtifacts, 60);
			return;
		}

		store.resetSystemTaskUiState();
		$popup.remove();
		store.cleanupPopupArtifacts();
	},
	resetSystemTaskUiState: function(){
		store.stopSystemTaskPolling();
		store.stopSystemTaskElapsedTimer();
		store.systemTaskPollToken++;
		store.systemTaskPollInFlight = false;
		store.systemTaskLastPollAt = 0;
		store.systemTaskTerminal = false;
		store.systemTaskPollTaskId = 0;
		store.systemTaskPollTaskTitle = '';
		store.systemTaskRefreshTaskId = 0;
		store.systemTaskRefreshPermissionsTaskId = 0;
		store.systemTaskCancelInFlight = false;
		store.systemTaskWarnings = [];
	},
	cleanupPopupArtifacts: function(){
		var cleanDoc = function(doc){
			if (!doc) {
				return;
			}
			var $doc = $(doc);
			if (!$doc.find('.evo-popup:visible').length) {
				$doc.find('.evo-popup-overlay').remove();
				$doc.find('.evo-popup:hidden').remove();
			}
		};

		try { cleanDoc(document); } catch (e) {}
		try { if (window.parent && window.parent.document) cleanDoc(window.parent.document); } catch (e) {}
	},
	refreshInstalledState: function(callback){
		$.ajax({
			url: link() + '&action=refresh_installed_state',
			cache: false,
			dataType: 'json',
			type: 'get',
			success: function(response){
				if (!response || !response.ok || !response.installed_state) {
					if (typeof callback === 'function') {
						callback(false);
					}
					return;
				}

				store.installedState = response.installed_state;
				$('[name="installed_state"]').val(JSON.stringify(response.installed_state));
				store.buildInstalledCatalog();
				store.renderInstalledCategory(store.installedCatalog.length);

				if ($('[name=parent]').val() === 'installed-extras') {
					store.update_list(store.installedCatalog, 'list');
				} else {
					store.renderCurrentList();
				}

				if (typeof callback === 'function') {
					callback(true);
				}
			},
			error: function(){
				if (typeof callback === 'function') {
					callback(false);
				}
			}
		});
	},
	loadSystemTaskHealth: function(callback){
		if (!store.canViewSystemTasks()) {
			store.systemTaskHealth.scheduler = null;
			store.systemTaskHealth.worker = null;
			if (typeof callback === 'function') {
				callback(false);
			}
			return;
		}

		$.ajax({
			url: link() + '&action=system_task_health',
			cache: false,
			dataType: 'json',
			type: 'get'
		}).done(function(response){
			var ok = !!(response && response.ok);
			store.systemTaskHealth.scheduler = ok ? (response.scheduler || null) : null;
			store.systemTaskHealth.worker = ok ? (response.worker || null) : null;
			if (typeof callback === 'function') {
				callback(ok);
			}
		}).fail(function(){
			store.systemTaskHealth.scheduler = null;
			store.systemTaskHealth.worker = null;
			if (typeof callback === 'function') {
				callback(false);
			}
		});
	},
	getSystemTaskHealthStatus: function(type){
		var health = store.systemTaskHealth && store.systemTaskHealth[type] ? store.systemTaskHealth[type] : null;
		return health && health.status ? String(health.status).toLowerCase() : 'unknown';
	},

	query:function(action,param,callback){
		param = store.extend(param);
		$.ajax({
			url:'https://extras.evo.im/get.php?get=' + action,
			cache:false,
			data:param,
			dataType: "json",
			type:'post',
			cache:false,
			success:function(data){
				callback(data);
			}
		})
	},
	get_category: function( param , callback){
		store.query('get_category',param,function(data){callback(data)});
	},
	get_list: function( param , callback){
		store.query('get_list',$.extend(param,{parent:$('[name=parent]').val(),sort:$('[name=sort]').val(),dir:$('[name=dir]').val()}),function(data){callback(data)});
	},

	get_own_list: function( param , callback){
		$('.item_list >  .loader').show();
		store.query('get_own_list',$.extend(param,{parent:$('[name=parent]').val(),sort:$('[name=sort]').val(),dir:$('[name=dir]').val()}),function(data){
		callback(data)
		});
	},

	update_category: function(data){
		$('.category_list').html( '<ul>' +store.parse_list1( data , $('.tpl #tpl_category').html() ) + '</ul>' );
		if (!store.catalogBootLoading) {
			store.renderInstalledCategory(store.installedCatalog.length);
		}
	},
	startCatalogBoot: function(){
		return;
	},
	finishCatalogBoot: function(){
		store.catalogBootLoading = false;
		if (store.consoleCatalog.length) {
			store.renderConsoleCategory(store.consoleCatalog.length);
		} else {
			$('.category_list ul').find('.console-category-item').remove();
		}
		store.buildInstalledCatalog();
		store.renderInstalledCategory(store.installedCatalog.length);
		store.renderSelectedCategory();
	},
	renderSelectedCategory: function(){
		var parentId = $('[name=parent]').val() || store.allCategoryId;
		if (parentId === 'console-extras') {
			store.update_list(store.consoleCatalog, 'list');
			return;
		}
		if (parentId === 'installed-extras') {
			store.update_list(store.installedCatalog, 'list');
			return;
		}

		var $selected = $('.category_list a[data-id="' + parentId + '"]');
		var tpl = ($selected.attr('data-tpl') || 'list');
		store.update_list(store.category[parentId] || [], tpl);
	},
	loadConsoleCatalog: function(callback){
		$.ajax({
			url: link() + '&action=console_catalog',
			cache: false,
			dataType: 'json',
			type: 'get',
			success: function(data){
				if (!data || !data.ok || !$.isArray(data.items) || data.items.length === 0) {
					if (typeof callback === 'function') {
						callback();
					}
					return;
				}
				store.consoleCatalog = data.items;
				store.mergeConsoleIntoAll();
				if (typeof callback === 'function') {
					callback();
				}
			},
			error: function(){
				if (typeof callback === 'function') {
					callback();
				}
			}
		});
	},
	mergeConsoleIntoAll: function(){
		if (!store.allCategoryId || !store.consoleCatalog.length) {
			return;
		}

		var existingAll = store.toArray(store.category[store.allCategoryId]);
		store.category[store.allCategoryId] = store.consoleCatalog.concat(existingAll);

		var firstCategory = $('.category_list ul li').first();
		if (firstCategory.length) {
			firstCategory.find('small').text('(' + store.category[store.allCategoryId].length + ')');
		}

		if (!store.catalogBootLoading && $('[name=parent]').val() == store.allCategoryId) {
			store.update_list(store.category[store.allCategoryId]);
		}
	},
	renderConsoleCategory: function(count){
		var label = $('[name="console_category_label"]').val() || 'Console extras';
		var html = store.parse($('.tpl #tpl_category').html(), {
			id: 'console-extras',
			tpl: '',
			title: label,
			count: count,
			source_attr: 'data-source="console"'
		});
		html = html.replace('<li>', '<li class="console-category-item">');
		var list = $('.category_list ul');
		if (!list.length) {
			$('.category_list').html('<ul>' + html + '</ul>');
			return;
		}
		list.find('.console-category-item').remove();
		var first = list.children('li').first();
		if (first.length) {
			first.after(html);
			return;
		}
		list.append(html);
	},
	buildInstalledCatalog: function(){
		if (!store.allCategoryId || !store.category[store.allCategoryId]) {
			store.installedCatalog = [];
			return;
		}

		var items = store.toArray(store.category[store.allCategoryId]);
		var installed = [];
		$.each(items, function(index, item){
			var prepared = store.applyInstalledStateToItem(item);
			if (prepared.is_installed) {
				installed.push(prepared);
			}
		});
		installed.sort(function(a, b){
			var titleA = store.normalizeTitle(a);
			var titleB = store.normalizeTitle(b);
			if (titleA !== titleB) {
				return titleA.localeCompare(titleB);
			}

			var kindA = (a.source_kind || (a.install_method === 'console-extra' ? 'console' : 'legacy'));
			var kindB = (b.source_kind || (b.install_method === 'console-extra' ? 'console' : 'legacy'));
			if (kindA !== kindB) {
				return kindA === 'legacy' ? -1 : 1;
			}

			var versionA = String(a.current_version || a.version || '');
			var versionB = String(b.current_version || b.version || '');
			return versionA.localeCompare(versionB);
		});
		store.installedCatalog = installed;
	},
	renderInstalledCategory: function(count){
		var label = $('[name="installed_category_label"]').val() || 'Installed';
		var html = store.parse($('.tpl #tpl_category').html(), {
			id: 'installed-extras',
			tpl: '',
			title: label,
			count: count,
			source_attr: 'data-source="installed"'
		});
		html = html.replace('<li>', '<li class="installed-category-item">');
		var list = $('.category_list ul');
		if (!list.length) {
			$('.category_list').html('<ul>' + html + '</ul>');
			return;
		}
		list.find('.installed-category-item').remove();
		var insertBefore = list.find('.console-category-item');
		if (insertBefore.length) {
			insertBefore.before(html);
			return;
		}
		var first = list.children('li').first();
		if (first.length) {
			first.after(html);
			return;
		}
		list.append(html);
	},
	update_list: function(data,tpl){
		tpl = tpl || 'list';
		store.currentList = store.toArray(data);
		store.currentTemplate = tpl;
		store.renderCurrentList();
	},
	updateUserPack: function(data){
		store.update_list(data, 'list');
	},
	updateUserCategory:function(data){
		if (data) {
			$('.category_list2').html( '<ul>' +store.parse_list1( data.category , $('.tpl #tpl_category2').html() ) + '</ul>' );
		}
	},
	parse_list:function(data,tpl,template){
		var out='';
		if (data){
			$.each( data , function( key, value ) {
			try {
				out = out + store.parse_list_item(tpl, value , template);
			} catch(e){
				console.log( e.name );
			}
			});
		} else {
			//console.log(data);
		}
		return out;
	},
	parse_list1:function(data,tpl){
		var out='';
		$.each( data , function( key, value ) {
			try {
				out = out + store.parse(tpl, value);
			} catch(e){
				console.log( e.name );
			}
		});
		return out;
	},
	parse_list_item: function(str,array,tpl){
		tpl = tpl || 'list';
		array = store.applyInstalledStateToItem(array);
		array.cls = array.cls || 'pack_install';
		array.install_method = array.install_method || array.type;
		array.install_command = array.install_command || '';
		array.source_kind = array.source_kind || (array.install_method === 'console-extra' ? 'console' : 'legacy');
		array.source_label = array.source_label || (array.source_kind === 'console'
			? ($('[name="source_label_console"]').val() || '')
			: ($('[name="source_label_legacy"]').val() || 'Legacy'));
		array.source_label_class = array.source_label ? '' : 'hidden';
		array.install_target = array.install_target || array.title || array.name || '';
		array.title_source_html = array.title_source_html || '';
		array.install_hidden_class = array.install_hidden_class || '';
		array.version_hidden_class = array.version_hidden_class || '';
		array.more_hidden_class = array.more_hidden_class || '';
		array.delete_hidden_class = array.delete_hidden_class || '';
		array.source_url = array.source_url || '';
		array.repo_full_name = array.repo_full_name || '';
		array.readme_branch = array.readme_branch || '';
		array.is_dev_package = String(array.is_dev_package || '0');
		array.dev_badge = array.is_dev_package === '1' ? ($('[name="dev_badge"]').val() || 'DEV') : '';
		array.dev_badge_class = array.is_dev_package === '1' ? '' : 'hidden';
		array.download_class = array.downloads ? '' : 'hidden';
		array.popup_downloads = array.downloads ? '<br/><span class=\'fa fa-download\'> </span> Downloads: <strong>' + store.escapeHtml(array.downloads) + '</strong>' : '';
		array.zip = array.url == ''?'zip':'github';

		array.version = array.version || '';
		array.date = array.date || '';

		if ($.isPlainObject(array.url)){
			var $str = $(str);
			var versions = (array.url && array.url.fieldValue) ? array.url.fieldValue : [];
			var isConsole = (array.source_kind || '') === 'console';
			var firstOptionLabel = '';

			if (isConsole) {
				var options = [];
				$.each(versions,function(key,value){
					var optionLabel = value.version || array.version || value.file || '';
					var selected = key === 0 ? ' selected="selected"' : '';
					options.push('<option value="'+store.escapeHtml(value.file)+'"'+selected+'>'+store.escapeHtml(optionLabel)+'</option>');
					if (firstOptionLabel === '') firstOptionLabel = optionLabel;
					if (!array.version) array.version = optionLabel;
					if (!array.date) array.date = value.date;
				});
				if (!options.length && array.version) {
					options.push('<option value="'+store.escapeHtml(array.version)+'" selected="selected">'+store.escapeHtml(array.version)+'</option>');
					firstOptionLabel = array.version;
				}
				$str.find('[name=link]').html(options.join(''));
			} else {
				var legacyOptions = [];
				var versionCount = 0;
				$.each(versions,function(key,value){
					var optionValue = value && value.file ? value.file : '';
					var optionLabel = (value && value.version) ? value.version : (array.version || optionValue || '');
					var selected = versionCount === 0 ? ' selected="selected"' : '';
					legacyOptions.push('<option value="'+store.escapeHtml(optionValue)+'"'+selected+'>'+store.escapeHtml(optionLabel)+'</option>');
					if (firstOptionLabel === '') firstOptionLabel = optionLabel;
					if (!array.version) array.version = optionLabel;
					if (!array.date && value) array.date = value.date;
					versionCount++;
				});
				if (!legacyOptions.length && array.version) {
					legacyOptions.push('<option value="'+store.escapeHtml(array.version)+'" selected="selected">'+store.escapeHtml(array.version)+'</option>');
					firstOptionLabel = array.version;
					versionCount = 1;
				}
				$str.find('[name=link]').html(legacyOptions.join(''));
				$str.find('option').first().prop('selected', true).attr('selected', 'selected');
				if (versionCount === 0){
					$str.find('[name=link]').attr('data-hide-display', '1');
				} else {
					$str.find('[name=link]').removeAttr('data-hide-display');
				}
			}

			if (firstOptionLabel !== '') {
				$str.find('.store-select-display').first().text(firstOptionLabel);
			}

			array.url = '';
			str = $str.wrapAll('<div></div>').parent().html();

		}
		out = str.replace(/%\w+%/g, function(placeholder) {
			return array[ placeholder.split('%').join('') ] || '';
		});
		img = array.image;
		if (tpl =='cart') img = array.cartimage;
		var $out = $('<div id="tmpl">' + out + '</div>');
		if (array.image) {
			$out.find('img').attr('src', img);
		}

		$out.find('.item-more')
			.attr('data-title', array.title || '')
			.attr('data-type', array.type || '')
			.attr('data-version', array.version || '')
			.attr('data-date', array.date || '')
			.attr('data-author', array.author || '')
			.attr('data-downloads', array.downloads || '')
			.attr('data-source-url', array.source_url || '')
			.attr('data-source-kind', array.source_kind || '')
			.attr('data-source-label', array.source_label || '')
			.attr('data-description', array.description || '')
			.attr('data-repo-full-name', array.repo_full_name || '')
			.attr('data-readme-branch', array.readme_branch || '');

		$out.find('.item-delete')
			.attr('data-title', array.title || '')
			.attr('data-display-title', array.title || '')
			.attr('data-type', array.type || '')
			.attr('data-id', array.id || '')
			.attr('data-current-version', array.current_version || '')
			.attr('data-source-kind', array.source_kind || '')
			.attr('data-source-url', array.source_url || '')
			.attr('aria-label', $('[name="delete_label"]').val() || 'delete')
			.attr('title', $('[name="delete_label"]').val() || 'delete');

		if ((array.source_kind || '') === 'console') {
			$out.find('.item-more').html('<i class="fa fa-book"></i> readme');
		}

		if (array.is_installed) {
			$out.find('.item-install')
				.text($('[name="reinstall_label"]').val() || 'Reinstall')
				.removeClass('btn-success')
				.addClass('btn-primary');
		}

		if ((array.source_kind || '') === 'legacy' && array.is_installed) {
			$out.children().first().addClass('has-legacy-delete');
		}

		if ((array.source_kind || '') === 'console' && array.is_installed && store.canManageSystemPackages()) {
			$out.children().first().addClass('has-console-delete');
		}

		return $out.html();
	},
	showConsoleInstallHelp: function(elm){
		var target = elm;
		store.loadSystemTaskHealth(function(){
			store.showConsoleInstallHelpPopup(target);
		});
	},
	handleConsoleInstall: function(elm){
		var target = elm;
		store.loadSystemTaskHealth(function(){
			if (store.canQueueSystemTaskInstall()) {
				store.queueConsoleInstallFromElement(target);
				return;
			}
			store.showConsoleInstallHelpPopup(target);
		});
	},
	handleConsoleUninstall: function(elm){
		var target = elm;
		if (!confirm($('[name="console_uninstall_confirm"]').val() || 'Remove this console package from Composer?')) {
			return;
		}

		store.loadSystemTaskHealth(function(){
			if (store.canQueueSystemTaskInstall()) {
				store.queueConsoleUninstallFromElement(target);
				return;
			}
			store.showConsoleUninstallHelpPopup(target);
		});
	},
	showConsoleInstallHelpPopup: function(elm){
		var name = $(elm).attr('data-name') || '';
		var packageName = $(elm).attr('data-package') || name;
		var catalogItemId = $(elm).attr('data-id') || '';
		var selectedVersion = $(elm).closest('.catalog_item').find('[name="link"]').val() || '';
		var command = $(elm).attr('data-command') || '';
		var sourceUrl = $(elm).attr('data-source-url') || $(elm).attr('data-url') || '';
		var corePath = $('[name="console_core_path"]').val() || '';
		var title = $('[name="console_install_title"]').val() || 'Install via console';
		var intro = $('[name="console_install_intro"]').val() || '';
		var manualLabel = $('[name="console_install_manual_label"]').val() || 'Manual console install';
		var autoLabel = $('[name="console_install_auto_label"]').val() || 'Automatic install via scheduler';
		var autoReadyLabel = $('[name="console_install_auto_ready"]').val() || '';
		var autoDisabledLabel = $('[name="console_install_auto_disabled"]').val() || '';
		var autoPermissionLabel = $('[name="console_install_auto_permission"]').val() || '';
		var schedulerIntroLabel = $('[name="console_install_scheduler_intro"]').val() || '';
		var schedulerLocalCommandLabel = $('[name="console_install_scheduler_command_local"]').val() || 'php artisan schedule:work';
		var schedulerServerTemplate = $('[name="console_install_scheduler_command_server"]').val() || '* * * * * cd {core_path} && php artisan schedule:run >/dev/null 2>&1';
		var autoWarningLabel = $('[name="console_install_auto_warning"]').val() || '';
		var openCoreLabel = $('[name="console_install_step_open_core"]').val() || '';
		var runArtisanLabel = $('[name="console_install_step_run_artisan"]').val() || '';
		var sourceLabel = $('[name="console_install_source_label"]').val() || 'Source';
		var copyLabel = $('[name="popup_copy_command"]').val() || 'Copy command';
		var queueLabel = $('[name="system_task_queue_install"]').val() || 'Queue install';
		var warningLabel = $('[name="system_task_modal_warning"]').val() || 'Warning';
		var schedulerServerCommand = schedulerServerTemplate.replace('{core_path}', corePath);
		var schedulerStatus = store.getSystemTaskHealthStatus('scheduler');
		var workerStatus = store.getSystemTaskHealthStatus('worker');
		var automaticAvailable = store.canQueueSystemTaskInstall();
		var showPermissionWarning = !store.canManageSystemPackages();
		var showSchedulerCommands = !automaticAvailable;
		var showAutoWarning = automaticAvailable && (schedulerStatus === 'degraded' || workerStatus === 'degraded' || workerStatus === 'unhealthy' || workerStatus === 'unknown');
		var isLocalRuntime = store.isLocalRuntime();
		var schedulerRuntimeLabel = isLocalRuntime
			? ($('[name="console_install_scheduler_local"]').val() || 'Local')
			: ($('[name="console_install_scheduler_server"]').val() || 'Server');
		var schedulerRuntimeCommand = isLocalRuntime
			? ('cd ' + corePath + ' && ' + schedulerLocalCommandLabel)
			: schedulerServerCommand;
		if (packageName !== '') {
			command = 'php artisan extras extras "' + packageName + (selectedVersion ? '@' + selectedVersion : '') + '"';
		}

		var html = ''
			+ '<div class="store-popup-shell store-popup-shell-install store-popup-shell-console ' + store.getPopupThemeClass() + '">'
			+ '<p class="store-popup-note">' + store.escapeHtml(intro) + '</p>'
			+ '<div class="store-popup-section">'
			+ '<h3>' + store.escapeHtml(manualLabel) + '</h3>'
			+ '<div class="store-install-card">'
			+ '<div class="store-install-step">'
			+ '<div class="store-install-card-head">'
			+ '<span class="store-install-card-label">' + store.escapeHtml(openCoreLabel) + '</span>'
			+ '<button type="button" class="store-copy-button" data-copy-command="' + store.escapeHtml('cd ' + corePath) + '" aria-label="' + store.escapeHtml(copyLabel) + '"><i class="fa fa-copy"></i></button>'
			+ '</div>'
			+ '<div class="store-install-command">' + store.escapeHtml('cd ' + corePath) + '</div>'
			+ '</div>'
			+ '<div class="store-install-step">'
			+ '<div class="store-install-card-head">'
			+ '<span class="store-install-card-label">' + store.escapeHtml(runArtisanLabel) + '</span>'
			+ '<button type="button" class="store-copy-button" data-copy-command="' + store.escapeHtml(command) + '" aria-label="' + store.escapeHtml(copyLabel) + '"><i class="fa fa-copy"></i></button>'
			+ '</div>'
			+ '<div class="store-install-command">' + store.escapeHtml(command) + '</div>'
			+ '</div>'
			+ '</div>'
			+ '</div>'
			+ '<div class="store-popup-section">'
			+ '<h3>' + store.escapeHtml(autoLabel) + '</h3>';

		if (showPermissionWarning) {
			html += '<div class="store-task-warning-list"><div class="store-task-warning-item"><strong>' + store.escapeHtml(warningLabel) + ':</strong> <span>' + store.escapeHtml(autoPermissionLabel) + '</span></div></div>';
		} else if (automaticAvailable) {
			html += '<p class="store-popup-note store-task-note-muted">' + store.escapeHtml(autoReadyLabel) + '</p>';
			if (showAutoWarning) {
				html += '<div class="store-task-warning-list"><div class="store-task-warning-item"><strong>' + store.escapeHtml(warningLabel) + ':</strong> <span>' + store.escapeHtml(autoWarningLabel) + '</span></div></div>';
			}
			html += '<div class="store-task-actions">'
				+ '<button type="button" class="btn btn-primary store-task-start" data-task-type="console_install" data-catalog-item-id="' + store.escapeHtml(catalogItemId) + '" data-version="' + store.escapeHtml(selectedVersion || '') + '" data-title="' + store.escapeHtml(name) + '"><i class="fa fa-play"></i> ' + store.escapeHtml(queueLabel) + '</button>'
				+ '</div>';
		} else {
			html += '<p class="store-popup-note store-task-note-muted">' + store.escapeHtml(autoDisabledLabel) + '</p>';
		}

		if (showSchedulerCommands) {
			html += '<p class="store-popup-note store-task-note-muted">' + store.escapeHtml(schedulerIntroLabel) + '</p>'
				+ '<div class="store-install-card">'
				+ '<div class="store-install-step">'
				+ '<div class="store-install-card-head">'
				+ '<span class="store-install-card-label">' + store.escapeHtml(schedulerRuntimeLabel) + '</span>'
				+ '<button type="button" class="store-copy-button" data-copy-command="' + store.escapeHtml(schedulerRuntimeCommand) + '" aria-label="' + store.escapeHtml(copyLabel) + '"><i class="fa fa-copy"></i></button>'
				+ '</div>'
				+ '<div class="store-install-command">' + store.escapeHtml(schedulerRuntimeCommand) + '</div>'
				+ '</div>'
				+ '</div>';
		}

		html += '</div>';

		if (sourceUrl !== '') {
			html += '<p class="store-popup-source"><strong>' + store.escapeHtml(sourceLabel) + ':</strong> <a href="' + store.escapeHtml(sourceUrl) + '" target="_blank" rel="noopener">' + store.escapeHtml(sourceUrl) + '</a></p>';
		}

		html += '</div>';

		store.openPopup(title + ': ' + name, html, 'wide', function(){
			store.bindPopupCopyButtons();
		});
	},
	showConsoleUninstallHelpPopup: function(elm){
		var name = $(elm).attr('data-title') || $(elm).closest('.catalog_item').find('h3').text() || '';
		var corePath = $('[name="console_core_path"]').val() || '';
		var isLocalRuntime = store.isLocalRuntime();
		var schedulerCommand = isLocalRuntime
			? ('cd ' + corePath + ' && ' + ($('[name="console_install_scheduler_command_local"]').val() || 'php artisan schedule:work'))
			: (($('[name="console_install_scheduler_command_server"]').val() || '* * * * * cd {core_path} && php artisan schedule:run >/dev/null 2>&1').replace('{core_path}', corePath));
		var runtimeLabel = isLocalRuntime
			? ($('[name="console_install_scheduler_local"]').val() || 'Local')
			: ($('[name="console_install_scheduler_server"]').val() || 'Server');
		var copyLabel = $('[name="popup_copy_command"]').val() || 'Copy command';
		var html = ''
			+ '<div class="store-popup-shell store-popup-shell-console ' + store.getPopupThemeClass() + '">'
			+ '<p class="store-popup-note">' + store.escapeHtml($('[name="console_uninstall_intro"]').val() || '') + '</p>'
			+ '<p class="store-popup-note store-task-note-muted">' + store.escapeHtml($('[name="console_uninstall_scheduler_intro"]').val() || '') + '</p>'
			+ '<div class="store-install-card">'
			+ '<div class="store-install-step">'
			+ '<div class="store-install-card-head">'
			+ '<span class="store-install-card-label">' + store.escapeHtml(runtimeLabel) + '</span>'
			+ '<button type="button" class="store-copy-button" data-copy-command="' + store.escapeHtml(schedulerCommand) + '" aria-label="' + store.escapeHtml(copyLabel) + '"><i class="fa fa-copy"></i></button>'
			+ '</div>'
			+ '<div class="store-install-command">' + store.escapeHtml(schedulerCommand) + '</div>'
			+ '</div>'
			+ '</div>'
			+ '</div>';

		store.openPopup(($('[name="delete_label"]').val() || 'Delete') + ': ' + name, html, 'compact', function(){
			store.bindPopupCopyButtons();
		});
	},
	queueConsoleInstallFromElement: function(elm){
		var $elm = $(elm);
		var catalogItemId = $.trim($elm.attr('data-id') || '');
		var version = $.trim($elm.closest('.catalog_item').find('[name="link"]').val() || '');
		var title = $.trim($elm.attr('data-name') || '');
		var sourceLabel = $.trim($elm.attr('data-source-label') || ($('[name="source_label_console"]').val() || ''));
		store.queueSystemTaskRequest('console_install', catalogItemId, version, title, sourceLabel);
	},
	queueConsoleUninstallFromElement: function(elm){
		var $elm = $(elm);
		var catalogItemId = $.trim($elm.attr('data-id') || '');
		var version = $.trim($elm.attr('data-current-version') || '');
		var title = $.trim($elm.attr('data-display-title') || $elm.attr('data-title') || '');
		var sourceLabel = $.trim($elm.attr('data-source-label') || ($('[name="source_label_console"]').val() || ''));
		store.queueSystemTaskRequest('console_uninstall', catalogItemId, version, title, sourceLabel);
	},
	resolveSystemTaskTriggerButton: function(taskType, catalogItemId){
		if (taskType === 'console_uninstall') {
			return $('.catalog_item .catalog-delete-btn[data-id="' + catalogItemId + '"]').first();
		}

		return $('.catalog_item .install_btn[data-id="' + catalogItemId + '"]').first();
	},
	queueSystemTaskRequest: function(taskType, catalogItemId, version, title, sourceLabel){
		var $triggerButton = store.resolveSystemTaskTriggerButton(taskType, catalogItemId);
		if ($triggerButton.length && $triggerButton.attr('data-system-task-busy') === '1') {
			return;
		}
		if ($triggerButton.length) {
			$triggerButton.attr('data-system-task-busy', '1').prop('disabled', true).addClass('disabled');
		}

		var popupTitle = store.getSystemTaskActionTitle(taskType, title, sourceLabel);
		if (!store.canManageSystemPackages()) {
			if ($triggerButton.length) {
				$triggerButton.removeAttr('data-system-task-busy').prop('disabled', false).removeClass('disabled');
			}
			store.openPopup(
				popupTitle,
				'<div class="store-popup-shell ' + store.getPopupThemeClass() + '"><div class="store-popup-empty">' + store.escapeHtml($('[name="system_task_modal_queue_error"]').val() || 'Unable to queue this system task.') + '</div></div>',
				'compact'
			);
			return;
		}

		if (!store.canQueueSystemTaskInstall()) {
			if ($triggerButton.length) {
				$triggerButton.removeAttr('data-system-task-busy').prop('disabled', false).removeClass('disabled');
			}
			store.openPopup(
				popupTitle,
				'<div class="store-popup-shell ' + store.getPopupThemeClass() + '"><div class="store-popup-empty">' + store.escapeHtml($('[name="console_install_auto_disabled"]').val() || 'Automatic install is not available right now. Start the scheduler first.') + '</div></div>',
				'compact'
			);
			return;
		}

		store.stopSystemTaskPolling();
		store.stopSystemTaskElapsedTimer();
		store.systemTaskPollToken++;

		$.ajax({
			url: link() + '&action=system_task_create',
			cache: false,
			dataType: 'json',
			type: 'post',
			data: {
				type: taskType,
				catalog_item_id: catalogItemId,
				version: version
			},
			success: function(response){
				if ($triggerButton.length) {
					$triggerButton.removeAttr('data-system-task-busy').prop('disabled', false).removeClass('disabled');
				}
				if (!response || !response.ok || !response.task) {
					if (response && response.error_code === 'GLOBAL_LOCK_ACTIVE' && response.active_task) {
						store.openBlockingTaskPopup(response.active_task, popupTitle);
						return;
					}
					store.openPopup(
						popupTitle,
						'<div class="store-popup-shell ' + store.getPopupThemeClass() + '"><div class="store-popup-empty">' + store.escapeHtml((response && response.message) || ($('[name="system_task_modal_queue_error"]').val() || 'Unable to queue this system task.')) + '</div></div>',
						'compact'
					);
					return;
				}

				if (sourceLabel && !response.task.source_label) {
					response.task.source_label = sourceLabel;
				}
				store.openSystemTaskPopup(response.task, title, response.warnings || []);
			},
			error: function(){
				if ($triggerButton.length) {
					$triggerButton.removeAttr('data-system-task-busy').prop('disabled', false).removeClass('disabled');
				}
				store.openPopup(
					popupTitle,
					'<div class="store-popup-shell ' + store.getPopupThemeClass() + '"><div class="store-popup-empty">' + store.escapeHtml($('[name="system_task_modal_queue_error"]').val() || 'Unable to queue this system task.') + '</div></div>',
					'compact'
				);
			}
		});
	},
	queueConsoleInstallTask: function(button){
		var $button = $(button);
		var catalogItemId = $.trim($button.attr('data-catalog-item-id') || '');
		var version = $.trim($button.attr('data-version') || '');
		var title = $.trim($button.attr('data-title') || '');
		store.queueSystemTaskRequest('console_install', catalogItemId, version, title);
	},
	openBlockingTaskPopup: function(task, popupTitle){
		var title = popupTitle || store.getSystemTaskActionTitle(task.type, task.display_title || task.target || '', task.source_label || '');
		var message = $('[name="system_task_modal_blocked_by_task"]').val() || 'Another task is currently blocking this action.';
		var taskLabel = $('[name="system_task_modal_blocked_task"]').val() || 'Blocking task';
		var html = '<div class="store-popup-shell ' + store.getPopupThemeClass() + '">';
		html += '<div class="store-popup-empty">';
		html += '<strong>' + store.escapeHtml(message) + '</strong>';
		html += '<div class="store-task-blocked-summary">';
		html += '<div><strong>' + store.escapeHtml(taskLabel) + ':</strong> ' + store.escapeHtml((task.display_title || task.target || 'Task')) + '</div>';
		html += '<div><strong>' + store.escapeHtml($('[name="system_task_modal_status"]').val() || 'Status') + ':</strong> ' + store.escapeHtml(task.status || '') + '</div>';
		html += '<div><strong>' + store.escapeHtml($('[name="system_task_modal_step"]').val() || 'Step') + ':</strong> ' + store.escapeHtml(task.step || '') + '</div>';
		html += '</div>';
		html += '<div class="store-task-note-actions">';
		html += '<button type="button" class="btn btn-secondary btn-sm store-task-open-existing" data-task-id="' + store.escapeHtml(String(task.id || '')) + '">' + store.escapeHtml($('[name="system_task_modal_open_blocking"]').val() || 'Open blocking task') + '</button>';
		if (task.can_cancel_queued) {
			html += '<button type="button" class="btn btn-warning btn-sm store-task-cancel-queued" data-task-id="' + store.escapeHtml(String(task.id || '')) + '">' + store.escapeHtml($('[name="system_task_modal_skip_queued"]').val() || 'Skip queued task') + '</button>';
		}
		html += '</div>';
		html += '</div>';
		html += '</div>';

		store.openPopup(title, html, 'compact');
	},
	refreshManagerPermissions: function(callback){
		$.ajax({
			url: link() + '&action=refresh_manager_permissions',
			cache: false,
			dataType: 'json',
			type: 'post',
			success: function(response){
				if (typeof callback === 'function') {
					callback(response && response.ok ? response : null);
				}
			},
			error: function(){
				if (typeof callback === 'function') {
					callback(null);
				}
			}
		});
	},
	refreshManagerUiAfterPermissionSync: function(){
		try {
			if (window.top && window.top.mainMenu && typeof window.top.mainMenu.reloadtree === 'function') {
				window.top.mainMenu.reloadtree();
			}
		} catch (e) {}

		try {
			if (window.top && window.top.location) {
				setTimeout(function(){
					window.top.location.reload();
				}, 700);
			}
		} catch (e) {}
	},
	openSystemTaskPopup: function(task, title, warnings){
		store.stopSystemTaskPolling();
		store.stopSystemTaskElapsedTimer();
		store.systemTaskPollToken++;
		var resolvedTitle = title || task.display_title || task.target || '';
		var initialized = false;
		var initializePopup = function(){
			if (initialized) {
				return;
			}
			initialized = true;
			store.systemTaskPollToken++;
			store.systemTaskPollTaskId = parseInt(task.id || 0, 10) || 0;
			store.systemTaskPollTaskTitle = resolvedTitle;
			store.systemTaskRefreshTaskId = 0;
			store.systemTaskRefreshPermissionsTaskId = 0;
			store.systemTaskWarnings = $.isArray(warnings) ? warnings : [];
			store.setActivePopupTitle(store.getSystemTaskActionTitle(task.type, store.systemTaskPollTaskTitle, task.source_label || ''));
			store.renderSystemTaskPopupState(task, task);
			store.bindPopupCopyButtons();
			store.startSystemTaskPolling();
			store.startSystemTaskElapsedTimer();
		};

		store.openPopup(
			store.getSystemTaskActionTitle(task.type, resolvedTitle, task.source_label || ''),
			store.buildSystemTaskPopupContent(task, task),
			'wide',
			function(){
				initializePopup();
			}
		);

		setTimeout(function(){
			initializePopup();
		}, 80);
		setTimeout(function(){
			if (store.systemTaskPollTaskId && !store.systemTaskPollInFlight && !store.systemTaskPollTimer && !store.systemTaskTerminal) {
				store.pollSystemTask();
			}
		}, 400);
	},
	buildSystemTaskPopupContent: function(task, result){
		var elapsedBase = task.created_at || task.started_at || task.updated_at || '';
		var elapsedEnd = task.finished_at || '';
		var html = '<div class="store-popup-shell store-popup-shell-task ' + store.getPopupThemeClass() + '">';
		html += '<div class="store-task-note-area" data-role="task-note-area"></div>';
		html += '<div class="store-task-warning-list" data-role="task-warnings"></div>';
		html += '<div class="store-task-status-grid">';
		html += store.buildSystemTaskLiveMetaItem('fa-tasks', $('[name="system_task_modal_status"]').val() || 'Status', 'task-status');
		html += store.buildSystemTaskLiveMetaItem('fa-list-ol', $('[name="system_task_modal_step"]').val() || 'Step', 'task-step');
		html += store.buildSystemTaskLiveMetaItem('fa-bar-chart', $('[name="system_task_modal_progress"]').val() || 'Progress', 'task-progress');
		html += ''
			+ '<div class="store-popup-meta-item">'
			+ '<i class="fa fa-clock-o" aria-hidden="true"></i>'
			+ '<span class="store-popup-meta-label">' + store.escapeHtml($('[name="system_task_modal_elapsed"]').val() || 'Elapsed') + ':</span>'
			+ '<strong class="store-task-elapsed-value" data-started-at="' + store.escapeHtml(elapsedBase) + '" data-finished-at="' + store.escapeHtml(elapsedEnd) + '">' + store.escapeHtml(store.formatElapsedTime(elapsedBase, elapsedEnd)) + '</strong>'
			+ '</div>';
		html += '</div>';
		html += '<div class="store-popup-section store-task-logs-section" data-role="task-logs-section" style="display:none">';
		html += '<h3>' + store.escapeHtml($('[name="system_task_modal_logs"]').val() || 'Logs') + '</h3>';
		html += '<div class="store-task-log-list" data-role="task-logs"></div>';
		html += '</div>';

		html += '</div>';
		return html;
	},
	getSystemTaskActionTitle: function(taskType, title, sourceLabel){
		var normalizedType = $.trim(String(taskType || '')).toLowerCase();
		var baseTitle = normalizedType === 'console_uninstall'
			? ($('[name="delete_label"]').val() || 'Delete')
			: ($('[name="install"]').val() || 'Install');
		var suffix = '';
		if (normalizedType === 'console_uninstall' && sourceLabel) {
			suffix = ' (' + sourceLabel + ')';
		}
		return baseTitle + (title ? ': ' + title : '') + suffix;
	},
	setActivePopupTitle: function(title){
		var $popup = store.getActivePopup();
		if (!$popup.length) {
			return;
		}

		$popup.find('.evo-popup-header, .evo-popup-title, .evo-popup-header-title, .modal-title, .popup-title').first().text(title || '');
	},
	buildSystemTaskLiveMetaItem: function(iconClass, label, role){
		return ''
			+ '<div class="store-popup-meta-item">'
			+ '<i class="fa ' + store.escapeHtml(iconClass) + '" aria-hidden="true"></i>'
			+ '<span class="store-popup-meta-label">' + store.escapeHtml(label) + ':</span>'
			+ '<strong data-role="' + store.escapeHtml(role) + '"></strong>'
			+ '</div>';
	},
	renderSystemTaskPopupState: function(task, result){
		var $popup = store.getActivePopup();
		if (!$popup.length) {
			return;
		}

		var $shell = $popup.find('.store-popup-shell-task');
		if (!$shell.length) {
			return;
		}

		var progress = parseInt(task.progress || 0, 10);
		if (isNaN(progress)) {
			progress = 0;
		}
		var currentElapsedBase = $shell.find('.store-task-elapsed-value').attr('data-started-at') || '';
		var elapsedBase = currentElapsedBase || task.created_at || task.started_at || task.updated_at || '';
		var elapsedEnd = task.finished_at || '';
		var logs = [];
		var warningsHtml = '';
		var logsHtml = '';
		var noteHtml = '';
		var schedulerHealth = (task && task.scheduler_health) ? task.scheduler_health : null;
		var workerHealth = (task && task.worker_health) ? task.worker_health : null;
		var queuedStaleState = store.getQueuedStaleState(task, schedulerHealth, workerHealth);

		if (result && $.isArray(result.logs)) {
			logs = result.logs;
		} else if (task && $.isArray(task.logs)) {
			logs = task.logs;
		}

		store.setActivePopupTitle(store.getSystemTaskActionTitle(task.type, store.systemTaskPollTaskTitle || task.display_title || task.target || '', task.source_label || ''));

		$shell.find('[data-role="task-status"]').text(task.status || '');
		$shell.find('[data-role="task-step"]').text(task.step || '');
		$shell.find('[data-role="task-progress"]').text(String(progress) + '%');
		$shell.find('.store-task-elapsed-value')
			.attr('data-started-at', elapsedBase)
			.attr('data-finished-at', elapsedEnd)
			.text(store.formatElapsedTime(elapsedBase, elapsedEnd));

		if ((task.type || '') === 'console_uninstall') {
			noteHtml = '<p class="store-popup-note store-task-note-muted">' + store.escapeHtml($('[name="system_task_modal_uninstall_note"]').val() || '') + '</p>';
		}
		if (queuedStaleState.is_stale) {
			noteHtml += '<div class="store-popup-note store-task-note-muted">';
			noteHtml += store.escapeHtml(queuedStaleState.message);
			if (queuedStaleState.command) {
				noteHtml += '<div class="store-install-card">';
				noteHtml += '<div class="store-install-step">';
				noteHtml += '<div class="store-install-card-head">';
				noteHtml += '<span class="store-install-card-label">' + store.escapeHtml(queuedStaleState.command_label || (($('[name="console_install_scheduler_local"]').val() || 'Local'))) + '</span>';
				noteHtml += '<button type="button" class="store-copy-button" data-copy-command="' + store.escapeHtml(queuedStaleState.command) + '" aria-label="' + store.escapeHtml($('[name="popup_copy_command"]').val() || 'Copy command') + '"><i class="fa fa-copy"></i></button>';
				noteHtml += '</div>';
				noteHtml += '<div class="store-install-command">' + store.escapeHtml(queuedStaleState.command) + '</div>';
				noteHtml += '</div>';
				noteHtml += '</div>';
			}
			noteHtml += '<div class="store-task-note-actions">';
			noteHtml += '<button type="button" class="btn btn-warning btn-sm store-task-cancel-queued" data-task-id="' + store.escapeHtml(String(task.id || '')) + '">' + store.escapeHtml($('[name="system_task_modal_cancel_queued"]').val() || 'Cancel queued task') + '</button>';
			noteHtml += '</div>';
			noteHtml += '</div>';
		}
		$shell.find('[data-role="task-note-area"]').html(noteHtml).toggle(!!noteHtml);

		var shouldShowWarnings = (task.type || '') === 'console_install'
			&& $.inArray((task.status || ''), ['queued', 'picked', 'running']) >= 0;

		if (shouldShowWarnings && store.systemTaskWarnings && store.systemTaskWarnings.length) {
			$.each(store.systemTaskWarnings, function(index, warning){
				warningsHtml += '<div class="store-task-warning-item">';
				warningsHtml += '<strong>' + store.escapeHtml($('[name="system_task_modal_warning"]').val() || 'Warning') + ':</strong> ';
				warningsHtml += '<span>' + store.escapeHtml((warning && warning.message) || '') + '</span>';
				warningsHtml += '</div>';
			});
		}
		$shell.find('[data-role="task-warnings"]').html(warningsHtml).toggle(!!warningsHtml);

		if (!logs.length && (task.status || '') === 'failed') {
			logs = [{
				step: task.step || 'failed',
				message: $.trim((task.message || '') + ((task.error_code || '') ? ' [' + task.error_code + ']' : ''))
			}];
		}

		if (queuedStaleState.is_stale) {
			logs = logs.concat([{
				level: 'warning',
				step: 'stalled',
				message: queuedStaleState.message
			}]);
		}

		$.each(logs, function(index, log){
			var rowClass = '';
			if ((log.level || '') === 'error') {
				rowClass = ' is-error';
			} else if ((log.level || '') === 'warning' || (log.step || '') === 'stalled') {
				rowClass = ' is-warning';
			} else if ((log.step || '') === 'completed' || ((task.status || '') === 'succeeded' && index === logs.length - 1)) {
				rowClass = ' is-success';
			}
			logsHtml += '<div class="store-task-log-row' + rowClass + '">';
			logsHtml += '<span class="store-task-log-message">' + store.escapeHtml(log.message || '') + '</span>';
			logsHtml += '</div>';
		});
		$shell.find('[data-role="task-logs"]').html(logsHtml);
		$shell.find('[data-role="task-logs-section"]').toggle(logs.length > 0);

		store.bindPopupCopyButtons();
		store.recenterActivePopup();
	},
	getQueuedStaleState: function(task, schedulerHealth, workerHealth){
		var taskStatus = String((task && task.status) || '').toLowerCase();
		if (taskStatus !== 'queued') {
			return { is_stale: false, message: '' };
		}

		var createdAt = store.parseDateToTimestamp((task && task.created_at) || '');
		if (!createdAt) {
			return { is_stale: false, message: '' };
		}

		var queuedForSeconds = Math.max(0, Math.floor((Date.now() - createdAt) / 1000));
		var schedulerAge = parseInt((schedulerHealth && schedulerHealth.age_seconds) || 0, 10);
		var workerAge = parseInt((workerHealth && workerHealth.age_seconds) || 0, 10);
		var schedulerStatus = String((schedulerHealth && schedulerHealth.status) || '').toLowerCase();
		var workerStatus = String((workerHealth && workerHealth.status) || '').toLowerCase();

		if (queuedForSeconds < 70) {
			return { is_stale: false, message: '' };
		}

		if (schedulerAge < 70 && schedulerStatus === 'healthy' && (!store.isLocalRuntime() || workerStatus === 'healthy' || workerAge < 70)) {
			return { is_stale: false, message: '' };
		}

		var corePath = $('[name="console_core_path"]').val() || '';
		var command = '';
		var commandLabel = '';
		if (store.isLocalRuntime()) {
			command = 'cd ' + corePath + ' && ' + ($('[name="console_install_scheduler_command_local"]').val() || 'php artisan schedule:work');
			commandLabel = $('[name="console_install_scheduler_local"]').val() || 'Local';
		} else {
			var schedulerServerTemplate = $('[name="console_install_scheduler_command_server"]').val() || '* * * * * cd {core_path} && php artisan schedule:run >/dev/null 2>&1';
			command = schedulerServerTemplate.replace('{core_path}', corePath);
			commandLabel = $('[name="console_install_scheduler_server"]').val() || 'Server';
		}

		return {
			is_stale: true,
			command: command,
			command_label: commandLabel,
			message: store.isLocalRuntime()
				? ($('[name="system_task_modal_stale_local"]').val() || 'This task has been queued for over a minute and the scheduler does not seem to be running. Start php artisan schedule:work again or cancel this queued task.')
				: ($('[name="system_task_modal_stale_server"]').val() || 'This task has been queued for over a minute and cron or scheduler does not seem to be running. Resume cron schedule:run or cancel this queued task.')
		};
	},
	startSystemTaskPolling: function(){
		store.stopSystemTaskPolling();
		if (!store.systemTaskPollTaskId) {
			return;
		}
		store.systemTaskTerminal = false;
		store.pollSystemTask();
	},
	stopSystemTaskPolling: function(){
		if (store.systemTaskPollTimer) {
			clearTimeout(store.systemTaskPollTimer);
			store.systemTaskPollTimer = null;
		}
		store.systemTaskPollInFlight = false;
		store.stopSystemTaskElapsedTimer();
	},
	scheduleNextSystemTaskPoll: function(delayMs){
		if (!store.systemTaskPollTaskId) {
			return;
		}
		if (store.systemTaskPollTimer) {
			clearTimeout(store.systemTaskPollTimer);
			store.systemTaskPollTimer = null;
		}
		store.systemTaskPollTimer = setTimeout(function(){
			store.systemTaskPollTimer = null;
			store.pollSystemTask();
		}, Math.max(0, parseInt(delayMs || 0, 10) || 0));
	},
	pollSystemTask: function(){
		if (!store.systemTaskPollTaskId) {
			return;
		}
		if (store.systemTaskPollInFlight) {
			store.scheduleNextSystemTaskPoll(1000);
			return;
		}
		store.systemTaskPollInFlight = true;
		store.systemTaskLastPollAt = Date.now();
		var pollToken = store.systemTaskPollToken;
		$.ajax({
			url: link() + '&action=system_task_result',
			cache: false,
			dataType: 'json',
			type: 'get',
			data: {
				task_id: store.systemTaskPollTaskId
			},
			success: function(response){
				store.systemTaskPollInFlight = false;
				if (pollToken !== store.systemTaskPollToken) {
					return;
				}
				if (!response || !response.ok || !response.task) {
					if (response && response.message) {
						store.renderSystemTaskPopupState({
							type: '',
							target: store.systemTaskPollTaskTitle || '',
							display_title: store.systemTaskPollTaskTitle || '',
							status: 'failed',
							step: 'failed',
							progress: 100,
							message: response.message,
							error_code: response.error_code || 'TASK_RESULT_UNAVAILABLE',
							finished_at: new Date().toISOString(),
							logs: [{
								step: 'failed',
								message: response.message + (response.error_code ? ' [' + response.error_code + ']' : '')
							}]
						}, null);
						store.systemTaskTerminal = true;
						store.stopSystemTaskPolling();
						return;
					}
					store.scheduleNextSystemTaskPoll(10000);
					return;
				}

				var task = response.task;
				store.systemTaskLastPollAt = Date.now();
				store.renderSystemTaskPopupState(task, task);
				if (
					task
					&& task.can_refresh_state
					&& parseInt(task.id || 0, 10) > 0
					&& store.systemTaskRefreshTaskId !== parseInt(task.id || 0, 10)
				) {
					store.systemTaskRefreshTaskId = parseInt(task.id || 0, 10);
					store.refreshInstalledState();
				}
				if (
					task
					&& task.status === 'succeeded'
					&& $.inArray(task.type, ['console_install', 'console_uninstall']) >= 0
					&& store.systemTaskRefreshPermissionsTaskId !== parseInt(task.id || 0, 10)
				) {
					store.systemTaskRefreshPermissionsTaskId = parseInt(task.id || 0, 10);
					store.refreshManagerPermissions(function(response){
						if (response && response.ok) {
							store.refreshManagerUiAfterPermissionSync();
						}
					});
				}

				if (task && $.inArray(task.status, ['finished', 'succeeded', 'failed']) >= 0) {
					store.systemTaskTerminal = true;
					store.stopSystemTaskPolling();
					return;
				}
				store.scheduleNextSystemTaskPoll(10000);
			},
			error: function(){
				store.systemTaskPollInFlight = false;
				store.systemTaskLastPollAt = Date.now();
				if (pollToken !== store.systemTaskPollToken) {
					return;
				}
				store.scheduleNextSystemTaskPoll(10000);
			}
		});
	},
	cancelQueuedSystemTask: function(taskId){
		taskId = parseInt(taskId || 0, 10);
		if (!taskId || store.systemTaskCancelInFlight) {
			return;
		}

		store.systemTaskCancelInFlight = true;
		$.ajax({
			url: link() + '&action=system_task_cancel',
			cache: false,
			dataType: 'json',
			type: 'post',
			data: {
				task_id: taskId
			},
			success: function(response){
				store.systemTaskCancelInFlight = false;
				if (!response || !response.ok || !response.task) {
					return;
				}
				store.renderSystemTaskPopupState(response.task, response.task);
				store.systemTaskTerminal = true;
				store.stopSystemTaskPolling();
			},
			error: function(){
				store.systemTaskCancelInFlight = false;
			}
		});
	},
	openExistingSystemTask: function(taskId){
		taskId = parseInt(taskId || 0, 10);
		if (!taskId) {
			return;
		}

		$.ajax({
			url: link() + '&action=system_task_result',
			cache: false,
			dataType: 'json',
			type: 'get',
			data: {
				task_id: taskId
			},
			success: function(response){
				if (!response || !response.ok || !response.task) {
					return;
				}
				store.openSystemTaskPopup(response.task, response.task.display_title || response.task.target || '', []);
			}
		});
	},
	startSystemTaskElapsedTimer: function(){
		store.stopSystemTaskElapsedTimer();
		store.updateSystemTaskElapsed();
		store.systemTaskElapsedTimer = setInterval(function(){
			if (!store.getActivePopup().length) {
				store.stopSystemTaskElapsedTimer();
				return;
			}
			store.updateSystemTaskElapsed();
			if (
				store.systemTaskPollTaskId
				&& !store.systemTaskTerminal
				&& !store.systemTaskPollInFlight
				&& !store.systemTaskPollTimer
				&& (!store.systemTaskLastPollAt || (Date.now() - store.systemTaskLastPollAt) >= 10000)
			) {
				store.scheduleNextSystemTaskPoll(0);
			}
		}, 1000);
	},
	stopSystemTaskElapsedTimer: function(){
		if (store.systemTaskElapsedTimer) {
			clearInterval(store.systemTaskElapsedTimer);
			store.systemTaskElapsedTimer = null;
		}
	},
	updateSystemTaskElapsed: function(){
		var $popup = store.getActivePopup();
		if (!$popup.length) {
			return;
		}

		$popup.find('.store-task-elapsed-value').each(function(){
			var $value = $(this);
			$value.text(store.formatElapsedTime($value.attr('data-started-at') || '', $value.attr('data-finished-at') || ''));
		});
	},
	formatElapsedTime: function(startedAt, finishedAt){
		var startedTimestamp = store.parseDateToTimestamp(startedAt);
		if (!startedTimestamp) {
			return '00:00';
		}

		var endTimestamp = store.parseDateToTimestamp(finishedAt);
		if (!endTimestamp) {
			endTimestamp = Date.now();
		}

		var totalSeconds = Math.max(0, Math.floor((endTimestamp - startedTimestamp) / 1000));
		var hours = Math.floor(totalSeconds / 3600);
		var minutes = Math.floor((totalSeconds % 3600) / 60);
		var seconds = totalSeconds % 60;

		if (hours > 0) {
			return store.padNumber(hours) + ':' + store.padNumber(minutes) + ':' + store.padNumber(seconds);
		}

		return store.padNumber(minutes) + ':' + store.padNumber(seconds);
	},
	parseDateToTimestamp: function(value){
		var stringValue = $.trim(value || '');
		if (!stringValue) {
			return 0;
		}

		var timestamp = Date.parse(stringValue);
		if (!isNaN(timestamp)) {
			return timestamp;
		}

		timestamp = Date.parse(stringValue.replace(' ', 'T'));
		return isNaN(timestamp) ? 0 : timestamp;
	},
	padNumber: function(number){
		number = parseInt(number, 10) || 0;
		return number < 10 ? '0' + number : String(number);
	},
	isLocalRuntime: function(){
		var host = (window.location && window.location.hostname ? window.location.hostname : '').toLowerCase();
		return host === 'localhost' || host === '127.0.0.1' || host === '::1';
	},
	showItemMore: function(elm){
		store.resetSystemTaskUiState();

		var $button = $(elm);
		var title = $button.attr('data-title') || '';
		var sourceKind = $button.attr('data-source-kind') || 'legacy';

		if (sourceKind === 'console' && $button.attr('data-repo-full-name')) {
			store.fetchConsoleReadme(
				$button.attr('data-repo-full-name'),
				$button.attr('data-readme-branch'),
				$button.attr('data-source-url'),
				function(response){
		store.openPopup(title, store.buildConsolePopupContent($button, response), 'wide');
				}
			);
			return;
		}

		store.openPopup(title, store.buildLegacyPopupContent($button), 'wide');
	},
	fetchConsoleReadme: function(repo, branch, sourceUrl, callback){
		$.ajax({
			url: link() + '&action=console_readme',
			cache: false,
			dataType: 'json',
			type: 'get',
			data: {
				repo: repo || '',
				branch: branch || '',
				source_url: sourceUrl || ''
			},
			success: function(data){
				callback(data || {});
			},
			error: function(){
				callback({
					ok: false,
					html: '',
					message: $('[name="popup_readme_missing"]').val() || 'README.md was not found for this package yet.',
					repo_url: sourceUrl || ''
				});
			}
		});
	},
	buildLegacyPopupContent: function($button){
		var html = '<div class="store-popup-shell store-popup-shell-console ' + store.getPopupThemeClass() + '">';
		html += store.buildPopupLead($button);
		html += store.buildPopupMeta($button);
		html += store.buildPopupSource($button.attr('data-source-url'));
		html += '</div>';
		return html;
	},
	buildConsolePopupContent: function($button, response){
		var readmeLabel = $('[name="popup_readme"]').val() || 'README';
		var openRepoLabel = $('[name="popup_open_repo"]').val() || 'Open repository';
		var sourceUrl = response && response.repo_url ? response.repo_url : ($button.attr('data-source-url') || '');
		var html = '<div class="store-popup-shell store-popup-shell-console ' + store.getPopupThemeClass() + '">';
		html += store.buildPopupLead($button);
		html += store.buildPopupMeta($button);
		html += store.buildPopupSource(sourceUrl);

		if (sourceUrl !== '') {
			html += '<p class="store-popup-actions"><a href="' + store.escapeHtml(sourceUrl) + '" target="_blank" rel="noopener">' + store.escapeHtml(openRepoLabel) + '</a></p>';
		}

		html += '<div class="store-popup-section">';
		html += '<h3>' + store.escapeHtml(readmeLabel) + '</h3>';
		if (response && response.ok && response.html) {
			html += '<div class="store-popup-readme">' + response.html + '</div>';
		} else {
			html += '<div class="store-popup-empty">' + store.escapeHtml((response && response.message) || ($('[name="popup_readme_missing"]').val() || 'README.md was not found for this package yet.')) + '</div>';
		}
		html += '</div></div>';
		return html;
	},
	buildPopupLead: function($button){
		var description = $button.attr('data-description') || '';
		var type = $button.attr('data-type') || '';
		var sourceLabel = $button.attr('data-source-label') || '';
		var html = '<div class="store-popup-lead">';
		if (sourceLabel !== '' || type !== '') {
			html += '<div class="store-popup-badges">';
			if (sourceLabel !== '') {
				html += '<span class="store-popup-badge store-popup-badge-source">' + store.escapeHtml(sourceLabel) + '</span>';
			}
			if (type !== '') {
				html += '<span class="store-popup-badge store-popup-badge-type">' + store.escapeHtml(type) + '</span>';
			}
			html += '</div>';
		}
		if (description !== '') {
			html += '<p class="store-popup-description">' + store.escapeHtml(description) + '</p>';
		}
		html += '</div>';
		return html;
	},
	buildPopupMeta: function($button){
		var versionLabel = $('[name="popup_version"]').val() || 'Version';
		var updatedLabel = $('[name="popup_updated"]').val() || 'Updated';
		var authorLabel = $('[name="popup_author"]').val() || 'Author';
		var downloadsLabel = $('[name="popup_downloads"]').val() || 'Downloads';
		var $selectedOption = $button.closest('.catalog_item').find('[name="link"] option:selected');
		var selectedVersion = $.trim($selectedOption.text() || '') || $.trim($button.closest('.catalog_item').find('[name="link"]').val() || '');
		var versionValue = selectedVersion || $button.attr('data-version') || '';
		var html = '<div class="store-popup-meta">';

		html += store.buildMetaItem('fa-refresh', versionLabel, versionValue);
		html += store.buildMetaItem('fa-clock-o', updatedLabel, $button.attr('data-date') || '');
		html += store.buildMetaItem('fa-user', authorLabel, $button.attr('data-author') || '');
		html += store.buildMetaItem('fa-download', downloadsLabel, $button.attr('data-downloads') || '');
		html += '</div>';

		return html;
	},
	buildMetaItem: function(iconClass, label, value){
		if (!value) {
			return '';
		}

		return ''
			+ '<div class="store-popup-meta-item">'
			+ '<i class="fa ' + store.escapeHtml(iconClass) + '" aria-hidden="true"></i>'
			+ '<span class="store-popup-meta-label">' + store.escapeHtml(label) + ':</span>'
			+ '<strong>' + store.escapeHtml(value) + '</strong>'
			+ '</div>';
	},
	buildPopupSource: function(sourceUrl){
		var label = $('[name="popup_source"]').val() || 'Source';
		if (!sourceUrl) {
			return '';
		}

		return '<p class="store-popup-source"><strong>' + store.escapeHtml(label) + ':</strong> <a href="' + store.escapeHtml(sourceUrl) + '" target="_blank" rel="noopener">' + store.escapeHtml(sourceUrl) + '</a></p>';
	},
	openPopup: function(title, content, size, onOpen){
		var width = size === 'wide' ? '78%' : '680px';
		var height = size === 'wide' ? 'auto' : '250px';
		var popupType = store.isDarkTheme() ? 'dark' : 'default';

		store.closeActivePopup();
		store.cleanupPopupArtifacts();

		var popupInstance = window.parent.evo.popup({
			title: title,
			content: content,
			type: popupType,
			width: width,
			height: height,
			maxheight: '82%',
			hide: 0,
			hover: 0,
			overlay: 1,
			overlayclose: 1,
			showclose: 1,
			position: 'top center',
			margin: '10px',
			wrap: document.body
		});

		store._activePopupUid = popupInstance && popupInstance.uid ? popupInstance.uid : null;
		store._activePopupDoc = popupInstance && popupInstance.wrap && popupInstance.wrap.ownerDocument
			? popupInstance.wrap.ownerDocument
			: document;

		store.schedulePopupStabilization(size, onOpen);
	},
	schedulePopupStabilization: function(size, onOpen){
		store.stopPopupStabilization();

		var didOpen = false;
		var stabilize = function(){
			var $popup = store.getActivePopup();
			if (!$popup.length) {
				if (didOpen) {
					store.stopPopupStabilization();
				}
				return;
			}

			store.decorateActivePopup(size);
			store.recenterActivePopup();

			if (!didOpen && typeof onOpen === 'function') {
				didOpen = true;
				onOpen();
			}
		};

		setTimeout(stabilize, 20);
		setTimeout(stabilize, 80);
		setTimeout(stabilize, 180);
		setTimeout(stabilize, 360);
		setTimeout(stabilize, 720);

		setTimeout(function(){
			var $popup = store.getActivePopup();
			if (!$popup.length || !window.MutationObserver) {
				return;
			}

			var contentNode = $popup.find('.evo-popup-content').get(0) || $popup.get(0);
			if (!contentNode) {
				return;
			}

			store._popupStabilizeObserver = new MutationObserver(function(){
				stabilize();
			});
			store._popupStabilizeObserver.observe(contentNode, {
				childList: true,
				subtree: true
			});

			$popup.find('img').each(function(){
				if (!this.complete) {
					$(this).one('load error', stabilize);
				}
			});
		}, 40);

		store._popupStabilizeTimeout = setTimeout(function(){
			store.stopPopupStabilization();
		}, 1500);
	},
	stopPopupStabilization: function(){
		if (store._popupStabilizeInterval) {
			clearInterval(store._popupStabilizeInterval);
			store._popupStabilizeInterval = null;
		}
		if (store._popupStabilizeTimeout) {
			clearTimeout(store._popupStabilizeTimeout);
			store._popupStabilizeTimeout = null;
		}
		if (store._popupStabilizeObserver) {
			store._popupStabilizeObserver.disconnect();
			store._popupStabilizeObserver = null;
		}
		store._activePopupUid = null;
		store._activePopupDoc = null;
	},
	getActivePopup: function(){
		if (store._activePopupUid) {
			var activeDoc = store._activePopupDoc || document;
			var activePopup = activeDoc.getElementById('evo-popup-' + store._activePopupUid);
			if (activePopup && $(activePopup).is(':visible')) {
				return $(activePopup);
			}
		}

		var $localPopup = $(document).find('.evo-popup:visible').last();
		if ($localPopup.length) {
			return $localPopup;
		}

		if (window.parent && window.parent.document) {
			return $(window.parent.document).find('.evo-popup:visible').last();
		}

		return $();
	},
	decorateActivePopup: function(size){
		var $popup = store.getActivePopup();
		if (!$popup.length) {
			return;
		}

		$popup.removeClass('store-popup-os-mac store-popup-os-win store-popup-size-wide store-popup-size-compact');
		$popup.addClass(size === 'wide' ? 'store-popup-size-wide' : 'store-popup-size-compact');
		$popup.addClass(store.isMacPlatform() ? 'store-popup-os-mac' : 'store-popup-os-win');
		if (size === 'wide') {
			var popupEl = $popup.get(0);
			var contentEl = $popup.find('.evo-popup-content').get(0);
			var frameBounds = store.getPopupFrameBounds($popup);
			var viewportHeight = frameBounds.height || document.documentElement.clientHeight || 0;
			if (popupEl) {
				popupEl.style.height = 'auto';
				popupEl.style.maxHeight = Math.max(420, viewportHeight - 20) + 'px';
			}
			if (contentEl) {
				contentEl.style.height = 'auto';
				contentEl.style.maxHeight = Math.max(360, viewportHeight - 80) + 'px';
				contentEl.style.overflowX = 'hidden';
				contentEl.style.overflowY = 'auto';
			}
		} else {
			var compactPopupEl = $popup.get(0);
			var compactContentEl = $popup.find('.evo-popup-content').get(0);
			if (compactPopupEl) {
				compactPopupEl.style.height = '';
				compactPopupEl.style.maxHeight = '';
			}
			if (compactContentEl) {
				compactContentEl.style.height = '';
				compactContentEl.style.maxHeight = '';
				compactContentEl.style.overflowX = '';
				compactContentEl.style.overflowY = '';
			}
		}
		store.applyActivePopupTheme($popup);
	},
	getPopupFrameBounds: function($popup){
		var popupDoc = $popup && $popup.length ? $popup.get(0).ownerDocument : document;
		var popupWindow = popupDoc.defaultView || window;
		var bounds = {
			top: 0,
			left: 0,
			width: popupWindow.innerWidth || popupDoc.documentElement.clientWidth || 0,
			height: popupWindow.innerHeight || popupDoc.documentElement.clientHeight || 0
		};

		if (popupDoc === document) {
			return bounds;
		}

		try {
			if (window.frameElement && window.frameElement.getBoundingClientRect) {
				var rect = window.frameElement.getBoundingClientRect();
				if (rect && rect.width && rect.height) {
					bounds.top = Math.round(rect.top);
					bounds.left = Math.round(rect.left);
					bounds.width = Math.round(rect.width);
					bounds.height = Math.round(rect.height);
				}
			}
		} catch (e) {}

		return bounds;
	},
	applyActivePopupTheme: function($popup){
		if (!$popup || !$popup.length) {
			return;
		}

		var isDark = store.isDarkTheme();
		$popup.removeClass('alert-dark alert-default');
		$popup.addClass(isDark ? 'alert-dark' : 'alert-default');
		$popup.find('.evo-popup-content')
			.removeClass('store-popup-content-theme-dark store-popup-content-theme-light')
			.addClass(isDark ? 'store-popup-content-theme-dark' : 'store-popup-content-theme-light');
		$popup.find('.store-popup-shell')
			.removeClass('store-popup-theme-dark store-popup-theme-light')
			.addClass(isDark ? 'store-popup-theme-dark' : 'store-popup-theme-light');
	},
	recenterActivePopup: function(){
		var $popup = store.getActivePopup();
		if (!$popup.length) {
			return;
		}

		var popup = $popup.get(0);
		var frameBounds = store.getPopupFrameBounds($popup);
		var popupWidth = popup.offsetWidth || 0;
		var left = frameBounds.left + Math.max(10, Math.round((frameBounds.width - popupWidth) / 2));
		popup.style.top = (frameBounds.top + 10) + 'px';
		popup.style.left = left + 'px';
		popup.style.bottom = 'auto';
		popup.style.right = 'auto';
		popup.style.marginTop = '0';
		popup.style.marginBottom = '0';
		popup.style.transform = 'none';
	},
	isDarkTheme: function(){
		return $('body').hasClass('darkness');
	},
	isMacPlatform: function(){
		var platform = '';
		try {
			platform = (window.parent && window.parent.navigator ? window.parent.navigator.platform : window.navigator.platform) || '';
		} catch (e) {
			platform = window.navigator.platform || '';
		}
		return /Mac/i.test(platform);
	},
	getPopupThemeClass: function(){
		return store.isDarkTheme() ? 'store-popup-theme-dark' : 'store-popup-theme-light';
	},
	bindPopupCopyButtons: function(){
		var copiedLabel = $('[name="popup_copied"]').val() || 'Copied';
		var copyLabel = $('[name="popup_copy_command"]').val() || 'Copy command';
		var $popup = store.getActivePopup();
		var $buttons = $popup.length ? $popup.find('.store-copy-button') : $('.store-copy-button');

		$buttons.off('click.storecopy').on('click.storecopy', function(event){
			event.preventDefault();
			event.stopPropagation();
			var button = this;
			var text = $(button).attr('data-copy-command') || '';
			if (!text) {
				return false;
			}

			store.copyText(text, button.ownerDocument, function(success){
				if (!success) {
					return;
				}

				var $button = $(button);
				$button.addClass('is-copied').attr('aria-label', copiedLabel).html('<i class="fa fa-check"></i>');
				setTimeout(function(){
					$button.removeClass('is-copied')
						.attr('aria-label', copyLabel)
						.html('<i class="fa fa-copy"></i>');
				}, 3000);
			});

			return false;
		});
	},
	copyText: function(text, sourceDocument, callback){
		var done = function(result){
			if (typeof callback === 'function') {
				callback(result);
			}
		};

		try {
			var clipboard = null;
			var sourceWindow = sourceDocument && sourceDocument.defaultView ? sourceDocument.defaultView : window;
			if (sourceWindow.navigator && sourceWindow.navigator.clipboard && sourceWindow.navigator.clipboard.writeText) {
				clipboard = sourceWindow.navigator.clipboard;
			} else if (window.navigator && window.navigator.clipboard && window.navigator.clipboard.writeText) {
				clipboard = window.navigator.clipboard;
			} else if (window.parent && window.parent.navigator && window.parent.navigator.clipboard && window.parent.navigator.clipboard.writeText) {
				clipboard = window.parent.navigator.clipboard;
			}
			if (clipboard) {
				clipboard.writeText(text).then(function(){
					done(true);
				}).catch(function(){
					done(store.copyTextFallback(text, sourceDocument));
				});
				return;
			}
		} catch (e) {}

		done(store.copyTextFallback(text, sourceDocument));
	},
	copyTextFallback: function(text, sourceDocument){
		var tryCopyInDocument = function(targetDoc){
			try {
				var textarea = targetDoc.createElement('textarea');
				textarea.value = text;
				textarea.setAttribute('readonly', 'readonly');
				textarea.style.position = 'fixed';
				textarea.style.top = '0';
				textarea.style.left = '0';
				textarea.style.opacity = '0';
				textarea.style.pointerEvents = 'none';
				targetDoc.body.appendChild(textarea);
				textarea.focus();
				textarea.select();
				textarea.setSelectionRange(0, textarea.value.length);
				var ok = targetDoc.execCommand('copy');
				targetDoc.body.removeChild(textarea);
				return ok;
			} catch (e) {
				return false;
			}
		};

		try {
			if (sourceDocument && tryCopyInDocument(sourceDocument)) {
				return true;
			}
			if (tryCopyInDocument(document)) {
				return true;
			}
			if (window.parent && window.parent.document && tryCopyInDocument(window.parent.document)) {
				return true;
			}
			return false;
		} catch (e) {
			return false;
		}
	},
	parse: function(str,array){
		var out = str.replace(/%\w+%/g, function(placeholder) {
			return array[ placeholder.split('%').join('') ] || '';
		});
		if (array.image) out = $('<div id="tmpl">'+out+'</div>').find('img').attr('src',array.image).closest('#tmpl').html();
		return out;
	},
	syncTheme: function(){
		var themeClass = '';
		try {
			if (window.parent && window.parent.document && window.parent.document.body && window.parent.document.body.classList.contains('darkness')) {
				themeClass = 'darkness';
			}
		} catch (e) {}

		if (!themeClass) {
			try {
				var rawMode = window.localStorage ? window.localStorage.getItem('EVO_themeMode') : null;
				if (String(rawMode) === '4') {
					themeClass = 'darkness';
				}
			} catch (e) {}
		}

		store.applyThemeClass(themeClass);
	},
	applyThemeClass: function(themeClass){
		$('body').removeClass('lightness light dark darkness');

		if (themeClass) {
			$('body').addClass(themeClass);
		}

		store.applyActivePopupTheme(store.getActivePopup());
	},
	observeParentTheme: function(){
		if (store._themeObserverReady) {
			return;
		}

		store._themeObserverReady = true;

		try {
			if (!window.parent || !window.parent.document || !window.parent.document.body || !window.MutationObserver) {
				return;
			}

			var target = window.parent.document.body;
			var observer = new MutationObserver(function(){
				store.syncTheme();
			});

			observer.observe(target, {
				attributes: true,
				attributeFilter: ['class']
			});

			store._themeObserver = observer;
		} catch (e) {}
	},
	renderCurrentList: function(){
		var tpl = store.currentTemplate || 'list';
		var sortedList = store.sortList(store.currentList);
		$('.item_list').html( store.parse_list( sortedList , $('.tpl #tpl_'+tpl).html() , tpl ) );
		store.syncSelectDisplays();
		store.applyCurrentSearchFilter();
	},
	applyCurrentSearchFilter: function(){
		var value = $('#store_search').val() || '';
		if (value === '') {
			$('.item_list .catalog_item').show();
			return;
		}
		store_search(value);
	},
	ensureSelectDisplay: function($select){
		if (!$select || !$select.length) {
			return $();
		}

		var $wrap;
		if ($select.attr('id') === 'store_sort') {
			$wrap = $select.closest('.store-sort-wrap');
			if (!$wrap.length) {
				$select.wrap('<span class="input-group-btn store-select-wrap store-sort-wrap"></span>');
				$wrap = $select.parent();
			}
			if (!$wrap.hasClass('store-select-wrap')) {
				$wrap.addClass('store-select-wrap');
			}
		} else {
			$wrap = $select.closest('.store-version-wrap');
			if (!$wrap.length) {
				$select.wrap('<span class="store-select-wrap store-version-wrap"></span>');
				$wrap = $select.parent();
			}
		}

		if (!$wrap.find('.store-select-display').length) {
			$select.before('<span class="store-select-display"></span>');
		}

		return $wrap;
	},
	syncSelectDisplay: function($wrap){
		if (!$wrap || !$wrap.length) {
			return;
		}

		var $select = $wrap.find('select').first();
		var $display = $wrap.find('.store-select-display').first();
		if (!$select.length || !$display.length) {
			return;
		}

		if ($select.attr('id') !== 'store_sort' && $select.attr('data-hide-display') === '1') {
			$wrap.hide();
			return;
		}

		$wrap.show();

		var text = $.trim($select.find('option:selected').text() || '');
		if (!text) {
			text = $.trim($select.find('option').first().text() || '');
		}
		$display.text(text);
		$wrap.toggleClass('store-select-empty', text === '');
	},
	syncSelectDisplays: function(context){
		var $root = context ? $(context) : $(document);
		$root.find('select[name="link"], #store_sort').each(function(){
			var $wrap = store.ensureSelectDisplay($(this));
			store.syncSelectDisplay($wrap);
		});
	},
	parseInstalledState: function(){
		var raw = $('[name="installed_state"]').val() || '';
		if (!raw) {
			return {
				legacy_by_type: store.types || {},
				legacy_items: [],
				console_by_composer: {}
			};
		}

		try {
			return eval('(' + raw + ')');
		} catch (error) {
			return {
				legacy_by_type: store.types || {},
				legacy_items: [],
				console_by_composer: {}
			};
		}
	},
	parseSystemTaskUiFlags: function(){
		var raw = $('[name="system_task_ui_flags"]').val() || '';
		if (!raw) {
			return {
				can_view: 0,
				can_manage_packages: 0,
				can_site_update: 0
			};
		}

		try {
			var parsed = JSON.parse(raw);
			return {
				can_view: parsed && parsed.can_view ? 1 : 0,
				can_manage_packages: parsed && parsed.can_manage_packages ? 1 : 0,
				can_site_update: parsed && parsed.can_site_update ? 1 : 0
			};
		} catch (error) {
			return {
				can_view: 0,
				can_manage_packages: 0,
				can_site_update: 0
			};
		}
	},
	canViewSystemTasks: function(){
		return !!(store.systemTaskUiFlags && parseInt(store.systemTaskUiFlags.can_view || 0, 10));
	},
	canManageSystemPackages: function(){
		return !!(store.systemTaskUiFlags && parseInt(store.systemTaskUiFlags.can_manage_packages || 0, 10));
	},
	canQueueSystemTaskInstall: function(){
		var schedulerStatus = store.getSystemTaskHealthStatus('scheduler');
		var workerStatus = store.getSystemTaskHealthStatus('worker');
		if (!store.canManageSystemPackages() || schedulerStatus !== 'healthy') {
			return false;
		}
		if (store.isLocalRuntime()) {
			return workerStatus === 'healthy';
		}
		return true;
	},
	applyInstalledStateToItem: function(item){
		var array = $.extend(true, {}, item || {});
		if (!array.cls) {
			array.cls = 'pack_install';
		}
		array.cls = 'pack_install';
		array.state_class = '';
		array.title_state_html = '';
		array.install_state_html = '';
		array.catalog_version = array.catalog_version || array.version || '';
		array.installed_state = 0;
		array.current_version = '';
		array.raw_current_version = '';
		array.is_installed = 0;

		if ((array.install_method || '') === 'console-extra' || (array.source_kind || '') === 'console') {
			array = store.applyConsoleInstalledState(array);
		} else {
			array = store.applyLegacyInstalledState(array);
		}

		return store.decorateInstalledVisualState(array);
	},
	applyConsoleInstalledState: function(array){
		var composerName = String(array.composer_name || '').toLowerCase();
		var consoleMap = (store.installedState && store.installedState.console_by_composer) || {};
		if (!composerName || !consoleMap[composerName]) {
			return array;
		}

		var installed = consoleMap[composerName];
		array.is_installed = installed.is_installed ? 1 : 0;
		array.current_version = installed.version || array.current_version || '';
		array.raw_current_version = installed.raw_version || array.raw_current_version || '';
		array.cls = store.resolveInstalledClass(array.current_version, array.version, array.readme_branch);
		return array;
	},
	applyLegacyInstalledState: function(array){
		var normalizedType = store.normalizeLegacyType(array.type);
		var itemName = array.name_in_modx || array.title || array.name || '';
		var installedVersion = store.findLegacyInstalledVersionByTypeAndName(normalizedType, itemName);

		if (!installedVersion) {
			installedVersion = store.findLegacyInstalledVersionByName(itemName);
		}

		if (!installedVersion && !store.hasLegacyInstalledName(itemName)) {
			return array;
		}

		array.is_installed = 1;
		array.current_version = installedVersion || array.current_version || '';
		array.cls = store.resolveInstalledClass(array.current_version, array.version, '');
		return array;
	},
	decorateInstalledVisualState: function(array){
		array.installed_state = array.is_installed ? 1 : 0;
		if (!array.is_installed) {
			array.state_class = '';
			array.title_state_html = '';
			array.install_state_html = '';
			return array;
		}

		var installedVersion = $.trim(String(array.current_version || ''));
		var latestVersion = $.trim(String(array.catalog_version || array.version || ''));
		var normalizedInstalled = store.normalizeComparableVersion(installedVersion, array.readme_branch);
		var normalizedLatest = store.normalizeComparableVersion(latestVersion, array.readme_branch);
		var badges = [];

		badges.push(
			'<span class="store-version-chip store-version-chip-current">' +
				store.escapeHtml(installedVersion || 'installed') +
			'</span>'
		);

		if (latestVersion && normalizedLatest && normalizedLatest !== normalizedInstalled) {
			badges.push(
				'<span class="store-version-chip store-version-chip-latest">' +
					store.escapeHtml('→ ' + latestVersion) +
				'</span>'
			);
		}

		array.state_class = 'is-installed';
		array.title_state_html = '<span class="store-title-state">' + badges.join('') + '</span>';
		array.install_state_html = '';
		return array;
	},
	findLegacyInstalledVersionByName: function(name){
		var target = String(name || '').toLowerCase();
		if (!target) {
			return '';
		}

		var items = (store.installedState && store.installedState.legacy_items) || [];
		var foundVersion = '';
		$.each(items, function(index, item){
			if (String((item && item.name) || '').toLowerCase() === target) {
				foundVersion = item.version || '';
				return false;
			}
		});
		return foundVersion;
	},
	hasLegacyInstalledName: function(name){
		var target = String(name || '').toLowerCase();
		if (!target) {
			return false;
		}

		var items = (store.installedState && store.installedState.legacy_items) || [];
		var found = false;
		$.each(items, function(index, item){
			if (String((item && item.name) || '').toLowerCase() === target) {
				found = true;
				return false;
			}
		});
		return found;
	},
	findLegacyInstalledVersionByTypeAndName: function(type, name){
		var normalizedType = store.normalizeLegacyLookupType(type);
		var target = $.trim(String(name || '')).toLowerCase();
		var items = (store.installedState && store.installedState.legacy_items) || [];
		var foundVersion = '';

		if (!normalizedType || !target) {
			return '';
		}

		$.each(items, function(index, item){
			if (store.normalizeLegacyLookupType(item.type) !== normalizedType) {
				return;
			}
			if (String((item && item.name) || '').toLowerCase() !== target) {
				return;
			}
			foundVersion = item.version || '';
			return false;
		});

		return foundVersion;
	},
	getLegacyInstalledKey: function(item){
		var normalizedType = store.normalizeLegacyLookupType(item.type);
		var name = $.trim(String(item.name_in_modx || item.name || item.title || ''));

		if (!normalizedType || !name) {
			return '';
		}

		return normalizedType + '::' + name.toLowerCase();
	},
	normalizeLegacyLookupType: function(type){
		type = String(type || '').toLowerCase();
		if (type === 'snippet' || type === 'snippets') return 'snippets';
		if (type === 'plugin' || type === 'plugins') return 'plugins';
		if (type === 'module' || type === 'modules') return 'modules';
		return '';
	},
	getLegacyDisplayType: function(type){
		type = store.normalizeLegacyLookupType(type);
		if (type === 'snippets') return 'snippet';
		if (type === 'plugins') return 'plugin';
		if (type === 'modules') return 'module';
		return 'package';
	},
	normalizeLegacyType: function(type){
		type = String(type || '');
		if (type === 'snippet') return 'snippets';
		if (type === 'plugin') return 'plugins';
		if (type === 'module') return 'modules';
		return type;
	},
	resolveInstalledClass: function(installedVersion, catalogVersion, defaultBranch){
		var normalizedInstalled = store.normalizeComparableVersion(installedVersion, defaultBranch);
		var normalizedCatalog = store.normalizeComparableVersion(catalogVersion, defaultBranch);

		if (!normalizedInstalled) {
			return 'pack_reinstall';
		}

		if (normalizedInstalled === normalizedCatalog && normalizedCatalog !== '') {
			return 'pack_reinstall';
		}

		if (store.isComparableSemver(normalizedInstalled) && store.isComparableSemver(normalizedCatalog)) {
			return window.versionCompare(normalizedInstalled, normalizedCatalog) < 0 ? 'pack_update' : 'pack_reinstall';
		}

		return 'pack_reinstall';
	},
	normalizeComparableVersion: function(version, defaultBranch){
		version = $.trim(String(version || ''));
		defaultBranch = $.trim(String(defaultBranch || ''));
		if (!version) {
			return '';
		}

		if (version.indexOf('dev-') === 0) {
			version = version.substring(4);
		}

		if (/^v\d+\.\d+\.\d+(?:[-+][A-Za-z0-9.\-]+)?$/.test(version)) {
			version = version.substring(1);
		}

		if (defaultBranch && version === defaultBranch) {
			return defaultBranch;
		}

		return version;
	},
	isComparableSemver: function(version){
		return /^\d+\.\d+\.\d+(?:[-+][A-Za-z0-9.\-]+)?$/.test(String(version || ''));
	},
	sortList: function(data){
		var items = store.toArray(data);
		var mode = $('#store_sort').val() || 'default';

		if (mode === 'default') {
			return items;
		}

		items.sort(function(a, b){
			var titleA = store.normalizeTitle(a);
			var titleB = store.normalizeTitle(b);
			var downloadsA = store.normalizeDownloads(a);
			var downloadsB = store.normalizeDownloads(b);

			if (mode === 'title_asc') {
				return titleA.localeCompare(titleB);
			}
			if (mode === 'title_desc') {
				return titleB.localeCompare(titleA);
			}
			if (mode === 'downloads_asc') {
				if (downloadsA === downloadsB) {
					return titleA.localeCompare(titleB);
				}
				return downloadsA - downloadsB;
			}
			if (mode === 'downloads_desc') {
				if (downloadsA === downloadsB) {
					return titleA.localeCompare(titleB);
				}
				return downloadsB - downloadsA;
			}

			return 0;
		});

		return items;
	},
	toArray: function(data){
		if (!data) {
			return [];
		}
		if ($.isArray(data)) {
			return data.slice();
		}

		var items = [];
		$.each(data, function(key, value){
			items.push(value);
		});
		return items;
	},
	normalizeTitle: function(item){
		return String(item.title || item.name_in_modx || item.name || '').toLowerCase();
	},
	normalizeDownloads: function(item){
		var raw = String(item.downloads || '0').replace(/[^\d.-]/g, '');
		var value = parseInt(raw, 10);
		return isNaN(value) ? 0 : value;
	},
	isStableVersion: function(value){
		return /^v?\d+\.\d+\.\d+(?:[-+][A-Za-z0-9.-]+)?$/.test(String(value || '').trim());
	},
	escapeHtml: function(value){
		return String(value || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#39;');
	},
	is_array: function(inputArray) {
            return inputArray && !(inputArray.propertyIsEnumerable('length')) && typeof inputArray === 'object' && typeof inputArray.length === 'number';
        }
};

$(function(){
	store.init();
})

window.versionCompare = function(a, b) {
	var normalize = function(version) {
		return String(version || '').split(/[-+]/)[0].split('.').map(function(part) {
			var value = parseInt(part, 10);
			return isNaN(value) ? 0 : value;
		});
	};

	var left = normalize(a);
	var right = normalize(b);
	var length = Math.max(left.length, right.length);

	for (var index = 0; index < length; index++) {
		var leftPart = left[index] || 0;
		var rightPart = right[index] || 0;
		if (leftPart < rightPart) return -1;
		if (leftPart > rightPart) return 1;
	}

	return 0;
};
