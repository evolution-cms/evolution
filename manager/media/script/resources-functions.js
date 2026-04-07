function unlockElement(type, id, domEl) {
  var msg = trans.msg.replace('[+id+]', id).replace('[+element_type+]', trans['type' + type]);
  if (confirm(msg) == true) {
    jQuery.get('index.php?a=67&type=' + type + '&id=' + id, function(data) {
      if (data == 1) {
        jQuery(domEl).fadeOut();
      } else alert(data);
    });
  }
}

function actionDisableElement(t) {
  let $t = $(t),
      $row = $(t).closest('.rTableRow'),
      $title = $('.rTableRowTitle', $row),
      $icon = $('i', $t);
  $row.fadeTo(100, .5);

  jQuery.get($t.data('disabled') ? $t.data('enable-href') : $t.data('disable-href'), function() {
    $row.fadeTo(100, 1);
    if ($t.data('disabled')) {
      $t.data('disabled', 0);
      $t.attr('title', $t.data('disable-title'));
      $icon.attr('class', $t.data('disable-icon'));
      $title.removeClass('disabledPlugin');
    } else {
      $t.data('disabled', 1);
      $t.attr('title', $t.data('enable-title'));
      $icon.attr('class', $t.data('enable-icon'));
      $title.addClass('disabledPlugin');
    }
  });
}

// Switch Views
var version = 1;

function initViews(pre, helppre, target) {
  jQuery('#' + helppre + '-help').click(function() {
    jQuery('#' + helppre + '-info').toggle(400);
  });
}

function setColumnCount(targetEl, count) {
  targetEl.find('.panel-collapse > ul').css({
    '-moz-column-count': count,
    '-webkit-column-count': count,
    'column-count': count
  });
}

function getViewOpts(form) {
  viewOpts = {};
  // Options
  viewOpts.cb_buttons = form.find('input:checkbox[name=cb_buttons]').is(':checked');
  viewOpts.cb_description = form.find('input:checkbox[name=cb_description]').is(':checked');
  viewOpts.cb_icons = form.find('input:checkbox[name=cb_icons]').is(':checked');
  viewOpts.cb_all = form.find('input:checkbox[name=cb_all]').is(':checked');

  // Views
  viewOpts.view = form.find('input[name=view]:checked').val();
  viewOpts.columns = form.find('input[name=columns]').val();

  viewOpts.fontsize = form.find('input[name=fontsize]').val();

  return viewOpts;
}

function setView(viewOpts, targetEl, target) {
  // Options
  if (viewOpts.cb_buttons) {
    targetEl.find('.btnCell').show();
  } else {
    targetEl.find('.btnCell').hide();
  }
  if (viewOpts.cb_description) {
    targetEl.find('span.elements_descr').show();
  } else {
    targetEl.find('span.elements_descr').hide();
  }
  if (viewOpts.cb_icons) {
    targetEl.removeClass('noicons');
  } else {
    targetEl.addClass('noicons');
  }

  // Views
  switch (viewOpts.view) {
    case 'inline':
      targetEl.removeClass('flex list');
      targetEl.addClass('inline');
      setColumnCount(targetEl, 1);
      break;
    case 'flex':
      targetEl.removeClass('inline list');
      targetEl.addClass('flex');
      setColumnCount(targetEl, viewOpts.columns);
      break;
    case 'list':
    default:
      targetEl.removeClass('flex inline');
      targetEl.addClass('list');
      setColumnCount(targetEl, 1);
      break;
  }

  // Set font-size
  targetEl.css('font-size', viewOpts.fontsize / 10 + 'em');

  // Save view-options to localStorage
  viewOpts.version = version; // Provides version of options-obj to allow easy resetting of localStorage on future updates
  localStorage.setItem('EVO_mgrResources_' + target, JSON.stringify(viewOpts));

  // console.log('save', viewOpts);
}

function setAllViews(viewOpts) {
  jQuery('.switchForm').each(function() {
    var form = jQuery(this);
    var target = form.data('target');
    var targetEl = jQuery('#' + target);
    setView(viewOpts, targetEl, target);
    setViewOptions(form, viewOpts);
  });
}

