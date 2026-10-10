/** This file is part of KCFinder project
 *
 *      @desc Native browser file uploader
 *   @package KCFinder
 *   @version 2.54
 *    @author Pavel Tzonkov <sunhater@sunhater.com>
 * @copyright 2010-2014 KCFinder Project
 *   @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 *   @license http://www.opensource.org/licenses/lgpl-2.1.php LGPLv2
 *      @link http://kcfinder.sunhater.com
 */

browser.initUploader = function() {
    var btn = $('#toolbar a[href="kcact:upload"]');
    if (!this.access.files.upload) {
        btn.css('display', 'none');
        return;
    }
    $('#toolbar').prepend('<input type="file" name="upload" style="display:none;" multiple="multiple" />');
    var upload = $('input[name="upload"]', '#toolbar');
    btn.click(function(e){
        e.preventDefault();
        if (!browser.canWriteCurrentDirectory(browser.access.files.upload)) {
            browser.alert(browser.label("Cannot write to upload folder."));
            return false;
        }
        browser.clearUpload();
        upload.trigger('click');
    });
    upload.change(function () {
        browser.prepareFiles(this.files);
    });
    browser.initFilesDropGuards();
    browser.initFilesDropzone();
    browser.updateWriteControls();
};

browser.initFilesDropGuards = function() {
    browser.bindDropListeners(document, '_filesDropGuardHandlers', {
        dragenter: function(evt) {
            browser.preventExternalFileDropDefault(evt);
        },
        dragover: function(evt) {
            browser.preventExternalFileDropDefault(evt);
        },
        drop: function(evt) {
            browser.preventExternalFileDropDefault(evt);
        }
    });
};

browser.initFilesDropzone = function() {
    var filesPane = $('#files').get(0);

    if (!filesPane) {
        return;
    }

    browser.bindDropListeners(filesPane, '_filesDropzoneHandlers', {
        dragenter: function(evt) {
            if (!browser.preventExternalFileDropDefault(evt)) {
                return;
            }

            if (browser.isFilesDropTarget(evt.target)) {
                $('#files').addClass('drag');
            } else {
                $('#files').removeClass('drag');
            }
        },
        dragover: function(evt) {
            if (!browser.preventExternalFileDropDefault(evt)) {
                return;
            }

            if (browser.isFilesDropTarget(evt.target)) {
                $('#files').addClass('drag');
            } else {
                $('#files').removeClass('drag');
            }
        },
        dragleave: function(evt) {
            if (!browser.preventExternalFileDropDefault(evt)) {
                return;
            }

            if (!browser.isFilesDropzoneElement(evt.relatedTarget)) {
                $('#files').removeClass('drag');
            }
        },
        drop: function(evt) {
            var files;

            if (!browser.preventExternalFileDropDefault(evt)) {
                return;
            }

            $('#files').removeClass('drag');
            if (!browser.isFilesDropTarget(evt.target)) {
                return;
            }

            files = browser.extractDroppedFiles(evt);
            if (files.length) {
                browser.prepareFiles(files);
            }
        }
    });
};

browser.bindDropListeners = function(element, storageKey, handlers) {
    var eventName;
    var previousHandlers = browser[storageKey];

    if (previousHandlers) {
        for (eventName in previousHandlers) {
            if (previousHandlers.hasOwnProperty(eventName)) {
                if (element.removeEventListener) {
                    element.removeEventListener(eventName, previousHandlers[eventName], false);
                } else {
                    $(element).unbind(eventName, previousHandlers[eventName]);
                }
            }
        }
    }

    browser[storageKey] = handlers;

    for (eventName in handlers) {
        if (handlers.hasOwnProperty(eventName)) {
            if (element.addEventListener) {
                element.addEventListener(eventName, handlers[eventName], false);
            } else {
                $(element).bind(eventName, handlers[eventName]);
            }
        }
    }
};

browser.preventExternalFileDropDefault = function(evt) {
    if (!browser.isExternalFileDrag(evt)) {
        return false;
    }

    if (evt.preventDefault) {
        evt.preventDefault();
    }
    if (evt.stopPropagation) {
        evt.stopPropagation();
    }

    return true;
};

browser.extractDroppedFiles = function(evt) {
    var event = evt && (evt.originalEvent || evt);
    var dataTransfer = event && event.dataTransfer;
    var items = dataTransfer && dataTransfer.items;
    var i;

    if (dataTransfer && dataTransfer.files && dataTransfer.files.length) {
        return Array.prototype.slice.call(dataTransfer.files);
    }

    if (!items || !items.length) {
        return [];
    }

    files = [];
    for (i = 0; i < items.length; i++) {
        if (items[i].kind === 'file' && items[i].getAsFile) {
            var file = items[i].getAsFile();
            if (file) {
                files.push(file);
            }
        }
    }

    return files;
};

browser.isExternalFileDrag = function(evt) {
    var event = evt && (evt.originalEvent || evt);
    var dataTransfer = event && event.dataTransfer;
    var types = dataTransfer && dataTransfer.types;

    if (!types) {
        return false;
    }

    if (typeof types.contains === 'function') {
        return types.contains('Files');
    }

    if (typeof types.indexOf === 'function') {
        return types.indexOf('Files') >= 0;
    }

    for (var i = 0; i < types.length; i++) {
        if (types[i] === 'Files') {
            return true;
        }
    }

    return false;
};

browser.isFilesDropzoneElement = function(target) {
    return !!$(target).closest('#files').length;
};

browser.isFilesDropTarget = function(target) {
    var file = $(target).closest('.file', '#files');

    return !file.length || !file.data('isDir');
};

