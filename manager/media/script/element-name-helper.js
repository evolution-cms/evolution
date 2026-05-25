(function (window, document) {
    'use strict';

    if (window.evoElementNameHelper) {
        return;
    }

    var data = null;
    var loading = false;
    var callbacks = [];

    function ensureStyles() {
        var style;

        if (document.getElementById('evo-element-name-helper-style')) {
            return;
        }

        style = document.createElement('style');
        style.id = 'evo-element-name-helper-style';
        style.appendChild(document.createTextNode(
            '.cm-modxInvalidReference{color:#f00!important;text-decoration:underline;text-decoration-color:#f00;' +
            'text-decoration-thickness:1px;}.evo-binding-picker{position:absolute;z-index:1000;max-height:220px;' +
            'overflow:auto;margin-top:2px;padding:4px;border:1px solid #b8c2cc;border-radius:4px;background:#fff;' +
            'box-shadow:0 8px 20px rgba(0,0,0,.16);-webkit-overflow-scrolling:touch;}.evo-binding-picker-item{' +
            'display:block;width:100%;min-height:32px;padding:6px 8px;border:0;background:transparent;color:#222;' +
            'text-align:left;cursor:pointer;font:inherit;touch-action:manipulation;}.evo-binding-picker-item:hover,' +
            '.evo-binding-picker-item.active{background:#0d6efd;color:#fff;}@media (pointer:coarse),(max-width:767px)' +
            '{.evo-binding-picker{max-height:45vh;}.evo-binding-picker-item,.CodeMirror-hint{min-height:44px;' +
            'padding:10px 12px;font-size:16px;touch-action:manipulation;}.CodeMirror-hints{max-height:45vh;' +
            '-webkit-overflow-scrolling:touch;}}'
        ));
        document.head.appendChild(style);
    }

    function config() {
        return window.evoElementNameHelperConfig || {};
    }

    function endpoint() {
        if (config().endpoint) {
            return config().endpoint;
        }

        if (window.evo && window.evo.EVO_MANAGER_URL) {
            return window.evo.EVO_MANAGER_URL + 'media/style/default/ajax.php';
        }

        return 'media/style/default/ajax.php';
    }

    function post(url, payload, callback) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
        xhr.setRequestHeader('X-REQUESTED-WITH', 'XMLHttpRequest');
        xhr.onload = function () {
            if (xhr.status !== 200) {
                callback(null);
                return;
            }

            try {
                callback(JSON.parse(xhr.responseText));
            } catch (ignore) {
                callback(null);
            }
        };
        xhr.onerror = function () {
            callback(null);
        };
        xhr.send(payload);
    }

    function getData(callback) {
        if (data) {
            callback(data);
            return;
        }

        callbacks.push(callback);

        if (loading) {
            return;
        }

        loading = true;
        post(endpoint(), 'a=elementNameHelper', function (response) {
            data = response || {
                bindings: [],
                chunks: [],
                snippets: []
            };
            loading = false;

            while (callbacks.length) {
                callbacks.shift()(data);
            }
        });
    }

    function makeSet(items) {
        var out = {};
        var i;

        for (i = 0; i < items.length; i += 1) {
            out[items[i]] = true;
        }

        return out;
    }

    function namesFor(source, type) {
        return source[type] || [];
    }

    function filterNames(items, term) {
        var lower = term.toLowerCase();
        var out = [];
        var i;

        for (i = 0; i < items.length; i += 1) {
            if (!term || items[i].toLowerCase().indexOf(lower) === 0) {
                out.push(items[i]);
            }
        }

        return out;
    }

    function debounce(fn, delay) {
        var timer = null;

        return function () {
            var args = arguments;
            var self = this;

            clearTimeout(timer);
            timer = setTimeout(function () {
                fn.apply(self, args);
            }, delay);
        };
    }

    function collectInvalidRanges(cm, source) {
        var value = cm.getValue();
        var chunkNames = makeSet(namesFor(source, 'chunks'));
        var snippetNames = makeSet(namesFor(source, 'snippets'));
        var ranges = [];
        var tagPattern = /(\[\[|\[!|\{\{)\s*([^\s\]\}\?!`]+)/g;
        var bindingPattern = /@CHUNK\s+([^\r\n`;&|]+)/gi;
        var match;
        var type;
        var name;
        var start;
        var end;

        while ((match = tagPattern.exec(value)) !== null) {
            type = match[1] === '{{' ? 'chunks' : 'snippets';
            name = match[2].trim();

            if (type === 'chunks' && /^@FILE$/i.test(name)) {
                continue;
            }

            if (name && !namesFor(source, type).length) {
                continue;
            }

            if (name && !(type === 'chunks' ? chunkNames[name] : snippetNames[name])) {
                start = match.index + match[0].indexOf(match[2]);
                end = start + match[2].length;
                ranges.push({
                    from: cm.posFromIndex(start),
                    to: cm.posFromIndex(end)
                });
            }
        }

        while ((match = bindingPattern.exec(value)) !== null) {
            name = match[1].trim();

            if (name && namesFor(source, 'chunks').length && !chunkNames[name]) {
                start = match.index + match[0].indexOf(match[1]);
                end = start + match[1].length;
                ranges.push({
                    from: cm.posFromIndex(start),
                    to: cm.posFromIndex(end)
                });
            }
        }

        return ranges;
    }

    function installCodeMirrorValidation(cm) {
        var marks = [];

        function clearMarks() {
            while (marks.length) {
                marks.pop().clear();
            }
        }

        function validate() {
            getData(function (source) {
                var ranges = collectInvalidRanges(cm, source);
                var i;

                cm.operation(function () {
                    clearMarks();

                    for (i = 0; i < ranges.length; i += 1) {
                        marks.push(cm.markText(ranges[i].from, ranges[i].to, {
                            className: 'cm-modxInvalidReference',
                            title: 'Unknown Evolution CMS element'
                        }));
                    }
                });
            });
        }

        validate();
        cm.on('change', debounce(validate, 300));
    }

    function codeMirrorHint(cm, source) {
        var cur = cm.getCursor();
        var line = cm.getLine(cur.line).slice(0, cur.ch);
        var match;
        var from;
        var list = [];
        var i;
        var bindings = namesFor(source, 'bindings');

        match = /@CHUNK\s+([^\r\n`;&|]*)$/i.exec(line);
        if (match) {
            from = {
                line: cur.line,
                ch: cur.ch - match[1].length
            };
            list = filterNames(namesFor(source, 'chunks'), match[1]);
        } else {
            match = /@([A-Za-z]*)$/.exec(line);
            if (match) {
                from = {
                    line: cur.line,
                    ch: cur.ch - match[0].length
                };

                for (i = 0; i < bindings.length; i += 1) {
                    if (!match[1] || bindings[i].indexOf(match[1].toUpperCase()) === 0) {
                        list.push({
                            text: '@' + bindings[i] + ' ',
                            displayText: '@' + bindings[i],
                            hint: bindings[i] === 'CHUNK' ? function (hintCm, data, completion) {
                                hintCm.replaceRange(completion.text, data.from, data.to, 'complete');
                                setTimeout(function () {
                                    hintCm.showHint({
                                        hint: function (nextCm) {
                                            return codeMirrorHint(nextCm, source);
                                        },
                                        completeSingle: false
                                    });
                                }, 0);
                            } : null
                        });
                    }
                }
            } else {
                match = /\[\[([^\]\?`\s]*)$/.exec(line) || /\[!([^\]!\?`\s]*)$/.exec(line);
                if (match) {
                    from = {
                        line: cur.line,
                        ch: cur.ch - match[1].length
                    };
                    list = filterNames(namesFor(source, 'snippets'), match[1]);
                } else {
                    match = /\{\{([^\}\?`\s]*)$/.exec(line);
                    if (match) {
                        from = {
                            line: cur.line,
                            ch: cur.ch - match[1].length
                        };
                        list = filterNames(namesFor(source, 'chunks'), match[1]);
                    }
                }
            }
        }

        if (!from || !list.length) {
            return null;
        }

        return {
            list: list,
            from: from,
            to: cur
        };
    }

    function shouldShowCodeMirrorHint(change) {
        var text;

        if (!change || !change.text || change.origin === 'setValue') {
            return false;
        }

        text = change.text.join('');

        return /[@A-Za-z0-9_\-\{\[\!]/.test(text);
    }

    function installCodeMirrorHints(cm) {
        if (!window.CodeMirror || !CodeMirror.showHint) {
            return;
        }

        cm.on('change', function (editor, change) {
            if (!shouldShowCodeMirrorHint(change) || editor.state.completionActive) {
                return;
            }

            getData(function (source) {
                editor.showHint({
                    hint: function (hintCm) {
                        return codeMirrorHint(hintCm, source);
                    },
                    completeSingle: false
                });
            });
        });
    }

    function installCodeMirror(cm) {
        if (!cm || cm.state.evoElementNameHelperInstalled) {
            return;
        }

        ensureStyles();
        cm.state.evoElementNameHelperInstalled = true;
        installCodeMirrorValidation(cm);
        installCodeMirrorHints(cm);
    }

    function textAreaContext(textarea) {
        var value = textarea.value.slice(0, textarea.selectionStart || 0);
        var match = /@CHUNK\s+([^\r\n`;&|]*)$/i.exec(value);

        if (match) {
            return {
                type: 'chunks',
                term: match[1],
                from: value.length - match[1].length,
                suffix: ''
            };
        }

        match = /@([A-Za-z]*)$/.exec(value);
        if (match) {
            return {
                type: 'bindings',
                term: match[1],
                from: value.length - match[0].length,
                suffix: ' '
            };
        }

        return null;
    }

    function setTextAreaRange(textarea, from, to, text) {
        var before = textarea.value.slice(0, from);
        var after = textarea.value.slice(to);

        textarea.value = before + text + after;
        textarea.selectionStart = textarea.selectionEnd = before.length + text.length;
        textarea.focus();

        if (typeof documentDirty !== 'undefined') {
            documentDirty = true;
        }
    }

    function installTvBindingPicker(textarea) {
        var menu;
        var active = 0;
        var current = null;
        var parent = textarea.parentNode;

        if (!textarea || textarea.dataset.evoBindingPicker) {
            return;
        }

        ensureStyles();
        textarea.dataset.evoBindingPicker = '1';
        menu = document.createElement('div');
        menu.className = 'evo-binding-picker';
        menu.style.display = 'none';
        parent.appendChild(menu);

        if (window.getComputedStyle(parent).position === 'static') {
            parent.style.position = 'relative';
        }

        function close() {
            menu.style.display = 'none';
            menu.innerHTML = '';
            current = null;
        }

        function pick(index) {
            var item;
            var continueWithChunks;

            if (!current || !current.items[index]) {
                return;
            }

            item = current.items[index];
            continueWithChunks = /^@CHUNK\s$/i.test(item.value);
            setTextAreaRange(textarea, current.from, textarea.selectionStart || 0, item.value);
            close();

            if (continueWithChunks) {
                setTimeout(update, 0);
            }
        }

        function positionMenu() {
            var width = textarea.offsetWidth;

            menu.style.left = textarea.offsetLeft + 'px';
            menu.style.top = (textarea.offsetTop + textarea.offsetHeight) + 'px';
            menu.style.width = (width > 0 ? width : parent.clientWidth) + 'px';
        }

        function bindPickEvent(item, index) {
            var picked = false;
            var handler = function (event) {
                if (event.cancelable) {
                    event.preventDefault();
                }

                if (picked) {
                    return;
                }

                picked = true;
                pick(index);
                setTimeout(function () {
                    picked = false;
                }, 350);
            };

            if (window.PointerEvent) {
                item.addEventListener('pointerdown', handler);
            } else {
                item.addEventListener('mousedown', handler);
                item.addEventListener('touchstart', handler, {passive: false});
            }

            item.addEventListener('click', handler);
        }

        function render(items, context) {
            var i;
            var item;

            active = 0;
            current = {
                from: context.from,
                items: items
            };
            menu.innerHTML = '';

            for (i = 0; i < items.length; i += 1) {
                item = document.createElement('button');
                item.type = 'button';
                item.className = 'evo-binding-picker-item' + (i === active ? ' active' : '');
                item.appendChild(document.createTextNode(items[i].label));
                item.dataset.index = i;
                bindPickEvent(item, i);
                menu.appendChild(item);
            }

            positionMenu();
            menu.style.display = items.length ? 'block' : 'none';
        }

        function update() {
            getData(function (source) {
                var context = textAreaContext(textarea);
                var raw = [];
                var items = [];
                var i;

                if (!context) {
                    close();
                    return;
                }

                raw = filterNames(namesFor(source, context.type), context.term);
                for (i = 0; i < raw.length && i < 30; i += 1) {
                    items.push({
                        label: context.type === 'bindings' ? '@' + raw[i] : raw[i],
                        value: context.type === 'bindings' ? '@' + raw[i] + context.suffix : raw[i]
                    });
                }

                if (!items.length) {
                    close();
                    return;
                }

                render(items, context);
            });
        }

        function move(delta) {
            var items = menu.querySelectorAll('.evo-binding-picker-item');

            if (!items.length) {
                return;
            }

            items[active].className = items[active].className.replace(' active', '');
            active = (active + delta + items.length) % items.length;
            items[active].className += ' active';
            items[active].scrollIntoView({block: 'nearest'});
        }

        textarea.addEventListener('input', update);
        textarea.addEventListener('click', update);
        window.addEventListener('resize', function () {
            if (menu.style.display !== 'none') {
                positionMenu();
            }
        });
        textarea.addEventListener('blur', function () {
            setTimeout(close, 120);
        });
        textarea.addEventListener('keydown', function (event) {
            if (menu.style.display === 'none') {
                if (event.key === 'ArrowDown') {
                    update();
                }
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                move(1);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                move(-1);
            } else if (event.key === 'Enter' || event.key === 'Tab') {
                event.preventDefault();
                pick(active);
            } else if (event.key === 'Escape') {
                close();
            }
        });
    }

    window.evoElementNameHelper = {
        getData: getData,
        installCodeMirror: installCodeMirror,
        installTvBindingPicker: installTvBindingPicker,
        installTvBindingPickers: function (selector) {
            var fields = document.querySelectorAll(selector || '#elements, #default_text');
            var i;

            for (i = 0; i < fields.length; i += 1) {
                installTvBindingPicker(fields[i]);
            }
        }
    };
}(window, document));
