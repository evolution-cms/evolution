(function($) {
    $.extend($.fn.datagrid.defaults.editors, {
        imageBrowser: {
            thumb_prefix: '',
            noImage: '',
            init: function (container, options) {
                var input = $('<input type="hidden">').appendTo(container);
                var image = $('<a href="javascript:void(0)"><img style="' + options.css + '" src=""></a>').appendTo(container);
                this.thumb_prefix = options.thumb_prefix;
                this.noImage = options.noImage;
                image.click({
                    target: this,
                    field: input,
                    browserUrl: options.browserUrl,
                    opener: options.opener
                }, this.browse);
                return input;
            },
            destroy: function (target) {
                $(target).remove();
            },
            getValue: function (target) {
                return $(target).val();
            },
            setValue: function (target, value) {
                $(target).val(value);
                $(target).parent().find('img').attr('src', (value == '' ? this.noImage : this.thumb_prefix + value));
            },
            resize: function (target, width) {
                return;
            },
            browse: function (e) {
                var width = screen.width * 0.5;
                var height = screen.height * 0.5;
                var iLeft = (screen.width - width) / 2;
                var iTop = (screen.height - height) / 2;
                var sOptions = 'toolbar=no,status=no,resizable=yes,dependent=yes';
                var url = e.data.browserUrl + '&opener=' + e.data.opener;
                sOptions += ',width=' + width;
                sOptions += ',height=' + height;
                sOptions += ',left=' + iLeft;
                sOptions += ',top=' + iTop;
                window.KCFinder = {};
                window.KCFinder = {
                    callBack: function (url) {
                        window.KCFinder = null;
                        e.data.target.setValue(e.data.field, url);
                    }
                };
                var oWindow = window.open(url, 'ImageBrowser', sOptions);
            }
        }
    });
})(jQuery);
