function toArray(list) {
  return Array.prototype.slice.call(list || []);
}

function qs(root, selector) {
  if (selector === undefined) {
    selector = root;
    root = document;
  }
  return root ? root.querySelector(selector) : null;
}

function qsa(root, selector) {
  if (selector === undefined) {
    selector = root;
    root = document;
  }
  return root ? toArray(root.querySelectorAll(selector)) : [];
}

function matchesSelector(el, selector) {
  if (!el || el.nodeType !== 1) return false;
  var proto = Element.prototype;
  var fn = proto.matches || proto.msMatchesSelector || proto.webkitMatchesSelector;
  return fn ? fn.call(el, selector) : false;
}

function closest(el, selector) {
  var current = el;
  while (current && current.nodeType === 1) {
    if (matchesSelector(current, selector)) return current;
    current = current.parentElement;
  }
  return null;
}

function setHidden(el, hidden) {
  if (!el) return;
  el.classList.toggle('hide', !!hidden);
}

function toggleHidden(el) {
  if (!el) return;
  var isHidden = el.classList.contains('hide') || window.getComputedStyle(el).display === 'none';
  if (isHidden) {
    el.classList.remove('hide');
    el.style.display = '';
  } else {
    el.classList.add('hide');
  }
}

function fadeTo(el, opacity) {
  if (!el) return;
  el.style.transition = 'opacity 0.1s';
  el.style.opacity = opacity;
}

function fadeOut(el) {
  if (!el) return;
  el.style.transition = 'opacity 0.2s';
  el.style.opacity = '0';
  window.setTimeout(function() {
    el.classList.add('hide');
  }, 200);
}

function fetchText(url, onDone) {
  var xhr = new XMLHttpRequest();
  xhr.open('GET', url, true);
  xhr.onreadystatechange = function() {
    if (xhr.readyState !== 4) return;
    if (xhr.status >= 200 && xhr.status < 300) {
      onDone(xhr.responseText);
    } else {
      alert(xhr.responseText || ('Request failed: ' + xhr.status));
    }
  };
  xhr.send(null);
}

function unlockElement(type, id, domEl) {
  var msg = trans.msg.replace('[+id+]', id).replace('[+element_type+]', trans['type' + type]);
  if (confirm(msg) === true) {
    fetchText('index.php?a=67&type=' + type + '&id=' + id, function(data) {
      if (String(data).trim() === '1') {
        fadeOut(domEl);
      } else {
        alert(data);
      }
    });
  }
}

function actionDisableElement(t) {
  var btn = t;
  if (!btn) return;
  var row = closest(btn, '.rTableRow');
  var title = row ? row.querySelector('.rTableRowTitle') : null;
  var icon = btn.querySelector('i');
  fadeTo(row, 0.5);

  var isDisabled = btn.dataset.disabled === '1' || btn.dataset.disabled === 'true';
  var url = isDisabled ? btn.dataset.enableHref : btn.dataset.disableHref;
  fetchText(url, function() {
    fadeTo(row, 1);
    if (isDisabled) {
      btn.dataset.disabled = '0';
      if (btn.dataset.disableTitle) btn.setAttribute('title', btn.dataset.disableTitle);
      if (icon && btn.dataset.disableIcon) icon.className = btn.dataset.disableIcon;
      if (title) title.classList.remove('disabledPlugin');
    } else {
      btn.dataset.disabled = '1';
      if (btn.dataset.enableTitle) btn.setAttribute('title', btn.dataset.enableTitle);
      if (icon && btn.dataset.enableIcon) icon.className = btn.dataset.enableIcon;
      if (title) title.classList.add('disabledPlugin');
    }
  });
}

// Switch Views
var version = 1;

function initViews(pre, helppre, target) {
  var help = document.getElementById(helppre + '-help');
  var info = document.getElementById(helppre + '-info');
  if (!help || !info) return;
  help.addEventListener('click', function() {
    toggleHidden(info);
  });
}

