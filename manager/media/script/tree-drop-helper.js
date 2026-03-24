(function (root, factory) {
    var exported = factory();

    if (typeof module === 'object' && module.exports) {
        module.exports = exported;
    }

    root.modxTreeDropHelper = exported;
}(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    'use strict';

    function getFolderDropContainer(targetAnchor) {
        if (!targetAnchor) {
            return null;
        }

        var container = targetAnchor.nextElementSibling || targetAnchor.nextSibling || null;

        if (!container || container.nodeType !== 1) {
            return null;
        }

        return container;
    }

    function moveNodeIntoFolder(targetAnchor, draggedNode) {
        var container = getFolderDropContainer(targetAnchor);

        if (container) {
            container.appendChild(draggedNode);

            return {
                container: container,
                children: container.children
            };
        }

        if (draggedNode && draggedNode.parentNode && typeof draggedNode.parentNode.removeChild === 'function') {
            draggedNode.parentNode.removeChild(draggedNode);
        }

        return {
            container: null,
            children: []
        };
    }

    return {
        getFolderDropContainer: getFolderDropContainer,
        moveNodeIntoFolder: moveNodeIntoFolder
    };
}));
