const test = require('node:test');
const assert = require('node:assert/strict');
const rs = require('../range-select');

// A list of fixed-height rows, enough of the DOM for the selection and the drag.
const HEIGHT = 20;

function listeners() {
    const map = {};
    return {
        addEventListener(type, fn) {
            (map[type] = map[type] || []).push(fn);
        },
        removeEventListener(type, fn) {
            map[type] = (map[type] || []).filter((f) => f !== fn);
        },
        fire(type, event) {
            (map[type] || []).slice().forEach((fn) => fn.call(this, event));
        }
    };
}

function classList() {
    const set = new Set();
    return {
        add: (c) => set.add(c),
        remove: (c) => set.delete(c),
        contains: (c) => set.has(c),
        toggle: (c, on) => (on ? set.add(c) : set.delete(c))
    };
}

function makeList(names) {
    const doc = Object.assign(listeners(), { body: { style: {} } });
    const kids = [];
    // Object.assign would read the getters once instead of copying them
    const container = Object.defineProperties(listeners(), Object.getOwnPropertyDescriptors({
        nodeType: 1,
        ownerDocument: doc,
        classList: classList(),
        get children() {
            return kids.slice();
        },
        insertBefore(node, ref) {
            kids.splice(kids.indexOf(node), 1);
            const at = ref ? kids.indexOf(ref) : -1;
            kids.splice(at === -1 ? kids.length : at, 0, node);
        }
    }));
    names.forEach((name) => {
        const el = Object.defineProperties(listeners(), Object.getOwnPropertyDescriptors({
            id: name,
            nodeType: 1,
            parentNode: container,
            classList: classList(),
            style: {},
            offsetHeight: HEIGHT,
            get offsetTop() {
                return kids.indexOf(el) * HEIGHT;
            },
            get nextSibling() {
                return kids[kids.indexOf(el) + 1] || null;
            }
        }));
        kids.push(el);
    });
    doc.elementFromPoint = (x, y) => kids[Math.floor(y / HEIGHT)] || null;
    const byId = (id) => kids.find((el) => el.id === id);
    const order = () => kids.map((el) => el.id).join('');
    const marked = (cls) => kids.filter((el) => el.classList.contains(cls)).map((el) => el.id).join('');
    return { doc, container, byId, order, marked };
}

function mouse(list, type, id, pageY, extra) {
    const el = id ? list.byId(id) : null;
    const event = Object.assign({ target: el, button: 0, pageX: 5, pageY, clientX: 5, clientY: pageY, preventDefault() {} }, extra || {});
    if (type === 'mousedown') {
        list.container.fire(type, event);
    } else {
        list.doc.fire(type, event);
    }
}

function click(list, id, shift) {
    const y = list.byId(id).offsetTop + 5;
    mouse(list, 'mousedown', id, y, { shiftKey: !!shift });
    mouse(list, 'mouseup', null, y);
}

function drag(list, id, dy) {
    const y = list.byId(id).offsetTop + 5;
    mouse(list, 'mousedown', id, y);
    mouse(list, 'mousemove', null, y + dy);
    mouse(list, 'mouseup', null, y + dy);
}

function touch(list, type, id, x, y) {
    const t = { clientX: x, clientY: y, pageY: y };
    list.container.fire(type, {
        target: id ? list.byId(id) : null,
        touches: type === 'touchend' ? [] : [t],
        cancelable: true,
        preventDefault() {}
    });
}

test('the selection is the run between the anchor and the last item picked', () => {
    assert.deepEqual(rs.range(4, 1), { start: 1, end: 4 });
    assert.deepEqual(rs.pick(null, 2, true), { anchor: 2, focus: 2 });
    assert.deepEqual(rs.pick({ anchor: 2, focus: 2 }, 5, true), { anchor: 2, focus: 5 });
    assert.deepEqual(rs.pick({ anchor: 2, focus: 5 }, 0, true), { anchor: 2, focus: 0 });
    // a click without Shift starts again
    assert.deepEqual(rs.pick({ anchor: 2, focus: 5 }, 3, false), { anchor: 3, focus: 3 });
    assert.deepEqual(rs.all(4), { anchor: 0, focus: 3 });
    assert.equal(rs.all(0), null);
});

