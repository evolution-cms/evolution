(function (root, factory) {
    var exported = factory();

    if (typeof module === 'object' && module.exports) {
        module.exports = exported;
    }

    root.modxMainTargetLinkHelper = exported;
}(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    'use strict';

    function getMainTargetLink(event) {
        if (!event || event.defaultPrevented || event.button !== 0 || !event.target) {
            return null;
        }

        var link = null;

        if (event.target.tagName === 'A') {
            link = event.target;
        } else if (event.target.parentNode && event.target.parentNode.tagName === 'A') {
            link = event.target.parentNode;
        }

        if (!link || link.target !== 'main') {
            return null;
        }

        return link;
    }

    return {
        getMainTargetLink: getMainTargetLink
    };
}));
