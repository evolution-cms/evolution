(function (root, factory) {
    var exported = factory();

    if (typeof module === 'object' && module.exports) {
        module.exports = exported;
    }

    root.modxTreeParentGuardHelper = exported;
}(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    'use strict';

    function acceptsChild(targetAnchor) {
        if (!targetAnchor || !targetAnchor.dataset) {
            return true;
        }

        var flag = parseInt(targetAnchor.dataset.canaddchild, 10);

        // nodes rendered without the flag, or with a placeholder a plugin left unresolved,
        // stay selectable: the save processor is still the one having the final word
        return isNaN(flag) || flag !== 0;
    }

    function isBlockedParentTarget(targetAnchor) {
        return !acceptsChild(targetAnchor);
    }

    return {
        acceptsChild: acceptsChild,
        isBlockedParentTarget: isBlockedParentTarget
    };
}));