test('a touch is a sideways swipe or a vertical move only once it has moved', () => {
    assert.equal(rs.gesture(3, 4), null);
    assert.equal(rs.gesture(12, 3), 'sideways');
    assert.equal(rs.gesture(2, -15), 'vertical');
    assert.equal(rs.blockStep(9, 20, 20), 0);
    assert.equal(rs.blockStep(10, 20, 20), 1);
    assert.equal(rs.blockStep(-10, 20, 20), -1);
    assert.equal(rs.blockStep(50, 20, null), 0);
    assert.equal(rs.blockStep(-50, null, 20), 0);
});

test('Ctrl+A, Cmd+A, Del and Esc, but not while typing', () => {
    const body = { tagName: 'TR' };
    assert.equal(rs.shortcut({ key: 'a', ctrlKey: true, target: body }), 'all');
    assert.equal(rs.shortcut({ key: 'A', metaKey: true, target: body }), 'all');
    assert.equal(rs.shortcut({ key: 'ф', code: 'KeyA', ctrlKey: true, target: body }), 'all');
    assert.equal(rs.shortcut({ key: 'Delete', target: body }), 'delete');
    assert.equal(rs.shortcut({ key: 'Escape', target: body }), 'clear');
    assert.equal(rs.shortcut({ key: 'a', target: body }), null);
    assert.equal(rs.shortcut({ key: 'Delete', ctrlKey: true, target: body }), null);
    assert.equal(rs.shortcut({ key: 'a', ctrlKey: true, target: { tagName: 'TEXTAREA' } }), null);
    assert.equal(rs.shortcut({ key: 'Delete', target: { tagName: 'INPUT' } }), null);
    assert.equal(rs.shortcut({ key: 'a', ctrlKey: true, target: { tagName: 'DIV', isContentEditable: true } }), null);
});

test('Shift+click selects a run and a plain click drops it', () => {
    const list = makeList(['A', 'B', 'C', 'D', 'E', 'F']);
    rs.sortable(list.container);

    click(list, 'B');
    click(list, 'E', true);
    assert.equal(list.marked('range-selected'), 'BCDE');
    assert.equal(list.marked('range-first'), 'B');

    click(list, 'C', true);
    assert.equal(list.marked('range-selected'), 'BC');

    click(list, 'D');
    assert.equal(list.marked('range-selected'), 'D');
    assert.equal(list.marked('range-first'), '');
});

test('a selected run is dragged as one block and every position follows', () => {
    const list = makeList(['A', 'B', 'C', 'D', 'E', 'F']);
    const completed = [];
    rs.sortable(list.container, { complete: (block) => completed.push(block.map((el) => el.id).join('')) });

    click(list, 'B');
    click(list, 'D', true);
    // grabbed by its middle item, two rows down
    drag(list, 'C', 40);
    assert.equal(list.order(), 'AEFBCD');
    assert.deepEqual(completed, ['BCD']);
    assert.equal(list.marked('range-selected'), 'BCD');
    assert.equal(list.marked('ghost'), '');
    assert.equal(list.byId('C').style.transform, '');

    // and back up past the top: it stops there
    drag(list, 'D', -200);
    assert.equal(list.order(), 'BCDAEF');
});

test('dragging an item outside the run drops the run and moves that item alone', () => {
    const list = makeList(['A', 'B', 'C', 'D', 'E']);
    rs.sortable(list.container);

    click(list, 'A');
    click(list, 'B', true);
    drag(list, 'D', 20);
    assert.equal(list.order(), 'ABCED');
    assert.equal(list.marked('range-selected'), 'D');
});

