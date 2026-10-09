/** This file is part of KCFinder project
  *
  *      @desc File related functionality
  *   @package KCFinder
  *   @version 2.54
  *    @author Pavel Tzonkov <sunhater@sunhater.com>
  * @copyright 2010-2014 KCFinder Project
  *   @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
  *   @license http://www.opensource.org/licenses/lgpl-2.1.php LGPLv2
  *      @link http://kcfinder.sunhater.com
  */

browser.initFiles = function() {
    this.initKeyboard();
    $('#files').unbind();
    $('#files').scroll(function() {
        browser.hideDialog();
    });
    $('#files').click(function(e) {
        if (!$(e.target).closest('.file').length)
            browser.clearSelection();
    });
    $('.file').unbind();
    $('.file').click(function(e) {
        browser.handleFileClick($(this), e);
    });
    $('.file').rightClick(function(e) {
        _.unselect();
        if ($(this).data('isDir'))
            browser.menuFolder($(this), e);
        else
            browser.menuFile($(this), e);
    });
    $('.file').dblclick(function() {
        browser.handleFileDoubleClick($(this));
    });
    $('.selectThis').click(function() {
        _.unselect();
        browser.returnFile($(this).parent('.file'));
    });
    $('.dirSize').click(function() {
        var link = $(this);
        if (link.data('loading') || link.data('loaded'))
            return false;

        var folder = link.closest('.file');
        var dir = browser.dir ? browser.dir + '/' : '';
        dir += folder.data('name');
        link.data('loading', true).addClass('loading');
        link.text('...');
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: browser.baseGetData('dirSize'),
            data: {dir:dir},
            success: function(data) {
                link.removeData('loading').removeClass('loading');
                if (browser.check4errors(data)) {
                    link.text('Size');
                    return;
                }
                link.data('loaded', true).replaceWith(browser.humanSize(parseInt(data.size, 10) || 0));
            },
            error: function() {
                link.removeData('loading').removeClass('loading');
                link.text('Size');
                browser.alert(browser.label("Unknown error."));
            }
        });
        return false;
    });
    $('.file').mouseup(function() {
        _.unselect();
    });
    $('.file').mouseout(function() {
        _.unselect();
    });
    $.each(this.shows, function(i, val) {
        var display = (_.kuki.get('show' + val) == 'off')
            ? 'none' : 'block';
        $('#files .file div.' + val).css('display', display);
    });
    this.statusDir();
    this.updateDeleteButton();
    lazyLoadInstance.update();
    this.updateMobileActions();
};

browser.handleFileClick = function(file, e) {
    _.unselect();
    if (file.data('isDir'))
        this.selectFolder(file);
    else
        this.selectFile(file, e);
};

browser.handleFileDoubleClick = function(file) {
    _.unselect();
    if (file.data('isDir'))
        return this.openDir(file);
    return this.returnFile(file);
};

browser.selectFolder = function(file) {
    $('.file').removeClass('selected');
    file.addClass('selected');
    this.statusDir();
    this.updateDeleteButton();
    this.updateMobileActions();
};

browser.initKeyboard = function() {
    $(document).unbind('keydown');
    $(document).keydown(function(e) {
        return !browser.handleFileKeydown(e);
    });
};

browser.handleFileKeydown = function(e) {
    var action = this.keyboardAction(e);
    if (action == 'clearSelection') {
        if ($('#dialog').is(':visible'))
            this.hideDialog();
        else if (!this.isEditableTarget(e.target))
            this.clearSelection();
        else
            return false;
        return true;
    }

    if (this.isEditableTarget(e.target))
        return false;

    if (action == 'selectAll') {
        this.selectAll(e);
        return true;
    }
    if (action == 'delete') {
        if (e.repeat)
            return $('.file.selected').filter(function() {
                return !$(this).data('isDir');
            }).length > 0;
        return this.deleteSelectedFiles();
    }

    return false;
};

browser.keyboardAction = function(e) {
    var key = e.key || e.which || e.keyCode;
    var hasCommand = e.ctrlKey || e.metaKey;

    if (hasCommand && (key == 'a' || key == 'A' || key == 65 || key == 97))
        return 'selectAll';
    if (key == 'Delete' || key == 46)
        return 'delete';
    if (hasCommand && (key == 'Backspace' || key == 8))
        return 'delete';
    if (key == 'Escape' || key == 27)
        return 'clearSelection';

    return null;
};

