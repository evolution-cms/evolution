(function (root, factory) {
    var exported = factory();

    if (typeof module === 'object' && module.exports) {
        module.exports = exported;
    }

    root.evoRangeSelect = exported;
}(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    'use strict';

    var HOLD_DELAY = 450;
    var MOVE_THRESHOLD = 8;
    var IGNORE = 'a, button, input, select, textarea, label';

    /**
     * The selection is always one run of neighbours: everything between the anchor (the item picked
     * without Shift) and the focus (the last item reached). Indexes are positions in the list as it is now.
     */
    function range(anchor, focus) {
        return { start: Math.min(anchor, focus), end: Math.max(anchor, focus) };
    }

    /** A plain pick starts a new selection; Shift extends the current one from its anchor. */
    function pick(state, index, shift) {
        if (shift && state) {
            return { anchor: state.anchor, focus: index };
        }

        return { anchor: index, focus: index };
    }

    function all(count) {
        return count > 0 ? { anchor: 0, focus: count - 1 } : null;
    }

    /** What a touch that has moved by dx/dy turns into: nothing yet, a sideways swipe or a vertical move. */
    function gesture(dx, dy, threshold) {
        threshold = threshold === undefined ? MOVE_THRESHOLD : threshold;
        if (Math.max(Math.abs(dx), Math.abs(dy)) < threshold) {
            return null;
        }

        return Math.abs(dx) > Math.abs(dy) ? 'sideways' : 'vertical';
    }

    /**
     * One step of a dragged block: 1 once it passes the middle of the item below it, -1 of the one above,
     * 0 otherwise. A missing neighbour has no height (null).
     */
    function blockStep(dy, aboveHeight, belowHeight) {
        if (belowHeight !== null && belowHeight !== undefined && dy >= belowHeight / 2) {
            return 1;
        }
        if (aboveHeight !== null && aboveHeight !== undefined && dy <= -aboveHeight / 2) {
            return -1;
        }

        return 0;
    }

    /**
     * Pixels to scroll per frame while a pointer at y drags or selects: up near the top of the visible
     * area, down near its bottom, faster the closer to (or the further past) the edge, 0 in between.
     */
    function edgeSpeed(y, top, bottom, zone, max) {
        zone = Math.max(1, Math.min(zone, (bottom - top) / 2));
        if (y < top + zone) {
            return -Math.ceil(max * Math.min(1, (top + zone - y) / zone));
        }
        if (y > bottom - zone) {
            return Math.ceil(max * Math.min(1, (y - bottom + zone) / zone));
        }

        return 0;
    }

    function isEditable(target) {
        if (!target) {
            return false;
        }
        var tag = (target.tagName || '').toUpperCase();

        return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || target.isContentEditable === true;
    }

    /** The list shortcut a key press asks for: 'all' (Ctrl/Cmd+A), 'delete' (Del), 'clear' (Esc) or null. */
    function shortcut(event) {
        if (!event || isEditable(event.target) || event.altKey) {
            return null;
        }
        var key = event.key || '';
        if ((event.ctrlKey || event.metaKey) && !event.shiftKey && (key === 'a' || key === 'A' || event.code === 'KeyA')) {
            return 'all';
        }
        if (event.ctrlKey || event.metaKey) {
            return null;
        }
        if (key === 'Delete' || key === 'Del') {
            return 'delete';
        }
        if (key === 'Escape' || key === 'Esc') {
            return 'clear';
        }

        return null;
    }

    function toArray(list) {
        return Array.prototype.slice.call(list || []);
    }

    function closest(el, selector, stop) {
        while (el && el !== stop && el.nodeType === 1) {
            if (el.matches && el.matches(selector)) {
                return el;
            }
            el = el.parentNode;
        }

        return null;
    }

    /**
     * Keeps the selected items of container: a run of neighbours, or with pick(el, false, true) (Ctrl+click,
     * where the caller allows it) any set of them. options: items() - the selectable elements in list
     * order (default: the children), selectedClass, firstClass (the top selected item), onChange(items),
     * pointer (false: the caller drives pick()/extendTo() itself), keyboard + onDelete(items), sideSwipe
     * (a sideways touch starts a selection at once; otherwise a touch is held first, so the page still scrolls).
     */
    function attach(container, options) {
        options = options || {};
        var selectedClass = options.selectedClass || 'range-selected';
        var firstClass = options.firstClass || 'range-first';
        var ignore = options.ignore || IGNORE;
        var anchor = null;
        var focus = null;
        var members = [];

        function items() {
            return toArray(options.items ? options.items() : container.children);
        }

        function itemOf(target) {
            var list = items();
            while (target && target !== container) {
                if (list.indexOf(target) !== -1) {
                    return target;
                }
                target = target.parentNode;
            }

            return null;
        }

        /** The selected items in their current list order. */
        function selected() {
            return items().filter(function (el) {
                return members.indexOf(el) !== -1;
            });
        }

        function runTo(el) {
            var list = items();
            var r = range(list.indexOf(anchor), list.indexOf(el));
            focus = el;
            members = list.slice(r.start, r.end + 1);
        }

        function render() {
            var list = items();
            var run = selected();
            list.forEach(function (el) {
                el.classList.toggle(selectedClass, run.indexOf(el) !== -1);
                el.classList.toggle(firstClass, run.length > 1 && el === run[0]);
            });
            container.classList.toggle('range-group', run.length > 1);
            if (typeof options.onChange === 'function') {
                options.onChange(run);
            }
        }

        var api = {
            items: items,
            itemOf: itemOf,
            selected: selected,
            includes: function (el) {
                return selected().indexOf(el) !== -1;
            },
            /** A plain pick selects el alone, Shift the run from the anchor to el, toggle adds or removes el. */
            pick: function (el, shift, toggle) {
                var list = items();
                if (toggle && !shift) {
                    var at = members.indexOf(el);
                    if (at === -1) {
                        members.push(el);
                        anchor = focus = el;
                    } else {
                        members.splice(at, 1);
                        if (anchor === el) {
                            var rest = selected();
                            anchor = focus = rest.length ? rest[rest.length - 1] : null;
                        }
                    }
                    render();
                    return;
                }
                var state = pick(list.indexOf(anchor) === -1 ? null : { anchor: list.indexOf(anchor), focus: list.indexOf(focus) },
                    list.indexOf(el), !!shift);
                anchor = list[state.anchor] || null;
                runTo(list[state.focus] || null);
                render();
            },
            extendTo: function (el) {
                if (el && anchor && el !== focus) {
                    runTo(el);
                    render();
                }
            },
            selectAll: function () {
                var list = items();
                var state = all(list.length);
                anchor = state ? list[state.anchor] : null;
                focus = state ? list[state.focus] : null;
                members = list.slice();
                render();
            },
            clear: function () {
                anchor = focus = null;
                members = [];
                render();
            },
            render: render
        };

        if (options.pointer !== false) {
            bindPointer(container, api, options, ignore);
        }
        if (options.clearOutside !== false) {
            bindOutside(container.ownerDocument || document, api);
        }
        if (options.keyboard) {
            (container.ownerDocument || document).addEventListener('keydown', function (e) {
                var action = shortcut(e);
                if (action === 'all' && items().length) {
                    e.preventDefault();
                    api.selectAll();
                } else if (action === 'delete' && selected().length && typeof options.onDelete === 'function') {
                    e.preventDefault();
                    options.onDelete(selected());
                } else if (action === 'clear' && selected().length) {
                    api.clear();
                }
            });
        }

        return api;
    }

    /** The nearest ancestor that scrolls the list, or null for the window. */
    function scrollBox(el, win) {
        var doc = el.ownerDocument;
        for (var node = el.parentNode; node && node.nodeType === 1 && node !== doc.body && node !== doc.documentElement; node = node.parentNode) {
            var overflow = win.getComputedStyle(node).overflowY;
            if ((overflow === 'auto' || overflow === 'scroll' || overflow === 'overlay') && node.scrollHeight > node.clientHeight) {
                return node;
            }
        }

        return null;
    }

    /**
     * Scrolls the list's page (or its scrolling box) while the pointer of a drag or a touch selection stays
     * near the top or bottom edge, frame after frame, until the pointer leaves the edge or nothing is left to
     * scroll. onScroll() runs after every step, so the drag or the selection follows the content under it.
     * offset() is the scroll distance to add to a clientY to get a position that scrolling does not change.
     */
    function edgeScroller(container, onScroll) {
        var doc = container.ownerDocument || document;
        var win = doc.defaultView || null;
        var box = null;
        var y = null;
        var frame = null;
        var later = win && win.requestAnimationFrame ? win.requestAnimationFrame.bind(win) : function (fn) {
            return setTimeout(fn, 16);
        };
        var cancel = win && win.cancelAnimationFrame ? win.cancelAnimationFrame.bind(win) : clearTimeout;

        function offset() {
            return (win ? win.pageYOffset || 0 : 0) + (box ? box.scrollTop : 0);
        }

        function tick() {
            frame = null;
            if (y === null) {
                return;
            }
            var top = 0;
            var bottom = win.innerHeight;
            if (box) {
                var rect = box.getBoundingClientRect();
                top = Math.max(top, rect.top);
                bottom = Math.min(bottom, rect.bottom);
            }
            var step = edgeSpeed(y, top, bottom, Math.min(80, Math.max(32, (bottom - top) * 0.12)), 18);
            if (!step) {
                return;
            }
            var before = offset();
            if (box) {
                box.scrollTop += step;
            } else {
                win.scrollBy(0, step);
            }
            if (offset() !== before) {
                onScroll();
                frame = later(tick);
            }
        }

        return {
            offset: offset,
            start: function () {
                box = win ? scrollBox(container, win) : null;
            },
            update: function (clientY) {
                y = clientY;
                if (win && frame === null) {
                    frame = later(tick);
                }
            },
            stop: function () {
                y = null;
                if (frame !== null) {
                    cancel(frame);
                    frame = null;
                }
            }
        };
    }

    function itemAtPoint(api, doc, x, y) {
        var hit = doc.elementFromPoint ? doc.elementFromPoint(x, y) : null;

        return hit ? api.itemOf(hit) : null;
    }

    /**
     * A click or a tap anywhere but on an item drops the selection. A touch that scrolls the page does not:
     * only one that ends where it started counts as a tap.
     */
    function bindOutside(doc, api) {
        var tap = null;

        function drop(target) {
            if (!api.itemOf(target) && api.selected().length) {
                api.clear();
            }
        }

        doc.addEventListener('mousedown', function (e) {
            var root = doc.documentElement;
            // a press on the page scrollbar is not a click on the page
            if (e.button !== 0 || (root && (e.clientX >= root.clientWidth || e.clientY >= root.clientHeight))) {
                return;
            }
            drop(e.target);
        }, true);
        doc.addEventListener('touchstart', function (e) {
            var t = e.touches[0];
            tap = e.touches.length === 1 && !api.itemOf(e.target) ? { target: e.target, x: t.clientX, y: t.clientY } : null;
        }, { capture: true, passive: true });
        doc.addEventListener('touchmove', function (e) {
            var t = e.touches[0];
            if (tap && gesture(t.clientX - tap.x, t.clientY - tap.y) !== null) {
                tap = null;
            }
        }, { capture: true, passive: true });
        doc.addEventListener('touchend', function () {
            if (tap) {
                drop(tap.target);
            }
            tap = null;
        }, true);
    }

    /** Click / Shift+click with a mouse; touch, hold (or swipe sideways) and slide over the items on a touch screen. */
    function bindPointer(container, api, options, ignore) {
        var doc = container.ownerDocument || document;
        var touch = null;
        var selectedByTouchAt = 0;
        var edge = edgeScroller(container, function () {
            if (touch && touch.mode === 'select') {
                api.extendTo(itemAtPoint(api, doc, touch.cx, touch.cy));
            }
        });

        container.addEventListener('mousedown', function (e) {
            // Shift+click would otherwise select the text between the two rows
            if (e.shiftKey && api.itemOf(e.target)) {
                e.preventDefault();
            }
        });
        container.addEventListener('click', function (e) {
            var el = api.itemOf(e.target);
            if (!el || closest(e.target, ignore, el)) {
                return;
            }
            // the click that follows a held or swiped touch must not start a new selection
            if (Date.now() - selectedByTouchAt < 700) {
                return;
            }
            api.pick(el, e.shiftKey, e.ctrlKey || e.metaKey);
        });
        container.addEventListener('contextmenu', function (e) {
            if (touch && touch.mode === 'select') {
                e.preventDefault();
            }
        });
        container.addEventListener('touchstart', function (e) {
            var el = api.itemOf(e.target);
            if (!el || e.touches.length !== 1) {
                touch = null;
                return;
            }
            var t = e.touches[0];
            touch = { el: el, x: t.clientX, y: t.clientY, cx: t.clientX, cy: t.clientY, mode: null };
            edge.start();
            touch.timer = setTimeout(function () {
                if (touch && touch.mode === null) {
                    touch.mode = 'select';
                    api.pick(touch.el, false);
                    if (typeof navigator !== 'undefined' && navigator.vibrate) {
                        navigator.vibrate(15);
                    }
                }
            }, options.holdDelay || HOLD_DELAY);
        }, { passive: true });
        container.addEventListener('touchmove', function (e) {
            if (!touch) {
                return;
            }
            var t = e.touches[0];
            if (touch.mode === null) {
                var g = gesture(t.clientX - touch.x, t.clientY - touch.y);
                if (g === null) {
                    return;
                }
                clearTimeout(touch.timer);
                if (g === 'sideways' && options.sideSwipe) {
                    touch.mode = 'select';
                    api.pick(touch.el, false);
                } else {
                    // the page scrolls
                    touch.mode = 'none';
                    return;
                }
            }
            if (touch.mode === 'select') {
                if (e.cancelable) {
                    e.preventDefault();
                }
                touch.cx = t.clientX;
                touch.cy = t.clientY;
                api.extendTo(itemAtPoint(api, doc, t.clientX, t.clientY));
                edge.update(t.clientY);
            }
        }, { passive: false });
        container.addEventListener('touchend', function () {
            edge.stop();
            if (!touch) {
                return;
            }
            clearTimeout(touch.timer);
            if (touch.mode === 'select') {
                selectedByTouchAt = Date.now();
            }
            touch = null;
        });
        container.addEventListener('touchcancel', function () {
            edge.stop();
            if (touch) {
                clearTimeout(touch.timer);
            }
            touch = null;
        });
    }

    /**
     * A list sorted by dragging, where a selected run moves as one block. options: items(), handleClass
     * (on the dragged items), selectedClass, sideSwipe, holdDelay, change(), complete(items).
     */
    function sortable(container, options) {
        options = options || {};
        var doc = container.ownerDocument || document;
        var handleClass = options.handleClass || 'ghost';
        var selection = attach(container, {
            items: options.items,
            selectedClass: options.selectedClass,
            pointer: false
        });
        var press = null;
        var drag = null;
        var edge = edgeScroller(container, function () {
            if (!press) {
                return;
            }
            if (drag) {
                moveDrag(press.cy + edge.offset());
            } else if (press.mode === 'select') {
                selection.extendTo(itemAtPoint(selection, doc, press.cx, press.cy));
            }
        });

        /**
         * Brings the other selected items next to el in their order: those listed above el end right above
         * it, those below right under it. The items that stood between them stay on their side of el.
         */
        function gather(el) {
            var block = selection.selected();
            var at = block.indexOf(el);
            var moved = false;
            var ref = el;
            var i;
            for (i = at - 1; i >= 0; i--) {
                if (siblingItem(ref, false) !== block[i]) {
                    ref.parentNode.insertBefore(block[i], ref);
                    moved = true;
                }
                ref = block[i];
            }
            ref = el;
            for (i = at + 1; i < block.length; i++) {
                if (siblingItem(ref, true) !== block[i]) {
                    ref.parentNode.insertBefore(block[i], ref.nextSibling);
                    moved = true;
                }
                ref = block[i];
            }
            if (moved) {
                selection.render();
                if (typeof options.change === 'function') {
                    options.change(block);
                }
            }

            return block;
        }

        function startDrag(el, y) {
            var block;
            if (selection.includes(el) && selection.selected().length > 1) {
                var before = el.offsetTop;
                block = gather(el);
                // el moves when the items gathered above it differ in height from those that left
                y += el.offsetTop - before;
            } else {
                // a press on any other item drops the group
                selection.pick(el, false);
                block = [el];
            }
            drag = { block: block, origin: y };
            block.forEach(function (item) {
                item.classList.add(handleClass);
            });
            doc.onselectstart = function (e) {
                e.preventDefault();
            };
            doc.body.style.overflow = 'hidden';
        }

        function siblingItem(el, forward) {
            var list = selection.items();
            var i = list.indexOf(el) + (forward ? 1 : -1);

            return list[i] || null;
        }

        function moveDrag(y) {
            var block = drag.block;
            for (var guard = 0; guard < 1000; guard++) {
                var first = block[0];
                var last = block[block.length - 1];
                var above = siblingItem(first, false);
                var below = siblingItem(last, true);
                var step = blockStep(y - drag.origin, above ? above.offsetHeight : null, below ? below.offsetHeight : null);
                if (step === 0) {
                    break;
                }
                var before = first.offsetTop;
                if (step === 1) {
                    below.parentNode.insertBefore(below, first);
                } else {
                    above.parentNode.insertBefore(above, last.nextSibling);
                }
                drag.origin += first.offsetTop - before;
                if (typeof options.change === 'function') {
                    options.change(block);
                }
            }
            var dy = y - drag.origin;
            if ((dy < 0 && !siblingItem(block[0], false)) || (dy > 0 && !siblingItem(block[block.length - 1], true))) {
                dy = 0;
            }
            block.forEach(function (item) {
                item.style.transform = dy ? 'translateY(' + dy + 'px)' : '';
            });
        }

        function endDrag() {
            var block = drag.block;
            drag = null;
            edge.stop();
            block.forEach(function (item) {
                item.style.transform = '';
                item.classList.remove(handleClass);
            });
            doc.onselectstart = null;
            doc.body.style.overflow = '';
            if (typeof options.complete === 'function') {
                options.complete(block);
            }
        }

        function onMouseMove(e) {
            if (!press) {
                return;
            }
            press.cx = e.clientX;
            press.cy = e.clientY;
            if (!drag) {
                if (press.shift || press.toggle || gesture(e.clientX - press.x, e.clientY - press.y, 3) === null) {
                    return;
                }
                startDrag(press.el, press.start);
            }
            moveDrag(e.clientY + edge.offset());
            edge.update(e.clientY);
        }

        function onMouseUp() {
            edge.stop();
            doc.removeEventListener('mousemove', onMouseMove);
            doc.removeEventListener('mouseup', onMouseUp);
            if (drag) {
                endDrag();
            } else if (press) {
                selection.pick(press.el, press.shift, press.toggle);
            }
            press = null;
        }

        container.addEventListener('mousedown', function (e) {
            var el = selection.itemOf(e.target);
            if (!el || e.button !== 0) {
                return;
            }
            e.preventDefault();
            edge.start();
            press = { el: el, x: e.clientX, y: e.clientY, cx: e.clientX, cy: e.clientY, start: e.clientY + edge.offset(), shift: e.shiftKey, toggle: e.ctrlKey || e.metaKey };
            doc.addEventListener('mousemove', onMouseMove);
            doc.addEventListener('mouseup', onMouseUp);
        });

        container.addEventListener('touchstart', function (e) {
            var el = selection.itemOf(e.target);
            if (!el || e.touches.length !== 1) {
                return;
            }
            var t = e.touches[0];
            edge.start();
            press = { el: el, x: t.clientX, y: t.clientY, cx: t.clientX, cy: t.clientY, start: t.clientY + edge.offset(), mode: null, touch: true };
            press.timer = setTimeout(function () {
                if (press && press.mode === null) {
                    press.mode = 'select';
                    selection.pick(press.el, false);
                    if (typeof navigator !== 'undefined' && navigator.vibrate) {
                        navigator.vibrate(15);
                    }
                }
            }, options.holdDelay || HOLD_DELAY);
        }, { passive: true });

        container.addEventListener('touchmove', function (e) {
            if (!press || !press.touch) {
                return;
            }
            var t = e.touches[0];
            if (press.mode === null) {
                var g = gesture(t.clientX - press.x, t.clientY - press.y);
                if (g === null) {
                    return;
                }
                clearTimeout(press.timer);
                if (g === 'sideways' && options.sideSwipe !== false) {
                    press.mode = 'select';
                    selection.pick(press.el, false);
                } else {
                    press.mode = 'drag';
                    startDrag(press.el, press.start);
                }
            }
            if (e.cancelable) {
                e.preventDefault();
            }
            press.cx = t.clientX;
            press.cy = t.clientY;
            if (press.mode === 'select') {
                selection.extendTo(itemAtPoint(selection, doc, t.clientX, t.clientY));
            } else {
                moveDrag(t.clientY + edge.offset());
            }
            edge.update(t.clientY);
        }, { passive: false });

        function onTouchEnd(e) {
            if (!press || !press.touch) {
                return;
            }
            edge.stop();
            clearTimeout(press.timer);
            if (press.mode === 'drag' && drag) {
                endDrag();
            } else if (press.mode === null) {
                selection.pick(press.el, false);
            }
            press = null;
            // no emulated mouse events after a handled touch
            if (e.cancelable) {
                e.preventDefault();
            }
        }

        container.addEventListener('touchend', onTouchEnd);
        container.addEventListener('touchcancel', function () {
            edge.stop();
            if (press) {
                clearTimeout(press.timer);
            }
            if (drag) {
                endDrag();
            }
            press = null;
        });
        container.addEventListener('contextmenu', function (e) {
            if (press && press.touch) {
                e.preventDefault();
            }
        });

        return selection;
    }

    return {
        range: range,
        pick: pick,
        all: all,
        gesture: gesture,
        blockStep: blockStep,
        edgeSpeed: edgeSpeed,
        shortcut: shortcut,
        attach: attach,
        sortable: sortable
    };
}));