test('a vertical touch drags, a sideways swipe selects the rows the finger crosses', () => {
    const list = makeList(['A', 'B', 'C', 'D', 'E']);
    rs.sortable(list.container);

    touch(list, 'touchstart', 'B', 50, 25);
    touch(list, 'touchmove', null, 70, 27);
    touch(list, 'touchmove', null, 70, 85);
    touch(list, 'touchend', null);
    assert.equal(list.marked('range-selected'), 'BCDE');
    assert.equal(list.order(), 'ABCDE');

    touch(list, 'touchstart', 'C', 50, 45);
    touch(list, 'touchmove', null, 50, 70);
    touch(list, 'touchend', null);
    assert.equal(list.order(), 'ABCDE', 'nothing below the group to pass');

    touch(list, 'touchstart', 'C', 50, 45);
    touch(list, 'touchmove', null, 50, 20);
    touch(list, 'touchend', null);
    assert.equal(list.order(), 'BCDEA');

    // a tap drops the group
    touch(list, 'touchstart', 'A', 50, 85);
    touch(list, 'touchend', null);
    assert.equal(list.marked('range-selected'), 'A');
});

test('a touch held still starts a selection that the finger then extends', async () => {
    const list = makeList(['A', 'B', 'C', 'D']);
    rs.sortable(list.container, { holdDelay: 5 });

    touch(list, 'touchstart', 'D', 50, 65);
    await new Promise((resolve) => setTimeout(resolve, 20));
    assert.equal(list.marked('range-selected'), 'D');
    touch(list, 'touchmove', null, 50, 25);
    touch(list, 'touchend', null);
    assert.equal(list.marked('range-selected'), 'BCD');
    assert.equal(list.order(), 'ABCD');
});

test('the keyboard selects every entry and hands the run to the delete action', () => {
    const list = makeList(['A', 'B', 'C']);
    const deleted = [];
    const selection = rs.attach(list.container, {
        keyboard: true,
        pointer: false,
        onDelete: (items) => deleted.push(items.map((el) => el.id).join(''))
    });
    const key = (props) => {
        let prevented = false;
        list.doc.fire('keydown', Object.assign({ target: { tagName: 'BODY' }, preventDefault: () => { prevented = true; } }, props));
        return prevented;
    };

    assert.equal(key({ key: 'Delete' }), false, 'nothing selected, nothing to delete');
    assert.equal(key({ key: 'a', ctrlKey: true }), true);
    assert.equal(list.marked('range-selected'), 'ABC');
    assert.equal(key({ key: 'Delete' }), true);
    assert.deepEqual(deleted, ['ABC']);

    key({ key: 'Escape' });
    assert.deepEqual(selection.selected(), []);
    // typing in the editor below the list is left alone
    assert.equal(key({ key: 'a', ctrlKey: true, target: { tagName: 'TEXTAREA' } }), false);
    assert.deepEqual(selection.selected(), []);
});

test('a click or a tap outside the items drops the selection, a scroll does not', () => {
    const list = makeList(['A', 'B', 'C', 'D']);
    rs.sortable(list.container);
    const page = { parentNode: null };
    const press = (target, extra) => list.doc.fire('mousedown', Object.assign({ target, button: 0, clientX: 5, clientY: 5 }, extra || {}));
    const finger = (type, target, y) => list.doc.fire(type, { target, touches: type === 'touchend' ? [] : [{ clientX: 5, clientY: y }] });
    const select = () => {
        click(list, 'B');
        click(list, 'D', true);
        assert.equal(list.marked('range-selected'), 'BCD');
    };

    select();
    press(list.byId('C'));
    press(page, { button: 2 });
    assert.equal(list.marked('range-selected'), 'BCD', 'a press on an item or a right click keeps the run');
    press(page);
    assert.equal(list.marked('range-selected'), '');
    assert.equal(list.container.classList.contains('range-group'), false);

    select();
    finger('touchstart', page, 100);
    finger('touchmove', page, 160);
    finger('touchend', page);
    assert.equal(list.marked('range-selected'), 'BCD', 'the page was scrolled');
    finger('touchstart', page, 100);
    finger('touchend', page);
    assert.equal(list.marked('range-selected'), '');

    // the gap between the rows is outside them too
    select();
    press(list.container);
    finger('touchstart', list.container, 100);
    finger('touchend', list.container);
    assert.equal(list.marked('range-selected'), '');
});