browser.isEditableTarget = function(target) {
    if (!target)
        return false;
    if (target.isContentEditable)
        return true;

    return /^(INPUT|TEXTAREA|SELECT)$/.test((target.tagName || '').toUpperCase());
};

browser.selectionMode = function(e) {
    if (e.shiftKey)
        return 'range';
    if (e.ctrlKey || e.metaKey)
        return 'toggle';
    return 'replace';
};

browser.selectionRange = function(start, end) {
    var range = [];
    var first = Math.min(start, end);
    var last = Math.max(start, end);
    for (var i = first; i <= last; i++)
        range.push(i);
    return range;
};

browser.canWriteCurrentDirectory = function(accessAllowed) {
    return !!accessAllowed && !!this.dirWritable;
};

browser.canModifyFile = function(fileWritable, accessAllowed) {
    return this.canWriteCurrentDirectory(accessAllowed) && !!fileWritable;
};

browser.deleteButtonEnabled = function(canDelete, files) {
    if (!this.canWriteCurrentDirectory(canDelete))
        return false;

    for (var i = 0; i < files.length; i++) {
        if (!files[i].isDir && files[i].writable)
            return true;
    }

    return false;
};

browser.clearSelection = function() {
    $('.file').removeClass('selected');
    this.lastSelectedFile = null;
    this.statusDir();
    this.updateDeleteButton();
    this.updateMobileActions();
};

browser.updateSelectionStatus = function() {
    var files = $('.file.selected').filter(function() {
        return !$(this).data('isDir');
    }).get();
    var size = 0;

    if (!files.length) {
        this.statusDir();
        this.updateDeleteButton();
        this.updateMobileActions();
        return;
    }

    $.each(files, function(i, file) {
        size += parseInt($(file).data('size'), 10) || 0;
    });

    if (files.length > 1)
        $('#fileinfo').text(files.length + ' ' + this.label("selected files") + ' (' + this.humanSize(size) + ')');
    else {
        var data = $(files[0]).data();
        $('#fileinfo').text(data.name + ' (' + this.humanSize(data.size) + ', ' + data.date + ')');
    }
    this.updateDeleteButton();
    this.updateMobileActions();
};

browser.applyThumbSize = function(size) {
    size = parseInt(size, 10);
    if (!isFinite(size) || size < 1)
        return false;

    var px = size + 'px';
    $('div.thumb').css({width: px, height: px});
    $('div.thumb.folder-icon').css('font-size', Math.round(size * 0.8) + 'px');
    $('div.thumb img').css({width: px, height: px});
    $('div.file').css('width', px);
    $('.thumbsize').text(px);

    return true;
};

browser.applyTextSize = function(size) {
    size = parseInt(size, 10);
    if (!isFinite(size) || size < 1)
        return false;

    $('tr.file td').css('font-size', size + 'px');
    $('.textsize').text(size + 'px');

    return true;
};

