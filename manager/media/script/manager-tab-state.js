(function (root, factory) {
    var helper = factory();
    if (typeof module === 'object' && module.exports) module.exports = helper;
    root.evoManagerTabState = helper;
}(typeof window !== 'undefined' ? window : this, function () {
    'use strict';

    // Store navigation URLs, not a second registry of pages or parameter names.
    function read(url, options) {
        try {
            var base = new URL(options.base, options.origin), input = String(url || '');
            if (!input) return null;
            if (input.charAt(0) === '#') input = input.slice(1);
            var target = new URL(input, base);
            if (target.hash && !target.search && (target.pathname === base.pathname
                || target.pathname === base.pathname + 'index.php')) {
                target = new URL(target.hash.slice(1), base);
            }
            if (!/^https?:$/.test(target.protocol) || target.origin !== base.origin
                || target.username || target.password) return null;
            // A restored iframe makes a fresh GET, never resubmits a form.
            target.searchParams.delete('_token');
            target.searchParams.delete('_method');
            return target;
        } catch (error) { return null; }
    }

    function relativeUrl(target, options) {
        var base = new URL(options.base, options.origin);
        var path = target.pathname === base.pathname || target.pathname === base.pathname + 'index.php' ? '' : target.pathname;
        var query = target.searchParams.toString();
        return path + (query ? '?' + query : '') + target.hash;
    }

    function storage(url, options) {
        var target = read(url, options);
        return target ? relativeUrl(target, options) : '';
    }

    function restore(url, options) {
        var target = read(url, options);
        if (!target || !options.token) return '';
        target.searchParams.set('_token', options.token);
        return relativeUrl(target, options);
    }

    // Rebuild legacy stored labels instead of inserting their HTML. Only the
    // known Font Awesome icon classes survive; all label text is escaped.
    function title(value) {
        if (typeof value !== 'string' || value.length > 4096) return '';
        var icon = value.match(/<i\s+class=(["'])([a-z0-9 -]+)\1\s*>\s*<\/i>/i);
        var classes = icon ? icon[2].split(/\s+/).filter(function (name) {
            return /^(?:fa[brsldt]?|fa-[a-z0-9-]+)$/.test(name);
        }) : [];
        var text = value.replace(/<(script|style|svg)\b[^>]*>[\s\S]*?<\/\1\s*>/gi, '')
            .replace(/<[^>]*>/g, '').trim();
        if (!text || text === 'blank') return '';
        text = text.replace(/&(?!(?:amp|lt|gt|quot|apos|#\d+|#x[0-9a-f]+);)/gi, '&amp;')
            .replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        return (classes.length ? '<i class="' + classes.join(' ') + '"></i> ' : '') + text;
    }

    function restoreTitle(url, storedTitle, options, document) {
        var target = read(url, options);
        if (!target) return 'blank';
        // The current server-rendered menu is authoritative for module labels
        // (including SVG icons), also repairing already persisted "blank" tabs.
        if (target.searchParams.get('a') === '112') {
            var links = document.querySelectorAll('#mainMenu a[href]');
            for (var i = 0; i < links.length; i++) {
                try {
                    var link = read(links[i].getAttribute('href'), options);
                    if (link && link.searchParams.get('a') === '112'
                        && link.searchParams.get('id') === target.searchParams.get('id')) {
                        return links[i].innerHTML;
                    }
                } catch (error) { }
            }
        }
        return title(storedTitle) || 'blank';
    }

    function remember(url, options) {
        var saved = storage(url, options);
        return saved ? { url: saved } : null;
    }

    function resume(saved, options, document, storedTitle) {
        if (typeof saved === 'string') saved = { url: saved };
        if (!saved || typeof saved !== 'object') return null;
        var source = saved.url;
        // Migrate ID-only entries from the previous fix using the current menu.
        if (!source && saved.module) {
            var links = document.querySelectorAll('#mainMenu a[href]');
            for (var i = 0; i < links.length; i++) {
                var candidate = read(links[i].getAttribute('href'), options);
                if (candidate && (candidate.searchParams.get('id') === saved.module
                    || (saved.title && title(links[i].innerHTML) === title(saved.title)))) {
                    source = links[i].getAttribute('href');
                    break;
                }
            }
        }
        if (typeof source !== 'string') return null;
        var url = restore(source, options);
        return url ? { url: url, title: restoreTitle(url, saved.title || storedTitle, options, document) } : null;
    }

    return { storage: storage, restore: restore, title: title, restoreTitle: restoreTitle,
        remember: remember, resume: resume };
}));