function setColumnCount(targetEl, count) {
  if (!targetEl) return;
  qsa(targetEl, '.panel-collapse > ul').forEach(function(el) {
    el.style.MozColumnCount = count;
    el.style.WebkitColumnCount = count;
    el.style.columnCount = count;
  });
}

function getViewOpts(form) {
  var viewOpts = {};
  var cbButtons = qs(form, 'input:checkbox[name=cb_buttons]');
  var cbDescription = qs(form, 'input:checkbox[name=cb_description]');
  var cbIcons = qs(form, 'input:checkbox[name=cb_icons]');
  var cbAll = qs(form, 'input:checkbox[name=cb_all]');

  viewOpts.cb_buttons = !!(cbButtons && cbButtons.checked);
  viewOpts.cb_description = !!(cbDescription && cbDescription.checked);
  viewOpts.cb_icons = !!(cbIcons && cbIcons.checked);
  viewOpts.cb_all = !!(cbAll && cbAll.checked);

  var viewRadio = qs(form, 'input[name=view]:checked');
  viewOpts.view = viewRadio ? viewRadio.value : 'list';

  var columns = qs(form, 'input[name=columns]');
  var fontsize = qs(form, 'input[name=fontsize]');
  viewOpts.columns = columns ? parseInt(columns.value, 10) : 0;
  viewOpts.fontsize = fontsize ? parseInt(fontsize.value, 10) : 10;

  return viewOpts;
}

function setView(viewOpts, targetEl, target) {
  if (!targetEl) return;

  qsa(targetEl, '.btnCell').forEach(function(el) {
    setHidden(el, !viewOpts.cb_buttons);
  });
  qsa(targetEl, 'span.elements_descr').forEach(function(el) {
    setHidden(el, !viewOpts.cb_description);
  });
  targetEl.classList.toggle('noicons', !viewOpts.cb_icons);

  switch (viewOpts.view) {
    case 'inline':
      targetEl.classList.remove('flex');
      targetEl.classList.remove('list');
      targetEl.classList.add('inline');
      setColumnCount(targetEl, 1);
      break;
    case 'flex':
      targetEl.classList.remove('inline');
      targetEl.classList.remove('list');
      targetEl.classList.add('flex');
      setColumnCount(targetEl, viewOpts.columns || 1);
      break;
    case 'list':
    default:
      targetEl.classList.remove('flex');
      targetEl.classList.remove('inline');
      targetEl.classList.add('list');
      setColumnCount(targetEl, 1);
      break;
  }

  targetEl.style.fontSize = viewOpts.fontsize / 10 + 'em';

  viewOpts.version = version;
  localStorage.setItem('MODX_mgrResources_' + target, JSON.stringify(viewOpts));
}

function setAllViews(viewOpts) {
  qsa('.switchForm').forEach(function(form) {
    var target = form.dataset.target;
    var targetEl = document.getElementById(target);
    setView(viewOpts, targetEl, target);
    setViewOptions(form, viewOpts);
  });
}

function setViewOptions(form, viewOpts) {
  var cbButtons = qs(form, 'input:checkbox[name=cb_buttons]');
  var cbDescription = qs(form, 'input:checkbox[name=cb_description]');
  var cbIcons = qs(form, 'input:checkbox[name=cb_icons]');
  var cbAll = qs(form, 'input:checkbox[name=cb_all]');
  var viewRadio = qs(form, 'input:radio[name=view][value=' + viewOpts.view + ']');
  var columns = qs(form, 'input[name=columns]');
  var fontsize = qs(form, 'input[name=fontsize]');

  if (cbButtons) cbButtons.checked = !!viewOpts.cb_buttons;
  if (cbDescription) cbDescription.checked = !!viewOpts.cb_description;
  if (cbIcons) cbIcons.checked = !!viewOpts.cb_icons;
  if (viewRadio) viewRadio.checked = true;
  if (columns) columns.value = viewOpts.columns;
  if (fontsize) fontsize.value = viewOpts.fontsize;
  if (cbAll) cbAll.checked = !!viewOpts.cb_all;
}