browser.showFiles = function(callBack, selected) {
    var showSelectButton = this.canReturnFile();
    this.fadeFiles();
    setTimeout(function() {
        var html = '';
        $.each(browser.files, function(i, file) {
            var stamp = [];
            $.each(file, function(key, val) {
                stamp[stamp.length] = key + "|" + val;
            });
            stamp = _.md5(stamp.join('|'));
            if (_.kuki.get('view') == 'list') {
                if (!i) html += '<table summary="list">';
                var icon = file.isDir ? 'folder' : _.getFileExtension(file.name);
                if (!file.isDir) {
                    if (file.thumb)
                        icon = '.image';
                    else if (!icon.length || !file.smallIcon)
                        icon = '.';
                }
                icon = 'themes/' + browser.theme + '/img/files/small/' + icon + '.png';
                html += '<tr class="file">' +
                    '<td class="name" style="background-image:url(' + icon + ')">' + _.htmlData(file.name) + '</td>' +
                    '<td class="time">' + file.date + '</td>' +
                    '<td class="size">' + browser.directorySizeDisplay(file) + '</td>' +
                '</tr>';
                if (i == browser.files.length - 1) html += '</table>';
            } else {
                if (!file.isDir && file.thumb)
                    var icon = browser.baseGetData('thumb') + '&file=' + encodeURIComponent(file.name) + '&dir=' + encodeURIComponent(browser.dir) + '&stamp=' + stamp;
                else if (!file.isDir && (file.smallThumb || _.getFileExtension(file.name) === 'svg')) {
                    var icon = browser.siteURL + browser.assetsURL + '/' + browser.dir + '/' + file.name;
                    icon = _.escapeDirs(icon).replace(/\'/g, "%27");
                } else if (!file.isDir) {
                    var icon = file.bigIcon ? _.getFileExtension(file.name) : '.';
                    if (!icon.length) icon = '.';
                    icon = 'themes/' + browser.theme + '/img/files/big/' + icon + '.png';
                }
                var thumbClass = file.isDir ? 'thumb folder-icon' : 'lazy thumb ' + (file.thumb ? '' : 'skipthumb');
                var thumbData = file.isDir ? '' : ' data-src="' + icon + '"';
                var fileClass = file.isDir ? 'file folder' : 'file';
                var selectControl = (!file.isDir && showSelectButton)
                    ? '<div class="selectThis">+</div>' : '';
                html += '<div class="' + fileClass + '">' +
                    '<div class="' + thumbClass + '"' + thumbData + '></div>' +
                    '<div class="name">' + _.htmlData(file.name) + '</div>' +
                    '<div class="time">' + file.date + '</div>' +
                    '<div class="size">' + browser.directorySizeDisplay(file) + '</div>' +
                    selectControl +
                '</div>';
            }
        });
        $('#files').html('<div>' + html + '<div>');
        $.each(browser.files, function(i, file) {
            var item = $('#files .file').get(i);
            $(item).data(file);
            if (_.inArray(file.name, selected) ||
                ((typeof selected != 'undefined') && !selected.push && (file.name == selected))
            )
                $(item).addClass('selected');
        });
        browser.applyThumbSize($('#rangeThumb').val());
        browser.applyTextSize($('#rangeText').val());
        $('#files > div').css({opacity:'', filter:''});
        if (callBack) callBack();
        browser.initFiles();
    }, 200);
};

browser.directorySizeDisplay = function(file) {
    return file.isDir
        ? '<a href="#" class="dirSize">Size</a>'
        : browser.humanSize(file.size);
};

browser.selectFile = function(file, e) {
    e = e || {};
    var mode = this.selectionMode(e);

    if (mode == 'range') {
        var allFiles = $('.file').get();
        var targetIndex = $.inArray(file.get(0), allFiles);
        var anchorIndex = this.selectionAnchorIndex(file.get(0), allFiles, targetIndex);

        if (!e.ctrlKey && !e.metaKey)
            $('.file').removeClass('selected');

        $.each(this.selectionRange(anchorIndex, targetIndex), function(i, index) {
            var candidate = $(allFiles[index]);
            if (!candidate.data('isDir'))
                candidate.addClass('selected');
        });
    } else if (mode == 'toggle') {
        if (file.hasClass('selected'))
            file.removeClass('selected');
        else
            file.addClass('selected');
    } else {
        var data = file.data();
        $('.file').removeClass('selected');
        file.addClass('selected');
    }

    if (mode != 'range')
        this.lastSelectedFile = file.get(0);
    this.updateSelectionStatus();
};

browser.selectionAnchorIndex = function(file, files, targetIndex) {
    var anchorIndex = $.inArray(this.lastSelectedFile, files);
    if (anchorIndex < 0) {
        this.lastSelectedFile = file;
        return targetIndex;
    }
    return anchorIndex;
};

browser.openDir = function(file) {
    var name = file.data('name');
    var path = browser.dir.length ? browser.dir + '/' + name : name;
    var dir = $('#folders a[href="kcdir:/' + _.escapeDirs(path) + '"]');

    if (!dir.get(0))
        return false;

    browser.changeDir(dir);
    return true;
};

browser.menuFolder = function(file, e) {
    var name = file.data('name');
    var path = browser.dir.length ? browser.dir + '/' + name : name;
    var dir = $('#folders a[href="kcdir:/' + _.escapeDirs(path) + '"]');

    if (!dir.get(0))
        return browser.openDir(file);

    $('.file').removeClass('selected');
    file.addClass('selected');
    this.updateDeleteButton();
    $('#fileinfo').text(name);
    browser.menuDir(dir, e);
    return true;
};

browser.selectAll = function(e) {
    if (this.keyboardAction(e) != 'selectAll')
        return false;
    var files = $('.file').get();
    var firstFile = null;
    $('.file').removeClass('selected');
    $.each(files, function(i, file) {
        var item = $(file);
        if (!item.data('isDir')) {
            item.addClass('selected');
            if (!firstFile)
                firstFile = file;
        }
    });
    this.lastSelectedFile = firstFile;
    this.updateSelectionStatus();
    return true;
};

browser.deleteSelectedFiles = function() {
    if (!this.canWriteCurrentDirectory(this.access.files['delete']))
        return false;

    var files = $('.file.selected').filter(function() {
        return !$(this).data('isDir');
    }).get();
    if (!files.length)
        return false;

    this.deleteFiles(files);
    return true;
};

browser.deleteFiles = function(files) {
    if (!this.canWriteCurrentDirectory(this.access.files['delete']))
        return false;

    var failed = 0;
    var dfiles = [];
    $.each(files, function(i, file) {
        var data = $(file).data();
        if (data.isDir)
            return;
        if (!browser.canModifyFile(data.writable, browser.access.files['delete']))
            failed++;
        else
            dfiles[dfiles.length] = browser.dir + "/" + data.name;
    });
    if (!dfiles.length) {
        this.alert(this.label("The selected files are not removable."));
        return true;
    }

    var go = function(callBack) {
        browser.fadeFiles();
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: browser.baseGetData('rm_cbd'),
            data: {files:dfiles},
            async: false,
            success: function(data) {
                if (callBack) callBack();
                browser.check4errors(data);
                browser.refresh();
            },
            error: function() {
                if (callBack) callBack();
                $('#files > div').css({
                    opacity: '',
                    filter: ''
                });
                browser.alert(browser.label("Unknown error."));
            }
        });
    };

    if (failed)
        this.confirm(
            this.label("{count} selected files are not removable. Do you want to delete the rest?", {count:failed}),
            go
        );
    else if (dfiles.length == 1)
        this.confirm(this.label("Are you sure you want to delete this file?"), go);
    else
        this.confirm(this.label("Are you sure you want to delete all selected files?"), go);

    return true;
};

