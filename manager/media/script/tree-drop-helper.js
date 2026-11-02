(function (root, factory) {
    var exported = factory();

    if (typeof module === 'object' && module.exports) {
        module.exports = exported;
    }

    root.modxTreeDropHelper = exported;
}(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    'use strict';

    // One effect for every allowed position; a drop moves the resource, so no "+" copy cursor.
    var DROP_EFFECT = 'move';

    // A pointer exactly on a boundary takes the lower zone. Without "into" (a deleted target)
    // the row splits into halves.
    function getDropZone(offsetY, height, canDropInto) {
        if (canDropInto === false) {
            return offsetY < height / 2 ? 'before' : 'after';
        }

        if (offsetY >= height / 1.51) {
            return 'after';
        }

        if (offsetY < height / 3) {
            return 'before';
        }

        return 'into';
    }

    function isTreeNode(element) {
        return !!element && element.nodeType === 1 && /^node\d+$/.test(element.id || '');
    }

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

    // first child node of an expanded folder; null when collapsed or empty
    function getFirstChildNode(targetAnchor) {
        var container = getFolderDropContainer(targetAnchor);
        var children = container ? container.children || [] : [];

        for (var i = 0; i < children.length; i++) {
            if (isTreeNode(children[i])) {
                return children[i];
            }
        }

        return null;
    }

    // resource ids in tree order; skips placeholders such as the "empty folder" row
    function collectMenuIndex(children) {
        var ids = [];

        for (var i = 0; i < (children ? children.length : 0); i++) {
            if (isTreeNode(children[i])) {
                ids.push(children[i].id.substr(4));
            }
        }

        return ids;
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
        DROP_EFFECT: DROP_EFFECT,
        getDropZone: getDropZone,
        isTreeNode: isTreeNode,
        getFolderDropContainer: getFolderDropContainer,
        getFirstChildNode: getFirstChildNode,
        collectMenuIndex: collectMenuIndex,
        moveNodeIntoFolder: moveNodeIntoFolder
    };
}));
