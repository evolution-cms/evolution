const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const helper = require('../document-save-helper');

// "Save and continue" saves over XHR and keeps the editor open, so the tab frame is not reloaded
// and its onload never clears the "changed" mark; the editor announces the save instead.
const root = path.join(__dirname, '..', '..', '..', '..');
const evoSource = fs.readFileSync(path.join(root, 'manager', 'media', 'style', 'default', 'js', 'evo.js'), 'utf8');
const editorSource = fs.readFileSync(path.join(root, 'manager', 'actions', 'mutate_content.dynamic.php'), 'utf8');

function loadWatchChanges() {
    const match = evoSource.match(/watchChanges: (function \(tabWindow\) \{[\s\S]*?\n {16}\}),\n/);
    assert.ok(match, 'tab.watchChanges is defined');
    return new Function('return (' + match[1] + ');')();
}

function tab() {
    const classes = new Set();
    return {
        watchChanges: loadWatchChanges(),
        tab: { classList: { add: (c) => classes.add(c), remove: (c) => classes.delete(c), contains: (c) => classes.has(c) } },
        classes,
    };
}

function frame() {
    const doc = new EventTarget();
    const live = { click: new Set(), keyup: new Set() };
    const add = doc.addEventListener.bind(doc);
    const remove = doc.removeEventListener.bind(doc);
    doc.addEventListener = (type, fn, o) => { live[type].add(fn); add(type, fn, o); };
    doc.removeEventListener = (type, fn, o) => { live[type].delete(fn); remove(type, fn, o); };
    const listeners = () => ({ click: live.click.size, keyup: live.keyup.size });
    return { win: { document: doc, documentDirty: false }, listeners };
}

test('the tab is marked changed only once the editor is dirty', () => {
    const t = tab();
    const { win, listeners } = frame();
    t.watchChanges(win);

    win.document.dispatchEvent(new Event('click'));
    assert.equal(t.classes.has('changed'), false);

    win.documentDirty = true;
    win.document.dispatchEvent(new Event('keyup'));
    assert.equal(t.classes.has('changed'), true);
    assert.deepEqual(listeners(), { click: 0, keyup: 0 });
});

test('watching again after a save does not stack listeners and marks the next edit', () => {
    const t = tab();
    const { win, listeners } = frame();
    t.watchChanges(win);
    t.watchChanges(win);
    assert.deepEqual(listeners(), { click: 1, keyup: 1 });

    win.documentDirty = true;
    win.document.dispatchEvent(new Event('click'));
    assert.equal(t.classes.has('changed'), true);

    // what the evo:saved handler does after a successful save
    win.documentDirty = false;
    t.tab.classList.remove('changed');
    t.watchChanges(win);
    assert.deepEqual(listeners(), { click: 1, keyup: 1 });

    win.documentDirty = true;
    win.document.dispatchEvent(new Event('keyup'));
    assert.equal(t.classes.has('changed'), true);
});

test('the editor announces a successful in-place save and the tab listens for it', () => {
    assert.match(editorSource, /ajaxSaveHelper\.applyResult\([^\n]*\);\s*ajaxSaveHelper\.announceSaved\(document\);/);
    const handler = evoSource.match(/addEventListener\('([^']+)', function \(\) \{\s*if \(!tabWindow\.documentDirty\) \{\s*s\.tab\.classList\.remove\('changed'\);\s*s\.watchChanges\(tabWindow\);/);
    assert.ok(handler, 'evo.js clears the mark on a save event');
    assert.equal(handler[1], helper.savedEvent);
});