browser.returnFile = function(file) {
    var fileURL = file.substr
        ? file : browser.assetsURL + '/' + browser.dir + '/' + file.data('name');
    fileURL = _.escapeDirs(fileURL);
    var win = window.opener ? window.opener : window.parent;
    var hasTinyMCE = false;

    try {
        hasTinyMCE = !!(win && win.tinymce && win.tinymce.activeEditor);
    } catch (e) {
        hasTinyMCE = false;
    }

    if (this.opener.TinyMCE4) {
        win.tinymceCallBackURL = fileURL;
        $(win.document).find('#' + this.opener.TinyMCE4).val(fileURL);
        win.tinyMCE.activeEditor.windowManager.close();

    } else if (this.opener.CKEditor) {
        this.opener.CKEditor.object.tools.callFunction(this.opener.CKEditor.funcNum, fileURL, '');
        window.close();

    } else if (this.opener.FCKeditor) {
        window.opener.SetUrl(fileURL) ;
        window.close() ;

    } else if (this.opener.TinyMCE && (typeof tinyMCEPopup !== 'undefined')) {
        var win = tinyMCEPopup.getWindowArg('window');
        win.document.getElementById(tinyMCEPopup.getWindowArg('input')).value = fileURL;
        if (win.getImageData) win.getImageData();
        if (typeof(win.ImageDialog) != "undefined") {
            if (win.ImageDialog.getImageData)
                win.ImageDialog.getImageData();
            if (win.ImageDialog.showPreviewImage)
                win.ImageDialog.showPreviewImage(fileURL);
        }
        tinyMCEPopup.close();

    } else if (this.opener.callBack) {

        if (window.opener && window.opener.KCFinder) {
            this.opener.callBack(fileURL);
            window.close();
        }

        if (window.parent && window.parent.KCFinder) {
            var button = $('#toolbar a[href="kcact:maximize"]');
            if (button.hasClass('selected'))
                this.maximize(button);
            this.opener.callBack(fileURL);
        }

    } else if (this.opener.callBackMultiple) {
        if (window.opener && window.opener.KCFinder) {
            this.opener.callBackMultiple([fileURL]);
            window.close();
        }

        if (window.parent && window.parent.KCFinder) {
            var button = $('#toolbar a[href="kcact:maximize"]');
            if (button.hasClass('selected'))
                this.maximize(button);
            this.opener.callBackMultiple([fileURL]);
        }

    } else if (hasTinyMCE) {
        win.tinymceCallBackURL = fileURL;
        try {
            if (win.tinymce.activeEditor.windowManager &&
                win.tinymce.activeEditor.windowManager.close
            )
                win.tinymce.activeEditor.windowManager.close();
            else if (win.console && win.console.warn)
                win.console.warn("TinyMCE windowManager.close is unavailable.");
        } catch (err) {
            if (win.console && win.console.warn)
                win.console.warn("TinyMCE windowManager.close failed.", err);
        }

    }
};

