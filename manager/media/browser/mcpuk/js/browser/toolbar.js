/** This file is part of KCFinder project
  *
  *      @desc Toolbar functionality
  *   @package KCFinder
  *   @version 2.54
  *    @author Pavel Tzonkov <sunhater@sunhater.com>
  * @copyright 2010-2014 KCFinder Project
  *   @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
  *   @license http://www.opensource.org/licenses/lgpl-2.1.php LGPLv2
  *      @link http://kcfinder.sunhater.com
  */

browser.initToolbar = function() {
    $('#toolbar > div > a').click(function() {
        browser.hideDialog();
    });

    if (!_.kuki.isSet('displaySettings'))
        _.kuki.set('displaySettings', 'off');

    if (_.kuki.get('displaySettings') == 'on') {
        $('#toolbar a[href="kcact:settings"]').addClass('selected');
        $('#settings').css('display', 'block');
        browser.resize();
    }

    $('#toolbar a[href="kcact:settings"]').click(function () {
        if ($('#settings').css('display') == 'none') {
            $(this).addClass('selected');
            _.kuki.set('displaySettings', 'on');
            $('#settings').css('display', 'block');
            browser.fixFilesHeight();
        } else {
            $(this).removeClass('selected');
            _.kuki.set('displaySettings', 'off');
            $('#settings').css('display', 'none');
            browser.fixFilesHeight();
        }
        return false;
    });

    $('#toolbar a[href="kcact:refresh"]').click(function() {
        browser.refresh();
        return false;
    });

    var deleteButton = $('#toolbar a[href="kcact:delete"]');
    if (!this.access.files['delete'])
        deleteButton.hide();
    deleteButton.click(function() {
        if ($(this).hasClass('disabled'))
            return false;
        browser.deleteSelectedFiles();
        return false;
    });
    this.updateDeleteButton();

    if (window.opener || this.opener.TinyMCE || $('iframe', window.parent.document).get(0))
        $('#toolbar a[href="kcact:maximize"]').click(function() {
            browser.maximize(this);
            return false;
        });
    else
        $('#toolbar a[href="kcact:maximize"]').css('display', 'none');
};

browser.updateDeleteButton = function() {
    var files = $('.file.selected').filter(function() {
        return !$(this).data('isDir');
    }).map(function() {
        return $(this).data();
    }).get();
    var enabled = this.deleteButtonEnabled(this.access.files['delete'], files);
    var button = $('#toolbar a[href="kcact:delete"]');

    if (enabled)
        button.removeClass('disabled');
    else
        button.addClass('disabled');
    button.attr('aria-disabled', enabled ? 'false' : 'true');
};

browser.updateWriteControls = function() {
    var canUpload = this.canWriteCurrentDirectory(this.access.files.upload),
        uploadButton = $('#toolbar a[href="kcact:upload"]');

    uploadButton.toggleClass('disabled', !canUpload);
    uploadButton.attr('aria-disabled', canUpload ? 'false' : 'true');
    this.updateDeleteButton();
};

browser.isMobileActionMode = function() {
    return $(window).width() <= 780;
};

browser.clearMobileActions = function() {
    $('#mobileActions').empty().removeClass('active');
    if (this.fixFilesHeight)
        this.fixFilesHeight();
};

browser.updateMobileActions = function() {
    if (!this.isMobileActionMode()) {
        this.clearMobileActions();
        return;
    }

    $('#mobileActions').empty().removeClass('active');
    var selected = $('.file.selected').get();
    var selectedFolder = null;
    var selectedFiles = [];
    $.each(selected, function(i, item) {
        if ($(item).data('isDir'))
            selectedFolder = item;
        else
            selectedFiles.push(item);
    });

    if (selectedFolder) {
        this.menuFolder($(selectedFolder));
    } else if (selectedFiles.length) {
        var target = $.inArray(this.lastSelectedFile, selectedFiles) >= 0
            ? this.lastSelectedFile : selectedFiles[0];
        this.menuFile($(target));
    } else {
        var currentDir = $('#folders a').filter(function() {
            return $(this).data('path') === browser.dir;
        });
        if (currentDir.get(0))
            this.menuDir(currentDir);
    }

    this.fixFilesHeight();
};

browser.syncMobileActions = function() {
    var isMobile = this.isMobileActionMode();
    if (this.mobileActionMode === isMobile)
        return;

    this.mobileActionMode = isMobile;
    if (isMobile) {
        if (typeof this.files != 'undefined')
            this.updateMobileActions();
    } else {
        this.clearMobileActions();
    }
};