test('the scroll speed grows towards either edge and is 0 in between', () => {
    assert.equal(rs.edgeSpeed(300, 0, 600, 60, 18), 0);
    assert.equal(rs.edgeSpeed(570, 0, 600, 60, 18), 9);
    assert.equal(rs.edgeSpeed(600, 0, 600, 60, 18), 18);
    assert.equal(rs.edgeSpeed(700, 0, 600, 60, 18), 18, 'past the edge is full speed');
    assert.equal(rs.edgeSpeed(30, 0, 600, 60, 18), -9);
    assert.equal(rs.edgeSpeed(-5, 0, 600, 60, 18), -18);
    // a short area keeps a still zone in the middle
    assert.equal(rs.edgeSpeed(50, 0, 100, 60, 18), 0);
});

// A window 60px high over 10 rows of 20px: the drag scrolls it while the pointer rests at an edge.
function withWindow(list) {
    const frames = [];
    const win = {
        pageYOffset: 0,
        innerHeight: 60,
        scrollBy(x, y) {
            this.pageYOffset = Math.max(0, Math.min(200 - 60, this.pageYOffset + y));
        },
        requestAnimationFrame(fn) {
            frames.push(fn);
            return frames.length;
        },
        cancelAnimationFrame() {
            frames.length = 0;
        },
        getComputedStyle: () => ({ overflowY: 'visible' })
    };
    list.doc.defaultView = win;
    const run = (n) => {
        for (let i = 0; i < n && frames.length; i++) {
            frames.shift()();
        }
    };
    return { win, frames, run };
}

test('a block dragged to the bottom edge scrolls the page down and keeps moving with it', () => {
    const list = makeList(['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J']);
    const { win, frames, run } = withWindow(list);
    rs.sortable(list.container);

    click(list, 'A');
    click(list, 'B', true);
    // press B, move to the bottom edge of the 60px window and hold still there
    list.container.fire('mousedown', { target: list.byId('B'), button: 0, clientX: 5, clientY: 25, preventDefault() {} });
    list.doc.fire('mousemove', { clientX: 5, clientY: 58 });
    assert.equal(list.order(), 'CDABEFGHIJ');
    run(100);
    assert.equal(win.pageYOffset, 140, 'scrolled to the end');
    assert.equal(frames.length, 0, 'stops when nothing is left to scroll');
    // the pointer is now over the last row: the block went to the end of the list
    assert.equal(list.order(), 'CDEFGHIJAB');
    list.doc.fire('mouseup', {});
    assert.equal(list.marked('range-selected'), 'AB');
});

test('a block dragged to the top edge of a scrolled page scrolls it up', () => {
    const list = makeList(['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J']);
    const { win, run } = withWindow(list);
    win.pageYOffset = 140;
    rs.sortable(list.container);

    // row I is at 160..180, 20..40 in the window
    list.container.fire('mousedown', { target: list.byId('I'), button: 0, clientX: 5, clientY: 25, preventDefault() {} });
    list.doc.fire('mousemove', { clientX: 5, clientY: 2 });
    run(100);
    assert.equal(win.pageYOffset, 0);
    assert.equal(list.order(), 'IABCDEFGHJ');
    list.doc.fire('mouseup', {});
});

test('a touch selection held at the bottom edge scrolls and takes in the rows that come up', () => {
    const list = makeList(['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J']);
    const { win, run } = withWindow(list);
    // the fake hit test is in page coordinates; the touch reports window ones
    list.doc.elementFromPoint = (x, y) => [...list.container.children][Math.floor((y + win.pageYOffset) / HEIGHT)] || null;
    const selection = rs.attach(list.container, { sideSwipe: true });

    touch(list, 'touchstart', 'B', 5, 25);
    touch(list, 'touchmove', 'B', 25, 26);
    touch(list, 'touchmove', 'B', 25, 58);
    assert.equal(list.marked('range-selected'), 'BC');
    run(5);
    assert.ok(win.pageYOffset > 0);
    assert.ok(selection.selected().length > 2, list.marked('range-selected'));
    touch(list, 'touchend', null, 0, 0);
    const scrolled = win.pageYOffset;
    run(5);
    assert.equal(win.pageYOffset, scrolled, 'lifting the finger stops the scroll');
});