browser.canReturnFile = function() {
    if (this.opener.TinyMCE4 || this.opener.CKEditor || this.opener.FCKeditor ||
        this.opener.TinyMCE || this.opener.callBack || this.opener.callBackMultiple
    )
        return true;

    try {
        var win = window.opener ? window.opener : window.parent;
        return !!(win && win.tinymce && win.tinymce.activeEditor);
    } catch (e) {
        return false;
    }
};

browser.returnFiles = function(files) {
    if (this.opener.callBackMultiple && files.length) {
        var rfiles = [];
        $.each(files, function(i, file) {
            if ($(file).data('isDir'))
                return;
            rfiles[i] = browser.assetsURL + '/' + browser.dir + '/' + $(file).data('name');
            rfiles[i] = _.escapeDirs(rfiles[i]);
        });
        this.opener.callBackMultiple(rfiles);
        if (window.opener) window.close()
    }
};

browser.returnThumbnails = function(files) {
    if (this.opener.callBackMultiple) {
        var rfiles = [];
        var j = 0;
        $.each(files, function(i, file) {
            if (!$(file).data('isDir') && $(file).data('thumb')) {
                rfiles[j] = browser.thumbsURL + '/' + browser.dir + '/' + $(file).data('name');
                rfiles[j] = _.escapeDirs(rfiles[j++]);
            }
        });
        this.opener.callBackMultiple(rfiles);
        if (window.opener) window.close()
    }
};

