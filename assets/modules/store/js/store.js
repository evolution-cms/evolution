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
		var search_name = $(this).find('h3').html();
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
	update:function(){
		$.ajax({
			url:'http://'+location.hostname+'/assets/modules/store/update.php',
			cache:false,
			data:{just:'empty'},
			type:'get',
			success:function(data){
				window.location.reload();
			}
		})
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
			store.update_category(data.category);
			/*Show firdt category*/
			var id = $('.category_list').find('li').first().find('a').attr('data-id');
			store.allCategoryId = id;
			$('[name=parent]').val(id);
			store.buildInstalledCatalog();
			store.update_list( store.category[id] );
			store.loadConsoleCatalog();

			var version = $('.version').html();
			if (data.version != version && version != '0.2.0') {
					$('.new_version').html(data.version);
					$('#actions').show();
			}

			if (data.user) {
				store.showUserForms( data.user.result );
				store.updateUserCategory( data.user );
			}
		});

		store.types =  eval('('+$('[name="types"]').val()+')');
		store.installedState = store.parseInstalledState();

		$('a.item-install').live('click',function(){
			store.install(this);
			return false;
		});
		$('.item-more').live('click', function(){
			store.showItemMore(this);
			return false;
		});

		$('.item-install2').live('click',function(){
			tpl = '<li data-id="'+$(this).attr('data-id')+'">'+$(this).parent().find('.row-category').text()+'<a href="#">X</a></li>';
			$('.cart_list ul').append(tpl);
			return false;
		});

		$('.category_list a').live('click',function(){
			if ($(this).attr('data-source') === 'console') {
				store.update_list(store.consoleCatalog, 'list');
				return false;
			}
			if ($(this).attr('data-source') === 'installed') {
				store.update_list(store.installedCatalog, 'list');
				return false;
			}
			$('[name=parent]').val($(this).attr('data-id'));
			//store.get_list({}, store.update_list );

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
			store.showConsoleInstallHelp(elm);
			return false;
		}

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
			$.fancybox.open({href : install_url, type: 'iframe'});
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
                    }
					el.closest('.catalog_item').addClass('is-installed');

					$('.item_list .catalog_item').removeClass('blocked');
				}

			})

		}

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
		store.renderInstalledCategory(store.installedCatalog.length);
	},
	loadConsoleCatalog: function(){
		$.ajax({
			url: link() + '&action=console_catalog',
			cache: false,
			dataType: 'json',
			type: 'get',
			success: function(data){
				if (!data || !data.ok || !$.isArray(data.items) || data.items.length === 0) {
					return;
				}
				store.consoleCatalog = data.items;
				store.mergeConsoleIntoAll();
				store.buildInstalledCatalog();
				store.renderConsoleCategory(data.items.length);
				store.renderInstalledCategory(store.installedCatalog.length);
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

		if ($('[name=parent]').val() == store.allCategoryId) {
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
			? ($('[name="source_label_console"]').val() || 'Console')
			: ($('[name="source_label_legacy"]').val() || 'Legacy'));
		array.install_target = array.install_target || array.title || array.name || '';
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

		if ((array.source_kind || '') === 'console') {
			$out.find('.item-more').html('<i class="fa fa-book"></i> readme');
		}

		if (array.is_installed) {
			$out.find('.item-install')
				.text($('[name="reinstall_label"]').val() || 'Reinstall')
				.removeClass('btn-success')
				.addClass('btn-primary');
		}

		return $out.html();
	},
	showConsoleInstallHelp: function(elm){
		var name = $(elm).attr('data-name') || '';
		var packageName = $(elm).attr('data-package') || name;
		var selectedVersion = $(elm).closest('.catalog_item').find('[name="link"]').val() || '';
		var command = $(elm).attr('data-command') || '';
		var sourceUrl = $(elm).attr('data-source-url') || $(elm).attr('data-url') || '';
		var corePath = $('[name="console_core_path"]').val() || '';
		var title = $('[name="console_install_title"]').val() || 'Install via console';
		var openCoreLabel = $('[name="console_install_step_open_core"]').val() || '';
		var runArtisanLabel = $('[name="console_install_step_run_artisan"]').val() || '';
		var sourceLabel = $('[name="console_install_source_label"]').val() || 'Source';
		var copyLabel = $('[name="popup_copy_command"]').val() || 'Copy command';
		if (packageName !== '') {
			command = 'php artisan extras extras "' + packageName + (selectedVersion ? '@' + selectedVersion : '') + '"';
		}

		var html = ''
			+ '<div class="store-popup-shell store-popup-shell-install store-popup-shell-console ' + store.getPopupThemeClass() + '">'
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
			+ '</div>';

		if (sourceUrl !== '') {
			html += '<p class="store-popup-source"><strong>' + store.escapeHtml(sourceLabel) + ':</strong> <a href="' + store.escapeHtml(sourceUrl) + '" target="_blank" rel="noopener">' + store.escapeHtml(sourceUrl) + '</a></p>';
		}

		html += '</div>';

		store.openPopup(title + ': ' + name, html, 'wide', function(){
			store.bindPopupCopyButtons();
		});
	},
	showItemMore: function(elm){
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
	applyInstalledStateToItem: function(item){
		var array = $.extend(true, {}, item || {});
		if (!array.cls) {
			array.cls = 'pack_install';
		}
		array.state_class = '';
		array.title_state_html = '';
		array.install_state_html = '';
		array.catalog_version = array.catalog_version || array.version || '';
		array.installed_state = parseInt(array.installed_state || 0, 10) ? 1 : 0;
		array.current_version = array.current_version || '';
		array.is_installed = parseInt(array.is_installed || 0, 10) ? 1 : 0;

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
		var legacyMap = (store.installedState && store.installedState.legacy_by_type) || store.types || {};
		var itemName = array.name_in_modx || array.title || array.name || '';
		var installedVersion = '';

		if (normalizedType && legacyMap[normalizedType] && legacyMap[normalizedType][itemName]) {
			installedVersion = legacyMap[normalizedType][itemName];
		}

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
		var target = String(name || '');
		if (!target) {
			return '';
		}

		var items = (store.installedState && store.installedState.legacy_items) || [];
		var foundVersion = '';
		$.each(items, function(index, item){
			if ((item.name || '') === target) {
				foundVersion = item.version || '';
				return false;
			}
		});
		return foundVersion;
	},
	hasLegacyInstalledName: function(name){
		var target = String(name || '');
		if (!target) {
			return false;
		}

		var items = (store.installedState && store.installedState.legacy_items) || [];
		var found = false;
		$.each(items, function(index, item){
			if ((item.name || '') === target) {
				found = true;
				return false;
			}
		});
		return found;
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
