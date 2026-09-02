(function (root, factory) {
    var helper = factory();
    if (typeof module === 'object' && module.exports) module.exports = helper;
    root.evoManagerTabState = helper;
}(typeof window !== 'undefined' ? window : this, function () {
    'use strict';

    // This list is deliberately smaller than the Manager action registry.
    // Unknown actions and extra parameters must never acquire a fresh token on restore.
    var readActions = ['2', '3', '27', '76', '106', '107'];
    var coreParams = { id: '^\\d+$', tab: '^[a-zA-Z0-9_-]{1,64}$' };

    function read(url, options) {
        try {
            var base = new URL(options.base, options.origin);
            var input = String(url || '');
            if (!input || input.length > 8192) return null;
            if (input.charAt(0) === '#') input = input.slice(1);
            var target = new URL(input, base);
            if (target.hash) {
                if (target.origin !== base.origin || target.search
                    || (target.pathname !== base.pathname && target.pathname !== base.pathname + 'index.php')) return null;
                target = new URL(target.hash.slice(1), base);
            }
            if (target.origin !== base.origin || target.username || target.password || target.hash) return null;
            if (target.pathname !== base.pathname && target.pathname !== base.pathname + 'index.php') return null;
            var seen = new Set(), valid = true;
            target.searchParams.forEach(function (value, key) {
                if (seen.has(key)) valid = false;
                seen.add(key);
            });
            if (!valid) return null;
            var action = target.searchParams.get('a');
            var params = coreParams;
            if (action === '112') {
                var id = target.searchParams.get('id');
                if (!options.modules || !Object.prototype.hasOwnProperty.call(options.modules, id)) return null;
                var rule = options.modules[id];
                if (!rule || !Array.isArray(rule.views) || rule.views.indexOf(target.searchParams.get('get') || '') === -1) return null;
                params = rule.params || {};
            } else if (readActions.indexOf(action) === -1) {
                return null;
            }
            target.searchParams.forEach(function (value, key) {
                if (key === 'a' || key === '_token' || (action === '112' && (key === 'id' || key === 'get'))) return;
                if (!Object.prototype.hasOwnProperty.call(params, key) || value.length > 512
                    || !(new RegExp(params[key])).test(value)) valid = false;
            });
            if (!valid) return null;
            target.searchParams.delete('_token');
            return target;
        } catch (error) {
            return null;
        }
    }

    function storage(url, options) {
        var target = read(url, options);
        return target ? '?' + target.searchParams.toString() : '';
    }

    function restore(url, options) {
        var target = read(url, options);
        if (!target || !options.token) return '';
        // All restored requests are fresh read-page loads, never retries of a failed action.
        target.searchParams.set('_token', options.token);
        return '?' + target.searchParams.toString();
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

    return { storage: storage, restore: restore, title: title, restoreTitle: restoreTitle };
}));