function setViewDefaultOptions(form) {
  var viewOpts = {
    cb_buttons: 1,
    cb_description: 1,
    cb_icons: 1,
    view: 'list',
    columns: 3,
    fontsize: 10,
    cb_all: true
  };
  setViewOptions(form, viewOpts);
}

function bindFilterElementsForm(root) {
  qsa(root || document, '.filterElements-form').forEach(function(el) {
    el.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' || e.keyCode === 13) {
        e.preventDefault();
      }
    });
  });
}

// Add switch-view functionality
document.addEventListener('DOMContentLoaded', function() {
  qsa('.switchForm').forEach(function(form) {
    var target = form.dataset.target;
    var targetEl = document.getElementById(target);

    function applyView() {
      var viewOpts = getViewOpts(form);
      if (viewOpts.cb_all) {
        setAllViews(viewOpts);
      } else {
        setView(viewOpts, targetEl, target);
      }
    }

    form.addEventListener('change', applyView);

    var viewOpts = null;
    try {
      viewOpts = JSON.parse(localStorage.getItem('MODX_mgrResources_' + target));
    } catch (err) {
      viewOpts = null;
    }

    if (viewOpts && viewOpts.version == version) {
      setViewOptions(form, viewOpts);
    } else {
      setViewDefaultOptions(form);
    }

    applyView();

    var resetBtn = qs(form, '.btn_reset');
    if (resetBtn) {
      resetBtn.addEventListener('click', function(e) {
        e.preventDefault();
        setViewDefaultOptions(form);
        applyView();
      });
    }

    form.addEventListener('submit', function(e) {
      e.preventDefault();
    });
  });

  qsa('.switchform-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var target = btn.dataset.target;
      var targetEl = target ? document.getElementById(target) : null;
      if (targetEl) {
        toggleHidden(targetEl);
      }
    });
  });

  bindFilterElementsForm();
});

function initQuicksearch(inputId, listId) {
  var input = document.getElementById(inputId);
  var list = document.getElementById(listId);
  if (!input || !list) return;
  if (input.dataset.quicksearchBound === 'true') return;
  input.dataset.quicksearchBound = 'true';

  var panelCollapses = qsa(list, '.panel-collapse');

  function applyFilter() {
    var term = input.value.trim().toLowerCase();
    var items = qsa(list, 'ul.elements > li');

    items.forEach(function(item) {
      var nameEl = qs(item, '.man_el_name');
      var text = nameEl ? nameEl.textContent : item.textContent;
      var match = term === '' || (text && text.toLowerCase().indexOf(term) !== -1);
      setHidden(item, !match);
    });

    panelCollapses.forEach(function(panel) {
      var total = qsa(panel, 'ul.elements > li').length;
      var hidden = qsa(panel, 'ul.elements > li.hide').length;
      var heading = panel.previousElementSibling;
      if (heading && heading.classList) {
        heading.classList.toggle('hide', hidden === total && total !== 0);
      }
    });
  }

  input.addEventListener('input', applyFilter);
  input.addEventListener('keyup', applyFilter);
  applyFilter();

  bindFilterElementsForm();
}

var storageKey = 'MODX_mgrResources';

// localStorage reset :
// localStorage.removeItem(storageKey);

// Prepare remember collapsed categories function
var storage = localStorage.getItem(storageKey);
var elementsInTreeParams = {};
var searchFieldCache = {};