function setViewOptions(form, viewOpts) {
  form.find('input:checkbox[name=cb_buttons]').attr('checked', viewOpts.cb_buttons).prop('checked', viewOpts.cb_buttons);
  form.find('input:checkbox[name=cb_description]').attr('checked', viewOpts.cb_description).prop('checked', viewOpts.cb_description);
  form.find('input:checkbox[name=cb_icons]').attr('checked', viewOpts.cb_icons).prop('checked', viewOpts.cb_icons);
  form.find('input:radio[name=view][value=' + viewOpts.view + ']').attr('checked', true).prop('checked', true);
  form.find('input[name=columns]').val(viewOpts.columns);
  form.find('input[name=fontsize]').val(viewOpts.fontsize);
  form.find('input:checkbox[name=cb_all]').attr('checked', viewOpts.cb_all).prop('checked', viewOpts.cb_all);
}

function setViewDefaultOptions(form) {
  var viewOpts = {};
  viewOpts.cb_buttons = 1;
  viewOpts.cb_description = 1;
  viewOpts.cb_icons = 1;
  viewOpts.view = 'list';
  viewOpts.columns = 3;
  viewOpts.fontsize = 10;
  viewOpts.cb_all = true;
  setViewOptions(form, viewOpts);
}

// Add switch-view functionality
jQuery(document).ready(function() {
  jQuery('.switchForm').each(function() {
    var form = jQuery(this);
    var target = form.data('target');
    var targetEl = jQuery('#' + target);

    form.change(function() {
      var viewOpts = getViewOpts(form);
      if (form.find('input:checkbox[name=cb_all]').is(':checked')) {
        // Set view in all tabs
        setAllViews(viewOpts);
      } else {
        // Set view in single tab
        setView(viewOpts, targetEl, target);
      }
    });

    // Get parameters from localStorage
    var viewOpts = JSON.parse(localStorage.getItem('EVO_mgrResources_' + target));

    // console.log('load', viewOpts.version, '==', version);
    // console.log(viewOpts);

    // Set views - if version is different, defaults will be set up
    if (viewOpts && viewOpts.version == version) {
      setViewOptions(form, viewOpts);
    } else {
      setViewDefaultOptions(form);
    }

    // Now restore settings
    form.trigger('change');

    // Add reset-button
    form.find('.btn_reset').click(function(e) {
      e.preventDefault();
      setViewDefaultOptions(form);
      form.trigger('change');
    });

    // Prevent sending form
    form.submit(function(e) {
      e.preventDefault();
    });
  });

  // Add switchForm-toggle
  jQuery('.switchform-btn').each(function() {
    jQuery(this).click(function() {
      var target = jQuery(this).data('target');
      jQuery('#' + target).toggle(400);
    });
  });

  // jQuery(function() {
  //   var context = jQuery('#resourcesPane').nuContextMenu({
  //     hideAfterClick: true,
  //     items: '.man_el_name',
  //     callback: function(action, element) {
  //       var el = jQuery(element);
  //       var name = el.text().trim();
  //       var cm = el.closest('.man_el_name');
  //       mgrResAction(name, action, cm.data('type'), cm.data('id'), cm.data('catid'));
  //     },
  //     menu: [
  //       {name: 'create', title: mraTrans.create_new, icon: 'plus'},
  //       {name: 'edit', title: mraTrans.edit, icon: 'edit'},
  //       {name: 'duplicate', title: mraTrans.duplicate, icon: 'clone'},
  //       {name: 'void'},
  //       {name: 'remove', title: mraTrans.remove, icon: 'trash'}
  //     ]
  //   });
  // });
  //
  // function mgrResAction(name, action, type, id, catid) {
  //   var actionIds, deleteMsg;
  //
  //   switch (type) {
  //     case 'site_templates' :
  //       actionsIds = {'create': 19, 'edit': 16, 'duplicate': 96, 'remove': 21};
  //       deleteMsg = mraTrans.confirm_delete_template;
  //       break;
  //     case 'site_tmplvars' :
  //       actionsIds = {'create': 300, 'edit': 301, 'duplicate': 304, 'remove': 303};
  //       deleteMsg = mraTrans.confirm_delete_tmplvars;
  //       break;
  //     case 'site_htmlsnippets' :
  //       actionsIds = {'create': 77, 'edit': 78, 'duplicate': 97, 'remove': 80};
  //       deleteMsg = mraTrans.confirm_delete_htmlsnippet;
  //       break;
  //     case 'site_snippets' :
  //       actionsIds = {'create': 23, 'edit': 22, 'duplicate': 98, 'remove': 25};
  //       deleteMsg = mraTrans.confirm_delete_snippet;
  //       break;
  //     case 'site_plugins' :
  //       actionsIds = {'create': 101, 'edit': 102, 'duplicate': 105, 'remove': 104};
  //       deleteMsg = mraTrans.confirm_delete_plugin;
  //       break;
  //     case 'site_modules' :
  //       actionsIds = {'create': 107, 'edit': 108, 'duplicate': 111, 'remove': 110};
  //       deleteMsg = mraTrans.confirm_delete_module;
  //       break;
  //     default :
  //       alert('Unknown type');
  //       return;
  //   }
  //
  //   // Actions that need confirmation
  //   var confirmMsg = false;
  //   switch (action) {
  //     case 'create' :
  //       id = false;
  //       break;
  //     case 'edit' :
  //       break;
  //     case 'duplicate' :
  //       confirmMsg = mraTrans.confirm_duplicate_record;
  //       break;
  //     case 'remove' :
  //       confirmMsg = deleteMsg;
  //       break;
  //   }
  //
  //   if (confirmMsg) {
  //     confirmMsg += ' \n \n ' + name; // + " ("+id+")"
  //     var r = confirm(confirmMsg);
  //     if (r != true) return;
  //   }
  //
  //   var target = 'index.php?a=' + actionsIds[action] + (id ? '&id=' + id : '') + (catid ? '&catid=' + catid : '');
  //
  //   if (top.main) top.main.document.location.href = target;
  //   else document.location.href = target;
  // }
});

