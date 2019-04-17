// evoTooltips
evo.tooltips = function(a) {
  'use strict';
  if (!a) {
    return;
  } else {
    a = 'string' === typeof a ? document.querySelectorAll(a) : a;
  }
  var b = document.querySelector('.evo-tooltip');
  if (!b) {
    b = document.createElement('div');
    b.className = 'evo-tooltip';
    document.body.appendChild(b);
  }
  b.style.pointerEvents = 'none';
  var c = parseFloat(getComputedStyle(b).marginTop);
  for (var i = 0; i < a.length; i++) {
    a[i].addEventListener('mouseenter', function(e) {
      if (e.buttons) {
        return;
      }
      var x = e.clientX, y = e.clientY;
      b.innerHTML = (this.dataset && this.dataset.tooltip ? (this.dataset.tooltip[0] === '#' ? document.querySelector(this.dataset.tooltip).innerHTML : this.dataset.tooltip) : this.innerHTML);
      if (x + b.offsetWidth + (c * 2) > window.innerWidth) {
        b.style.left = Math.round(x - b.offsetWidth - (c * 2)) + 'px';
        b.classList.add('evo-tooltip-right');
      } else {
        b.style.left = Math.round(x) + 'px';
        b.classList.add('evo-tooltip-left');
      }
      if (y - (b.offsetHeight / 2) - c < 0) {
        b.style.top = 0;
      } else if (y + (b.offsetHeight / 2) > window.innerHeight) {
        b.style.top = Math.round(window.innerHeight - b.offsetHeight) - (c * 2) + 'px';
      } else {
        b.style.top = Math.round(y - (b.offsetHeight / 2)) - c + 'px';
      }
      b.classList.add('show');
    });
    a[i].addEventListener('mouseleave', function() {
      b.className = 'evo-tooltip';
    });
    a[i].addEventListener('mousedown', function() {
      b.className = 'evo-tooltip';
    });
  }
};

// evoSortable
evo.sortable = function(a, b) {
  'use strict';
  if (!a) {
    return;
  } else {
    a = 'string' === typeof a ? document.querySelectorAll(a) : a;
    b = b || {};
  }
  var o = {
    el: null,
    handleClass: b.handleClass || 'ghost',
    position: b.position || 'vertical',
    complete: function(c) {
      if ('function' === typeof b.complete) {
        b.complete(c);
      }
    },
    change: function(c) {
      if ('function' === typeof b.change) {
        b.change(c);
      }
    }
  };

  function onmousedown(e) {
    o.el = this;
    o.x = e.pageX || e.touches[0].pageX;
    o.y = e.pageY || e.touches[0].pageY;
    o.marginX = parseFloat(getComputedStyle(o.el).marginLeft) + parseFloat(getComputedStyle(o.el).marginRight);
    o.marginY = parseFloat(getComputedStyle(o.el).marginTop) + parseFloat(getComputedStyle(o.el).marginBottom);
    o.el.classList.add(o.handleClass);
    o.el.ownerDocument.addEventListener('mousemove', onmousemove);
    o.el.ownerDocument.addEventListener('mouseup', onmouseup);
    o.el.ownerDocument.addEventListener('touchmove', onmousemove);
    o.el.ownerDocument.addEventListener('touchend', onmouseup);
    o.el.ownerDocument.onselectstart = function(e) {
      e.preventDefault();
    };
    o.el.ownerDocument.body.style.overflow = 'hidden';
  }

  function onmousemove(e) {
    if (o.position === 'vertical') {
      var y = ((e.pageY || e.touches[0].pageY) - o.y);
      if (y >= o.el.offsetHeight && o.el.nextElementSibling) {
        o.y += o.el.offsetHeight + o.marginY;
        o.el.parentNode.insertBefore(o.el, o.el.nextElementSibling.nextElementSibling);
        o.change();
        y = 0;
      } else if (y <= -o.el.offsetHeight && o.el.previousElementSibling) {
        o.y -= o.el.offsetHeight + o.marginY;
        o.el.parentNode.insertBefore(o.el, o.el.previousElementSibling);
        o.change();
        y = 0;
      } else if (!o.el.previousElementSibling && y < 0 || !o.el.nextElementSibling && y > 0) {
        y = 0;
      }
      o.el.style.webkitTransform = 'translateY(' + y + 'px)';
      o.el.style.transform = 'translateY(' + y + 'px)';
    } else {
      var x = ((e.pageX || e.touches[0].pageX) - o.x);
      if (x >= o.el.offsetWidth && o.el.nextElementSibling) {
        o.x += o.el.offsetWidth + o.marginX;
        o.el.parentNode.insertBefore(o.el, o.el.nextElementSibling.nextElementSibling);
        o.change();
        x = 0;
      } else if (x <= -o.el.offsetWidth && o.el.previousElementSibling) {
        o.x -= o.el.offsetHeight + o.marginX;
        o.el.parentNode.insertBefore(o.el, o.el.previousElementSibling);
        o.change();
        x = 0;
      } else if (!o.el.previousElementSibling && x < 0 || !o.el.nextElementSibling && x > 0) {
        x = 0;
      }
      o.el.style.webkitTransform = 'translateX(' + x + 'px)';
      o.el.style.transform = 'translateX(' + x + 'px)';
    }
  }

  function onmouseup() {
    o.el.style.webkitTransform = '';
    o.el.style.transform = '';
    o.el.classList.remove(o.handleClass);
    o.el.ownerDocument.removeEventListener('mousemove', onmousemove);
    o.el.ownerDocument.removeEventListener('mouseup', onmouseup);
    o.el.ownerDocument.removeEventListener('touchmove', onmousemove);
    o.el.ownerDocument.removeEventListener('touchend', onmouseup);
    o.el.ownerDocument.onselectstart = null;
    o.el.ownerDocument.body.style.overflow = '';
    o.complete(o.el);
  }

  for (var i = 0; i < a.length; i++) {
    a[i].addEventListener('mousedown', onmousedown);
    a[i].addEventListener('touchstart', onmousedown);
  }
};

