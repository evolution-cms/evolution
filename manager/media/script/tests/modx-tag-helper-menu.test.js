const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

// Right-clicking a {{chunk}} or [[snippet]] tag in a code editor builds a context menu whose items
// carry an onclick string that evo.tree.showPopup() later eval()s. The header is the element name,
// entity-escaped without single quotes, so a name such as  a');alert(1);//  used to close the string
// literal and run in the administrator's manager.
const evoSource = fs.readFileSync(
    path.join(__dirname, '..', '..', 'style', 'default', 'js', 'evo.js'),
    'utf8'
);

function buildOnclick(r, k) {
    const match = evoSource.match(/(r\[k\]\['onclick'\] = [\s\S]*?\) \+ '\)\}';)/);
    assert.ok(match, 'the tag helper onclick statement is present');
    new Function('r', 'k', match[1])(r, k);
    return r[k].onclick;
}

function runOnclick(code, shiftKey) {
    const calls = [];
    let injected = false;
    const evo = {
        popup: (o) => calls.push(['popup', o]),
        openWindow: (o) => calls.push(['openWindow', o]),
    };
    // alert is what an injected name would call
    new Function('evo', 'event', 'alert', code)(evo, { shiftKey }, () => { injected = true; });
    return { calls, injected };
}

test('an element name with quotes stays a value of the popup call', () => {
    const name = "a');alert(1);//";
    const r = {
        header: { innerHTML: '<i class="fa fa-code"></i> ' + name },
        item: { innerHTML: 'Edit', url: "index.php?a=77&itemname=a')%3Balert(1)%3B%2F%2F" },
    };

    const code = buildOnclick(r, 'item');
    const popup = runOnclick(code, false);

    assert.equal(popup.injected, false);
    assert.equal(popup.calls.length, 1);
    assert.equal(popup.calls[0][0], 'popup');
    assert.equal(popup.calls[0][1].title, r.header.innerHTML);
    assert.equal(popup.calls[0][1].url, r.item.url);
    assert.equal(popup.calls[0][1].width, '95%');

    const win = runOnclick(code, true);
    assert.equal(win.injected, false);
    assert.deepEqual(win.calls, [['openWindow', { url: r.item.url }]]);
});

test('the old string concatenation is gone', () => {
    assert.doesNotMatch(evoSource, /title:\\'' \+ r\.header\.innerHTML \+ '\\'/);
    assert.doesNotMatch(evoSource, /evo\.openWindow\(\{url:\\'' \+ r\[k\]\.url/);
});
