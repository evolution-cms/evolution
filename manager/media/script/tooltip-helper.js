(function (root, factory) {
    var exported = factory();

    if (typeof module === 'object' && module.exports) {
        module.exports = exported;
    }

    root.evoTooltipHelper = exported;
}(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    'use strict';

    function getTooltipContent(target, querySelector) {
        var datasetTooltip;
        var tooltipNode;

        if (!target) {
            return '';
        }

        datasetTooltip = target.dataset && target.dataset.tooltip;
        if (datasetTooltip) {
            if (datasetTooltip.charAt(0) === '#' && typeof querySelector === 'function') {
                tooltipNode = querySelector(datasetTooltip);
                return tooltipNode ? tooltipNode.innerHTML : '';
            }

            return datasetTooltip;
        }

        if (typeof target.getAttribute === 'function') {
            return target.getAttribute('title') || '';
        }

        return '';
    }

    return {
        getTooltipContent: getTooltipContent
    };
}));
