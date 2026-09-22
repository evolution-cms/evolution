(function (root, factory) {
    var exported = factory();

    if (typeof module === 'object' && module.exports) {
        module.exports = exported;
    }

    root.modxDocumentSaveHelper = exported;
}(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    'use strict';

    var TITLE_LENGTH = 50;

    function fieldValue(form, name) {
        var field = form && form.elements ? form.elements[name] : null;
        return field && typeof field.value === 'string' ? field.value : '';
    }

    // The in-place save covers "save and keep editing" of an existing resource; a new
    // resource still needs one navigation to its own editor, and "close" / "add another"
    // navigate away anyway, so those keep the form post.
    function usesAjax(form, capabilities) {
        capabilities = capabilities || {};
        if (capabilities.formData === false || capabilities.xhr === false) {
            return false;
        }
        if (fieldValue(form, 'stay') !== '2') {
            return false;
        }
        if (fieldValue(form, 'refresh_preview') === '1') {
            return false;
        }

        return parseInt(fieldValue(form, 'id'), 10) > 0;
    }

    // The action goes into the query string so the request reads as the manager action it is.
    function requestUrl(action, base) {
        return (base || 'index.php') + '?a=' + encodeURIComponent(String(action || '5'));
    }

    // The form body of the XHR: the action is in the URL (the manager refuses it in both
    // places) and the CSRF token is the page's current one.
    function requestBody(body, tokenMeta) {
        body.delete('a');
        var token = tokenMeta && tokenMeta.getAttribute ? tokenMeta.getAttribute('content') : '';
        if (token) {
            body.set('_token', token);
        }
        return body;
    }

    function parseResponse(status, text) {
        var body = null;
        try {
            body = JSON.parse(text);
        } catch (e) {
            body = null;
        }
        if (!body || typeof body !== 'object') {
            // not our JSON: an expired session, a plugin that exited, a fatal error. The
            // save may or may not have run, so it is never replayed; the editor reloads.
            return { ok: false, message: '', unconfirmed: true };
        }
        if (status === 200 && body.success === true) {
            return { ok: true, result: body };
        }

        return { ok: false, message: typeof body.message === 'string' ? body.message : '', unconfirmed: false };
    }

    function shortTitle(title) {
        title = String(title || '');
        return title.length > TITLE_LENGTH ? title.substr(0, TITLE_LENGTH) + '...' : title;
    }

    // Writes back what the server may have changed: the alias it generated or cleaned,
    // the title it substituted for an empty one, the heading that shows it, and the
    // CSRF token in case the session rotated it.
    function applyResult(form, result, heading, tokenMeta) {
        var changed = [];
        var fields = { alias: result.alias, pagetitle: result.pagetitle };
        if (typeof result.token === 'string' && result.token !== '') {
            fields._token = result.token;
            if (tokenMeta && tokenMeta.getAttribute('content') !== result.token) {
                tokenMeta.setAttribute('content', result.token);
                changed.push('meta');
            }
        }

        Object.keys(fields).forEach(function (name) {
            var field = form && form.elements ? form.elements[name] : null;
            if (!field || typeof fields[name] !== 'string' || field.value === fields[name]) {
                return;
            }
            field.value = fields[name];
            changed.push(name);
        });

        if (heading && heading.childNodes) {
            for (var i = 0; i < heading.childNodes.length; i++) {
                var node = heading.childNodes[i];
                if (node.nodeType === 3 && node.nodeValue.replace(/\s/g, '') !== '') {
                    var text = shortTitle(result.pagetitle);
                    if (node.nodeValue !== text) {
                        node.nodeValue = text;
                        changed.push('heading');
                    }
                    break;
                }
            }
        }

        return changed;
    }

    return {
        usesAjax: usesAjax,
        requestUrl: requestUrl,
        requestBody: requestBody,
        parseResponse: parseResponse,
        applyResult: applyResult,
        shortTitle: shortTitle
    };
}));
