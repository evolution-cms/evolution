const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

// The tree prints a resource title once escaped (data-title-esc="&lt;img ...&gt;"). Scripts read it
// through dataset or an inline handler argument, both already decoded, so evo.js must escape it
// again before it builds tab titles, the context menu header or the parent name from it.
const evoSource = fs.readFileSync(
    path.join(__dirname, '..', '..', 'style', 'default', 'js', 'evo.js'),
    'utf8'
);

function loadEscapeHtml() {
    const match = evoSource.match(/escapeHtml: (function \(t\) \{[\s\S]*?\n {8}\}),\n/);
    assert.ok(match, 'evo.escapeHtml is defined');
    return new Function('return (' + match[1] + ');')();
}

test('escapeHtml turns a decoded title back into inert text', () => {
    const escapeHtml = loadEscapeHtml();

    assert.equal(
        escapeHtml('Title <img src=x onerror=alert(1)>'),
        'Title &lt;img src=x onerror=alert(1)&gt;'
    );
    assert.equal(escapeHtml('Tom & "Jerry" \'s'), 'Tom &amp; &quot;Jerry&quot; &#39;s');
    assert.equal(escapeHtml(undefined), '');
    assert.equal(escapeHtml(null), '');
    assert.equal(escapeHtml(42), '42');
});

test('every tab opened from the tree escapes the node title', () => {
    assert.doesNotMatch(evoSource, /title: this\.selectedObjectName \+ '<small>/);
    assert.doesNotMatch(evoSource, /title: title \+ '<small>/);
    assert.doesNotMatch(evoSource, /title: target\.title \+ '<small>/);
    assert.equal(
        (evoSource.match(/title: evo\.escapeHtml\(this\.selectedObjectName\) \+ '<small>/g) || []).length,
        11
    );
    assert.match(evoSource, /title: evo\.escapeHtml\(title\) \+ '<small>\(' \+ id \+ '\)<\/small>'/);
    assert.match(evoSource, /title: evo\.escapeHtml\(target\.title\) \+ '<small>/);
});

test('the context menu header and the parent name do not take the title as HTML', () => {
    assert.match(evoSource, /f\.textContent = this\.selectedObjectName;/);
    assert.doesNotMatch(evoSource, /f\.innerHTML = this\.selectedObjectName;/);
    assert.match(evoSource, /evo\.escapeHtml\(d\.querySelector\('#node' \+ parent \+ ' > a'\)\.dataset\.titleEsc\)/);
});
