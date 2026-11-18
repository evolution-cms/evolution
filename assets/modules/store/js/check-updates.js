(function (root, factory) {
    var exported = factory();

    if (typeof module === 'object' && module.exports) {
        module.exports = exported;
    }

    root.evoStoreCheckUpdates = exported;
}(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    'use strict';

    /**
     * What the Extras module shows after "Check for updates" (action check_updates).
     *
     * Only a fresh catalog is worth a reload: when the update caches were dropped but the catalog
     * could not be fetched or stored (catalog_refreshed false), the module still holds the old
     * list, so it stays as it is and says so, and the dashboard link stays useful either way.
     *
     * @param {object|null} data  the JSON answer of check_updates
     * @param {object} texts      {done, partial, failed}
     * @return {{state: string, text: string, dashboard: boolean, reload: boolean}}
     */
    function noticeFor(data, texts) {
        texts = texts || {};

        if (!data || !data.ok) {
            return {
                state: 'failed',
                text: (data && typeof data.message === 'string' && data.message) || texts.failed || '',
                dashboard: false,
                reload: false
            };
        }

        if (data.catalog_refreshed !== true) {
            return {state: 'partial', text: texts.partial || '', dashboard: true, reload: false};
        }

        return {state: 'done', text: texts.done || '', dashboard: true, reload: true};
    }

    return {
        noticeFor: noticeFor
    };
}));
