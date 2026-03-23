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
