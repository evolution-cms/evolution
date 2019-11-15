(function(w, d, u) {
  'use strict';
  modx.extended({
    frameset: 'frameset',
    minWidth: 840,
    isMobile: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent),
    typesactions: {'16': 1, '301': 2, '78': 3, '22': 4, '102': 5, '108': 6, '3': 7, '4': 7, '6': 7, '27': 7, '61': 7, '62': 7, '63': 7, '72': 7},
    thememodes: ['', 'lightness', 'light', 'dark', 'darkness'],
    tabsTimer: 0,
    popupTimer: 0,
    init: function() {
      if (!localStorage.getItem('MODX_widthSideBar')) {
        localStorage.setItem('MODX_widthSideBar', this.config.tree_width);
      }
      //this.tree.init();
      this.mainmenu.init();
      if (w.location.hash) {
        if (modx.getActionFromUrl(w.location.hash, 2)) {
          w.history.replaceState(null, d.title, modx.MODX_MANAGER_URL);
        } else if (modx.getActionFromUrl(w.location.hash) || modx.main.getQueryVariable('filemanager', w.location.hash)) {
          var url = modx.main.getQueryVariable('filemanager', w.location.hash) ? modx.MODX_MANAGER_URL + modx.main.getQueryVariable('filemanager', w.location.hash) + w.location.hash.replace('#?', '?') : w.location.href.replace('#?', '?');
          if (modx.config.global_tabs) {
            modx.tabs({url: url, title: 'blank'});
          } else if (w.main) {
            w.main.frameElement.src = url;
          } else {
            modx.openWindow(url);
          }
        }
      }
      this.resizer.init();
      this.search.init();
      if (this.config.session_timeout > 0) {
        w.setInterval(this.keepMeAlive, 1000 * 60 * this.config.session_timeout);
      }
      if (modx.config.mail_check_timeperiod > 0 && modx.permission.messages) {
        setTimeout('modx.updateMail(true)', 1000);
      }
      d.addEventListener('click', this.hideDropDown, false);
      if (modx.config.global_tabs) {
        d.addEventListener('click', this.tabs, false);
        this.tabs({url: '?a=2', reload: 0});
      }
    },
    mainmenu: {
      id: 'mainMenu',
      init: function() {
        //console.log('modx.mainMenu.init()');
        var mm = d.getElementById('mainMenu'), timer;
        mm.delegateEventListener('click', 'a', function(e) {
          if (this.classList.contains('dropdown-toggle')) {
            if (mm.classList.contains('show') && (this.classList.contains('selected') || !modx.isMobile && this.parentElement.classList.contains('hover'))) {
              this.classList.remove('selected');
              mm.classList.remove('show');
            } else {
              mm.querySelectorAll('.nav > li > a:not(:hover)').forEach(function(el) {
                el.classList.remove('selected');
              });
              this.classList.add('selected');
              mm.classList.add('show');
            }
            e.stopPropagation();
            e.target.dataset.toggle = '#mainMenu';
            modx.hideDropDown(e);
          }
          if (this.closest('ul').classList.contains('dropdown-menu') && !this.closest('li').classList.contains('dropdown-back')) {
            mm.querySelectorAll('.nav > .active').forEach(function(el) {
              el.classList.remove('active');
            });
            mm.querySelectorAll('.nav li.selected').forEach(function(el) {
              el.classList.remove('selected');
            });
            this.closest('.nav > li').classList.add('active');
            if (this.offsetParent.id) {
              d.getElementById(this.offsetParent.id.substr(7)).classList.add('selected');
            }
            if ((modx.isMobile || w.innerWidth < modx.minWidth) && this.classList.contains('toggle')) {
              this.parentNode.classList.add('selected');
              e.stopPropagation();
              e.preventDefault();
            }
          }
        });
        mm.delegateEventListener('click', '.nav li', function() {
          if ((modx.isMobile || w.innerWidth < modx.minWidth)) {
            mm.querySelectorAll('.nav ul.selected').forEach(function(el) {
              el.classList.remove('selected');
            });
          }
        });
        mm.querySelectorAll('.nav > li').forEach(function(el) {
          el.addEventListener('mouseenter', function() {
            mm.querySelectorAll('.nav > li.hover:not(:hover)').forEach(function(el) {
              el.classList.remove('hover');
            });
            this.classList.add('hover');
          });
        });
        mm.querySelectorAll('.nav > li li').forEach(function(el) {
          el.addEventListener('mouseenter', function(e) {
            var self = this, ul;
            mm.querySelectorAll('.nav > li li.hover:not(:hover)').forEach(function(el) {
              el.classList.remove('hover');
            });
            this.classList.add('hover');
            clearTimeout(timer);
            if (this.offsetParent.nextElementSibling && this.offsetParent.nextElementSibling.classList.contains('sub-menu')) {
              ul = this.offsetParent.nextElementSibling;
            } else if (this.offsetParent && this.offsetParent.classList.contains('sub-menu')) {
              ul = this.offsetParent;
            } else {
              ul = d.createElement('ul');
              ul.className = 'sub-menu dropdown-menu';
              this.parentNode.parentNode.appendChild(ul);
            }
            timer = setTimeout(function() {
              if (d.querySelector('.nav .sub-menu.show')) {
                d.querySelector('.nav .sub-menu.show').classList.remove('show');
              }
              ul.style.left = self.offsetWidth + 'px';
              if (self.classList.contains('dropdown-toggle')) {
                if (ul.id === 'parent_' + self.id) {
                  if (modx.isMobile) {
                    self.parentNode.classList.add('selected');
                  } else {
                    self.onclick = function(e) {
                      if (e.target.classList.contains('toggle')) {
                        self.parentNode.classList.add('selected');
                      }
                    };
                  }
                  ul.classList.add('show');
                } else {
                  ul.classList.remove('show');
                  mm.querySelectorAll('.nav ul.selected').forEach(function(el) {
                    el.classList.remove('selected');
                  });
                  timer = setTimeout(function() {
                    var href = self.firstElementChild.href && self.firstElementChild.target === 'main' ? self.firstElementChild.href.split('?')[1] + '&elements=' + self.id : '';
                    modx.post(modx.MODX_MANAGER_URL + 'media/style/' + modx.config.theme + '/ajax.php', href, function(data) {
                      if (data) {
                        if (modx.isMobile || w.innerWidth < modx.minWidth) {
                          data = '<li class="dropdown-back"><span class="dropdown-item"><i class="fa fa-arrow-left"></i>' + modx.lang.paging_prev + '</span></li>' + data;
                        }
                        ul.id = 'parent_' + self.id;
                        ul.innerHTML = data;
                        var id = w.location.hash.substr(2).replace(/=/g, '_').replace(/&/g, '__');
                        var el = d.getElementById(id);
                        if (el) {
                          el.parentNode.classList.add('selected');
                          d.getElementById(el.parentNode.parentNode.id.substr(7)).classList.add('selected');
                        }
                        for (var i = 0; i < ul.children.length; i++) {
                          el = ul.children[i];
                          if (el.classList.contains('dropdown-back')) {
                            el.onclick = function(e) {
                              e.target.offsetParent.previousElementSibling.classList.remove('selected');
                              e.target.offsetParent.classList.remove('show');
                              e.preventDefault();
                              e.stopPropagation();
                            };
                          }
                          el.onmouseenter = function(e) {
                            clearTimeout(timer);
                            var el = e.target.offsetParent.querySelector('li.hover');
                            if (el) el.classList.remove('hover');
                            e.target.classList.add('hover');
                            self.classList.add('hover');
                            e.preventDefault();
                            e.stopPropagation();
                          };
                        }
                        ul.classList.add('show');
                        if (modx.isMobile) {
                          if (e.target.classList.contains('toggle')) {
                            self.parentNode.classList.add('selected');
                          }
                        } else {
                          self.onclick = function(e) {
                            if (e.target.classList.contains('toggle')) {
                              self.parentNode.classList.add('selected');
                            }
                          };
                        }
                        setTimeout(function() {
                          modx.mainmenu.search(href, ul);
                        }, 200);
                      }
                    });
                  }, 85);
                }
              } else {
                if (ul.classList.contains('open')) {
                  ul.classList.remove('open');
                  setTimeout(function() {
                    ul.parentNode.removeChild(ul);
                  }, 100);
                }
              }
            }, 85);
            e.preventDefault();
          });
        });
      },
      search: function(href, ul) {
        var items,
            input = ul.querySelector('input[name=filter]'),
            index = -1,
            el = null;
        if (input) {
          if (!modx.isMobile) {
            input.focus();
          }
          input.onkeyup = function(e) {
            if (e.keyCode === 13 && ul.querySelector('.item.hover')) {
              d.body.click();
              //w.main.location.href = ul.querySelector('.item.hover').firstChild.href;
              el = ul.querySelector('.item.hover').firstChild;
              modx.tabs({url: el.href, title: el.innerHTML});
            } else if (e.keyCode === 38 || e.keyCode === 40) {
              input.selectionStart = input.value.length;
              items = ul.querySelectorAll('.item');
              if (items.length) {
                if (e.keyCode === 40) {
                  index++;
                } else {
                  index--;
                }
                if (index < 0) {
                  index = -1;
                  el = ul.querySelector('.hover');
                  if (el) el.classList.remove('hover');
                } else if (index > items.length - 1) {
                  index = items.length - 1;
                }
                if (index >= 0 && index < items.length) {
                  el = ul.querySelector('.hover');
                  if (el) el.classList.remove('hover');
                  items[index].classList.add('hover');
                }
              }
            } else {
              modx.post(modx.MODX_MANAGER_URL + 'media/style/' + modx.config.theme + '/ajax.php', href + '&filter=' + input.value, function(data) {
                index = -1;
                ul.querySelectorAll('.item').forEach(function(el) {
                  el.parentNode.removeChild(el);
                });
                ul.insertAdjacentHTML('beforeend', data);
                ul.querySelectorAll('.item').forEach(function(el) {
                  el.addEventListener('mouseenter', function(e) {
                    this.parentElement.querySelectorAll('li').forEach(function(el) {
                      el.classList.remove('hover');
                    });
                    this.classList.add('hover');
                    e.stopPropagation();
                  });
                });
              }, 'html');
            }
          };
        }
      }
    },
    search: {
      result: null,
      results: null,
      input: null,
      mask: null,
      loader: null,
      timer: 0,
      init: function() {
        this.result = d.getElementById('searchresult');
        this.input = d.getElementById('searchid');
        this.mask = d.querySelector('#searchform .mask');
        if (!this.result) {
          this.result = d.createElement('div');
          this.result.id = 'searchresult';
          d.body.appendChild(this.result);
        }
        this.loader = d.createElement('i');
        this.loader.className = 'fa fa-refresh fa-spin fa-fw';
        this.input.parentNode.appendChild(this.loader);
        if (modx.config.global_tabs) {
          this.input.parentNode.onsubmit = function(e) {
            e.preventDefault();
            this.target = 'mainsearch';
            modx.tabs({url: this.action, title: 'Search', name: 'mainsearch'});
            this.submit();
          };
        }
        var s = this;
        this.input.onkeyup = function(e) {
          e.preventDefault();
          clearTimeout(s.timer);
          if (s.input.value.length !== '' && s.input.value.length > 2) {
            s.timer = setTimeout(function() {
              s.loader.style.display = 'block';
              modx.get(modx.MODX_MANAGER_URL + '?a=71&ajax=1&submitok=Search&searchid=' + s.input.value, function(data) {
                s.loader.style.display = 'none';
                s.results = data.querySelector('.ajaxSearchResults');
                if (s.results && s.results.innerHTML !== '') {
                  s.result.innerHTML = s.results.outerHTML;
                  s.open();
                  s.result.onclick = function(e) {
                    var t = e.target,
                        p = t.parentNode;
                    if (t.tagName === 'I') {
                      modx.openWindow({
                        title: p.innerText,
                        id: p.id,
                        url: p.href
                      });
                      e.preventDefault();
                      e.stopPropagation();
                    } else {
                      var a = t.tagName === 'A' && t || p.tagName === 'A' && p;
                      if (a) {
                        var el = s.result.querySelector('.selected');
                        if (el) el.className = '';
                        a.className = 'selected';
                        if (modx.isMobile) s.close();
                      }
                    }
                  };
                } else {
                  s.empty();
                }
              }, 'document');
            }, 300);
          } else {
            s.empty();
          }
        };
        if (modx.isMobile) {
          this.input.onblur = this.close;
        }
        this.input.onfocus = this.open;
        this.input.onclick = this.open;
        this.input.onmouseenter = this.open;
        this.result.onmouseenter = this.open;
        this.result.onmouseleave = this.close;
        this.mask.onmouseenter = this.open;
        this.mask.onmouseleave = this.close;
      },
      open: function() {
        if (modx.search.results) {
          modx.search.result.classList.add('open');
        }
      },
      close: function() {
        modx.search.result.classList.remove('open');
      },
      empty: function() {
        modx.search.result.classList.remove('open');
        modx.search.result.innerHTML = '';
      }
    },
    main: {
      id: 'main',
      idFrame: 'mainframe',
      as: null,
      onload: function(e) {
        w.main = e.target.contentWindow || e.target.defaultView;
        modx.main.tabRow.init();
        modx.main.stopWork();
        modx.main.scrollWork();
        w.main.document.addEventListener('click', modx.hideDropDown, false);
        w.main.document.addEventListener('contextmenu', modx.main.oncontextmenu, false);
        if (modx.config.global_tabs) {
          w.main.document.addEventListener('click', modx.tabs, false);
        }
        w.history.replaceState(null, d.title, modx.getActionFromUrl(w.main.location.search, 2) ? modx.MODX_MANAGER_URL : '#' + w.main.location.search);
        setTimeout('modx.tree.restoreTree()', 100);
      },
      oncontextmenu: function(e) {
        if (e.ctrlKey) return;
        var el = e.target;
        if (modx.user.role === 1 && /modxtv|modxplaceholder|modxattributevalue|modxchunk|modxsnippet|modxsnippetnocache/i.test(el.className)) {
          var id = Date.now(),
              name = el.innerText.replace(/[\[|\]|{|}|\*||\#|\+|?|\!|&|=|`|:]/g, ''),
              type = el.className.replace(/cm-modx/, ''),
              n = !!name.replace(/^\d+$/, '');
          if (name && n) {
            e.preventDefault();
            modx.post(modx.MODX_MANAGER_URL + 'media/style/' + modx.config.theme + '/ajax.php', {
              a: 'modxTagHelper',
              name: name,
              type: type
            }, function(r) {
              if (r) {
                r = JSON.parse(r);
                for (var k in r) {
                  if (r.hasOwnProperty(k) && r[k].url) {
                    r[k]['onclick'] = 'if(event.shiftKey){modx.openWindow({url:\'' + r[k].url + '\'})}else{modx.popup({url:\'' + r[k].url + '\',title:\'' + r.header.innerHTML + '\',width:\'95%\',height:\'95%\',margin:0,hide:0,hover:0,overlay:1,overlayclose:0,position:\'center elements\',animation:0,icon:\'none\'})}';
                  }
                }
                r = JSON.stringify(r);
                el.id = 'node' + id;
                el.dataset.contextmenu = r;
                modx.tree.showPopup(e, id, name);
              }
            });
          }
          e.preventDefault();
        }
      },
      tabRow: {
        init: function() {
          var row = w.main.document.querySelector('.tab-pane > .tab-row');
          if (row) this.build(row);
        },
        build: function(row) {
          var rowContainer = d.createElement('div'),
              sel = row.querySelector('.selected');
          rowContainer.className = 'tab-row-container';
          row.parentNode.insertBefore(rowContainer, row);
          rowContainer.appendChild(row);
          var p = d.createElement('i');
          p.className = 'fa fa-angle-left prev disable';
          p.onclick = function(e) {
            e.stopPropagation();
            e.preventDefault();
            var sel = row.querySelector('.selected');
            if (sel.previousSibling) {
              sel.previousSibling.click();
              modx.main.tabRow.scroll(row);
            }
          };
          rowContainer.appendChild(p);
          var n = d.createElement('i');
          n.className = 'fa fa-angle-right next disable';
          n.onclick = function(e) {
            e.stopPropagation();
            e.preventDefault();
            var sel = row.querySelector('.selected');
            if (sel.nextSibling) {
              sel.nextSibling.click();
              modx.main.tabRow.scroll(row);
            }
          };
          rowContainer.appendChild(n);
          setTimeout(function() {
            sel = row.querySelector('.selected');
            modx.main.tabRow.scroll(row, sel);
            w.main.addEventListener('resize', function() {
              modx.main.tabRow.scroll(row);
            }, false);
            if (sel) {
              if (sel.previousSibling) p.classList.remove('disable');
              if (sel.nextSibling) n.classList.remove('disable');
            }
          }, 100);
          row.onclick = function(e) {
            var sel = e.target.tagName === 'H2' ? e.target : e.target.tagName === 'SPAN' ? e.target.parentNode : null;
            if (sel) {
              if (sel.previousSibling) {
                this.parentNode.querySelector('i.prev').classList.remove('disable');
              } else {
                this.parentNode.querySelector('i.prev').classList.add('disable');
              }
              if (sel.nextSibling) {
                this.parentNode.querySelector('i.next').classList.remove('disable');
              } else {
                this.parentNode.querySelector('i.next').classList.add('disable');
              }
              modx.main.tabRow.scroll(this, sel);
            }
          };
        },
        scroll: function(row, sel, a) {
          sel = sel || row.querySelector('.selected') || row.firstChild;
          a = a || 64;
          var c = 0,
              elms = row.childNodes;
          for (var i = 0; i < elms.length; i++) {
            c += elms[i].offsetWidth;
          }
          if (row.scrollLeft > sel.offsetLeft) {
            modx.animate(row, {
              scrollLeft: sel.offsetLeft - (sel.previousSibling ? 30 : 1)
            }, a);
          }
          if (sel.offsetLeft + sel.offsetWidth > row.offsetWidth + row.scrollLeft) {
            modx.animate(row, {
              scrollLeft: sel.offsetLeft - row.offsetWidth + sel.offsetWidth + (sel.nextSibling ? 30 : 0)
            }, a);
          }
          if (c > row.offsetWidth) {
            this.drag(row);
          }
        },
        drag: function(row) {
          row.onmousedown = function(e) {
            if (e.button === 0) {
              e.preventDefault();
              var x = e.clientX,
                  f = row.scrollLeft;
              row.ownerDocument.body.focus();
              row.onmousemove = row.ownerDocument.onmousemove = function(e) {
                if (Math.abs(e.clientX - x) > 5) {
                  e.stopPropagation();
                  row.scrollLeft = f - (e.clientX - x);
                  row.ownerDocument.body.classList.add('drag');
                }
              };
              row.onmouseup = row.ownerDocument.onmouseup = function(e) {
                e.stopPropagation();
                row.onmousemove = null;
                row.ownerDocument.onmousemove = null;
                row.ownerDocument.body.classList.remove('drag');
              };
            }
          };
        }
      },
      work: function() {
        d.getElementById('mainloader').classList.add('show');
      },
      stopWork: function() {
        d.getElementById('mainloader').classList.remove('show');
      },
      scrollWork: function() {
        var a = w.main.frameElement.contentWindow,
            b = a.location.search.substring(1) || a.location.hash.substring(2),
            c = localStorage.getItem('page_y') || 0,
            f = localStorage.getItem('page_url') || b;
        if (((modx.getActionFromUrl(f) === modx.getActionFromUrl(b)) && (modx.main.getQueryVariable('id', f) && modx.main.getQueryVariable('id', f) === modx.main.getQueryVariable('id', b))) || (f === b)) {
          a.scrollTo(0, c);
        }
        a.addEventListener('scroll', function() {
          if (this.pageYOffset >= 0) {
            localStorage.setItem('page_y', this.pageYOffset.toString());
            localStorage.setItem('page_url', b);
          }
        }, false);
      },
      getQueryVariable: function(a, b) {
        var f = '';
        if (b || typeof b === 'string') {
          b = b.split('?');
          b = b[1] || b[0];
          b = b.split('&');
          for (var i = 0; i < b.length; i++) {
            var c = b[i].split('=');
            if (c[0] === a) {
              f = decodeURIComponent(c[1]);
            }
          }
        }
        return f;
      }
    },
    resizer: {
      dragElement: null,
      oldZIndex: 99,
      newZIndex: 999,
      left: modx.config.tree_width,
      id: 'resizer',
      switcher: 'hideMenu',
      background: 'rgba(0, 0, 0, 0.1)',
      mask: null,
      init: function() {
        if (!d.getElementById(modx.resizer.id)) {
          return;
        }
        modx.resizer.mask = d.createElement('div');
        modx.resizer.mask.id = 'mask_resizer';
        modx.resizer.mask.style.zIndex = modx.resizer.oldZIndex;
        d.getElementById(modx.resizer.id).onmousedown = modx.resizer.onMouseDown;
        d.getElementById(modx.resizer.id).onmouseup = modx.resizer.mask.onmouseup = modx.resizer.onMouseUp;
        if (modx.isMobile) {
          var x, y, tree = d.getElementById('tree'), h = tree.offsetWidth;
          d.getElementById('frameset').appendChild(modx.resizer.mask);
          w.addEventListener('touchstart', function(e) {
            if (!/tab|tab\-row|tab\-row\-container/.test(e.target.className || e.target.offsetParent.className)) {
              x = e.changedTouches[0].clientX;
              y = e.changedTouches[0].clientY;
              this.swipe = true;
              this.sidebar = !d.body.classList.contains('sidebar-closed');
            } else {
              this.swipe = false;
            }
          }, false);
          w.addEventListener('touchmove', function(e) {
            var touch = e.changedTouches[0];
            tree.style.transition = 'none';
            tree.style.WebkitTransition = 'none';
            modx.resizer.mask.style.transition = 'none';
            modx.resizer.mask.style.WebkitTransition = 'none';
            modx.resizer.mask.style.visibility = 'visible';
            var ax = touch.clientX - x;
            var ay = touch.clientY - y;
            if (Math.abs(ax) > Math.abs(ay) && this.swipe) {
              if (ax < 0 && this.sidebar) {
                if (Math.abs(ax) > h) ax = -h;
                tree.style.transform = 'translate3d(' + ax + 'px, 0, 0)';
                tree.style.WebkitTransform = 'translate3d(' + ax + 'px, 0, 0)';
                modx.resizer.mask.style.opacity = (0.5 - 0.5 / -h * ax).toFixed(2);
                if (Math.abs(ax) > h / 3) {
                  this.swipe = 'left';
                } else {
                  this.swipe = 'right';
                }
              } else if (ax > 0 && !this.sidebar) {
                if (Math.abs(ax) > h) ax = h;
                tree.style.transform = 'translate3d(' + -(h - ax) + 'px, 0, 0)';
                tree.style.WebkitTransform = 'translate3d(' + -(h - ax) + 'px, 0, 0)';
                modx.resizer.mask.style.opacity = (0.5 / h * ax).toFixed(2);
                if (Math.abs(ax) > h / 3) {
                  this.swipe = 'right';
                } else {
                  this.swipe = 'left';
                }
              }
            }
          }, false);
          w.addEventListener('touchend', function() {
            if (this.swipe === 'left') {
              d.body.classList.add('sidebar-closed');
              modx.resizer.setWidth(0);
            }
            if (this.swipe === 'right') {
              d.body.classList.remove('sidebar-closed');
              modx.resizer.setWidth(h);
            }
            tree.style.cssText = '';
            modx.resizer.mask.style.cssText = '';
          }, false);
        }
      },
      onMouseDown: function(e) {
        e = e || w.event;
        modx.resizer.dragElement = e.target !== null ? e.target : e.srcElement;
        if ((e.buttons === 1 || e.button === 0) && modx.resizer.dragElement.id === modx.resizer.id) {
          modx.resizer.oldZIndex = modx.resizer.dragElement.style.zIndex;
          modx.resizer.dragElement.style.zIndex = modx.resizer.newZIndex;
          modx.resizer.dragElement.style.background = modx.resizer.background;
          localStorage.setItem('MODX_widthSideBar', modx.resizer.dragElement.offsetLeft > 0 ? modx.resizer.dragElement.offsetLeft : 0);
          d.body.appendChild(modx.resizer.mask);
          d.onmousemove = modx.resizer.onMouseMove;
          d.body.focus();
          d.body.classList.add('resizer_move');
          d.onselectstart = function() {
            return false;
          };
          modx.resizer.dragElement.ondragstart = function() {
            return false;
          };
          return false;
        }
      },
      onMouseMove: function(e) {
        e = e || w.event;
        if (e.clientX > 0) {
          modx.resizer.left = e.clientX;
        } else {
          modx.resizer.left = 0;
        }
        modx.resizer.dragElement.style.left = modx.pxToRem(modx.resizer.left) + 'rem';
        d.getElementById('tree').style.width = modx.pxToRem(modx.resizer.left) + 'rem';
        d.getElementById('main').style.left = modx.pxToRem(modx.resizer.left) + 'rem';
        if (e.clientX < -2 || e.clientY < -2) {
          modx.resizer.onMouseUp(e);
        }
      },
      onMouseUp: function(e) {
        if (modx.resizer.dragElement !== null && e.button === 0 && modx.resizer.dragElement.id === modx.resizer.id) {
          if (e.clientX > 0) {
            d.body.classList.remove('sidebar-closed');
            modx.resizer.left = e.clientX;
          } else {
            d.body.classList.add('sidebar-closed');
            modx.resizer.left = 0;
          }
          d.cookie = 'MODX_widthSideBar=' + modx.pxToRem(modx.resizer.left);
          modx.resizer.dragElement.style.zIndex = modx.resizer.oldZIndex;
          modx.resizer.dragElement.style.background = '';
          modx.resizer.dragElement.ondragstart = null;
          modx.resizer.dragElement = null;
          d.body.classList.remove('resizer_move');
          d.body.removeChild(modx.resizer.mask);
          d.onmousemove = null;
          d.onselectstart = null;
        }
      },
      toggle: function() {
        if (modx.isMobile || w.innerWidth <= modx.minWidth) {
          if (d.body.classList.contains('sidebar-closed')) {
            d.body.classList.remove('sidebar-closed');
            localStorage.setItem('MODX_widthSideBar', 0);
            d.cookie = 'MODX_widthSideBar=' + modx.pxToRem(parseInt(d.getElementById('tree').offsetWidth));
          } else {
            localStorage.setItem('MODX_widthSideBar', parseInt(d.getElementById('tree').offsetWidth));
            d.body.classList.add('sidebar-closed');
            d.cookie = 'MODX_widthSideBar=0';
          }
        } else {
          var p = d.getElementById('tree').offsetWidth !== 0 ? 0 : parseInt(localStorage.getItem('MODX_widthSideBar')) ? parseInt(localStorage.getItem('MODX_widthSideBar')) : modx.config.tree_width;
          modx.resizer.setWidth(p);
        }
      },
      setWidth: function(a) {
        if (a > 0) {
          localStorage.setItem('MODX_widthSideBar', 0);
          d.body.classList.remove('sidebar-closed');
        } else {
          localStorage.setItem('MODX_widthSideBar', parseInt(d.getElementById('tree').offsetWidth));
          d.body.classList.add('sidebar-closed');
        }
        d.cookie = 'MODX_widthSideBar=' + modx.pxToRem(a);
        d.getElementById('tree').style.width = modx.pxToRem(a) + 'rem';
        d.getElementById('resizer').style.left = modx.pxToRem(a) + 'rem';
        d.getElementById('main').style.left = modx.pxToRem(a) + 'rem';
      },
      setDefaultWidth: function() {
        modx.resizer.setWidth(modx.remToPx(modx.config.tree_width));
      }
    },
    tree: {
      ctx: null,
      rpcNode: null,
      itemToChange: '',
      selectedObjectName: null,
      selectedObject: 0,
      selectedObjectDeleted: 0,
      selectedObjectUrl: '',
      drag: false,
      deleted: [],
      init: function() {
        this.restoreTree();
      },
      draggable: function() {
        if (modx.permission.dragndropdocintree) {
          var els = d.querySelectorAll('#treeRoot a:not(.empty)');
          for (var i = 0; i < els.length; i++) {
            var el = els[i];
            el.onmousedown = this.onmousedown;
            el.ondragstart = this.ondragstart;
            el.ondragenter = this.ondragenter;
            el.ondragover = this.ondragover;
            el.ondragleave = this.ondragleave;
            el.ondrop = this.ondrop;
          }
        }
      },
      onmousedown: function(e) {
        if (e.ctrlKey) {
          this.parentNode.removeAttribute('draggable');
          return true;
        } else {
          var roles = this.dataset.roles + (this.parentNode.parentNode.id !== 'treeRoot' ? this.parentNode.parentNode.previousSibling.dataset.roles : '');
          var draggable = roles && modx.user.role !== 1 ? roles.split(',').map(Number).indexOf(modx.user.role) > -1 : true;
          modx.tree.itemToChange = this.dataset.id || this.parentNode.id.replace('node', '');
          modx.tree.selectedObjectName = this.dataset.titleEsc;
          if (draggable) {
            this.parentNode.draggable = true;
            this.parentNode.ondragstart = modx.tree.ondragstart;
          } else {
            this.parentNode.draggable = false;
            this.parentNode.ondragstart = function() {
              return false;
            };
          }
        }
      },
      ondragstart: function(e) {
        e.dataTransfer.effectAllowed = 'all';
        e.dataTransfer.dropEffect = 'all';
        e.dataTransfer.setData('text', this.id.substr(4));
      },
      ondragenter: function(e) {
        if (d.getElementById('node' + modx.tree.itemToChange) === (this.parentNode.closest('#node' + modx.tree.itemToChange) || this.parentNode)) {
          this.parentNode.className = '';
          e.dataTransfer.effectAllowed = 'none';
          e.dataTransfer.dropEffect = 'none';
          modx.tree.drag = false;
        } else {
          this.parentNode.className = 'dragenter';
          e.dataTransfer.effectAllowed = 'copy';
          e.dataTransfer.dropEffect = 'copy';
          modx.tree.drag = true;
        }
        e.preventDefault();
      },
      ondragover: function(e) {
        if (modx.tree.drag) {
          var a = e.clientY;
          var b = parseInt(this.getBoundingClientRect().top);
          var c = a - b;
          if (c > this.offsetHeight / 1.51) {
            //this.parentNode.className = 'dragafter';
            this.parentNode.classList.add('dragafter');
            this.parentNode.classList.remove('dragbefore');
            this.parentNode.classList.remove('dragenter');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.dropEffect = 'move';
          } else if (c < this.offsetHeight / 3) {
            //this.parentNode.className = 'dragbefore';
            this.parentNode.classList.add('dragbefore');
            this.parentNode.classList.remove('dragafter');
            this.parentNode.classList.remove('dragenter');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.dropEffect = 'move';
          } else {
            //this.parentNode.className = 'dragenter';
            this.parentNode.classList.add('dragenter');
            this.parentNode.classList.remove('dragafter');
            this.parentNode.classList.remove('dragbefore');
            e.dataTransfer.effectAllowed = 'copy';
            e.dataTransfer.dropEffect = 'copy';
          }
        } else {
          e.dataTransfer.effectAllowed = 'none';
          e.dataTransfer.dropEffect = 'none';
          modx.tree.drag = false;
        }
        e.preventDefault();
      },
      ondragleave: function(e) {
        this.parentNode.className = '';
        this.parentNode.removeAttribute('draggable');
        e.preventDefault();
      },
      ondrop: function(e) {
        var el = d.getElementById('node' + modx.tree.itemToChange),
            els = null,
            id = modx.tree.itemToChange,
            parent = 0,
            menuindex = [],
            level = 0,
            indent = el.firstChild.querySelector('.indent'),
            i = 0;
        indent.innerHTML = '';
        el.removeAttribute('draggable');
        if (this.parentNode.classList.contains('dragenter')) {
          parent = parseInt(this.parentNode.id.substr(4));
          level = parseInt(this.dataset.level) + 1;
          for (i = 0; i < level; i++) {
            indent.innerHTML += '<i></i>';
          }
          if (this.nextSibling) {
            if (this.nextSibling.innerHTML) {
              this.nextSibling.appendChild(el);
            } else {
              el.parentNode.removeChild(el);
            }
            els = this.parentNode.lastChild.children;
            for (i = 0; i < els.length; i++) {
              menuindex[i] = els[i].id.substr(4);
            }
          } else {
            el.parentNode.removeChild(el);
            d.querySelector('#node' + parent + ' .icon').innerHTML = parseInt(this.dataset.private) ? modx.style.tree_folder_secure : modx.style.tree_folder;
          }
          modx.tree.ondragupdate(this, id, parent, menuindex);
        }
        if (this.parentNode.classList.contains('dragafter')) {
          parent = /node/.test(this.parentNode.parentNode.parentNode.id) ? parseInt(this.parentNode.parentNode.parentNode.id.substr(4)) : 0;
          level = parseInt(this.dataset.level);
          for (i = 0; i < level; i++) {
            indent.innerHTML += '<i></i>';
          }
          this.parentNode.parentNode.insertBefore(el, this.parentNode.nextSibling);
          els = this.parentNode.parentNode.children;
          for (i = 0; i < els.length; i++) {
            menuindex[i] = els[i].id.substr(4);
          }
          modx.tree.ondragupdate(this, id, parent, menuindex);
        }
        if (this.parentNode.classList.contains('dragbefore')) {
          parent = /node/.test(this.parentNode.parentNode.parentNode.id) ? parseInt(this.parentNode.parentNode.parentNode.id.substr(4)) : 0;
          level = parseInt(this.dataset.level);
          for (i = 0; i < level; i++) {
            indent.innerHTML += '<i></i>';
          }
          this.parentNode.parentNode.insertBefore(el, this.parentNode);
          els = this.parentNode.parentNode.children;
          for (i = 0; i < els.length; i++) {
            menuindex[i] = els[i].id.substr(4);
          }
          modx.tree.ondragupdate(this, id, parent, menuindex);
        }
        this.parentNode.removeAttribute('class');
        this.parentNode.removeAttribute('draggable');
        e.preventDefault();
      },
      ondragupdate: function(a, id, parent, menuindex) {
        var roles = a.dataset.roles + (a.parentNode.parentNode.id !== 'treeRoot' ? a.parentNode.parentNode.previousSibling.dataset.roles : '');
        if (!(roles && modx.user.role !== 1 ? roles.split(',').map(Number).indexOf(modx.user.role) > -1 : true)) {
          alert(modx.lang.error_no_privileges);
          modx.tree.restoreTree();
          return;
        }
        modx.post(modx.MODX_MANAGER_URL + 'media/style/' + modx.config.theme + '/ajax.php', {
          a: 'movedocument',
          id: id,
          parent: parent,
          menuindex: menuindex
        }, function(r) {
          if (r.errors) alert(r.errors);
          modx.tree.restoreTree();
        }, 'json');
        var b = w.main.frameElement.contentWindow.location.search.substr(1);
        if (modx.getActionFromUrl(b, 27) && parseInt(modx.main.getQueryVariable('id', b)) === parseInt(id)) {
          var index = menuindex.indexOf(id),
              elMenuIndex = w.main.document.querySelector('#documentPane input[name=menuindex]'),
              elParent = w.main.document.querySelector('#documentPane input[name=parent]'),
              elParentName = w.main.document.querySelector('#documentPane #parentName');
          if (elMenuIndex && index >= 0) elMenuIndex.value = index;
          if (elParent && elParentName) {
            elParent.value = parent;
            elParentName.innerHTML = parent + ' (' + d.querySelector('#node' + parent + ' > a').dataset.titleEsc + ')';
          }
        }
      },
      toggleTheme: function() {
        var a, b = 1, myCodeMirrors = w.main.myCodeMirrors, key;
        if (typeof localStorage['MODX_themeMode'] === 'undefined') {
          localStorage['MODX_themeMode'] = modx.config.theme_mode;
        }
        if (modx.thememodes[parseInt(localStorage['MODX_themeMode']) + 1]) {
          b = parseInt(localStorage['MODX_themeMode']) + 1;
        }
        a = modx.thememodes[b];
        for (key in modx.thememodes) {
          if (modx.thememodes[key]) {
            d.body.classList.remove(modx.thememodes[key]);
            w.main.document.body.classList.remove(modx.thememodes[key]);
          }
        }
        d.body.classList.add(a);
        w.main.document.body.classList.add(a);
        d.cookie = 'MODX_themeMode=' + b;
        localStorage['MODX_themeMode'] = b;
        if (typeof myCodeMirrors !== 'undefined') {
          for (key in myCodeMirrors) {
            if (myCodeMirrors.hasOwnProperty(key)) {
              if (~a.indexOf('dark')) {
                w.main.document.getElementsByName(key)[0].nextElementSibling.classList.add('cm-s-' + myCodeMirrors[key].options.darktheme);
                w.main.document.getElementsByName(key)[0].nextElementSibling.classList.remove('cm-s-' + myCodeMirrors[key].options.defaulttheme);
              } else {
                w.main.document.getElementsByName(key)[0].nextElementSibling.classList.remove('cm-s-' + myCodeMirrors[key].options.darktheme);
                w.main.document.getElementsByName(key)[0].nextElementSibling.classList.add('cm-s-' + myCodeMirrors[key].options.defaulttheme);
              }
            }
          }
        }
      },
      toggleNode: function(e, id) {
        e = e || w.event;
        if (e.ctrlKey) return;
        e.stopPropagation();
        var el = d.getElementById('node' + id).firstChild;
        this.rpcNode = el.nextSibling;
        var toggle = el.querySelector('.toggle'),
            icon = el.querySelector('.icon');
        if (this.rpcNode.innerHTML === '') {
          if (toggle) toggle.innerHTML = el.dataset.iconCollapsed;
          icon.innerHTML = el.dataset.iconFolderOpen;
          var rpcNodeText = this.rpcNode.innerHTML,
              loadText = modx.lang.loading_doc_tree;
          modx.openedArray[id] = 1;
          if (rpcNodeText === '' || rpcNodeText.indexOf(loadText) > 0) {
            var folderState = this.getFolderState();
            d.getElementById('treeloader').classList.add('visible');
            modx.post(modx.MODX_MANAGER_URL + 'media/style/' + modx.config.theme + '/ajax.php', 'a=1&f=nodes&indent=' + el.dataset.indent + '&parent=' + id + '&expandAll=' + el.dataset.expandall + folderState, function(r) {
              modx.tree.rpcLoadData(r);
              modx.tree.draggable();
            });
          }
          this.saveFolderState();
        } else {
          if (toggle) toggle.innerHTML = el.dataset.iconExpanded;
          icon.innerHTML = el.dataset.iconFolderClose;
          delete modx.openedArray[id];
          this.rpcNode.style.overflow = 'hidden';
          modx.animate(this.rpcNode.firstChild, {
            marginTop: -this.rpcNode.offsetHeight
          }, 64, function() {
            this.parentNode.innerHTML = '';
          });
          this.saveFolderState();
        }
        e.preventDefault();
      },
      rpcLoadData: function(a) {
        if (this.rpcNode !== null) {
          var el;
          this.rpcNode.innerHTML = typeof a === 'object' ? a.responseText : a;
          this.rpcNode.loaded = true;
          if (this.rpcNode.firstChild.tagName === 'DIV') {
            if (this.rpcNode.id === 'treeRoot') {
              el = d.getElementById('binFull');
              if (el) {
                this.showBin(true);
              } else {
                this.showBin(false);
              }
            } else {
              this.rpcNode.style.overflow = 'hidden';
              this.rpcNode.firstElementChild.style.marginTop = -this.rpcNode.offsetHeight + 'px';
              modx.animate(this.rpcNode.firstChild, {
                marginTop: 0
              }, 64);
            }
            d.getElementById('treeloader').classList.remove('visible');
          } else {
            el = d.getElementById('loginfrm');
            if (el) {
              this.rpcNode.parentNode.removeChild(this.rpcNode);
              w.location.href = modx.MODX_MANAGER_URL;
            }
          }
        }
      },
      treeAction: function(e, id, title) {
        if (e.ctrlKey) return;
        var el = d.getElementById('node' + id).firstChild,
            treepageclick = el.dataset.treepageclick,
            showchildren = parseInt(el.dataset.showchildren),
            openfolder = parseInt(el.dataset.openfolder);
        title = title || el.dataset && el.dataset.titleEsc;
        if (tree.ca === 'move') {
          try {
            this.setSelectedByContext(id);
            w.main.setMoveValue(id, title);
          } catch (oException) {
            alert(modx.lang.unable_set_parent);
          }
        }
        if (tree.ca === 'open' || tree.ca === '') {
          if (id === 0) {
            href = '?a=2';
          } else {
            var href = '';
            if (!isNaN(treepageclick) && isFinite(treepageclick)) {
              href = '?a=' + treepageclick + '&r=1&id=' + id + (openfolder === 0 ? this.getFolderState() : '');
            } else {
              href = treepageclick;
            }
            if (openfolder === 2) {
              if (showchildren !== 1) {
                href = '';
              }
              this.toggleNode(e, id);
            }
          }
          if (href) {
            if (e.shiftKey) {
              w.getSelection().removeAllRanges();
              modx.openWindow(href);
              this.restoreTree();
            } else {
              modx.tabs({url: modx.MODX_MANAGER_URL + href, title: title + '<small>(' + id + ')</small>'});
              if (modx.isMobile && w.innerWidth < modx.minWidth) modx.resizer.toggle();
            }
          }
          this.itemToChange = id;
          this.setSelected(id);
        }
        if (tree.ca === 'parent') {
          try {
            this.setSelectedByContext(id);
            w.main.setParent(id, title);
          } catch (oException) {
            alert(modx.lang.unable_set_parent);
          }
        }
        if (tree.ca === 'link') {
          try {
            this.setSelectedByContext(id);
            w.main.setLink(id);
          } catch (oException) {
            alert(modx.lang.unable_set_link);
          }
        }
        e.preventDefault();
      },
      showPopup: function(e, id, title) {
        if (e.ctrlKey) return;
        e.preventDefault();
        var tree = d.getElementById('tree'),
            el = d.getElementById('node' + id) || e.target,
            x = 0,
            y = 0;
        if (el.firstChild && el.firstChild.dataset && el.firstChild.dataset.contextmenu) {
          el = el.firstChild;
        }
        if (el) {
          if (el.dataset.contextmenu) {
            e.target.dataset.toggle = '#contextmenu';
            modx.hideDropDown(e);
            this.ctx = d.createElement('div');
            this.ctx.id = 'contextmenu';
            this.ctx.className = 'dropdown-menu';
            d.getElementById(modx.frameset).appendChild(this.ctx);
            this.setSelectedByContext(id);
            var dataJson = JSON.parse(el.dataset.contextmenu);
            for (var key in dataJson) {
              if (dataJson.hasOwnProperty(key)) {
                var item = d.createElement('div');
                for (var k in dataJson[key]) {
                  if (dataJson[key].hasOwnProperty(k)) {
                    if (k.substring(0, 2) === 'on') {
                      var onEvent = dataJson[key][k];
                      item[k] = function(onEvent) {
                        return function(event) {
                          eval(onEvent);
                        };
                      }(onEvent);
                    } else {
                      item[k] = dataJson[key][k];
                    }
                  }
                }
                if (key.indexOf('header') === 0) item.className += ' menuHeader';
                if (key.indexOf('item') === 0) item.className += ' menuLink';
                if (key.indexOf('seperator') === 0 || key.indexOf('separator') === 0) item.className += ' seperator separator';
                this.ctx.appendChild(item);
              }
            }
            x = e.clientX > 0 ? e.clientX : e.pageX;
            y = e.clientY > 0 ? e.clientY : e.pageY;
            e.view.position = e.view.frameElement ? e.view.frameElement.getBoundingClientRect() : e.target.offsetParent.getBoundingClientRect();
            if (e.view.frameElement) {
              x += e.view.position.left;
              y += e.view.frameElement.offsetParent.offsetTop;
            } else {
              if (e.target.parentNode.parentNode.classList.contains('node')) {
                x += 50;
              }
            }
            if (x > e.view.position.width) {
              x = e.view.position.width - this.ctx.offsetWidth;
            }
            if (y + this.ctx.offsetHeight / 2 > e.view.position.height) {
              y = e.view.position.height - this.ctx.offsetHeight - 5;
            } else if (y - this.ctx.offsetHeight / 2 < e.view.position.top) {
              y = e.view.position.top + 5;
            } else {
              y = y - this.ctx.offsetHeight / 2;
            }
            this.itemToChange = id;
            this.selectedObjectName = title;
            this.dopopup(this.ctx, x + 10, y);
          } else {
            el = el.firstChild;
            var ctx = d.getElementById('mx_contextmenu');
            e.target.dataset.toggle = '#mx_contextmenu';
            modx.hideDropDown(e);
            this.setSelectedByContext(id);
            var i4 = d.getElementById('item4'),
                i5 = d.getElementById('item5'),
                i8 = d.getElementById('item8'),
                i9 = d.getElementById('item9'),
                i10 = d.getElementById('item10'),
                i11 = d.getElementById('item11');
            if (modx.permission.publish_document === 1) {
              i9.style.display = 'block';
              i10.style.display = 'block';
              if (parseInt(el.dataset.published) === 1) {
                i9.style.display = 'none';
              } else {
                i10.style.display = 'none';
              }
            } else if (i5) {
              i5.style.display = 'none';
            }
            if (modx.permission.delete_document === 1) {
              i4.style.display = 'block';
              i8.style.display = 'block';
              if (parseInt(el.dataset.deleted) === 1) {
                i4.style.display = 'none';
                i9.style.display = 'none';
                i10.style.display = 'none';
              } else {
                i8.style.display = 'none';
              }
            }
            if (i11) {
              if (parseInt(el.dataset.isfolder) === 1) {
                i11.style.display = 'block';
              } else {
                i11.style.display = 'none';
              }
            }
            var bodyHeight = tree.offsetHeight + tree.offsetTop;
            x = e.clientX > 0 ? e.clientX : e.pageX;
            y = e.clientY > 0 ? e.clientY : e.pageY;
            if (y + ctx.offsetHeight / 2 > bodyHeight) {
              y = bodyHeight - ctx.offsetHeight - 5;
            } else if (y - ctx.offsetHeight / 2 < tree.offsetTop) {
              y = tree.offsetTop + 5;
            } else {
              y = y - ctx.offsetHeight / 2;
            }
            if (e.target.parentNode.parentNode.classList.contains('node')) x += 50;
            this.itemToChange = id;
            this.selectedObjectName = title;
            if (ctx.classList.contains('show')) {
              ctx.classList.remove('show');
              setTimeout(function() {
                modx.tree.dopopup(ctx, x + 10, y);
              }, 100);
            } else {
              this.dopopup(ctx, x + 10, y);
            }
          }
          e.stopPropagation();
        }
      },
      dopopup: function(el, a, b) {
        if (this.selectedObjectName.length > 30) {
          this.selectedObjectName = this.selectedObjectName.substr(0, 30) + '...';
        }
        var f = d.getElementById('nameHolder');
        f.innerHTML = this.selectedObjectName;
        el.style.left = a + (modx.config.textdir ? '-190' : '') + 'px';
        el.style.top = b + 'px';
        el.classList.add('show');
      },
      menuHandler: function(a) {
        switch (a) {
          case 1:
            this.setActiveFromContextMenu(this.itemToChange);
            modx.tabs({url: modx.MODX_MANAGER_URL + '?a=3&id=' + this.itemToChange, title: this.selectedObjectName + '<small>(' + this.itemToChange + ')</small>'});
            break;
          case 2:
            this.setActiveFromContextMenu(this.itemToChange);
            modx.tabs({url: modx.MODX_MANAGER_URL + '?a=27&r=1&id=' + this.itemToChange, title: this.selectedObjectName + '<small>(' + this.itemToChange + ')</small>'});
            break;
          case 3:
            modx.tabs({url: modx.MODX_MANAGER_URL + '?a=4&pid=' + this.itemToChange, title: this.selectedObjectName + '<small>(' + this.itemToChange + ')</small>'});
            break;
          case 4:
            if (this.selectedObjectDeleted) {
              alert('"' + this.selectedObjectName + '" ' + modx.lang.already_deleted);
            } else if (confirm('"' + this.selectedObjectName + '"\n\n' + modx.lang.confirm_delete_resource) === true) {
              modx.tabs({url: modx.MODX_MANAGER_URL + '?a=6&id=' + this.itemToChange, title: this.selectedObjectName + '<small>(' + this.itemToChange + ')</small>'});
            }
            break;
          case 5:
            this.setActiveFromContextMenu(this.itemToChange);
            modx.tabs({url: modx.MODX_MANAGER_URL + '?a=51&id=' + this.itemToChange, title: this.selectedObjectName + '<small>(' + this.itemToChange + ')</small>'});
            break;
          case 6:
            modx.tabs({url: modx.MODX_MANAGER_URL + '?a=72&pid=' + this.itemToChange, title: this.selectedObjectName + '<small>(' + this.itemToChange + ')</small>'});
            break;
          case 7:
            if (confirm(modx.lang.confirm_resource_duplicate) === true) {
              modx.tabs({url: modx.MODX_MANAGER_URL + '?a=94&id=' + this.itemToChange, title: this.selectedObjectName + '<small>(' + this.itemToChange + ')</small>'});
            }
            break;
          case 8:
            if (d.getElementById('node' + this.itemToChange).firstChild.dataset.deleted) {
              if (confirm('"' + this.selectedObjectName + '" ' + modx.lang.confirm_undelete) === true) {
                modx.tabs({url: modx.MODX_MANAGER_URL + '?a=63&id=' + this.itemToChange, title: this.selectedObjectName + '<small>(' + this.itemToChange + ')</small>'});
              }
            } else {
              alert('"' + this.selectedObjectName + '"' + modx.lang.not_deleted);
            }
            break;
          case 9:
            if (confirm('"' + this.selectedObjectName + '" ' + modx.lang.confirm_publish) === true) {
              modx.tabs({url: modx.MODX_MANAGER_URL + '?a=61&id=' + this.itemToChange, title: this.selectedObjectName + '<small>(' + this.itemToChange + ')</small>'});
            }
            break;
          case 10:
            if (this.itemToChange !== modx.config.site_start) {
              if (confirm('"' + this.selectedObjectName + '" ' + modx.lang.confirm_unpublish) === true) {
                modx.tabs({url: modx.MODX_MANAGER_URL + '?a=62&id=' + this.itemToChange, title: this.selectedObjectName + '<small>(' + this.itemToChange + ')</small>'});
              }
            } else {
              alert('Document is linked to site_start variable and cannot be unpublished!');
            }
            break;
          case 11:
            modx.tabs({url: modx.MODX_MANAGER_URL + '?a=56&id=' + this.itemToChange, title: this.selectedObjectName + '<small>(' + this.itemToChange + ')</small>'});
            break;
          case 12:
            w.open(d.getElementById('node' + this.itemToChange).firstChild.dataset.href, 'previeWin');
            break;
          default:
            alert('Unknown operation command.');
        }
      },
      setSelected: function(a) {
        var el = d.querySelector('#tree .current');
        if (el) el.classList.remove('current');
        if (a) {
          if (typeof a === 'object') {
            a.classList.add('current');
          } else {
            el = d.querySelector('#node' + a + '>.node') || d.getElementById('node' + a);
            if (el) el.classList.add('current');
          }
        }
      },
      setActiveFromContextMenu: function(a) {
        var el = d.querySelector('#node' + a + '>.node');
        if (el) this.setSelected(el);
      },
      setSelectedByContext: function(a) {
        var el = d.querySelector('#treeRoot .selected');
        if (el) el.classList.remove('selected');
        el = d.querySelector('#node' + a + '>.node');
        if (el) el.classList.add('selected');
      },
      setItemToChange: function() {
        var a = w.main.document && (w.main.document.URL || w.main.document.location.search),
            b = modx.getActionFromUrl(a);
        if (a && modx.typesactions[b]) {
          this.itemToChange = (modx.typesactions[b] === 7 ? '' : modx.typesactions[b] + '_') + parseInt(modx.main.getQueryVariable('id', a));
        } else {
          this.itemToChange = '';
        }
        this.setSelected(this.itemToChange);
      },
      restoreTree: function() {
        //console.log('modx.tree.restoreTree()');
        if (d.getElementById('treeRoot')) {
          d.getElementById('treeloader').classList.add('visible');
          this.setItemToChange();
          this.rpcNode = d.getElementById('treeRoot');
          modx.post(modx.MODX_MANAGER_URL + 'media/style/' + modx.config.theme + '/ajax.php', 'a=1&f=nodes&indent=1&parent=0&expandAll=2&id=' + this.itemToChange, function(r) {
            modx.tree.rpcLoadData(r);
            modx.tree.draggable();
          });
        }
      },
      expandTree: function() {
        this.rpcNode = d.getElementById('treeRoot');
        d.getElementById('treeloader').classList.add('visible');
        modx.post(modx.MODX_MANAGER_URL + 'media/style/' + modx.config.theme + '/ajax.php', 'a=1&f=nodes&indent=1&parent=0&expandAll=1&id=' + this.itemToChange, function(r) {
          modx.tree.rpcLoadData(r);
          modx.tree.saveFolderState();
          modx.tree.draggable();
        });
      },
      collapseTree: function() {
        this.rpcNode = d.getElementById('treeRoot');
        d.getElementById('treeloader').classList.add('visible');
        modx.post(modx.MODX_MANAGER_URL + 'media/style/' + modx.config.theme + '/ajax.php', 'a=1&f=nodes&indent=1&parent=0&expandAll=0&id=' + this.itemToChange, function(r) {
          modx.openedArray = [];
          modx.tree.saveFolderState();
          modx.tree.rpcLoadData(r);
          modx.tree.draggable();
        });
      },
      updateTree: function() {
        this.rpcNode = d.getElementById('treeRoot');
        d.getElementById('treeloader').classList.add('visible');
        var a = d.sortFrm;
        var b = 'a=1&f=nodes&indent=1&parent=0&expandAll=2&dt=' + a.dt.value + '&tree_sortby=' + a.sortby.value + '&tree_sortdir=' + a.sortdir.value + '&tree_nodename=' + a.nodename.value + '&id=' + this.itemToChange + '&showonlyfolders=' + a.showonlyfolders.value;
        modx.post(modx.MODX_MANAGER_URL + 'media/style/' + modx.config.theme + '/ajax.php', b, function(r) {
          modx.tree.rpcLoadData(r);
          modx.tree.draggable();
        });
      },
      getFolderState: function() {
        var a;
        if (modx.openedArray !== [0]) {
          a = '&opened=';
          for (var key in modx.openedArray) {
            if (modx.openedArray[key]) {
              a += key + '|';
            }
          }
        } else {
          a = '&opened=';
        }
        return a;
      },
      saveFolderState: function() {
        modx.post(modx.MODX_MANAGER_URL + 'media/style/' + modx.config.theme + '/ajax.php', 'a=1&f=nodes&savestateonly=1' + this.getFolderState());
      },
      showSorter: function(e) {
        e = e || w.event;
        var el = d.getElementById('floater');
        e.target.dataset.toggle = '#floater';
        el.classList.toggle('show');
        el.onclick = function(e) {
          e.stopPropagation();
        };
      },
      emptyTrash: function() {
        if (confirm(modx.lang.confirm_empty_trash) === true) {
          modx.get(modx.MODX_MANAGER_URL + '?a=64', function() {
            modx.tabsClose(modx.tree.deleted);
            modx.tree.deleted = [];
            modx.tree.restoreTree();
          });
        }
      },
      showBin: function(a) {
        var el = d.getElementById('treeMenu_emptytrash');
        if (el) {
          if (a) {
            el.title = modx.lang.empty_recycle_bin;
            el.classList.remove('disabled');
            el.innerHTML = modx.style.empty_recycle_bin;
            el.onclick = function() {
              modx.tree.emptyTrash();
            };
            var els = d.getElementById('tree').querySelectorAll('.deleted');
            for (var i = 0; i < els.length; i++) {
              this.deleted[els[i].dataset.id] = 'evo-tab-page-' + modx.urlToUid('a=27&id=' + els[i].dataset.id);
            }
          } else {
            el.title = modx.lang.empty_recycle_bin_empty;
            el.classList.add('disabled');
            el.innerHTML = modx.style.empty_recycle_bin_empty;
            el.onclick = null;
          }
        }
      },
      unlockElement: function(a, b, c) {
        var m = modx.lockedElementsTranslation.msg.replace('[+id+]', b).replace('[+element_type+]', modx.lockedElementsTranslation['type' + a]);
        if (confirm(m) === true) {
          modx.get(modx.MODX_MANAGER_URL + '?a=67&type=' + a + '&id=' + b, function(r) {
            if (parseInt(r) === 1) {
              c.parentNode.removeChild(c);
            } else {
              alert(r);
            }
          });
        }
      },
      resizeTree: function() {}
    },
    removeLocks: function() {
      if (confirm(modx.lang.confirm_remove_locks) === true) {
        //w.main.location.href = modx.MODX_MANAGER_URL + '?a=67'
        modx.get(modx.MODX_MANAGER_URL + '?a=67', function() {
          modx.tree.restoreTree();
        });
      }
    },
    keepMeAlive: function() {
      modx.get('includes/session_keepalive.php?tok=' + d.getElementById('sessTokenInput').value + '&o=' + Math.random(), function(r) {
        r = JSON.parse(r);
        if (r.status !== 'ok') w.location.href = modx.MODX_MANAGER_URL + '?a=8';
      });
    },
    updateMail: function(a) {
      try {
        if (a) {
          this.post(modx.MODX_MANAGER_URL, {
            updateMsgCount: true
          }, function(r) {
            var c = r.split(','),
                el = d.getElementById('msgCounter');
            if (c[0] > 0) {
              if (el) {
                el.innerHTML = c[0];
                el.style.display = 'block';
              }
            } else {
              if (el) el.style.display = 'none';
            }
            if (c[1] > 0) {
              el = d.getElementById('newMail');
              if (el) {
                el.innerHTML = '<a href="javascript:;" onclick="modx.tabs({url:\'' + modx.MODX_MANAGER_URL + '?a=10\'' + ',title:\'' + modx.lang.inbox + '\'' + '});">' + modx.style.email + modx.lang.inbox + ' (' + c[0] + ' / ' + c[1] + ')</a>';
                el.style.display = 'block';
              }
            }
            if (modx.config.mail_check_timeperiod > 0) setTimeout('modx.updateMail(true)', 1000 * modx.config.mail_check_timeperiod);
          });
        }
      } catch (oException) {
        setTimeout('modx.updateMail(true)', 1000 * modx.config.mail_check_timeperiod);
      }
    },
    openWindow: function(a) {
      if (typeof a !== 'object') {
        a = {
          'url': a
        };
      }
      if (!a.width) a.width = parseInt(w.innerWidth * 0.9) + 'px';
      if (!a.height) a.height = parseInt(w.innerHeight * 0.8) + 'px';
      if (!a.left) a.left = parseInt(w.innerWidth * 0.05) + 'px';
      if (!a.top) a.top = parseInt(w.innerHeight * 0.1) + 'px';
      if (!a.title) a.title = Math.floor((Math.random() * 999999) + 1);
      if (a.url) {
        if (this.plugins.EVOmodal === 1) {
          top.EVO.modal.show(a);
        } else {
          w.open(a.url, a.title, 'width=' + a.width + ',height=' + a.height + ',top=' + a.top + ',left=' + a.left + ',toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no');
        }
      }
    },
    tabsClose: function(a) {
      if (modx.config.global_tabs && a) {
        for (var k in a) {
          if (a.hasOwnProperty(k)) {
            var el = d.getElementById(a[k]);
            if (el) {
              el.firstElementChild.contentWindow.documentDirty = false;
              el.close();
            }
          }
        }
      }
    },
    tabs: function(a) {
      function Tabs(a)
      {
        var s = this;
        this.url = a.url;
        this.title = a.title || '';
        this.name = a.name || '';
        this.timer = null;
        this.olduid = '';
        this.closeactions = [6, 61, 62, 63, 94];
        this.saveAndCloseActions = [75, 86, 99, 106];
        this.reload = typeof a.reload !== 'undefined' ? a.reload : 1;
        this.action = modx.getActionFromUrl(a.url);
        this.uid = modx.getActionFromUrl(a.url, 2) ? 'home' : modx.urlToUid(a.url);
        this.page = d.getElementById('evo-tab-page-' + this.uid);
        this.row = d.getElementsByClassName('evo-tab-row')[0].firstElementChild;
        this.tab = d.getElementById('evo-tab-' + this.uid);
        if (this.page) {
          if (this.uid === 'home') {
            if (this.reload) {
              this.page.firstElementChild.src = this.url;
              this.show();
            } else {
              this.reload = 1;
              if (this.tab.onclick === null) {
                this.tab.onclick = function(e) {
                  s.select.call(s, e, this);
                };
              }
              this.tab.show = function(e) {
                s.show.call(s, e);
              };
              modx.tabs.selected = this.tab;
            }
          } else {
            this.show();
            if (~this.closeactions.indexOf(this.action)) {
              this.setDocPublished();
              modx.get(this.url, function() {
                modx.tree.restoreTree();
              });
            }
          }
        } else if (~this.closeactions.indexOf(this.action)) {
          modx.get(this.url, function() {
            modx.tree.restoreTree();
          });
        } else {
          this.create();
        }
      }

      Tabs.prototype = {
        create: function() {
          var s = this;
          modx.main.work();
          modx.tabs.selected = this.row.querySelector('.selected');
          if (modx.tabs.selected) {
            d.getElementById(modx.tabs.selected.id.replace('tab', 'tab-page')).classList.remove('show');
            modx.tabs.selected.classList.remove('selected');
          }
          this.page = d.createElement('div');
          this.page.id = 'evo-tab-page-' + this.uid;
          this.page.className = 'evo-tab-page iframe-scroller show';
          if (/iPhone|iPad|iPod/i.test(navigator.userAgent)) {
            this.page.innerHTML = '<iframe class="tabframes" src="' + this.url + '" name="' + this.name + '" width="100%" height="100%" scrolling="no" frameborder="0"></iframe>';
          } else {
            this.page.innerHTML = '<iframe class="tabframes" src="' + this.url + '" name="' + this.name + '" width="100%" height="100%" scrolling="auto" frameborder="0"></iframe>';
          }
          d.getElementById('main').appendChild(this.page);
          //console.time('load-tab');
          this.page.firstElementChild.onload = function(e) {
            s.onload.call(s, e);
            //console.timeEnd('load-tab');
          };
          this.tab = d.createElement('h2');
          this.tab.id = 'evo-tab-' + this.uid;
          this.tab.className = 'tab selected';
          this.icon = '';
          if (!/<i/.test(this.title)) {
            this.icon = '<i class="' + modx.setTypeIcon(this.action) + '"></i>';
          }
          this.tab.innerHTML = '<span class="tab-title" title="' + this.title.replace(/<\/?[^>]+>/g, '') + '">' + this.icon + this.title + '</span><span class="tab-close">×</span>';
          this.row.appendChild(this.tab);
          this.tab.onclick = function(e) {
            s.select.call(s, e, this);
          };
          this.tab.close = function(e) {
            s.close.call(s, e);
          };
          this.tab.show = function(e) {
            s.show.call(s, e);
          };
          this.page.close = function(e) {
            s.close.call(s, e);
          };
        },
        onload: function(e) {
          var s = this;
          w.main = e.target.contentWindow || e.target.defaultView;
          this.url = w.main.location.search || w.location.hash.substring(1);
          this.olduid = this.uid;
          this.uid = modx.urlToUid(this.url);
          if (!!w.main.__alertQuit) {
            w.main.alert = function(a) { };
            var message = w.main.document.body.innerHTML;
            w.main.document.body.style.display = 'none';
            history.pushState(null, d.title, modx.getActionFromUrl(w.location.search, 2) ? modx.MODX_MANAGER_URL : '#' + w.location.search);
            w.onpopstate = function() {
              history.go(1);
            };
            modx.popup({
              type: 'warning',
              title: 'MODX :: Alert',
              position: 'top center alertQuit',
              content: message,
              wrap: 'body'
            });
            modx.getLockedElements(modx.getActionFromUrl(this.url), modx.main.getQueryVariable('id', this.url), function(data) {
              if (!!data) {
                s.page.close();
                modx.tree.restoreTree();
              } else {
                w.main.location.href = modx.MODX_MANAGER_URL + s.url;
                w.history.replaceState(null, d.title, modx.getActionFromUrl(s.url, 2) ? modx.MODX_MANAGER_URL : '#' + s.url);
              }
            });
            modx.main.stopWork();
          } else {
            if (modx.getActionFromUrl(this.url, 2) || (~this.saveAndCloseActions.indexOf(modx.getActionFromUrl(this.url)) && parseInt(modx.main.getQueryVariable('r', this.url)))) {
              this.close(e);
            } else if (this.olduid !== this.uid && d.getElementById('evo-tab-' + this.uid)) {
              this.close(e);
              d.getElementById('evo-tab-' + this.uid).show();
            } else {
              this.title = w.main.document.body.querySelectorAll('h1')[0] && w.main.document.body.querySelectorAll('h1')[0].innerHTML || this.title;
              if (this.title && this.uid !== 'home') {
                this.tab.innerHTML = '<span class="tab-title" title="' + this.title.replace(/<\/?[^>]+>/g, '') + '">' + this.title + '</span><span class="tab-close">×</span>';
              }
              this.page.id = 'evo-tab-page-' + this.uid;
              this.tab.id = 'evo-tab-' + this.uid;
              this.tab.classList.remove('changed');
              this.show();
              modx.main.onload(e);
              w.main.document.addEventListener('click', function a(e) {
                if (typeof e.view.documentDirty !== 'undefined' && e.view.documentDirty && !s.tab.classList.contains('changed')) {
                  s.tab.classList.add('changed');
                  this.removeEventListener(e.type, a, false);
                }
              }, false);
              w.main.document.addEventListener('keyup', function a(e) {
                if (typeof e.view.documentDirty !== 'undefined' && e.view.documentDirty && !s.tab.classList.contains('changed')) {
                  s.tab.classList.add('changed');
                  this.removeEventListener(e.type, a, false);
                }
              }, false);
            }
          }
        },
        show: function() {
          var s = this;
          modx.tabs.selected = this.row.querySelector('.selected');
          if (modx.tabs.selected && modx.tabs.selected !== this.tab) {
            d.getElementById(modx.tabs.selected.id.replace('tab', 'tab-page')).classList.remove('show');
            modx.tabs.selected.classList.remove('selected');
          }
          this.page.classList.add('show');
          this.tab.classList.add('selected');
          modx.tabs.selected = this.tab;
          w.main = this.page.firstElementChild.contentWindow;
          w.history.replaceState(null, w.main.document.title, modx.getActionFromUrl(w.main.location.search, 2) ? modx.MODX_MANAGER_URL : '#' + w.main.location.search);
          modx.tree.setItemToChange();
          modx.main.tabRow.scroll(this.row, this.tab, 350);
        },
        close: function(e) {
          var documentDirty = this.page.firstElementChild.contentWindow.documentDirty;
          var checkDirt = !!this.page.firstElementChild.contentWindow.checkDirt;
          if (documentDirty && checkDirt && confirm(this.page.firstElementChild.contentWindow.checkDirt(e)) || !documentDirty) {
            if (modx.tabs.selected === this.tab) {
              tree.ca = 'open';
            }
            modx.tabs.selected = this.tab.classList.contains('selected') ? this.tab.previousElementSibling : this.row.querySelector('.selected');
            this.page.parentNode.removeChild(this.page);
            this.row.removeChild(this.tab);
            modx.tabs.selected.show();
          }
        },
        select: function(e) {
          if (e.target.className === 'tab-close') {
            this.close(e);
          } else if (modx.tabs.selected === this.tab) {
            var s = this;
            if (this.timer) {
              clearTimeout(this.timer);
              this.timer = null;
              w.main.location.reload();
            } else {
              this.timer = setTimeout(function() {
                s.timer = null;
              }, 250);
            }
          } else {
            this.show();
          }
        },
        setDocPublished: function() {
          var el = w.main.document.getElementsByName('publishedcheck')[0];
          if (el) {
            el.checked = this.action === 61;
            w.main.document.getElementsByName('published')[0].value = +el.checked;
          }
          if (modx.getActionFromUrl(w.main.location.search, 3)) {
            w.main.location.reload();
          }
        }
      };
      if (modx.config.global_tabs) {
        if (typeof a.currentTarget !== 'undefined') {
          var e = a;
          if (e.button === 0 && e.target && (e.target.tagName === 'A' && e.target.target === 'main' || (e.target.parentNode && e.target.parentNode.tagName === 'A' && e.target.parentNode.target === 'main'))) {
            a = e.target.tagName === 'A' && e.target || e.target.parentNode.tagName === 'A' && e.target.parentNode;
            if (e.shiftKey) {
              modx.openWindow({url: a.href});
            } else {
              modx.tabs({url: a.href, title: a.innerHTML});
            }
            e.preventDefault();
          }
        } else {
          return new Tabs(a);
        }
      } else if (a.url) {
        if (w.main) {
          w.main.frameElement.src = a.url;
        } else {
          modx.openWindow(a.url);
        }
      }
    },
    popup: function(a) {
      if (typeof a === 'object' && (a.url || a.content || a.text)) {
        var o = {
          addclass: '',
          animation: 'fade', // fade
          content: a.content || a.text || '',
          clickclose: 0,
          closeall: 0,
          data: '', // for ajax send data
          dataType: 'document', // for ajax
          delay: 5000,
          draggable: !1, // false | true
          event: null,
          height: 'auto', // auto | 100 | 100rem | 100px | 100%
          hide: 1, // close after delay
          hover: 1, // close after hover
          icon: '', // empty | fa class | none
          iframe: 'iframe', // iframe | ajax
          margin: '.5rem',
          maxheight: '',
          method: 'GET', // POST | GET
          overlay: 0, // add overlay
          overlayclose: 0, // click overlay to close
          position: 'center', // center | left top | left bottom | right top | right bottom
          resize: !1, // false | true
          selector: '', // dataType: document, selector: 'body'
          showclose: 1, // show close button
          target: 'main', // ! not used
          uid: '',
          type: 'default', // default | info | danger | success | dark | warning
          title: '',
          url: '',
          width: '20rem',
          wrap: a.wrap || w.main.document.body, // parentNode
          zIndex: 10500,
          w: null,
          show: function() {
            if (~o.position.indexOf('center')) {
              if (o.event) {
                o.el.style.left = o.event.clientX + o.mt + 'px';
                o.el.style.bottom = o.w.innerHeight - o.el.offsetHeight - o.event.clientY + o.mt + 'px';
              } else {
                o.el.style.left = /(%)/.test(o.width) ? (100 - parseInt(o.width)) / 2 - o.mt / (o.w.innerWidth / 100) + '%' : (o.w.innerWidth - o.el.offsetWidth) / 2 - o.mt + 'px';
                o.el.style.bottom = /(%)/.test(o.height) ? (100 - parseInt(o.height)) / 2 - o.mt / (o.w.innerHeight / 100) + '%' : (o.w.innerHeight - o.el.offsetHeight - o.wrap.offsetTop) / 2 - o.mt + 'px';
              }
            }
            if (~o.position.indexOf('left')) {
              o.el.style.left = 0;
            }
            if (~o.position.indexOf('right')) {
              o.el.style.right = 0;
            } else {
              o.el.style.right = 'auto';
            }
            if (~o.position.indexOf('top')) {
              o.el.style.top = 0;
              o.el.style.bottom = '';
            } else {
              o.el.style.top = 'auto';
            }
            if (~o.position.indexOf('bottom')) {
              o.el.style.bottom = 0;
            }
            o.calc();
            o.el.className += ' in';
            if (o.showclose) {
              o.el.querySelector('.close').onclick = o.close;
            }
            if (o.hide) {
              o.el.timer = setTimeout(function() {
                clearTimeout(o.el.timer);
                o.close();
              }, o.delay);
            }
            if (o.hover) {
              o.el.onmouseenter = function() {
                clearTimeout(o.el.timer);
              };
              o.el.onmouseleave = o.close;
            }
            if (o.overlayclose && o.o) {
              o.o.onclick = o.close;
            }
            if (o.clickclose) {
              o.el.onclick = o.close;
            }
            if (o.draggable) {
              modx.dragging(o.el, {wrap: o.wrap, resize: o.resize});
            }
          },
          close: function(e) {
            o.event = e || o.event || w.event;
            if (o.url && o.iframe === 'iframe') {
              var els = o.wrap.ownerDocument.querySelectorAll('.' + o.className + '.in');
              if (els) {
                var documentDirty = o.el.lastElementChild.firstElementChild.contentWindow.documentDirty;
                if (documentDirty && confirm(o.el.lastElementChild.firstElementChild.contentWindow.checkDirt(o.event)) || !documentDirty) {
                  o.el.classList.remove('in');
                  if (!o.animation) {
                    o.el.classList.remove('show');
                  }
                  o.calc(1);
                  if (o.o && o.o.parentNode) {
                    o.o.parentNode.removeChild(o.o);
                    o.wrap.style.overflow = '';
                  }
                  o.el.timer = setTimeout(function() {
                    clearTimeout(o.el.timer);
                    if (o.el.parentNode) {
                      o.el.parentNode.removeChild(o.el);
                    }
                  }, 200);
                }
              }
            } else {
              o.el.classList.remove('in');
              if (!o.animation) {
                o.el.classList.remove('show');
              }
              o.calc(1);
              if (o.o && o.o.parentNode) {
                o.o.parentNode.removeChild(o.o);
                o.wrap.style.overflow = '';
              }
              o.el.timer = setTimeout(function() {
                clearTimeout(o.el.timer);
                if (o.el.parentNode) {
                  o.el.parentNode.removeChild(o.el);
                }
              }, 200);
            }
            if (typeof o.onclose === 'function') {
              o.onclose(e, o.el);
            }
          },
          calc: function(f) {
            var els = o.wrap.ownerDocument.querySelectorAll('.' + o.className + '.in[data-position="' + o.el.dataset.position + '"]');
            if (els && els.length) {
              o.els = [];
              for (var i = 0; i < els.length; i++) {
                o.els.push(els[i]);
              }
              o.els.sort(function(a, b) {
                return a.index - b.index;
              });
              o.t = 0;
              if (o.position.indexOf('center') === 0) {
                o.t = !f ? (o.w.innerHeight + o.el.offsetHeight) / 2 : (o.w.innerHeight - o.els[o.els.length - 1].offsetHeight) / 2 - o.mt;
              } else {
                o.t = !f ? o.el.offsetHeight + o.mt : 0;
              }
              i = o.els.length;
              while (i--) {
                o.wrap.ownerDocument.getElementById(o.els[i].id).index = i - o.els.length;
                if (~o.position.indexOf('top')) {
                  o.wrap.ownerDocument.getElementById(o.els[i].id).style.top = o.t + 'px';
                } else {
                  o.wrap.ownerDocument.getElementById(o.els[i].id).style.bottom = o.t + 'px';
                }
                o.t += o.els[i].offsetHeight + o.mt;
                if (o.closeall && o.el !== els[i]) {
                  o.els[i].close();
                }
              }
            }
          },
          onclose: function(e, obj) { }
        };
        for (var k in a) {
          if (a.hasOwnProperty(k) && typeof o[k] !== 'undefined') {
            o[k] = a[k];
          }
        }
        o.timer = 0;
        o.position = o.position.split(' ');
        if (modx.popupLastIndex) {
          o.zIndex = modx.popupLastIndex++;
        } else {
          modx.popupLastIndex = o.zIndex;
        }
        o.uid = a.url ? modx.urlToUid(a.url) : modx.toHash(a);
        o.className = 'evo-popup';
        if (typeof o.wrap === 'string') {
          o.wrap = d.querySelector(o.wrap);
        }
        o.w = o.wrap.ownerDocument.defaultView || o.wrap.ownerDocument.parentWindow;
        if (o.overlay) {
          if (o.wrap.querySelector('.evo-popup-overlay')) {
            o.o = o.wrap.querySelector('.evo-popup-overlay');
            o.o.style.zIndex = o.zIndex - 1;
          } else {
            o.o = d.createElement('div');
            o.o.className = 'evo-popup-overlay';
            o.o.style.zIndex = o.zIndex - 1;
            o.wrap.appendChild(o.o);
            o.wrap.style.overflow = 'hidden';
          }
        }
        o.el = o.wrap.ownerDocument.getElementById('evo-popup-' + o.uid);
        if (o.el) {
          clearTimeout(o.el.timer);
          o.el.index = 0;
          o.el.classList.remove('in');
          o.el.classList.add('show');
          o.el.style.zIndex = o.zIndex;
          o.el.dataset.position = o.position.join(':');
          o.mt = parseFloat(getComputedStyle(o.el).marginTop);
          o.el.close = o.close;
          o.show();
        } else {
          o.el = d.createElement('div');
          o.el.id = 'evo-popup-' + o.uid;
          o.el.close = o.close;
          o.el.index = 0;
          o.el.style.position = 'fixed';
          o.el.style.width = !/[^[0-9]/.test(o.width) ? o.width + 'px' : o.width;
          o.el.style.height = !/[^[0-9]/.test(o.height) ? o.height + 'px' : o.height;
          o.el.style.zIndex = o.zIndex;
          o.el.style.margin = o.margin;
          o.el.className = o.className + ' show alert alert-' + o.type + ' ' + o.addclass + (o.animation ? ' animation ' + o.animation : '');
          o.el.dataset.position = o.position.join(':');
          if (o.showclose) {
            o.el.innerHTML += '<div class="evo-popup-close close">&times;</div>';
          }
          if (o.title) {
            o.header = document.createElement('div');
            if (o.icon !== 'none') {
              o.header.innerHTML += '<i class="fa fa-fw ' + (o.icon ? o.icon : 'fa-' + o.type) + '"></i>';
            }
            o.header.innerHTML += o.title;
            o.header.className = 'evo-popup-header';
            o.el.appendChild(o.header);
          }
          o.el.innerHTML += '<div class="evo-popup-body"></div>';
          o.wrap.appendChild(o.el);
          o.mt = parseFloat(getComputedStyle(o.el).marginTop);
          if (o.maxheight) {
            o.maxheight = /(%)/.test(o.maxheight) ? (o.w.innerHeight - o.el.offsetHeight - o.mt) / 100 * parseInt(o.maxheight) : o.maxheight;
            o.el.lastChild.style.overflowY = 'auto';
            o.el.lastChild.style.maxHeight = o.maxheight + 'px';
          }
          if (o.url) {
            o.draggable = 1;
            if (o.iframe === 'iframe') {
              o.resize = 1;
              o.uid = modx.urlToUid(a.url);
              o.el.className += ' ' + o.addclass + ' ' + o.className + '-iframe';
              o.el.id = 'evo-popup-' + o.uid;
              d.getElementById('mainloader').className = 'show';
              o.frame = d.createElement('iframe');
              o.frame.width = '100%';
              o.frame.height = '100%';
              o.frame.frameBorder = '0';
              o.frame.src = o.url;
              o.frame.onload = function(e) {
                e.target.contentWindow.opener = o.w;
                a.url = e.target.contentWindow.location.href;
                o.uid = modx.urlToUid(a.url);
                o.event = e;
                if (!!e.target.contentWindow.__alertQuit) {
                  modx.popup({
                    type: 'warning',
                    title: 'MODX :: Alert',
                    position: 'top center alertQuit',
                    content: e.target.contentWindow.document.body.querySelector('p').innerHTML
                  });
                  e.target.contentWindow.document.body.innerHTML = '';
                  e.target.contentWindow.alert = function() {};
                } else {
                  if (modx.getActionFromUrl(a.url, 2) || o.wrap.querySelectorAll('#evo-popup-' + o.uid).length > 1) {
                    o.el.close();
                  } else {
                    if (e.target.contentDocument.querySelectorAll('h1')[0]) {
                      a.title = e.target.contentDocument.querySelectorAll('h1')[0].innerHTML;
                    } else if (e.target.contentDocument.title) {
                      a.title = e.target.contentDocument.title;
                    }
                    if (o.header) {
                      e.target.offsetParent.offsetParent.getElementsByClassName(o.header.className)[0].innerHTML = a.title;
                    }
                    e.target.offsetParent.offsetParent.id = 'evo-popup-' + o.uid;
                    e.target.offsetParent.offsetParent.classList.remove('changed');
                    //modx.main.onload(e);
                  }
                }
                e.target.contentWindow.close = o.close;
                modx.main.stopWork();
              };
              o.el.lastChild.appendChild(o.frame);
              o.show();
            } else {
              var xhr = new XMLHttpRequest();
              xhr.open(o.method, o.url, true);
              xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded;');
              xhr.setRequestHeader('X-REQUESTED-WITH', 'XMLHttpRequest');
              if (o.dataType) {
                xhr.responseType = o.dataType;
              }
              xhr.onload = function() {
                if (this.readyState === 4) {
                  o.el.className += ' ' + o.className + '-ajax';
                  if (o.dataType === 'document') {
                    if (o.selector) {
                      var r = this.response.documentElement.querySelector(o.selector);
                      if (r) {
                        o.el.lastChild.innerHTML += r.innerHTML;
                      }
                    } else {
                      o.el.lastChild.innerHTML += this.response.body.innerHTML;
                    }
                  } else {
                    o.el.lastChild.innerHTML += this.response;
                  }
                  o.show();
                }
              };
              xhr.send(o.data);
            }
          } else {
            o.el.lastChild.innerHTML += o.content;
            o.show();
          }
        }
        return o;
      }
    },
    getWindowDimension: function() {
      var a = 0,
          b = 0,
          c = d.documentElement,
          e = d.body;
      if (typeof (w.innerWidth) === 'number') {
        a = w.innerWidth;
        b = w.innerHeight;
      } else if (c && (c.clientWidth || c.clientHeight)) {
        a = c.clientWidth;
        b = c.clientHeight;
      } else if (e && (e.clientWidth || e.clientHeight)) {
        a = e.clientWidth;
        b = e.clientHeight;
      }
      return {
        'width': a,
        'height': b
      };
    },
    hideDropDown: function(e) {
      e = e || w.event || w.main.event;
      if (tree.ca === 'open' || tree.ca === '') {
        modx.tree.setSelectedByContext();
      }
      if (modx.tree.ctx !== null) {
        d.getElementById(modx.frameset).removeChild(modx.tree.ctx);
        modx.tree.ctx = null;
      }
      if (!/dropdown\-item/.test(e.target.className)
      //&& !(e && ("click" === e.type && /form|label|input|textarea|select/i.test(e.target.tagName)))
      ) {
        var els = d.querySelectorAll('.dropdown.show'),
            n = null,
            t = e.target || e.target.parentNode,
            i;
        if (typeof t.dataset.toggle !== 'undefined') {
          n = d.querySelector(t.dataset.toggle);
        } else if (t.classList.contains('dropdown-toggle')) {
          n = t.offsetParent;
        }
        els.forEach(function(el) {
          if (n !== el) {
            el.classList.remove('show');
          }
        });
        els = w.main && w.main.document.querySelectorAll('.dropdown.show') || [];
        els.forEach(function(el) {
          if (n !== el) {
            el.classList.remove('show');
          }
        });
      }
    },
    XHR: function() {
      return 'XMLHttpRequest' in w ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    },
    get: function(a, b, c) {
      var x = this.XHR();
      x.open('GET', a, true);
      x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
      if (c) x.responseType = c;
      x.onload = function() {
        if (this.status === 200 && typeof b === 'function') {
          return b(this.response);
        }
      };
      x.send();
    },
    post: function(a, b, c, t) {
      var x = this.XHR(),
          f = '';
      if (typeof b === 'function') {
        t = c;
        c = b;
      } else if (typeof b === 'object') {
        var e = [],
            i = 0,
            k;
        for (k in b) {
          if (b.hasOwnProperty(k)) e[i++] = k + '=' + b[k];
        }
        f = e.join('&');
      } else if (typeof b === 'string') {
        f = b;
      }
      x.open('POST', a, true);
      x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
      x.setRequestHeader('X-REQUESTED-WITH', 'XMLHttpRequest');
      if (t) x.responseType = t;
      x.onload = function() {
        if (this.readyState === 4 && c !== u) {
          return c(this.response);
        }
      };
      x.send(f);
    },
    pxToRem: function(a) {
      return a / parseInt(w.getComputedStyle(d.documentElement).fontSize);
    },
    remToPx: function(a) {
      return a * parseInt(w.getComputedStyle(d.documentElement).fontSize);
    },
    toHash: function(a) {
      a = String(JSON.stringify(a));
      var b = 0, c, i;
      if (a.length === 0) return b;
      for (i = 0; i < a.length; i++) {
        c = a.charCodeAt(i);
        b = (b << 5) - b + c;
        b = b & b;
      }
      return Math.abs(b).toString();
    },
    urlToUid: function(a) {
      var b = '',
          c;
      a = a.split('?');
      a = a[1] || a[0];
      if (a) {
        c = modx.getActionFromUrl(a);
        if (c) {
          if (modx.typesactions[c]) {
            b += '&type=' + modx.typesactions[c];
          } else {
            b += '&a=' + c;
          }
        }
        if (modx.main.getQueryVariable('id', a)) {
          b += '&id=' + modx.main.getQueryVariable('id', a);
        }
        if (modx.main.getQueryVariable('type', a)) {
          b += '&type=' + modx.main.getQueryVariable('type', a);
        }
        b = modx.toHash(b);
      }
      return b;
    },
    getActionFromUrl: function(a, b) {
      if (typeof b !== 'undefined') {
        return parseInt(modx.main.getQueryVariable('a', a)) === parseInt(b);
      } else {
        return parseInt(modx.main.getQueryVariable('a', a));
      }
    },
    getLockedElements: function(a, b, c) {
      if (modx.typesactions[a] && b) {
        modx.post(modx.MODX_MANAGER_URL + 'media/style/' + modx.config.theme + '/ajax.php', {
          a: 'getLockedElements',
          id: modx.main.getQueryVariable('id', w.main.location.search),
          type: modx.typesactions[modx.getActionFromUrl(w.main.location.search)]
        }, c);
      }
    },
    dragging: function(a, b) {
      this.dragging.init = function(a, b) {
        var c = {
              resize: !1,
              minWidth: 50,
              minHeight: 50,
              wrap: document,
              opacity: '0.65',
              classDrag: 'drag',
              classResize: 'resize',
              handler: '',
              onstart: function(e, obj) { },
              onstop: function(e, obj) { },
              ondrag: function(e, obj) { },
              onresize: function(e, obj) { }
            },
            f = {
              x: 0,
              y: 0,
              elmX: 0,
              elmY: 0,
              border: 5,
              isdrag: !1,
              isresize: !1,
              onTop: 0,
              onLeft: 0,
              onRight: 0,
              onBottom: 0,
              handlerHeight: a.offsetHeight,
              borders: document.createElement('div')
            },
            s = modx.extend(this, c, b, f);
        this.el = a;
        this.borders.className = 'border-outline';
        this.borders.innerHTML = '<i class="bo-left"></i><i class="bo-top"></i><i class="bo-right"></i><i class="bo-bottom"></i>';
        if (this.classDrag) this.el.classList.add(this.classDrag);
        if (this.classResize) this.el.classList.add(this.classResize);
        this.el.insertBefore(this.borders, this.el.firstChild);
        if (this.handler && this.el.getElementsByClassName(this.handler)[0]) {
          this.handler = this.el.getElementsByClassName(this.handler)[0];
          this.handlerHeight = this.handler.offsetHeight;
        }
        this.el.onmousedown = function(e) {
          s.mousedown(e);
        };
        this.el.ontouchstart = function(e) {
          s.mousedown(e.changedTouches[0]);
        };
        this.wrap.addEventListener('mousemove', function(e) {
          s.calc(e);
        }, false);
        this.wrap.addEventListener('touchmove', function(e) {
          s.calc(e.changedTouches[0]);
        }, false);
      };
      this.dragging.init.prototype = {
        mousedown: function(e) {
          if (e.target.classList.contains('close')) {
            return;
          }
          e = document.all ? window.event : e;
          var s = this,
              x = document.all ? window.event.clientX : e.clientX,
              y = document.all ? window.event.clientY : e.clientY,
              style = w.getComputedStyle(this.el);
          this.elmX = (x - this.el.offsetLeft + parseInt(style.marginLeft));
          this.elmY = (y - this.el.offsetTop + parseInt(style.marginTop));
          this.el.position = this.el.getBoundingClientRect();
          this.el.style.position = 'fixed';
          this.el.style.transitionDuration = '0s';
          this.el.style.webkitTransitionDuration = '0s';
          this.el.style.left = this.el.offsetLeft - parseInt(style.marginLeft) + 'px';
          this.el.style.top = this.el.offsetTop - parseInt(style.marginTop) + 'px';
          this.el.style.right = 'auto';
          this.el.style.bottom = 'auto';
          this.el.style.width = this.el.position.width + 'px';
          this.el.style.height = this.el.position.height + 'px';
          if (e.target.parentNode === this.borders) {
            this.isresize = this.resize;
            this.el.classList.add('is-resize');
          } else {
            if (!this.handler || (this.handler && e.target === this.handler)) {
              this.isdrag = true;
              this.el.classList.add('is-drag');
            }
          }
          if (typeof this.start === 'function') {
            this.onstart(e, this.el);
          }
          if (e.preventDefault) {
            e.preventDefault();
          } else {
            document.onselectstart = function() {
              return false;
            };
          }
          this.wrap.onmousemove = function(e) {
            s.mousemove(e);
          };
          this.wrap.ontouchmove = function(e) {
            s.mousemove(e.changedTouches[0]);
            e.stopPropagation();
          };
          this.wrap.onmouseup = function(e) {
            s.mouseup(e);
          };
          this.wrap.ontouchend = function(e) {
            s.mouseup(e.changedTouches[0]);
            e.stopPropagation();
          };
        },
        mousemove: function(e) {
          var x = document.all ? window.event.clientX : e.clientX,
              y = document.all ? window.event.clientY : e.clientY;
          if (this.isresize) {
            if (this.onRight) this.el.style.width = Math.max(x - this.el.position.left + (this.el.position.width - this.x), this.minWidth) + 'px';
            if (this.onBottom) this.el.style.height = Math.max(y - this.el.position.top + (this.el.position.height - this.y), this.minHeight) + 'px';
            if (this.onLeft) {
              this.width = Math.max(this.el.position.left - x + this.el.position.width + this.x, this.minWidth);
              if (this.width > this.minWidth) {
                this.el.style.width = this.width + 'px';
                this.el.style.left = x - this.elmX + 'px';
              }
            }
            if (this.onTop) {
              var currentHeight = Math.max(this.el.position.top - y + this.el.position.height + this.y, this.minHeight);
              if (currentHeight > this.minHeight) {
                this.el.style.height = currentHeight + 'px';
                this.el.style.top = y - this.elmY + 'px';
              }
            }
            if (typeof this.onresize === 'function') {
              this.onresize(e, this.el);
            }
          } else if (this.isdrag) {
            this.el.style.opacity = this.opacity;
            this.el.style.left = x - this.elmX + 'px';
            this.el.style.top = y - this.elmY + 'px';
            if (typeof this.drag === 'function') {
              this.ondrag(e, this.el);
            }
          }
        },
        mouseup: function(e) {
          if (this.isdrag || this.isresize) {
            this.el.classList.remove('is-resize');
            this.el.classList.remove('is-drag');
            this.el.style.opacity = '';
            this.el.style.transitionDuration = '';
            this.el.style.webkitTransitionDuration = '';
            this.el.position = this.el.getBoundingClientRect();
            this.isdrag = false;
            this.isresize = false;
            this.wrap.onmousemove = null;
            this.wrap.onselectstart = null;
            if (typeof this.stop === 'function') {
              this.onstop(e, this.el);
            }
          }
        },
        calc: function(e) {
          if (this.isresize || this.isdrag) {
            return;
          }
          this.x = e.clientX - this.el.offsetLeft;
          this.y = e.clientY - this.el.offsetTop;
          if (this.resize) {
            this.onTop = this.y < this.border;
            this.onLeft = this.x < this.border;
            this.onRight = this.x >= this.el.offsetWidth - this.border;
            this.onBottom = this.y >= this.el.offsetHeight - this.border;
            if (this.onRight && this.onBottom || this.onLeft && this.onTop) {
              this.el.style.cursor = 'nwse-resize';
            } else if (this.onRight && this.onTop || this.onBottom && this.onLeft) {
              this.el.style.cursor = 'nesw-resize';
            } else if (this.onRight || this.onLeft) {
              this.el.style.cursor = 'ew-resize';
            } else if (this.onBottom || this.onTop) {
              this.el.style.cursor = 'ns-resize';
            } else if (this.x > 0 && this.y > 0 && (this.x < this.el.offsetWidth && this.y <= this.handlerHeight && this.y >= this.border)) {
              this.el.style.cursor = 'move';
            } else {
              this.el.style.cursor = 'default';
            }
          } else {
            if (this.x > 0 && this.y > 0 && (this.x < this.el.offsetWidth && this.y <= this.handlerHeight && this.y >= this.border)) {
              this.el.style.cursor = 'move';
            }
          }
        }
      };
      return new this.dragging.init(a, b);
    },
    setTypeIcon: function(a) {
      var b = '';
      switch (this.typesactions[a]) {
        case 1:
          b = 'fa fa-newspaper-o';
          break;
        case 2:
          b = 'fa fa-list-alt';
          break;
        case 3:
          b = 'fa fa-th-large';
          break;
        case 4:
          b = 'fa fa-code';
          break;
        case 5:
          b = 'fa fa-plug';
          break;
        case 6:
          b = 'fa fa-cube';
          break;
        case 7:
          b = 'fa fa-pencil-square-o';
          break;
        default:
          b = 'fa fa-circle';
      }
      return b;
    },
    animate: function(a, b, c, d) {
      if (!a || !b || typeof b !== 'object') return;
      var e = 'px';
      if (typeof c === 'function') {
        d = c;
        c = null;
      }
      c = c || 64;
      d = typeof d !== 'function' ? function() { } : d;
      var f = getComputedStyle(a);
      for (var k in b) {
        if (b.hasOwnProperty(k)) {
          if (k === 'opacity') {
            e = '';
          }
          var g = f[k];
          if (k === 'scrollLeft') {
            g = a.scrollLeft;
          } else if (k === 'scrollTop') {
            g = a.scrollTop;
          }
          b[k] = {
            start: parseInt(g),
            end: b[k],
            unit: e
          };
        }
      }
      var h = performance.now();
      requestAnimationFrame(function animate(i) {
        var l = (h > i ? h - i : i - h) / c;
        if (l > 1) {
          l = 1;
        }
        for (var k in b) {
          if (b.hasOwnProperty(k)) {
            var m = b[k]['start'] + ((b[k]['end'] - b[k]['start']) * l);
            if (k === 'scrollLeft') {
              a.scrollLeft = m;
            } else if (k === 'scrollTop') {
              a.scrollTop = m;
            } else {
              a.style[k] = m + b[k]['unit'];
            }
          }
        }
        if (l < 1) {
          requestAnimationFrame(animate);
        } else {
          d.call(a);
        }
      });
    }
  });
  w.mainMenu = {};
  w.mainMenu.stopWork = function() {
    modx.main.stopWork();
  };
  w.mainMenu.work = function() {
    modx.main.work();
  };
  w.mainMenu.reloadtree = function() {
    //console.log('mainMenu.reloadtree()');
    if (modx.plugins.ElementsInTree) {
      setTimeout('reloadElementsInTree()', 50);
    }
    if (modx.config.global_tabs) {
      setTimeout('modx.tree.restoreTree()', 100);
    }
  };
  w.mainMenu.startrefresh = function(a) {
    //console.log('mainMenu.startrefresh(' + a + ')');////
    if (a === 1) {
      //setTimeout('modx.tree.restoreTree()', 50)
    }
    if (a === 2) {
      //modx.tree.restoreTree();
    }
    if (a === 9) {
      modx.tree.restoreTree();
    }
    if (a === 10) {
      w.location.href = modx.MODX_MANAGER_URL;
    }
  };
  w.mainMenu.startmsgcount = function(a, b, c) {
    modx.updateMail(c);
  };
  w.mainMenu.hideTreeFrame = function() {
    modx.resizer.setWidth(0);
  };
  w.mainMenu.defaultTreeFrame = function() {
    modx.resizer.setDefaultWidth();
  };
  w.tree = {};
  w.tree.ca = 'open';
  w.tree.document = document;
  w.tree.saveFolderState = function() {};
  w.tree.updateTree = function() {
    //console.log('tree.updateTree()');
    modx.tree.updateTree();
  };
  w.tree.restoreTree = function() {
    //console.log('tree.restoreTree()');
    modx.tree.restoreTree();
  };
  w.tree.resizeTree = function() {
    //console.log('tree.resizeTree() off')
  };
  w.onbeforeunload = function() {
    var a = w.main.frameElement.contentWindow;
    if (modx.getActionFromUrl(a.location.search, 27)) {
      modx.get(modx.MODX_MANAGER_URL + '?a=67&type=7&id=' + modx.main.getQueryVariable('id', a.location.search.substring(1)));
    }
  };
  d.addEventListener('DOMContentLoaded', function() {
    modx.init();
  });
})(window, document, undefined);

(function() {
  if (!Element.prototype.closest) {
    Element.prototype.closest = function(a) {
      var b = this,
          c, d;
      ['matches', 'webkitMatchesSelector', 'mozMatchesSelector', 'msMatchesSelector', 'oMatchesSelector'].some(function(fn) {
        if (typeof document.body[fn] === 'function') {
          c = fn;
          return true;
        }
        return false;
      });
      if (b && c && b[c](a)) return b;
      while (b) {
        d = b.parentElement;
        if (d && c && d[c](a)) return d;
        b = d;
      }
      return null;
    };
  }
})();

!function(o, e) {
  var n = window.Element.prototype,
      i = n.matches;
  i || ['webkit', 'ms', 'moz'].some(function(e) {
    var t = e + 'MatchesSelector';
    if (n.hasOwnProperty(t)) return i = n[t], !0;
  }), e.prototype.delegateEventListener = function(e, n, r) {
    this.addEventListener(e, function(e) {
      var t = function(e, t, n) {
        for (var r = e.target; ;) {
          if (i.call(r, t)) return r;
          if (r === n || r === o.body) return !1;
          r = r.parentNode;
        }
      }(e, n, e.currentTarget);
      t && r.call(t, e);
    });
  };
}(window.document, window.EventTarget || window.Element);
