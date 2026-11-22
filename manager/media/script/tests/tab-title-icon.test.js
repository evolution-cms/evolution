const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

// A manager tab takes its title from the page's first <h1>, as markup. The tab CSS places an icon
// only as a direct child of .tab-title, so a module heading that wraps it (the Package Management
// module's <span class="pagetitle-icon">) showed the icon stranded above the text.
const evoSource = fs.readFileSync(
    path.join(__dirname, '..', '..', 'style', 'default', 'js', 'evo.js'),
    'utf8'
);

function loadTabTitle() {
    const match = evoSource.match(/tabTitle: (function \(html\) \{[\s\S]*?\n {8}\}),\n/);
    assert.ok(match, 'evo.tabTitle is defined');
    return new Function('return (' + match[1] + ');')();
}

test('a wrapped heading icon becomes a direct child, as in a document tab', () => {
    const tabTitle = loadTabTitle();

    assert.equal(
        tabTitle(`
      <span class="pagetitle-icon">
        <i class="evo-icon evo-icon-archive"></i>
      </span>
      <span class="pagetitle-text">Package Management</span>
    `),
        '<i class="evo-icon evo-icon-archive"></i><span class="pagetitle-text">Package Management</span>'
    );
    assert.equal(
        tabTitle('<div class="icon"><svg viewBox="0 0 24 24"><path d="M0 0"></path></svg></div> Store'),
        '<svg viewBox="0 0 24 24"><path d="M0 0"></path></svg>Store'
    );
});

test('titles that are already flat keep their markup and text', () => {
    const tabTitle = loadTabTitle();

    assert.equal(
        tabTitle('\n            <i class="evo-icon evo-icon-news"></i>\n INVO: Legal<small>(5)</small>\n        '),
        '<i class="evo-icon evo-icon-news"></i>INVO: Legal<small>(5)</small>'
    );
    assert.equal(tabTitle('Tom &amp; <i>Jerry</i> show'), 'Tom &amp; <i>Jerry</i> show');
    assert.equal(tabTitle('<span class="x">text <i class="a"></i></span>'), '<span class="x">text <i class="a"></i></span>');
    assert.equal(tabTitle(undefined), '');
    assert.equal(tabTitle(null), '');
});

test('both ways a tab gets its title go through it', () => {
    assert.match(evoSource, /this\.title = evo\.tabTitle\(a\.title \|\| ''\);/);
    assert.match(evoSource, /evo\.tabTitle\(w\.main\.document\.body\.querySelectorAll\('h1'\)\[0\]\.innerHTML\)/);
});