browser.maximize = function(button) {
    if (window.opener) {
        window.moveTo(0, 0);
        width = screen.availWidth;
        height = screen.availHeight;
        if ($.browser.opera)
            height -= 50;
        window.resizeTo(width, height);

    } else if (browser.opener.TinyMCE || browser.opener.TinyMCE4) {
        var win, ifr, id;
    
        if(browser.opener.TinyMCE) {
            $('iframe', window.parent.document).each(function() {
                if (/^mce_\d+_ifr$/.test($(this).attr('id'))) {
                    id = parseInt($(this).attr('id').replace(/^mce_(\d+)_ifr$/, "$1"));
                    win = $('#mce_' + id, window.parent.document);
                    ifr = $('#mce_' + id + '_ifr', window.parent.document);
                }
            });
        } else {
            $('.mce-window', window.parent.document).each(function() {
                if (/^mceu_\d/.test($(this).attr('id'))) {
                    id = parseInt($(this).attr('id').replace(/^mceu_(\d+)/, "$1"));
                    win = $('#mceu_' + id, window.parent.document);
                    ifr = $('#mceu_' + id + '-body', window.parent.document);
                }
            });
        }

        if ($(button).hasClass('selected')) {
            $(button).removeClass('selected');
            win.css({
                left: browser.maximizeMCE.left + 'px',
                top: browser.maximizeMCE.top + 'px',
                width: browser.maximizeMCE.width + 'px',
                height: browser.maximizeMCE.height + 'px'
            });
            ifr.css({
                width: browser.maximizeMCE.width - browser.maximizeMCE.Hspace + 'px',
                height: browser.maximizeMCE.height - browser.maximizeMCE.Vspace + 'px'
            });

        } else {
            $(button).addClass('selected')
            browser.maximizeMCE = {
                width: _.nopx(win.css('width')),
                height: _.nopx(win.css('height')),
                left: win.position().left,
                top: win.position().top,
                Hspace: _.nopx(win.css('width')) - _.nopx(ifr.css('width')),
                Vspace: _.nopx(win.css('height')) - _.nopx(ifr.css('height'))
            };
            var width = $(window.parent).width();
            var height = $(window.parent).height();
            win.css({
                left: $(window.parent).scrollLeft() + 'px',
                top: $(window.parent).scrollTop() + 'px',
                width: width + 'px',
                height: height + 'px'
            });
            ifr.css({
                width: width - browser.maximizeMCE.Hspace + 'px',
                height: height - browser.maximizeMCE.Vspace + 'px'
            });
        }
    } else if ($('iframe', window.parent.document).get(0)) {
        var ifrm = $('iframe[name="' + window.name + '"]', window.parent.document);
        var parent = ifrm.parent();
        var width, height;
        if ($(button).hasClass('selected')) {
            $(button).removeClass('selected');
            if (browser.maximizeThread) {
                clearInterval(browser.maximizeThread);
                browser.maximizeThread = null;
            }
            if (browser.maximizeW) browser.maximizeW = null;
            if (browser.maximizeH) browser.maximizeH = null;
            $.each($('*', window.parent.document).get(), function(i, e) {
                e.style.display = browser.maximizeDisplay[i];
            });
            ifrm.css({
                display: browser.maximizeCSS.display,
                position: browser.maximizeCSS.position,
                left: browser.maximizeCSS.left,
                top: browser.maximizeCSS.top,
                width: browser.maximizeCSS.width,
                height: browser.maximizeCSS.height
            });
            $(window.parent).scrollLeft(browser.maximizeLest);
            $(window.parent).scrollTop(browser.maximizeTop);

        } else {
            $(button).addClass('selected');
            browser.maximizeCSS = {
                display: ifrm.css('display'),
                position: ifrm.css('position'),
                left: ifrm.css('left'),
                top: ifrm.css('top'),
                width: ifrm.outerWidth() + 'px',
                height: ifrm.outerHeight() + 'px'
            };
            browser.maximizeTop = $(window.parent).scrollTop();
            browser.maximizeLeft = $(window.parent).scrollLeft();
            browser.maximizeDisplay = [];
            $.each($('*', window.parent.document).get(), function(i, e) {
                browser.maximizeDisplay[i] = $(e).css('display');
                $(e).css('display', 'none');
            });

            ifrm.css('display', 'block');
            ifrm.parents().css('display', 'block');
            var resize = function() {
                width = $(window.parent).width();
                height = $(window.parent).height();
                if (!browser.maximizeW || (browser.maximizeW != width) ||
                    !browser.maximizeH || (browser.maximizeH != height)
                ) {
                    browser.maximizeW = width;
                    browser.maximizeH = height;
                    ifrm.css({
                        width: width + 'px',
                        height: height + 'px'
                    });
                    browser.resize();
                }
            }
            ifrm.css('position', 'absolute');
            if ((ifrm.offset().left == ifrm.position().left) &&
                (ifrm.offset().top == ifrm.position().top)
            )
                ifrm.css({left: '0', top: '0'});
            else
                ifrm.css({
                    left: - ifrm.offset().left + 'px',
                    top: - ifrm.offset().top + 'px'
                });

            resize();
            browser.maximizeThread = setInterval(resize, 250);
        }
    }
};

browser.refresh = function(selected) {
    this.fadeFiles();
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: browser.baseGetData('chDir'),
        data: {dir:browser.dir},
        async: false,
        success: function(data) {
            if (browser.check4errors(data))
                return;
            browser.dirWritable = data.dirWritable;
            browser.updateWriteControls();
            browser.files = data.files ? data.files : [];
            browser.orderFiles(null, selected);
            browser.statusDir();
        },
        error: function() {
            $('#files > div').css({opacity:'', filter:''});
            $('#files').html(browser.label("Unknown error."));
        }
    });
};
