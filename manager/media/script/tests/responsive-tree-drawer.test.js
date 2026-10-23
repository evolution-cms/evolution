const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

function setup(width) {
    const classes = new Set();
    const stored = new Map();
    const tree = {offsetWidth: 320, style: {}};
    const document = {body: {classList: {contains: c => classes.has(c), add: c => classes.add(c), remove: c => classes.delete(c)}},
        addEventListener() {}, getElementById: () => tree};
    const evo = {config: {theme: 'default', global_tabs: false}, extended(methods) {Object.assign(this, methods);}};
    const window = {innerWidth: width, main: {frameElement: {}}, addEventListener() {}};
    const localStorage = {setItem: (k, v) => stored.set(k, v), getItem: k => stored.get(k)};
    const context = {evo, window, document, localStorage, Element: function () {}, navigator: {userAgent: 'Desktop browser', appVersion: ''}};
    vm.createContext(context);
    vm.runInContext(fs.readFileSync(path.join(__dirname, '../../style/default/js/evo.js'), 'utf8'), context);
    evo.pxToRem = n => n / 16;
    return {evo, classes, document, window};
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