browser.clearUpload = function() {
    var upload = $('input[name="upload"]', '#toolbar');
    upload.wrap('<form>').closest('form').get(0).reset();
    upload.unwrap();
};
browser.prepareFiles = function(files) {
    var accepted = [],
        rejected = [],
        selected = Array.prototype.slice.call(files || []);

    $.each(selected, function(i, file) {
        var ext = file.name.split('.').pop().toLowerCase();
        var message = '';

        if (!browser.allowedExts.test(ext) || browser.deniedExts.test(ext)) {
            message = browser.label("Denied file extension.");
        } else if (browser.maxFileSize <= file.size) {
            message = browser.label("The uploaded file exceeds {size} bytes.", {size: browser.maxFileSize});
        }

        if (message) {
            rejected.push(file.name + ': ' + message);
        } else {
            accepted.push(file);
        }
    });

    if (rejected.length) {
        browser.alert(rejected.join('<br>'), true, function() {
            browser.uploadFiles(accepted);
        });
    } else {
        browser.uploadFiles(accepted);
    }
};
browser.resizeImageForUpload = function(file) {
    var resize = browser.clientResize || {},
        maxWidth = Number(resize.maxWidth),
        maxHeight = Number(resize.maxHeight),
        quality = Number(resize.quality);

    if (maxWidth <= 0 || maxHeight <= 0 || (file.type !== 'image/jpeg' && file.type !== 'image/png')) {
        return Promise.resolve(file);
    }

    quality = isFinite(quality) ? Math.max(0, Math.min(1, quality)) : 1;

    function makeBlob(image) {
        var width = image.width || image.naturalWidth,
            height = image.height || image.naturalHeight,
            scale = Math.min(1, maxWidth / width, maxHeight / height);

        if (!width || !height || scale >= 1) {
            return Promise.resolve(file);
        }

        var canvas = document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(width * scale));
        canvas.height = Math.max(1, Math.round(height * scale));
        var context = canvas.getContext('2d');

        if (!context || !canvas.toBlob) {
            return Promise.resolve(file);
        }

        try {
            context.drawImage(image, 0, 0, canvas.width, canvas.height);
        } catch (error) {
            return Promise.resolve(file);
        }

        return new Promise(function(resolve) {
            canvas.toBlob(function(blob) {
                resolve(blob || file);
            }, file.type, quality);
        });
    }

    if (window.createImageBitmap) {
        return Promise.resolve().then(function() {
            return window.createImageBitmap(file, {imageOrientation: 'from-image'});
        }).catch(function() {
            return window.createImageBitmap(file);
        }).then(function(bitmap) {
            return makeBlob(bitmap).then(function(blob) {
                if (bitmap.close) bitmap.close();
                return blob;
            }).catch(function() {
                if (bitmap.close) bitmap.close();
                return file;
            });
        }).catch(function() {
            return file;
        });
    }

    if (!window.URL || !window.URL.createObjectURL) {
        return Promise.resolve(file);
    }

    return new Promise(function(resolve) {
        var image = new Image(),
            url = window.URL.createObjectURL(file);

        image.onload = function() {
            window.URL.revokeObjectURL(url);
            makeBlob(image).then(resolve);
        };
        image.onerror = function() {
            window.URL.revokeObjectURL(url);
            resolve(file);
        };
        image.src = url;
    });
};

browser.uploadFile = function(file, index, count) {
    return browser.resizeImageForUpload(file).then(function(blob) {
        return new Promise(function(resolve, reject) {
            var xhr = new XMLHttpRequest(),
                data = new FormData();

            data.append('file', blob, file.name);
            data.append('dir', browser.dir);
            xhr.open('POST', browser.baseGetData('upload'), true);
            xhr.upload.addEventListener('progress', function(evt) {
                var loaded = evt.lengthComputable ? evt.loaded : file.size,
                    total = evt.lengthComputable ? evt.total : file.size,
                    progress = total ? Math.round((loaded * 100) / total) + '%' : '0%';

                $('#loading').html(browser.label("Uploading file {number} of {count}... {progress}", {
                    number: index + 1,
                    count: count,
                    progress: progress
                }));
            });
            xhr.onload = function() {
                var response;

                try {
                    response = $.parseJSON(xhr.responseText);
                } catch (error) {
                    reject(browser.label("Unable to process server response"));
                    return;
                }

                if (xhr.status < 200 || xhr.status >= 300) {
                    reject(browser.label("Server error") + ' ' + xhr.status);
                } else {
                    resolve(response);
                }
            };
            xhr.onerror = function() {
                reject(browser.label("Server error"));
            };
            xhr.send(data);
        });
    });
};

browser.uploadFiles = function(files) {
    if (!this.canWriteCurrentDirectory(this.access.files.upload)) {
        browser.alert(this.label("Cannot write to upload folder."));
        return;
    }

    files = Array.prototype.slice.call(files || []);
    if (!files.length) return;

    var errors = [],
        index = 0;

    $('#loading').show();
    browser.fadeFiles();

    function uploadNext() {
        if (index >= files.length) {
            $('#loading').hide();
            browser.refresh();
            browser.clearUpload();
            if (errors.length) {
                browser.alert(errors.join('<br>'), false);
            }
            return;
        }

        var file = files[index];
        $('#loading').html(browser.label("Uploading file {number} of {count}... {progress}", {
            number: index + 1,
            count: files.length,
            progress: ""
        }));

        Promise.resolve().then(function() {
            return browser.uploadFile(file, index, files.length);
        }).then(function(response) {
            if (!response || !response.success) {
                var message = response && response.message ? response.message : browser.label("Server error");
                if (typeof message === 'object') message = message.join('; ');
                errors.push(file.name + ': ' + message);
            }
        }).catch(function(error) {
            errors.push(file.name + ': ' + error);
        }).then(function() {
            index++;
            uploadNext();
        });
    }

    uploadNext();
};