// evo draggable
evo.draggable = function(a, b) {
  'use strict';
  if (!a) {
    return;
  } else {
    a = 'string' === typeof a ? document.querySelectorAll(a) : a;
    b = b || {};
  }
  var o = {
    handle: {
      start: function(c) {
        'function' === typeof b.handle.start ? b.handle.start.call(c) : '';
      },
      end: function(c) {
        'function' === typeof b.handle.end ? b.handle.end.call(c) : '';
      }
    },
    container: {
      className: b.container.className || 'drop',
      classOver: b.container.classOver || 'over',
      over: function(c) {
        'function' === typeof b.container.over ? b.container.over.call(c) : '';
      },
      leave: function(c) {
        'function' === typeof b.container.leave ? b.container.leave.call(c) : '';
      },
      drop: function(c, i) {
        'function' === typeof b.container.drop ? b.container.drop.call(c, i) : '';
      }
    }
  };

  o.container.els = document.querySelectorAll('.' + o.container.className);

  function onmousedown(e) {
    o.el = this;
    o.parent = o.el.offsetParent;
    o.x = e.pageX;
    o.y = e.pageY;
    o.draggable = false;
    document.onselectstart = function(e) {
      e.preventDefault();
    };
    document.addEventListener('mousemove', onmousemove);
    document.addEventListener('mouseup', onmouseup);
    o.el.addEventListener('mouseup', onmouseup);
  }

  function onmousemove(e) {
    var x = e.pageX - o.x, y = e.pageY - o.y;
    if (Math.abs(x) + Math.abs(y) > 10) {
      o.draggable = true;
      o.el.style.pointerEvents = 'none';
      o.el.style.left = x + 'px';
      o.el.style.top = y + 'px';
      o.handle.start(o.el);
    }
  }

  function onmouseup() {
    document.removeEventListener('mousemove', onmousemove);
    document.removeEventListener('mouseup', onmouseup);
    o.el.removeEventListener('mouseup', onmouseup);
    if (o.draggable) {
      o.draggable = false;
      o.el.style.pointerEvents = '';
      o.el.style.left = '';
      o.el.style.top = '';
      o.el.draggable = false;
      var h = document.querySelector('.' + o.container.classOver);
      if (h && h !== o.parent) {
        h.appendChild(o.el);
        o.container.drop(h, o.el);
      }
      o.handle.end(o.el);
    }
  }

  for (var i = 0; i < a.length; i++) {
    a[i].addEventListener('mousedown', onmousedown);
  }

  for (var i = 0; i < o.container.els.length; i++) {
    o.container.els[i].onmouseenter = function() {
      this.classList.add(b.container.classOver);
    };
    o.container.els[i].onmouseleave = function() {
      this.classList.remove(b.container.classOver);
    };
  }

};

