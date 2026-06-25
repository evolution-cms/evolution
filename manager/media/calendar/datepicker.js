'use strict';

var _createClass = function() {
  function defineProperties(target, props)
  {
    for (var i = 0; i < props.length; i++) {
      var descriptor = props[i];
      descriptor.enumerable = descriptor.enumerable || false;
      descriptor.configurable = true;
      if ('value' in descriptor) {
        descriptor.writable = true;
      }
      Object.defineProperty(target, descriptor.key, descriptor);
    }
  }

  return function(Constructor, protoProps, staticProps) {
    if (protoProps) {
      defineProperties(Constructor.prototype, protoProps);
    }
    if (staticProps) {
      defineProperties(Constructor, staticProps);
    }
    return Constructor;
  };
}();

var DatePicker = function() {
  function DatePicker(dp, options)
  {
    if (!(this instanceof DatePicker)) {
      throw new TypeError('Cannot call a class as a function');
    }

    var self = this;
    this.dayChars = 1;
    this.dayNames = [
      'Sunday',
      'Monday',
      'Tuesday',
      'Wednesday',
      'Thursday',
      'Friday',
      'Saturday',
    ];
    this.daysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    this.format = 'dd-mm-yyyy hh:mm:00';
    this.monthNames = [
      'January',
      'February',
      'March',
      'April',
      'May',
      'June',
      'July',
      'August',
      'September',
      'October',
      'November',
      'December',
    ];
    this.startDay = 7;
    this.yearOrder = 'asc';
    this.yearRange = 10;
    this.yearStart = new Date().getFullYear();
    this.yearOffset = -10;
    this.error = '';
    options = options || [];
    dp.options = {
      monthNames: (options.monthNames && options.monthNames.length === 12
          ? options.monthNames
          : this.monthNames) || this.monthNames,
      daysInMonth: (options.daysInMonth && options.daysInMonth.length === 12
          ? options.daysInMonth
          : this.daysInMonth) || this.daysInMonth,
      dayNames: (options.dayNames && options.dayNames.length === 7
          ? options.dayNames
          : this.dayNames) || this.dayNames,
      startDay: options.startDay || this.startDay,
      dayChars: options.dayChars || this.dayChars,
      format: options.format || this.format,
      yearStart: options.yearStart || this.yearStart,
      yearRange: options.yearRange || this.yearRange,
      yearOrder: options.yearOrder || this.yearOrder,
      yearOffset: options.yearOffset || this.yearOffset,
    };
    dp.id = dp.name;
    dp.autocomplete = 'off';
    dp = DatePicker.getValue(dp);
    dp.lastValidDate = dp.value;
    dp.oldYear = dp.year = dp.then.getFullYear();
    dp.oldMonth = dp.month = dp.then.getMonth();
    dp.oldDay = dp.then.getDate();
    dp.nowYear = dp.today.getFullYear();
    dp.nowMonth = dp.today.getMonth();
    dp.nowDay = dp.today.getDate();
    dp.container = false;
    dp.calendar = false;
    dp.interval = null;
    dp.active = false;
    dp.onclick = dp.onfocus = function() {
      DatePicker.create(dp, self);
    };
    DatePicker.bindToggle(dp);
  }

  _createClass(DatePicker, null, [
    {
      key: 'getValue', value: function getValue(dp) {
      if (dp.value !== '') {
        if (dp.options.format === 'dd-mm-YYYY hh:mm:00' ||
            dp.options.format === 'dd-mm-YYYY') {
          var dateVals = dp.value.split(' ');
          var dateParts = dateVals[0].split('-');
          dp.thenvalue = dateParts[1] + '/' + dateParts[0] + '/' + dateParts[2];
          if (dateVals[1]) {
            dp.thenvalue = dp.thenvalue + ' ' + dateVals[1];
          }
        } else {
          dp.thenvalue = dp.value;
        }
        dp.then = new Date(dp.thenvalue);
        dp.today = new Date();
      } else {
        dp.thenvalue = dp.then = dp.today = new Date();
        dp.thenvalue = dp.then;
      }
      return dp;
    },
    }, {
      key: 'updateValue', value: function updateValue(dp) {
        var el = document.querySelector('td.dp_selected');
        var ds = void 0;
        if (el) {
          ds = el.axis.split('|');
          var formatted = DatePicker.formatValue(dp, ds[0], ds[1], ds[2]);
          if (formatted !== '') {
            dp.value = formatted;
            dp.lastValidDate = formatted;
            DatePicker.updateToggle(dp);
          }
          this.dp.dirty = true;
        }
      },
    }, {
      key: 'getToggle', value: function getToggle(dp) {
        if (!dp.nextElementSibling) {
          return null;
        }

        if (dp.nextElementSibling.tagName === 'A' ||
            dp.nextElementSibling.classList.contains('clearDate')) {
          return dp.nextElementSibling;
        }

        return null;
      },
    }, {
      key: 'getToggleIcon', value: function getToggleIcon(toggle) {
        if (!toggle) {
          return null;
        }

        if (toggle.tagName === 'I') {
          return toggle;
        }

        return toggle.querySelector('i');
      },
    }, {
      key: 'updateToggle', value: function updateToggle(dp) {
        var icon = DatePicker.getToggleIcon(DatePicker.getToggle(dp));
        if (!icon) {
          return;
        }

        icon.classList.remove('fa-calendar-plus-o', 'fa-calendar-times-o');
        icon.classList.add(dp.value === '' ? 'fa-calendar-plus-o' : 'fa-calendar-times-o');
      },
    }, {
      key: 'bindToggle', value: function bindToggle(dp) {
        var toggle = DatePicker.getToggle(dp);
        if (!toggle) {
          return;
        }

        toggle.onclick = function(e) {
          if (e) {
            e.preventDefault ? e.preventDefault() : e.returnValue = false;
            e.stopPropagation ? e.stopPropagation() : e.cancelBubble = true;
          }

          if (dp.value === '') {
            DatePicker.create(dp);
          } else {
            dp.value = '';
            dp.lastValidDate = '';
            if (typeof dp.onblur === 'function') {
              dp.onblur();
            }
            DatePicker.updateToggle(dp);
          }

          return false;
        };

        DatePicker.updateToggle(dp);
      },
    }, {
      key: 'alertError', value: function alertError(dp) {
        if (typeof dp.error !== 'undefined' && dp.error !== '') {
          alert(dp.error);
          dp.error = '';
        }
      },
    }, {
      key: 'hasChild', value: function hasChild(a, b) {
        var parent = a.parentNode;
        while (parent && parent !== document.body) {
          if (parent === b) {
            return parent;
          } else {
            parent = parent.parentNode;
          }
        }
        return null;
      },
    }, {
      key: 'close', value: function close(e) {
        if (!document.getElementById(this.dp.id + 'dp_container')) {
          return;
        }
        var clickOutside = e.target && e.target !== this.dp && e.target !==
            this.dp.container &&
            !DatePicker.hasChild(e.target, this.dp.container);
        if (clickOutside) {
          if (this.dp.dirty) {
            this.updateValue(this.dp);
            DatePicker.alertError(this.dp);
          }
          DatePicker.remove(this.dp);
        }
      },
    }, {
      key: 'create', value: function create(dp) {
        var y = void 0;
        var self = this;
        dp = DatePicker.getValue(dp);
        this.dp = dp;
        if (dp.calendar) {
          return false;
        }
        this.dp.dirty = false;
        dp.container = document.createElement('div');
        dp.container.className = 'dp_container';
        dp.container.id = dp.id + 'dp_container';
        document.body.appendChild(dp.container);
        document.onmousedown = function(e) {
          DatePicker.close(e);
        };
        document.onkeydown = function(e) {
          if (e.code === 9 && !e.shift || e.code === 27 || e.code === 13) {
            if (e.code === 13 && dp.container) {
              e.preventDefault ? e.preventDefault() : e.returnValue = false;
              if (self.dp.dirty) {
                self.updateValue(dp);
              }
              DatePicker.remove(self.dp);
              DatePicker.alertError(self.dp);
            } else {
              DatePicker.remove(self.dp);
            }
          }
        };
        dp.calendar = document.createElement('div');
        dp.calendar.className = 'dp_cal';
        dp.container.appendChild(dp.calendar);
        var date = new Date();
        if (dp.month >= 0 && dp.year) {
          date.setFullYear(dp.year, dp.month, 1);
        } else {
          dp.month = parseInt(date.getMonth());
          dp.year = parseInt(date.getFullYear());
          date.setDate(1);
        }
        dp.year % 4 === 0
            ? dp.options.daysInMonth[1] = 29
            : dp.options.daysInMonth[1] = 28;
        var firstDay = 1 - (7 + date.getDay() - dp.options.startDay) % 7;
        var monthSel = document.createElement('select');
        monthSel.id = dp.id + '_monthSelect';
        for (var m = 0; m < dp.options.monthNames.length; m++) {
          monthSel.options[m] = new Option(dp.options.monthNames[m], m);
          if (parseInt(dp.month) === m) {
            monthSel.options[m].selected = true;
          }
        }
        var yearSel = document.createElement('select');
        yearSel.id = dp.id + '_yearSelect';
        var i = 0;
        if (!dp.options.yearStart) {
          dp.options.yearStart = date.getFullYear();
        }
        if (dp.options.yearOrder === 'desc') {
          for (y = dp.options.yearStart - dp.options.yearOffset; y >
          dp.options.yearStart - dp.options.yearRange - 1; y--) {
            yearSel.options[i] = new Option(y, y);
            if (parseInt(dp.year) === y) {
              yearSel.options[i].selected = true;
            }
            i++;
          }
        } else {
          for (y = dp.options.yearStart + dp.options.yearOffset; y <
          dp.options.yearStart + dp.options.yearRange + 1; y++) {
            yearSel.options[i] = new Option(y, y);
            if (parseInt(dp.year) === y) {
              yearSel.options[i].selected = true;
            }
            i++;
          }
        }
        var time = void 0;
        if (!dp.time) {
          var d = new Date(dp.thenvalue);
          var minutes = d.getMinutes();
          if (minutes < 10) {
            minutes = '0' + minutes;
          }
          time = d.getHours() + ':' + minutes;
        } else {
          time = dp.time;
        }
        var timeTextBox = document.createElement('input');
        timeTextBox.id = dp.id + '_timeTextBox';
        timeTextBox.className = 'cal_timeTextBox';
        timeTextBox.type = 'text';
        timeTextBox.value = time;
        var submitButton = document.createElement('button');
        submitButton.id = dp.id + '_submit';
        submitButton.className = 'cal_submit btn btn-secondary';
        submitButton.innerHTML = 'OK';
        var calTable = document.createElement('table');
        var calTableThead = document.createElement('thead');
        var calSelRow = document.createElement('tr');
        var calSelCell = document.createElement('th');
        calSelCell.colSpan = 7;
        calSelCell.appendChild(monthSel);
        calSelCell.appendChild(yearSel);
        calSelRow.appendChild(calSelCell);
        calTableThead.appendChild(calSelRow);
        var calTableTbody = document.createElement('tbody');
        var calDayNameRow = document.createElement('tr');
        var calDayNameCell = void 0;
        for (i = 0; i < dp.options.dayNames.length; i++) {
          calDayNameCell = document.createElement('th');
          calDayNameCell.innerHTML = dp.options.dayNames[(dp.options.startDay +
              i) % 7].substr(0, dp.options.dayChars);
          calDayNameRow.appendChild(calDayNameCell);
        }
        calTableTbody.appendChild(calDayNameRow);
        var calDayRow = void 0;
        var calDayCell = void 0;
        while (firstDay <= dp.options.daysInMonth[dp.month]) {
          calDayRow = document.createElement('tr');
          for (i = 0; i < 7; i++) {
            calDayCell = document.createElement('td');
            if (firstDay <= dp.options.daysInMonth[dp.month] && firstDay > 0) {
              calDayCell.className = dp.id + '_calDay';
              calDayCell.axis = dp.year + '|' + (parseInt(dp.month) + 1) + '|' +
                  firstDay;
              calDayCell.innerHTML = firstDay;
            } else {
              calDayCell.className = 'dp_empty';
              calDayCell.innerHTML = ' ';
            }
            calDayRow.appendChild(calDayCell);
            if (firstDay === parseInt(dp.oldDay) && dp.month === dp.oldMonth &&
                dp.year === dp.oldYear) {
              calDayCell.classList.add('dp_selected');
            }
            if (firstDay === dp.nowDay && parseInt(dp.month) === dp.nowMonth &&
                parseInt(dp.year) === dp.nowYear) {
              calDayCell.classList.add('dp_today');
            }
            firstDay++;
          }
          calTableTbody.appendChild(calDayRow);
        }
        calTable.appendChild(calTableThead);
        calTable.appendChild(calTableTbody);
        var calTimePara = document.createElement('p');
        calTimePara.id = dp.id + '_calTime';
        calTimePara.appendChild(timeTextBox);
        calTimePara.appendChild(submitButton);
        dp.calendar.appendChild(calTable);
        dp.calendar.appendChild(calTimePara);
        dp.position = dp.getBoundingClientRect();
        dp.container.style.left = dp.position.left + window.pageXOffset + 'px';
        if (dp.position.top + dp.container.offsetHeight > window.innerHeight) {
          dp.container.style.top = dp.position.top + window.pageYOffset -
              dp.container.offsetHeight + 'px';
        } else {
          dp.container.style.top = dp.position.top + dp.position.height +
              window.pageYOffset + 'px';
        }
        var calDays = document.querySelectorAll('td.' + dp.id + '_calDay');
        for (i = 0; i < calDays.length; i++) {
          calDays[i].onmouseover = function() {
            this.classList.add('dp_roll');
          };
          calDays[i].onmouseout = function() {
            this.classList.remove('dp_roll');
          };
          calDays[i].onclick = function() {
            var el = document.querySelector('td.dp_selected');
            if (el) {
              el.classList.remove('dp_selected');
            }
            this.classList.add('dp_selected');
            self.updateValue(self.dp);
          };
          calDays[i].ondblclick = function() {
            var el = document.querySelector('td.dp_selected');
            if (el) {
              el.classList.remove('dp_selected');
            }
            this.classList.add('dp_selected');
            self.updateValue(self.dp);
            DatePicker.remove(dp);
          };
        }
        monthSel.onfocus = function() {
          dp.active = true;
        };
        monthSel.onblur = function() {
          dp.active = true;
        };
        monthSel.onchange = function() {
          dp.month = monthSel.value;
          dp.year = yearSel.value;
          DatePicker.remove(dp);
          self.create(dp);
        };
        yearSel.onfocus = function() {
          dp.active = true;
        };
        yearSel.onblur = function() {
          dp.active = true;
        };
        yearSel.onchange = function() {
          dp.month = monthSel.value;
          dp.year = yearSel.value;
          DatePicker.remove(dp);
          self.create(dp);
        };
        timeTextBox.onfocus = function() {
          dp.active = true;
        };
        timeTextBox.onblur = function() {
          self.updateValue(self.dp);
          DatePicker.alertError(self.dp);
        };
        timeTextBox.onkeyup = function(e) {
          self.dp.dirty = true;
        };
        submitButton.onclick = function(e) {
          if (self.dp.dirty) {
            self.updateValue(self.dp);
            DatePicker.alertError(self.dp);
          }
          DatePicker.remove(self.dp);
          e.stopPropagation();
        };
      },
    }, {
      key: 'formatValue', value: function formatValue(dp, year, month, day) {
        var time = document.getElementById(dp.id + '_timeTextBox').
            value.
            split(':');
        if (!time[0] || time[0] === '' || time[0] < 0 || time[0] > 23) {
          dp.error = 'Invalid hours value: ' + time[0] +
              '\nAllowed range is 00:23';
          return '';
        }
        if (!time[1] || time[1] === '' || time[1] < 0 || time[1] > 59 ||
            time[1].length !== 2) {
          dp.error = 'Invalid minutes value: ' + time[1] +
              '\nAllowed range is 00:59';
          return '';
        }
        if (day < 10) {
          day = '0' + day;
        }
        if (month < 10) {
          month = '0' + month;
        }
        var dateStr = dp.options.format.replace(/dd/i, day).
            replace(/mm/i, month).
            replace(/yyyy/i, year).
            replace(/hh/, time[0]).
            replace(/mm/, time[1]);
        dp.month = dp.oldMonth = '' + (month - 1) + '';
        dp.year = dp.oldYear = year;
        dp.oldDay = day;
        this.dp.thenvalue = month + '/' + day + '/' + year + ' ' + time[0] +
            ':' + time[1] + ':00';
        return dateStr;
      },
    }, {
      key: 'remove', value: function remove(dp) {
        dp.active = false;
        if (dp.container) {
          dp.container.remove();
        }
        dp.calendar = false;
        dp.container = false;
      },
    },
  ]);

  return DatePicker;
}();
