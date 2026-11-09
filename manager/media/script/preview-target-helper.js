(function (root, factory) {
    var exported = factory();

    if (typeof module === 'object' && module.exports) {
        module.exports = exported;
    }

    root.evoPreviewTargetHelper = exported;
}(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    'use strict';

    function parse(value, base) {
        try {
            return base === undefined ? new URL(value) : new URL(value, base);
        } catch (e) {
            return null;
        }
    }

    /**
     * Decides how the editor's Preview button opens a target.
     *
     * The URL is parsed the way the browser will navigate it (relative to the site, so "page",
     * "/page", "//host/page" and "?q" all resolve), then judged by origin rather than by text:
     * anything on the site's or the manager's origin, inside the installation path or not, is
     * same-origin with the manager session and goes to the sandbox. Only http(s) is opened at all.
     *
     * @param {string} target   document id, weblink value or absolute preview URL
     * @param {string} siteUrl  absolute site URL (EVO_SITE_URL)
     * @param {string} [managerUrl] the manager page's location
     * @returns {{mode: 'sandbox'|'window'|'reject', url: string}}
     */
    function resolve(target, siteUrl, managerUrl) {
        var manager = managerUrl ? parse(managerUrl) : null;
        // A site URL defined without a host ("/") is taken relative to the manager page.
        var site = parse(siteUrl, manager ? manager.href : undefined);
        var raw = String(target === undefined || target === null ? '' : target).trim();

        if (!site || raw === '') {
            return { mode: 'reject', url: '' };
        }

        if (/^[1-9]\d*$/.test(raw)) {
            raw = 'index.php?id=' + raw;
        }

        var url = parse(raw, site.href);

        if (!url || (url.protocol !== 'http:' && url.protocol !== 'https:')) {
            return { mode: 'reject', url: '' };
        }

        var sameOrigin = url.origin === site.origin || (manager !== null && url.origin === manager.origin);

        return { mode: sameOrigin ? 'sandbox' : 'window', url: url.href };
    }

    return {
        resolve: resolve
    };
}));
