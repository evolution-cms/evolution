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
		store.query('start',{'user':'1'},function(data){
			store.category = data.allcategory;
			store.catalog = data.category;
			store.update_category(data.category);
			/*Show firdt category*/
			var id = $('.category_list').find('li').first().find('a').attr('data-id');
			$('[name=parent]').val(id);
			store.update_list( store.category[id] );
			
			var version = $('.version').html();
			if (data.version != version && version != '0.1.3') {
					$('.new_version').html(data.version);
					$('#actions').show();
			}
			
			if (data.user) {
				store.showUserForms( data.user.result );
				store.updateUserCategory( data.user );
			}
		});
		
		store.types =  eval('('+$('[name="types"]').val()+')');

		$('a.item-reinstall,a.item-update').live('click',function(){
			if (confirm($(this).attr('data-text'))) store.install(this);
			return false;
		});
		$('a.item-install').live('click',function(){
			store.install(this);
			return false;
		});
		
		$('.item-install2').live('click',function(){
			tpl = '<li data-id="'+$(this).attr('data-id')+'">'+$(this).parent().find('.row-category').text()+'<a href="#">X</a></li>';
			$('.cart_list ul').append(tpl);
			return false;
		});
		
		$('.category_list a').live('click',function(){
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
				
		$('.item_header :input').change(function(){
			store.get_list({}, store.update_list );
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
	install:function(elm){
			
		var el = $(elm).closest('.catalog_item').find('.informer');
		var file = $(elm).closest('.catalog_item').find('[name="link"]').val();
		store.query('download',{id:$(elm).attr('data-id')},function(data){
			//el.find('.download').html( parseInt(el.find('.download').html())+1 );
		});
		
		if ($(elm).attr('data-method') == "package"){
			var install_url = link() + "&action=install&cid="+$(elm).attr('data-id')+"&name="+$(elm).attr('data-name')+"&file="+file;
			$.fancybox.open({href : install_url, type: 'iframe'});
		} else {
			$('.item_list .catalog_item').addClass('blocked');
			$(elm).closest('.catalog_item').find('.loader').show();
			$.ajax({
				url:link()+"&method=fast&action=install&cid="+$(elm).attr('data-id')+"&name="+$(elm).attr('data-name'),
				type:'POST',
				data:{method:'fast',file:file},
				success:function(){
					el.closest('.catalog_item').find('.loader').hide();
					el.css('display','block').animate({opacity:1},500,function(){
						el.delay(2000).animate({opacity:0},3000,function(){el.css('display','none')});
					});
					
					el.closest('.catalog_item').removeClass('item-install').removeClass('item-update').addClass('item-reinstall');
					el.closest('.catalog_item').find('.curr').hide();
					
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
	},
	update_list: function(data,tpl){
		tpl = tpl || 'list';
		$('.item_list').html( store.parse_list( data , $('.tpl #tpl_'+tpl).html() ,tpl) );
	},
	updateUserPack: function(data){
		$('.item_list').html( store.parse_list( data , $('.tpl #tpl_list').html() ) + '<div class="loader"></div>' );
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
		array.cls = 'pack_install';
		array.zip = array.url == ''?'zip':'github';

		array.version = array.version || '';
		array.date = array.date || '';

		if ($.isPlainObject(array.url)){
			options =[];
			
			versions = array.url.fieldValue;
			$.each(versions,function(key,value){
				options.push('<option value="'+value.file+'">'+value.version+'</option>');
				if (!array.version) array.version = value.version;
				if (!array.date) array.date = value.date;
				$str = $(str);
				$str.find('[name=link]').append( options.join(''));
				
			});
			$str.find('option').first().prop('selected',true);
			if (versions.length == 1){
				$str.find('[name=link]').hide();
			}
			
			str = $str.wrapAll('<div></div>').parent().html();
			
		}
			
		if ( array.type ) {
			array.type = array.type == 'snippet'?'snippets':array.type;
			array.type = array.type == 'module'?'modules':array.type;
			array.type = array.type == 'plugin'?'plugins':array.type;
			
			if ( store.types[ array.type ]) {
				if ( store.types[ array.type ][ array.name_in_modx ]) {
					array.current_version = store.types[ array.type ][ array.name_in_modx ];
					if ( store.types[ array.type ][ array.name_in_modx ] <  array.version){
						array.cls = 'pack_update';
					}
					if ( store.types[ array.type ][ array.name_in_modx ] ==  array.version){
						array.cls = 'pack_reinstall';
					} 
				}
			}
		}
		
		out = str.replace(/%\w+%/g, function(placeholder) {
			return array[ placeholder.split('%').join('') ] || '';
		});
		img = array.image;
		if (tpl =='cart') img = array.cartimage;
		if (array.image) out = $('<div id="tmpl">'+out+'</div>').find('img').attr('src', img).closest('#tmpl').html(); 
		return out;
	},
	parse: function(str,array){
		var out = str.replace(/%\w+%/g, function(placeholder) {
			return array[ placeholder.split('%').join('') ] || '';
		});
		if (array.image) out = $('<div id="tmpl">'+out+'</div>').find('img').attr('src',array.image).closest('#tmpl').html(); 
		return out;
	},
	is_array: function(inputArray) {
            return inputArray && !(inputArray.propertyIsEnumerable('length')) && typeof inputArray === 'object' && typeof inputArray.length === 'number';
        }
};

$(function(){
	store.init();
})