// evo collapse
evo.collapse = function(a, b) {
  if (!a) {
    return;
  } else {
    a = 'string' === typeof a ? document.querySelectorAll(a) : a;
  }
  var h = {
    containerClass: b && b.containerClass || 'tab-body'
  };

  for (var i = 0; i < a.length; i++) {
    if (a[i].nextElementSibling && a[i].nextElementSibling.classList.contains(h.containerClass)) {
      a[i].nextElementSibling.classList.add('collapse', 'in');
      a[i].onclick = function() {
        if (a[i].nextElementSibling.classList.contains('in')) {
          a[i].nextElementSibling.classList.remove('in');
          a[i].classList.add('collapsed');
        } else {
          a[i].nextElementSibling.classList.add('in');
          a[i].classList.remove('collapsed');
        }
      };
    }
  }
};

// check connection to server
evo.checkConnectionToServer = function() {
  /*var xhr = new (window.ActiveXObject || XMLHttpRequest)('Microsoft.XMLHTTP');
  xhr.open('GET', evo.urlCheckConnectionToServer + '?time=' + new Date().getTime(), false);
  try {
    xhr.send();
    return (xhr.status >= 200 && xhr.status < 300 || xhr.status === 304);
  } catch (error) {
    return false;
  }*/
  return true;
};

function document_onload() {
  stopWorker();

  var actionButtons = document.getElementById('actions'), actionSelect = document.getElementById('stay');
  if (actionButtons !== null && actionSelect !== null) {
    var actionPlus = actionButtons.querySelector('.plus'), actionSaveButton = actionButtons.querySelector('a#Button1') || actionButtons.querySelector('#Button1 > a');
    actionPlus.classList.add('dropdown-toggle');
    actionStay['stay1'] = '<i class="' + evo.style.actions_file + '"></i>';
    actionStay['stay2'] = '<i class="' + evo.style.actions_pencil + '"></i>';
    actionStay['stay3'] = '<i class="' + evo.style.actions_reply + '"></i>';
    if (actionSelect.value) {
      actionSaveButton.innerHTML += '<i class="' + evo.style.actions_plus + '"></i><span> + </span>' + actionStay['stay' + actionSelect.value] + '<span>' + actionSelect.children['stay' + actionSelect.value].innerHTML + '</span>';
    }
    var actionSelectNewOption = null, actionSelectOptions = actionSelect.children, div = document.createElement('div');
    div.className = 'dropdown-menu';
    actionSaveButton.parentNode.classList.add('dropdown');
    for (var i = 0; i < actionSelectOptions.length; i++) {
      if (!actionSelectOptions[i].selected) {
        actionSelectNewOption = document.createElement('SPAN');
        actionSelectNewOption.className = 'btn btn-block';
        actionSelectNewOption.dataset.id = i;
        actionSelectNewOption.innerHTML = actionStay[actionSelect.children[i].id] + ' <span>' + actionSelect.children[i].innerHTML + '</span>';
        actionSelectNewOption.onclick = function() {
          var s = actionSelect.querySelector('option[selected=selected]');
          if (s) {
            s.selected = false;
          }
          actionSelect.children[this.dataset.id].selected = true;
          actionSaveButton.click();
        };
        div.appendChild(actionSelectNewOption);
      }
    }
    actionSaveButton.parentNode.appendChild(div);
    actionPlus.onclick = function() {
      this.parentNode.classList.toggle('show');
    };
  }
  evo.tooltips('[data-tooltip]');

  if (document.forms.length && document.forms.mutate && window.frameElement.parentNode.parentNode.classList.contains('evo-popup')) {
    window.focus();
    document.forms.mutate.addEventListener('submit', function(e) {
      if ((actionSelect && actionSelect.value === '') || (!actionSelect && actionSaveButton)) {
        if (actionSelect) {
          actionSelect.parentNode.removeChild(actionSelect);
        }
        if (top.mainMenu) {
          top.mainMenu.work();
        }
        var xhr = new XMLHttpRequest();
        xhr.onload = function() {
          if (this.status === 200 && this.readyState === 4) {
            if (top.mainMenu) {
              top.mainMenu.stopWork();
            }
            if (top.tree) {
              top.tree.restoreTree();
            }
            window.frameElement.parentNode.parentNode.close(e);
          }
        };
        xhr.open(document.forms.mutate.method, document.forms.mutate.action, true);
        xhr.send(new FormData(document.forms.mutate));
        e.preventDefault();
      }
    }, false);

    actions.cancel = function() {
      window.frameElement.parentNode.parentNode.close();
    };

    window.addEventListener('keydown', function(e) {
      if (e.keyCode === 27) {
        window.frameElement.parentNode.parentNode.close();
      }
    });
  }
}

