<?php
if (IN_MANAGER_MODE != "true") {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
}
$mxla = $modx_lang_attribute ? $modx_lang_attribute : 'en';

// invoke OnManagerRegClientStartupHTMLBlock event
$evtOut = $modx->invokeEvent('OnManagerMainFrameHeaderHTMLBlock');
$modx_textdir = isset($modx_textdir) ? $modx_textdir : null;
$onManagerMainFrameHeaderHTMLBlock = is_array($evtOut) ? implode("\n", $evtOut) : '';
$textdir = $modx_textdir === 'rtl' ? 'rtl' : 'ltr';
if (!isset($modx->config['mgr_jquery_path'])) {
    $modx->config['mgr_jquery_path'] = 'media/script/jquery/jquery.min.js';
}
if (!isset($modx->config['mgr_date_picker_path'])) {
    $modx->config['mgr_date_picker_path'] = 'media/script/air-datepicker/datepicker.inc.php';
}

if (!empty($_COOKIE['MODX_themeColor'])) {
    $body_class .= ' ' . $_COOKIE['MODX_themeColor'];
}

$css = 'media/style/' . $modx->config['manager_theme'] . '/style.css?v=' . $lastInstallTime;

if ($modx->config['manager_theme'] == 'default') {
    if (!file_exists(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/css/styles.min.css') && is_writable(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/css')) {
        require_once MODX_BASE_PATH . 'assets/lib/Formatter/CSSMinify.php';
        $minifier = new Formatter\CSSMinify();
        $minifier->addFile(MODX_MANAGER_PATH . 'media/style/common/bootstrap/css/bootstrap.min.css');
        $minifier->addFile(MODX_MANAGER_PATH . 'media/style/common/font-awesome/css/font-awesome.min.css');
        $minifier->addFile(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/css/fonts.css');
        $minifier->addFile(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/css/forms.css');
        $minifier->addFile(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/css/mainmenu.css');
        $minifier->addFile(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/css/tree.css');
        $minifier->addFile(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/css/custom.css');
        $minifier->addFile(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/css/tabpane.css');
        $minifier->addFile(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/css/contextmenu.css');
        $minifier->addFile(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/css/index.css');
        $minifier->addFile(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/css/main.css');
        $css = $minifier->minify();
        file_put_contents(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/css/styles.min.css', $css);
    }
    if (file_exists(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/css/styles.min.css')) {
        $css = 'media/style/' . $modx->config['manager_theme'] . '/css/styles.min.css?v=' . $lastInstallTime;
    }
}

?>
<!DOCTYPE html>
<html lang="<?= $mxla ?>" dir="<?= $textdir ?>">
<head>
    <title>Evolution CMS</title>
    <meta http-equiv="Content-Type" content="text/html; charset=<?= $modx_manager_charset ?>" />
    <meta name="viewport" content="initial-scale=1.0,user-scalable=no,maximum-scale=1,width=device-width" />
    <meta name="theme-color" content="#1d2023" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="stylesheet" type="text/css" href="<?= $css ?>" />
    <script type="text/javascript" src="media/script/tabpane.js"></script>
    <?= sprintf('<script type="text/javascript" src="%s"></script>' . "\n", $modx->config['mgr_jquery_path']) ?>
    <?php if ($modx->config['show_picker'] != "0") { ?>
        <script src="media/style/<?= $modx->config['manager_theme'] ?>/js/color.switcher.js" type="text/javascript"></script>
    <?php } ?>
    <?php
    $aArr = array('2');
    if (!in_array($_REQUEST['a'], $aArr)) { ?>
        <script src="media/script/mootools/mootools.js" type="text/javascript"></script>
        <script src="media/script/mootools/moodx.js" type="text/javascript"></script>
    <?php } ?>

    <!-- OnManagerMainFrameHeaderHTMLBlock -->
    <?= $onManagerMainFrameHeaderHTMLBlock . "\n" ?>

    <script type="text/javascript">
      /* <![CDATA[ */

      if (!evo) {
        var evo = {};
      }

      var actions;

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

        function onmousedown(e)
        {
          o.el = this;
          o.x = e.pageX;
          o.y = e.pageY;
          o.marginX = parseFloat(getComputedStyle(o.el).marginLeft) + parseFloat(getComputedStyle(o.el).marginRight);
          o.marginY = parseFloat(getComputedStyle(o.el).marginTop) + parseFloat(getComputedStyle(o.el).marginBottom);
          o.el.classList.add(o.handleClass);
          o.el.ownerDocument.addEventListener('mousemove', onmousemove);
          o.el.ownerDocument.addEventListener('mouseup', onmouseup);
          o.el.ownerDocument.onselectstart = function(e) {
            e.preventDefault();
          };
        }

        function onmousemove(e)
        {
          if (o.position === 'vertical') {
            var y = (e.pageY - o.y);
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
            var x = (e.pageX - o.x);
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

        function onmouseup()
        {
          o.el.style.webkitTransform = '';
          o.el.style.transform = '';
          o.el.classList.remove(o.handleClass);
          o.el.ownerDocument.removeEventListener('mousemove', onmousemove);
          o.el.ownerDocument.removeEventListener('mouseup', onmouseup);
          o.el.ownerDocument.onselectstart = null;
          o.complete(o.el);
        }

        for (var i = 0; i < a.length; i++) {
          a[i].addEventListener('mousedown', onmousedown);
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

        function onmousedown(e)
        {
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

        function onmousemove(e)
        {
          var x = e.pageX - o.x, y = e.pageY - o.y;
          if (Math.abs(x) + Math.abs(y) > 10) {
            o.draggable = true;
            o.el.style.pointerEvents = 'none';
            o.el.style.left = x + 'px';
            o.el.style.top = y + 'px';
            o.handle.start(o.el);
          }
        }

        function onmouseup()
        {
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
        var xhr = new ( window.ActiveXObject || XMLHttpRequest )('Microsoft.XMLHTTP');
        xhr.open('HEAD', '<?= MODX_MANAGER_URL ?>includes/version.inc.php?time=' + new Date().getTime(), false);
        try {
          xhr.send();
          return (xhr.status >= 200 && xhr.status < 300 || xhr.status === 304);
        } catch (error) {
          return false;
        }
      };

      function document_onload()
      {
        stopWorker();

          <?php
          if (isset($_REQUEST['r']) && preg_match('@^[0-9]+$@', $_REQUEST['r'])) {
              echo 'doRefresh(' . $_REQUEST['r'] . ");\n";
          }
          ?>

        var actionButtons = document.getElementById('actions'), actionSelect = document.getElementById('stay');
        if (actionButtons !== null && actionSelect !== null) {
          var actionPlus = actionButtons.querySelector('.plus'), actionSaveButton = actionButtons.querySelector('a#Button1') || actionButtons.querySelector('#Button1 > a'), actionStay = [];
          actionPlus.classList.add('dropdown-toggle');
          actionStay['stay1'] = '<i class="<?= $_style['actions_file'] ?>"></i>';
          actionStay['stay2'] = '<i class="<?= $_style['actions_pencil'] ?>"></i>';
          actionStay['stay3'] = '<i class="<?= $_style['actions_reply'] ?>"></i>';
          if (actionSelect.value) {
            actionSaveButton.innerHTML += '<i class="<?= $_style['actions_plus'] ?>"></i><span> + </span>' + actionStay['stay' + actionSelect.value] + '<span>' + actionSelect.children['stay' + actionSelect.value].innerHTML + '</span>';
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
          })
        }
      }

      function reset_path(elementName)
      {
        document.getElementById(elementName).value = document.getElementById('default_' + elementName).innerHTML;
      }

      var dontShowWorker = false;

      function document_onunload(e)
      {
        if (!dontShowWorker) {
          top.mainMenu.work();
        }
      }

      // set tree to default action.
      if (parent.tree) {
        parent.tree.ca = 'open';
      }

      // call the updateMail function, updates mail notification in top navigation
      if (top.mainMenu) {
        if (top.mainMenu.updateMail) {
          top.mainMenu.updateMail(true);
        }
      }

      function stopWorker()
      {
        try {
          parent.mainMenu.stopWork();
        } catch (oException) {
          ww = window.setTimeout('stopWorker()', 500);
        }
      }

      function doRefresh(r)
      {
        try {
          rr = r;
          top.mainMenu.startrefresh(rr);
        } catch (oException) {
          vv = window.setTimeout('doRefresh()', 1000);
        }
      }

      var documentDirty = false;
      var timerForUnload;

      function checkDirt(evt)
      {
        evt = evt || window.event;
        var message = '';
        if (!evo.checkConnectionToServer()) {
          message = '<?= addslashes($_lang['error_internet_connection']) ?>';
          setTimeout(function() {
            alert(message);
          }, 10);
          evt.returnValue = message;
          timerForUnload = setTimeout('stopWorker()', 100);
          return message;
        }
        if (documentDirty === true) {
          message = '<?= addslashes($_lang['warning_not_saved']) ?>';
          evt.returnValue = message;
          timerForUnload = setTimeout('stopWorker()', 100);
          return message;
        }
      }

      function saveWait(fName)
      {
        document.getElementById('savingMessage').innerHTML = '<?= $_lang['saving'] ?>';
        for (var i = 0; i < document.forms[fName].elements.length; i++) {
          document.forms[fName].elements[i].disabled = 'disabled';
        }
      }

      var managerPath = '';

      function hideLoader()
      {
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

      /* ]]> */
    </script>
</head>
<body <?= ($modx_textdir ? ' class="rtl"' : '') ?> class="<?= $body_class ?>" data-evocp="color">