function ctrlClick(list, id, key) {
    const y = list.byId(id).offsetTop + 5;
    mouse(list, 'mousedown', id, y, { [key || 'ctrlKey']: true });
    mouse(list, 'mouseup', null, y);
}

test('Ctrl+click (Cmd+click) adds and removes single items, a plain click starts again', () => {
    const list = makeList(['A', 'B', 'C', 'D', 'E', 'F', 'G']);
    rs.sortable(list.container);

    click(list, 'B');
    ctrlClick(list, 'D');
    ctrlClick(list, 'F', 'metaKey');
    assert.equal(list.marked('range-selected'), 'BDF');
    assert.equal(list.marked('range-first'), 'B');
    ctrlClick(list, 'D');
    assert.equal(list.marked('range-selected'), 'BF');
    // Shift extends a run from the last item added
    ctrlClick(list, 'D');
    click(list, 'E', true);
    assert.equal(list.marked('range-selected'), 'DE');
    click(list, 'C');
    assert.equal(list.marked('range-selected'), 'C');
    // a Ctrl press that moves does not drag
    ctrlClick(list, 'E');
    mouse(list, 'mousedown', 'E', 85, { ctrlKey: true });
    mouse(list, 'mousemove', null, 125);
    mouse(list, 'mouseup', null, 125);
    assert.equal(list.order(), 'ABCDEFG');
});

test('dragging one of scattered items gathers the others to it, pushing the items between aside', () => {
    const list = makeList(['A', 'B', 'C', 'D', 'E', 'F', 'G']);
    const completed = [];
    rs.sortable(list.container, { complete: (block) => completed.push(block.map((el) => el.id).join('')) });

    click(list, 'B');
    ctrlClick(list, 'D');
    ctrlClick(list, 'F');
    // a short move of D: B comes down above it, F up under it; C goes up, E down
    drag(list, 'D', 4);
    assert.equal(list.order(), 'ACBDFEG');
    assert.equal(list.marked('range-selected'), 'BDF');
    assert.deepEqual(completed, ['BDF']);

    // from here on it is an ordinary run: dragged two rows up it passes C and A
    drag(list, 'D', -40);
    assert.equal(list.order(), 'BDFACEG');
    assert.equal(list.marked('range-selected'), 'BDF');
});

test('the gathered block follows the pointer in the same drag', () => {
    const list = makeList(['A', 'B', 'C', 'D', 'E', 'F', 'G']);
    rs.sortable(list.container);

    click(list, 'A');
    ctrlClick(list, 'C');
    ctrlClick(list, 'G');
    // grab C and pull it down past D and E: the block A C G is gathered at C, then moves down two rows
    drag(list, 'C', 40);
    assert.equal(list.order(), 'BDEACGF');
    assert.equal(list.marked('range-selected'), 'ACG');
});

test('in a list without sorting Ctrl+click picks scattered rows for one delete', () => {
    const list = makeList(['A', 'B', 'C', 'D', 'E']);
    const deleted = [];
    const changes = [];
    rs.attach(list.container, {
        keyboard: true,
        onChange: (items) => changes.push(items.map((el) => el.id).join('')),
        onDelete: (items) => deleted.push(items.map((el) => el.id).join(''))
    });
    const rowClick = (id, extra) => list.container.fire('click', Object.assign({ target: list.byId(id) }, extra || {}));

    rowClick('D');
    rowClick('A', { ctrlKey: true });
    rowClick('E', { metaKey: true });
    assert.equal(list.marked('range-selected'), 'ADE');
    // the top selected row carries the one trash icon of the group
    assert.equal(list.marked('range-first'), 'A');
    assert.equal(changes[changes.length - 1], 'ADE');
    rowClick('D', { ctrlKey: true });
    assert.equal(list.marked('range-selected'), 'AE');
    list.doc.fire('keydown', { key: 'Delete', target: { tagName: 'BODY' }, preventDefault() {} });
    assert.deepEqual(deleted, ['AE']);
    rowClick('B');
    assert.equal(list.marked('range-selected'), 'B');
});
