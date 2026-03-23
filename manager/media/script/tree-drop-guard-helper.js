(function (root, factory) {
    var exported = factory();

    if (typeof module === 'object' && module.exports) {
        module.exports = exported;
    }

    root.modxTreeDropGuardHelper = exported;
}(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    'use strict';

    function isDeletedTarget(targetAnchor) {
        if (!targetAnchor || !targetAnchor.dataset) {
            return false;
        }

        return parseInt(targetAnchor.dataset.deleted || '0', 10) === 1;
    }

    function canDropIntoTarget(targetAnchor) {
        return !isDeletedTarget(targetAnchor);
    }

    return {
        canDropIntoTarget: canDropIntoTarget,
        isDeletedTarget: isDeletedTarget
    };
}));