try {
  if (storage != null) {
    try {
      elementsInTreeParams = JSON.parse(storage);
    } catch (err) {
      console.log(err);
      elementsInTreeParams = {'cat_collapsed': {}};
    }
  } else {
    elementsInTreeParams = {'cat_collapsed': {}};
  }

  function setCollapseState(el, show) {
    if (!el) return;
    el.classList.toggle('in', !!show);
  }

  function getPanelGroupItems(panelGroup) {
    var toggles = [];
    var collapses = [];
    toArray(panelGroup.children).forEach(function(panel) {
      if (!panel.classList || !panel.classList.contains('panel')) return;
      var toggle = panel.querySelector('.accordion-toggle');
      var collapse = panel.querySelector('.panel-collapse');
      if (toggle) toggles.push(toggle);
      if (collapse) collapses.push(collapse);
    });
    return {toggles: toggles, collapses: collapses};
  }

  // Remember collapsed categories functions
  function setRememberCollapsedCategories(obj) {
    obj = obj == null ? elementsInTreeParams.cat_collapsed : obj;
    var state;
    for (var type in obj) {
      if (!elementsInTreeParams.cat_collapsed.hasOwnProperty(type)) continue;
      for (var category in elementsInTreeParams.cat_collapsed[type]) {
        if (!elementsInTreeParams.cat_collapsed[type].hasOwnProperty(category)) {
          continue;
        }
        state = elementsInTreeParams.cat_collapsed[type][category];
        if (state == null) {
          continue;
        }
        var collapseItem = document.getElementById('collapse' + type + category);
        var toggleItem = document.getElementById('toggle' + type + category);
        if (state == 0) {
          // Collapsed
          setCollapseState(collapseItem, false);
          if (toggleItem) toggleItem.classList.add('collapsed');
        } else {
          // Open
          setCollapseState(collapseItem, true);
          if (toggleItem) toggleItem.classList.remove('collapsed');
        }
      }
    }
    // Avoid first category collapse-flicker on reload
    setTimeout(function() {
      qsa('.panel-group').forEach(function(el) {
        el.classList.remove('no-transition');
      });
    }, 50);
  }

  function setLastCollapsedCategory(type, id, state) {
    state = state != 1 ? 1 : 0;
    if (typeof elementsInTreeParams.cat_collapsed[type] == 'undefined') elementsInTreeParams.cat_collapsed[type] = {};
    elementsInTreeParams.cat_collapsed[type][id] = state;
  }

  function writeElementsInTreeParamsToStorage() {
    var jsonString = JSON.stringify(elementsInTreeParams);
    localStorage.setItem(storageKey, jsonString);
  }

  document.addEventListener('DOMContentLoaded', function() {
    bindFilterElementsForm();

    // Shift-Mouseclick opens/collapsed all categories
    qsa('.accordion-toggle').forEach(function(toggle) {
      toggle.addEventListener('click', function(e) {
        e.preventDefault();
        var thisItemCollapsed = toggle.classList.contains('collapsed');
        var panelGroup = closest(toggle, '.panel-group');
        if (e.shiftKey && panelGroup) {
          var items = getPanelGroupItems(panelGroup);
          if (thisItemCollapsed) {
            items.toggles.forEach(function(el) { el.classList.remove('collapsed'); });
            items.collapses.forEach(function(el) { setCollapseState(el, true); });
          } else {
            items.toggles.forEach(function(el) { el.classList.add('collapsed'); });
            items.collapses.forEach(function(el) { setCollapseState(el, false); });
          }
          // Save states to localStorage
          items.toggles.forEach(function(el) {
            var state = el.classList.contains('collapsed') ? 1 : 0;
            setLastCollapsedCategory(el.dataset.cattype, el.dataset.catid, state);
          });
          writeElementsInTreeParamsToStorage();
        } else {
          toggle.classList.toggle('collapsed');
          var targetSelector = toggle.getAttribute('href');
          if (targetSelector) {
            var collapseItem = document.querySelector(targetSelector);
            setCollapseState(collapseItem, thisItemCollapsed);
          }
          // Save state to localStorage
          var state = thisItemCollapsed ? 0 : 1;
          setLastCollapsedCategory(toggle.dataset.cattype, toggle.dataset.catid, state);
          writeElementsInTreeParamsToStorage();
        }
      });
    });

    setRememberCollapsedCategories();
  });
} catch (err) {
  alert('document.ready error: ' + err);
}
