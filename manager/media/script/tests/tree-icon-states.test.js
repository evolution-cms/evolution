const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

function manager(document) {
    document.addEventListener = () => {};
    const evo = {config: {theme: 'default'}, extended(methods) {Object.assign(this, methods);}};
    const window = {evo, addEventListener() {}};
    const context = {window, document, evo, navigator: {userAgent: '', appVersion: ''},
        Element: function () {}, localStorage: {}, console};
    vm.createContext(context);
    vm.runInContext(fs.readFileSync(path.join(__dirname, '../../style/default/js/evo.js'), 'utf8'), context);
    return evo;
}

test('trash switches between full/actionable and empty/disabled without losing its labels', () => {
    const attributes = {}, classes = new Set();
    const bin = {dataset: {}, classList: {add: c => classes.add(c), remove: c => classes.delete(c)},
        setAttribute: (name, value) => attributes[name] = value};
    const evo = manager({getElementById: id => id === 'treeMenu_emptytrash' ? bin : {querySelectorAll: () => []}});
    evo.style = {icon_trash: '<svg>full</svg>', icon_trash_alt: '<svg>empty</svg>'};
    evo.lang = {empty_recycle_bin: 'Empty recycle bin', empty_recycle_bin_empty: 'Recycle bin is empty'};
    let clicks = 0;
    evo.tree.emptyTrash = () => clicks++;
    evo.tree.showBin(true);
    assert.equal(bin.innerHTML, evo.style.icon_trash);
    assert.equal(bin.title, evo.lang.empty_recycle_bin);
    assert.equal(attributes['aria-disabled'], 'false');
    bin.onclick();
    assert.equal(clicks, 1);
    evo.tree.showBin(false);
    assert.equal(bin.innerHTML, evo.style.icon_trash_alt);
    assert.equal(bin.title, evo.lang.empty_recycle_bin_empty);
    assert.equal(attributes['aria-disabled'], 'true');
    assert.equal(bin.onclick, null);
    assert.ok(classes.has('disabled'));
});

test('opening and closing a private folder replaces only its glyph, preserving privacy and lock overlays', () => {
    const icon = {innerHTML: 'closed'}, toggle = {innerHTML: 'right'};
    const overlay = {innerHTML: '<svg>editing lock</svg>'};
    const children = {innerHTML: '', style: {}, firstChild: {}};
    const row = {dataset: {private: '1', iconFolderOpen: 'open', iconFolderClose: 'closed',
        iconCollapsed: 'down', iconExpanded: 'right'}, nextSibling: children,
        querySelector: s => s === '.icon' ? icon : s === '.toggle' ? toggle : overlay};
    const evo = manager({getElementById: id => id === 'node42' ? {firstChild: row} : {classList: {add() {}}}});
    evo.lang = {};
    evo.openedArray = [];
    evo.post = () => {};
    evo.tree.getFolderState = () => '';
    evo.tree.saveFolderState = () => {};
    evo.animate = () => {};
    const event = {stopPropagation() {}, preventDefault() {}};
    evo.tree.toggleNode(event, 42);
    assert.equal(icon.innerHTML, 'open');
    assert.equal(toggle.innerHTML, 'down');
    assert.equal(evo.openedArray[42], 1);
    children.innerHTML = '<div>children</div>';
    evo.tree.toggleNode(event, 42);
    assert.equal(icon.innerHTML, 'closed');
    assert.equal(toggle.innerHTML, 'right');
    assert.equal(evo.openedArray[42], undefined);
    assert.equal(row.dataset.private, '1');
    assert.equal(overlay.innerHTML, '<svg>editing lock</svg>');
});