function initQuicksearch(inputId, listId) {
  jQuery('#' + inputId).quicksearch('#' + listId + ' ul.elements > li', {
    selector: '.man_el_name',
    'show': function() { jQuery(this).removeClass('hide'); },
    'hide': function() { jQuery(this).addClass('hide'); },
    'bind': 'keyup',
    'onAfter': function() {
      jQuery('#' + listId).find('.panel-collapse').each(function() {
        var parentLI = jQuery(this);
        var totalLI = jQuery(this).find('ul.elements > li').length;
        var hiddenLI = jQuery(this).find('ul.elements > li.hide').length;
        if (hiddenLI == totalLI) { parentLI.prev('.panel-heading').addClass('hide'); } else { parentLI.prev('.panel-heading').removeClass('hide'); }
      });
    }
  });
  jQuery('.filterElements-form').keydown(function(e) {
    if (e.keyCode == 13) {
      e.preventDefault();
    }
  });
}

var storageKey = 'EVO_mgrResources';

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
        var collapseItem = jQuery('#collapse' + type + category);
        var toggleItem = jQuery('#toggle' + type + category);
        if (state == 0) {
          // Collapsed
          collapseItem.collapse('hide');
          toggleItem.addClass('collapsed');
        } else {
          // Open
          collapseItem.collapse('show');
          toggleItem.removeClass('collapsed');
        }
      }
    }
    // Avoid first category collapse-flicker on reload
    setTimeout(function() {
      jQuery('.panel-group').removeClass('no-transition');
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

  jQuery(document).ready(function() {

    jQuery('.filterElements-form').keydown(function(e) {
      if (e.keyCode == 13) e.preventDefault();
    });

    // Shift-Mouseclick opens/collapsed all categories
    jQuery('.accordion-toggle').click(function(e) {
      e.preventDefault();
      var thisItemCollapsed = jQuery(this).hasClass('collapsed');
      if (e.shiftKey) {
        // Shift-key pressed
        var toggleItems = jQuery(this).closest('.panel-group').find('> .panel .accordion-toggle');
        var collapseItems = jQuery(this).closest('.panel-group').find('> .panel > .panel-collapse');
        if (thisItemCollapsed) {
          toggleItems.removeClass('collapsed');
          collapseItems.collapse('show');
        } else {
          toggleItems.addClass('collapsed');
          collapseItems.collapse('hide');
        }
        // Save states to localStorage
        toggleItems.each(function() {
          state = jQuery(this).hasClass('collapsed') ? 1 : 0;
          setLastCollapsedCategory(jQuery(this).data('cattype'), jQuery(this).data('catid'), state);
        });
        writeElementsInTreeParamsToStorage();
      } else {
        jQuery(this).toggleClass('collapsed');
        jQuery(jQuery(this).attr('href')).collapse('toggle');
        // Save state to localStorage
        state = thisItemCollapsed ? 0 : 1;
        setLastCollapsedCategory(jQuery(this).data('cattype'), jQuery(this).data('catid'), state);
        writeElementsInTreeParamsToStorage();
      }
    });

    setRememberCollapsedCategories();
  });
} catch (err) {
  alert('document.ready error: ' + err);
}