browser.menuFile = function(file, e) {
    var data = file.data();
    if (data.isDir) {
        browser.openDir(file);
        return;
    }
    var path = this.dir + '/' + data.name;
    var files = $('.file.selected').get();
    var html = '';

    if (file.hasClass('selected') && files.length && (files.length > 1)) {
        var thumb = false;
        var notWritable = 0;
        var cdata;
        $.each(files, function(i, cfile) {
            cdata = $(cfile).data();
            if (cdata.thumb) thumb = true;
            if (!browser.canModifyFile(cdata.writable, browser.access.files['delete'])) notWritable++;
        });
        if (this.opener.callBackMultiple) {
            html += '<a href="kcact:pick">' + this.label("Select") + '</a>';
            if (thumb) html +=
                '<a href="kcact:pick_thumb">' + this.label("Select Thumbnails") + '</a>';
        }
        if (data.thumb || data.smallThumb || this.support.zip) {
            html += (html.length ? '<div class="delimiter"></div>' : '');
            if (data.thumb || data.smallThumb || data.preview)
                html +='<a href="kcact:view">' + this.label("View") + '</a>';
            if (this.support.zip) html += (html.length ? '<div class="delimiter"></div>' : '') +
                '<a href="kcact:download">' + this.label("Download") + '</a>';
        }

        if (this.access.files.copy || this.access.files.move)
            html += (html.length ? '<div class="delimiter"></div>' : '') +
                '<a href="kcact:clpbrdadd">' + this.label("Add to Clipboard") + '</a>';
        if (this.access.files['delete'] && !this.isMobileActionMode())
            html += (html.length ? '<div class="delimiter"></div>' : '') +
                '<a href="kcact:rm"' + ((notWritable == files.length) ? ' class="denied"' : '') +
                '>' + this.label("Delete") + '</a>';

        if (html.length) {
            html = '<div class="menu">' + html + '</div>';
            $('#dialog').html(html);
            this.showMenu(e);
        } else
            return;

        $('.menu a[href="kcact:pick"]').click(function() {
            browser.returnFiles(files);
            browser.hideDialog();
            return false;
        });

        $('.menu a[href="kcact:pick_thumb"]').click(function() {
            browser.returnThumbnails(files);
            browser.hideDialog();
            return false;
        });

        $('.menu a[href="kcact:download"]').click(function() {
            browser.hideDialog();
            var pfiles = [];
            $.each(files, function(i, cfile) {
                pfiles[i] = $(cfile).data('name');
            });
            browser.post(browser.baseGetData('downloadSelected'), {dir:browser.dir, files:pfiles});
            return false;
        });

        $('.menu a[href="kcact:clpbrdadd"]').click(function() {
            browser.hideDialog();
            var msg = '';
            $.each(files, function(i, cfile) {
                var cdata = $(cfile).data();
                var failed = false;
                for (i = 0; i < browser.clipboard.length; i++)
                    if ((browser.clipboard[i].name == cdata.name) &&
                        (browser.clipboard[i].dir == browser.dir)
                    ) {
                        failed = true
                        msg += cdata.name + ": " + browser.label("This file is already added to the Clipboard.") + "\n";
                        break;
                    }

                if (!failed) {
                    cdata.dir = browser.dir;
                    browser.clipboard[browser.clipboard.length] = cdata;
                }
            });
            browser.initClipboard();
            if (msg.length) browser.alert(msg.substr(0, msg.length - 1));
            return false;
        });

        $('.menu a[href="kcact:rm"]').click(function() {
            if ($(this).hasClass('denied')) return false;
            browser.hideDialog();
            browser.deleteFiles(files);
            return false;
        });

    } else {
        html += '<div class="menu">';
        $('.file').removeClass('selected');
        file.addClass('selected');
        $('#fileinfo').text(data.name + ' (' + this.humanSize(data.size) + ', ' + data.date + ')');
        if (this.opener.callBack || this.opener.callBackMultiple) {
            html += '<a href="kcact:pick">' + this.label("Select") + '</a>';
            if (data.thumb) html +=
                '<a href="kcact:pick_thumb">' + this.label("Select Thumbnail") + '</a>';
            html += '<div class="delimiter"></div>';
        }

        if (data.thumb || data.smallThumb || data.preview)
            html +='<a href="kcact:view">' + this.label("View") + '</a>';

        html +=
            '<a href="kcact:download">' + this.label("Download") + '</a>';

        if (this.access.files.copy || this.access.files.move)
            html += '<div class="delimiter"></div>' +
                '<a href="kcact:clpbrdadd">' + this.label("Add to Clipboard") + '</a>';
        if (this.access.files.rename || this.access.files['delete'])
            html += '<div class="delimiter"></div>';
        if (this.access.files.rename)
            html += '<a href="kcact:mv"' + (!this.canModifyFile(data.writable, this.access.files.rename) ? ' class="denied"' : '') + '>' +
                this.label("Rename...") + '</a>';
        if (this.access.files['delete'] && !this.isMobileActionMode())
            html += '<a href="kcact:rm"' + (!this.canModifyFile(data.writable, this.access.files['delete']) ? ' class="denied"' : '') + '>' +
                this.label("Delete") + '</a>';
        html += '</div>';

        $('#dialog').html(html);
        this.showMenu(e);

        $('.menu a[href="kcact:pick"]').click(function() {
            browser.returnFile(file);
            browser.hideDialog();
            return false;
        });

        $('.menu a[href="kcact:pick_thumb"]').click(function() {
            var path = browser.thumbsURL + '/' + browser.dir + '/' + data.name;
            browser.returnFile(path);
            browser.hideDialog();
            return false;
        });

        $('.menu a[href="kcact:download"]').click(function() {
            var html = '<form id="downloadForm" method="post" action="' + browser.baseGetData('download') + '">' +
                '<input type="hidden" name="dir" />' +
                '<input type="hidden" name="file" />' +
            '</form>';
            $('#dialog').html(html);
            $('#downloadForm input').get(0).value = browser.dir;
            $('#downloadForm input').get(1).value = data.name;
            $('#downloadForm').submit();
            return false;
        });

        $('.menu a[href="kcact:clpbrdadd"]').click(function() {
            for (i = 0; i < browser.clipboard.length; i++)
                if ((browser.clipboard[i].name == data.name) &&
                    (browser.clipboard[i].dir == browser.dir)
                ) {
                    browser.hideDialog();
                    browser.alert(browser.label("This file is already added to the Clipboard."));
                    return false;
                }
            var cdata = data;
            cdata.dir = browser.dir;
            browser.clipboard[browser.clipboard.length] = cdata;
            browser.initClipboard();
            browser.hideDialog();
            return false;
        });

        $('.menu a[href="kcact:mv"]').click(function(e) {
            if (!browser.canModifyFile(data.writable, browser.access.files.rename)) return false;
            browser.fileNameDialog(
                e, {dir: browser.dir, file: data.name},
                'newName', data.name, browser.baseGetData('rename'), {
                    title: "New file name:",
                    errEmpty: "Please enter new file name.",
                    errSlash: "Unallowable characters in file name.",
                    errDot: "File name shouldn't begins with '.'"
                },
                function() {
                    browser.refresh();
                }
            );
            return false;
        });

        $('.menu a[href="kcact:rm"]').click(function() {
            if (!browser.canModifyFile(data.writable, browser.access.files['delete'])) return false;
            browser.hideDialog();
            browser.confirm(browser.label("Are you sure you want to delete this file?"),
                function(callBack) {
                    $.ajax({
                        type: 'POST',
                        dataType: 'json',
                        url: browser.baseGetData('delete'),
                        data: {dir:browser.dir, file:data.name},
                        async: false,
                        success: function(data) {
                            if (callBack) callBack();
                            browser.clearClipboard();
                            if (browser.check4errors(data))
                                return;
                            browser.refresh();
                        },
                        error: function() {
                            if (callBack) callBack();
                            browser.alert(browser.label("Unknown error."));
                        }
                    });
                }
            );
            return false;
        });
    }

    $('.menu a[href="kcact:view"]').click(function() {
        browser.hideDialog();
        var ts = new Date().getTime();
        var showImage = function(data) {
            url = (browser.siteURL + browser.assetsURL + '/' + browser.dir + '/' + data.name) + '?ts=' + ts,
            $('#loading').html(browser.label("Loading image..."));
            $('#loading').css('display', 'inline');
            var img = new Image();
            img.src = url;
            img.onerror = function() {
                browser.lock = false;
                $('#loading').css('display', 'none');
                browser.alert(browser.label("Unknown error."));
                browser.initKeyboard();
                browser.refresh();
            };
            var onImgLoad = function() {
                browser.lock = false;
                $('#files .file').each(function() {
                    if ($(this).data('name') == data.name)
                        browser.ssImage = this;
                });
                $('#loading').css('display', 'none');
                $('#dialog').html('<div class="slideshow"><img /></div>');
                $('#dialog img').attr({
                    src: url,
                    title: data.name
                }).fadeIn('fast', function() {
                    var o_w = $('#dialog').outerWidth();
                    var o_h = $('#dialog').outerHeight();
                    var f_w = $(window).width() - 30;
                    var f_h = $(window).height() - 30;
                    if ((o_w > f_w) || (o_h > f_h)) {
                        if ((f_w / f_h) > (o_w / o_h))
                            f_w = parseInt((o_w * f_h) / o_h);
                        else if ((f_w / f_h) < (o_w / o_h))
                            f_h = parseInt((o_h * f_w) / o_w);
                        $('#dialog img').attr({
                            width: f_w,
                            height: f_h
                        });
                    }
                    $('#dialog').unbind('click');
                    $('#dialog').click(function(e) {
                        browser.hideDialog();
                        browser.initKeyboard();
                        if (browser.ssImage) {
                            browser.selectFile($(browser.ssImage), e);
                        }
                    });
                    browser.showDialog();
                    var images = [];
                    $.each(browser.files, function(i, file) {
                        if (file.thumb || file.smallThumb)
                            images[images.length] = file;
                    });
                    if (images.length)
                        $.each(images, function(i, image) {
                            if (image.name == data.name) {
                                $(document).unbind('keydown');
                                $(document).keydown(function(e) {
                                    if (browser.keyboardAction(e) == 'clearSelection') {
                                        browser.hideDialog();
                                        browser.initKeyboard();
                                        return false;
                                    }
                                    if (browser.handleFileKeydown(e))
                                        return false;
                                    if (images.length > 1) {
                                        if (!browser.lock && (e.keyCode == 37)) {
                                            var nimg = i
                                                ? images[i - 1]
                                                : images[images.length - 1];
                                            browser.lock = true;
                                            showImage(nimg);
                                        }
                                        if (!browser.lock && (e.keyCode == 39)) {
                                            var nimg = (i >= images.length - 1)
                                                ? images[0]
                                                : images[i + 1];
                                            browser.lock = true;
                                            showImage(nimg);
                                        }
                                    }
                                });
                            }
                        });
                });
            };
            if (img.complete)
                onImgLoad();
            else
                img.onload = onImgLoad;
        };
        showImage(data);
        return false;
    });
};
