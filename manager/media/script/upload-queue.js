(function (root, factory) {
    var exported = factory();

    if (typeof module === 'object' && module.exports) {
        module.exports = exported;
    }

    root.evoUploadQueue = exported;
}(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    'use strict';

    // room in a request for the other form fields and the multipart framing
    var REQUEST_OVERHEAD = 16384;
    var PART_OVERHEAD = 512;

    /**
     * Splits the chosen files into requests PHP takes whole: PHP drops every file past max_file_uploads
     * and the whole request past post_max_size, both without telling anyone. sizes are in bytes, in the
     * order chosen; a limit of 0 means none. A file over fileLimit, or too large for a request of its own,
     * goes to skipped. Returns indexes into sizes.
     */
    function plan(sizes, maxFiles, postLimit, fileLimit) {
        var budget = postLimit > 0 ? postLimit - REQUEST_OVERHEAD : 0;
        var batches = [];
        var skipped = [];
        var current = [];
        var bytes = 0;

        for (var i = 0; i < sizes.length; i++) {
            var cost = sizes[i] + PART_OVERHEAD;
            if ((fileLimit > 0 && sizes[i] > fileLimit) || (budget > 0 && cost > budget)) {
                skipped.push(i);
                continue;
            }
            if (current.length && ((maxFiles > 0 && current.length >= maxFiles) || (budget > 0 && bytes + cost > budget))) {
                batches.push(current);
                current = [];
                bytes = 0;
            }
            current.push(i);
            bytes += cost;
        }
        if (current.length) {
            batches.push(current);
        }

        return { batches: batches, skipped: skipped };
    }

    /**
     * Sends the batches one after another; each answer carries the single-use token of the next request.
     * send(batch, token) resolves to { report, uploaded, total, token } and rejects with a reason.
     * After a failed request the token chain is broken, so that batch and all later ones are not sent.
     */
    function run(batches, token, send, onProgress) {
        var result = { reports: [], uploaded: 0, notSent: [], reason: '' };
        var index = 0;

        function next() {
            if (index >= batches.length) {
                return Promise.resolve(result);
            }
            var batch = batches[index];
            if (onProgress) {
                onProgress(index, batches.length);
            }
            return Promise.resolve()
                .then(function () {
                    return send(batch, token);
                })
                .then(function (answer) {
                    result.reports.push(answer.report);
                    result.uploaded += answer.uploaded;
                    token = answer.token;
                    index++;
                    if (!token && index < batches.length) {
                        throw new Error('no token for the next request');
                    }
                    return next();
                })
                .catch(function (error) {
                    result.reason = error && error.message ? error.message : String(error);
                    for (var i = index; i < batches.length; i++) {
                        result.notSent = result.notSent.concat(batches[i]);
                    }
                    return result;
                });
        }

        return next();
    }

    function format(text, values) {
        return String(text).replace(/%(\d)\$s/g, function (match, n) {
            return values[n - 1];
        });
    }

    function escapeHtml(text) {
        return String(text).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    return {
        plan: plan,
        run: run,
        format: format,
        escapeHtml: escapeHtml
    };
}));
