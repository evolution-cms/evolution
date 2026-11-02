const test = require('node:test');
const assert = require('node:assert/strict');
const helper = require('../tree-drop-helper');

function createContainer(children = []) {
    const container = {
        nodeType: 1,
        children: [],
        appendChild(node) {
            if (node.parentNode && typeof node.parentNode.removeChild === 'function') {
                node.parentNode.removeChild(node);
            }

            this.children.push(node);
            node.parentNode = this;
            return node;
        },
        removeChild(node) {
            const index = this.children.indexOf(node);

            if (index >= 0) {
                this.children.splice(index, 1);
                node.parentNode = null;
            }

            return node;
        }
    };

    children.forEach((child) => container.appendChild(child));

    return container;
}

test('moveNodeIntoFolder appends into an empty collapsed folder container', () => {
    const sourceContainer = createContainer();
    const draggedNode = { id: 'node10', parentNode: null };
    sourceContainer.appendChild(draggedNode);

    const targetContainer = createContainer();
    const targetAnchor = {
        nextElementSibling: targetContainer
    };

    const result = helper.moveNodeIntoFolder(targetAnchor, draggedNode);

    assert.equal(result.container, targetContainer);
    assert.deepEqual(Array.from(result.children).map((node) => node.id), ['node10']);
    assert.equal(draggedNode.parentNode, targetContainer);
    assert.deepEqual(sourceContainer.children, []);
});

test('moveNodeIntoFolder falls back to removing the dragged node when no folder container exists', () => {
    const sourceContainer = createContainer();
    const draggedNode = { id: 'node10', parentNode: null };
    sourceContainer.appendChild(draggedNode);

    const result = helper.moveNodeIntoFolder({ nextElementSibling: null, nextSibling: null }, draggedNode);

    assert.equal(result.container, null);
    assert.deepEqual(result.children, []);
    assert.equal(draggedNode.parentNode, null);
    assert.deepEqual(sourceContainer.children, []);
});

test('every drop position uses the same move drop effect', () => {
    assert.equal(helper.DROP_EFFECT, 'move');
});

test('getDropZone splits a row into before, into and after', () => {
    assert.equal(helper.getDropZone(0, 21), 'before');
    assert.equal(helper.getDropZone(6, 21), 'before');
    assert.equal(helper.getDropZone(7, 21), 'into');
    assert.equal(helper.getDropZone(10.5, 21), 'into');
    assert.equal(helper.getDropZone(14, 21), 'after');
    assert.equal(helper.getDropZone(21, 21), 'after');
});

test('getDropZone gives a pointer on a boundary to the lower zone', () => {
    assert.equal(helper.getDropZone(10, 30), 'into');
    assert.equal(helper.getDropZone(15.1, 15.1 * 1.51), 'after');
});

test('getDropZone splits a row into halves when it takes no drop into it', () => {
    assert.equal(helper.getDropZone(0, 20, false), 'before');
    assert.equal(helper.getDropZone(9.9, 20, false), 'before');
    assert.equal(helper.getDropZone(10, 20, false), 'after');
    assert.equal(helper.getDropZone(20, 20, false), 'after');
});

test('getFirstChildNode returns the first resource of an expanded folder', () => {
    const placeholder = { nodeType: 1, id: '' };
    const first = { nodeType: 1, id: 'node5' };
    const second = { nodeType: 1, id: 'node6' };
    const anchor = { nextElementSibling: createContainer([placeholder, first, second]) };

    assert.equal(helper.getFirstChildNode(anchor), first);
});

test('getFirstChildNode is null for collapsed, empty and non-folder rows', () => {
    const emptyFolder = { nextElementSibling: createContainer([{ nodeType: 1, id: '' }]) };

    assert.equal(helper.getFirstChildNode({ nextElementSibling: createContainer() }), null);
    assert.equal(helper.getFirstChildNode(emptyFolder), null);
    assert.equal(helper.getFirstChildNode({ nextElementSibling: null, nextSibling: null }), null);
    assert.equal(helper.getFirstChildNode(null), null);
});

test('collectMenuIndex lists resource ids in order and skips placeholders', () => {
    const children = [
        { nodeType: 1, id: '' },
        { nodeType: 1, id: 'node12' },
        { nodeType: 3, id: 'node99' },
        { nodeType: 1, id: 'node3' },
        { nodeType: 1, id: 'nodeX' }
    ];

    assert.deepEqual(helper.collectMenuIndex(children), ['12', '3']);
    assert.deepEqual(helper.collectMenuIndex(null), []);
});
