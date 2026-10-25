const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

function setup(width) {
    const classes = new Set();
    const stored = new Map();
    const tree = {style: {}, get offsetWidth() {
        return width <= 840 ? 320 : this.style.width === undefined ? 320 : parseFloat(this.style.width) * 16;
    }};
    const document = {body: {classList: {contains: c => classes.has(c), add: c => classes.add(c), remove: c => classes.delete(c)}},
        addEventListener() {}, getElementById: () => tree};
    const evo = {config: {theme: 'default', global_tabs: false}, extended(methods) {Object.assign(this, methods);}};
    const window = {innerWidth: width, main: {frameElement: {}}, addEventListener() {}};
    const localStorage = {setItem: (k, v) => stored.set(k, v), getItem: k => stored.get(k)};
    const context = {evo, window, document, localStorage, modxMainTargetLinkHelper: require('../main-target-link-helper.js'), Element: function () {}, navigator: {userAgent: 'Desktop browser', appVersion: ''}};
    vm.createContext(context);
    vm.runInContext(fs.readFileSync(path.join(__dirname, '../../style/default/js/evo.js'), 'utf8'), context);
    evo.pxToRem = n => n / 16;
    evo.remToPx = n => n * 16;
    return {evo, classes, document, window, tree, stored};
}

for (const width of [480, 746, 840]) {
    test(`navigation closes the tree drawer at ${width}px in a desktop browser`, () => {
        const {evo, classes, window} = setup(width);
        evo.tabs({url: '?a=76&tab=0'});
        assert.ok(classes.has('sidebar-closed'));
        assert.equal(window.main.frameElement.src, '?a=76&tab=0');
        evo.resizer.closeDrawer();
        assert.ok(classes.has('sidebar-closed'), 'repeated navigation must not reopen the drawer');
    });
}

test('restoring a session leaves an explicitly opened narrow tree alone', () => {
    const {evo, classes} = setup(746);
    evo.tabs({url: '?a=76', restoring: true});
    assert.equal(classes.has('sidebar-closed'), false);
});

test('desktop navigation retains a docked tree above the drawer breakpoint', () => {
    const {evo, classes, document} = setup(841);
    evo.tabs({url: '?a=99'});
    assert.equal(classes.has('sidebar-closed'), false);
    assert.equal(document.cookie, undefined);
});

for (const width of [480, 1200]) {
    test(`the toggle reopens a tree restored at zero width and keeps the manual choice at ${width}px`, () => {
        const {evo, classes, tree, stored} = setup(width);
        evo.config.tree_width = 0; // A collapsed sidebar cookie on frame reload.
        evo.config.tree_width_default = 18;
        stored.set('EVO_widthSideBar', '0');
        tree.style.width = '0rem';
        classes.add('sidebar-closed');
        evo.moduleViewport.wantsHiddenTree = () => true;
        evo.resizer.toggle();
        assert.equal(classes.has('sidebar-closed'), false);
        assert.ok(tree.offsetWidth >= 288, 'the configured rem width becomes a usable pixel width');
        evo.moduleViewport.syncFrame({});
        assert.equal(classes.has('sidebar-closed'), false, 'module auto-hide must respect a manual open');
        evo.resizer.toggle();
        assert.equal(classes.has('sidebar-closed'), true);
        evo.resizer.toggle();
        assert.equal(classes.has('sidebar-closed'), false);
    });
}

test('leaving a full-width module restores the configured width even with an empty saved width', () => {
    const {evo, tree} = setup(1200);
    evo.config.tree_width = 0;
    evo.config.tree_width_default = 18.5;
    evo.moduleViewport.restoreTree();
    assert.equal(tree.offsetWidth, 296);
});

test('the global tab click listener does not immediately close a manually opened mobile drawer', () => {
    const {evo, classes, document} = setup(480);
    evo.config.global_tabs = true;
    classes.add('sidebar-closed');
    evo.resizer.toggle();
    evo.tabs({currentTarget: document, target: {closest: () => ({target: '', href: 'javascript:;'})}});
    assert.equal(classes.has('sidebar-closed'), false);
    evo.tabs({currentTarget: document, target: {closest: () => null}});
    assert.equal(classes.has('sidebar-closed'), false, 'typing/clicking menu controls is not navigation');
    evo.tabs({currentTarget: document, defaultPrevented: true, target: {closest: () => ({target: 'main', href: '?a=76'})}});
    assert.equal(classes.has('sidebar-closed'), false, 'an inline/plugin handler can cancel a link without closing the drawer');
});