function reset_path(elementName) {
  document.getElementById(elementName).value = document.getElementById('default_' + elementName).innerHTML;
}

function document_onunload(e) {
  if (!dontShowWorker) {
    top.mainMenu.work();
  }
}

// set tree to default action.
if (parent.tree) {
  parent.tree.ca = 'open';
}

// call the updateMail function, updates mail notification in top navigation
if (top.mainMenu && top.mainMenu.updateMail) {
  top.mainMenu.updateMail(true);
}

function stopWorker() {
  try {
    parent.mainMenu.stopWork();
  } catch (oException) {
    ww = window.setTimeout('stopWorker()', 500);
  }
}

function doRefresh(r) {
  try {
    rr = r;
    top.mainMenu.startrefresh(rr);
  } catch (oException) {
    vv = window.setTimeout('doRefresh()', 1000);
  }
}

function checkDirt(evt) {
  evt = evt || window.event;
  var message = '';
  if (!evo.checkConnectionToServer()) {
    message = evo.lang.error_internet_connection;
    setTimeout(function() {
      alert(message);
    }, 10);
    evt.returnValue = message;
    timerForUnload = setTimeout('stopWorker()', 100);
    return message;
  }
  if (documentDirty === true) {
    message = evo.lang.warning_not_saved;
    evt.returnValue = message;
    timerForUnload = setTimeout('stopWorker()', 100);
    return message;
  }
}

function saveWait(fName) {
  document.getElementById('savingMessage').innerHTML = evo.lang.saving;
  for (var i = 0; i < document.forms[fName].elements.length; i++) {
    document.forms[fName].elements[i].disabled = 'disabled';
  }
}

function hideLoader() {
  document.getElementById('preLoader').style.display = 'none';
}

// add the 'unsaved changes' warning event handler
if (typeof window.addEventListener !== 'undefined') {
  window.addEventListener('beforeunload', function(e) {
    checkDirt(e);
    document_onunload();
  }, false);
} else if (typeof window.attachEvent !== 'undefined') {
  window.attachEvent('onbeforeunload', function(e) {
    checkDirt(e);
    document_onunload();
  });
} else {
  window.onbeforeunload = function(e) {
    checkDirt(e);
    document_onunload();
  };
}

if (typeof window.addEventListener !== 'undefined') {
  window.addEventListener('load', function() {
    document_onload();
  }, false);
} else if (typeof window.attachEvent !== 'undefined') {
  window.attachEvent('onload', function() {
    document_onload();
  });
} else {
  window.onload = function() {
    document_onload();
  };
}

window.addEventListener('unload', function() {
  clearTimeout(timerForUnload);
});