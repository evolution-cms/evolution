/*
 * DatePicker
 * @author Rick Hopkins
 * @modified by Micah Nolte and Martin Vašina
 * @version 0.3.2
 * @classDescription A date picker object. Created with the help of MooTools v1.11
 * MIT-style License.

-- start it up by doing this in your domready:

$$('input.DatePicker').each( function(el){
    new DatePicker(el);
});

 */

var DatePicker = new Class({

    /* set and create the date picker text box */
    initialize: function(dp, options){

        // Options defaults
        this.dayChars = 1; // number of characters in day names abbreviation
        this.dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        this.daysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        this.format = 'dd-mm-yyyy hh:mm:00';
        this.monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        this.startDay = 7; // 1 = week starts on Monday, 7 = week starts on Sunday
        this.yearOrder = 'asc';
        this.yearRange = 10;
        this.yearStart = (new Date().getFullYear());
        this.yearOffset = -10;

        // Pull the rest of the options
        if(options) {
            options = options;
        } else {
            options = [];
        }
        dp.options = {
            monthNames: (options.monthNames && options.monthNames.length == 12 ? options.monthNames : this.monthNames) || this.monthNames, 
            daysInMonth: (options.daysInMonth && options.daysInMonth.length == 12 ? options.daysInMonth : this.daysInMonth) || this.daysInMonth, 
            dayNames: (options.dayNames && options.dayNames.length == 7 ? options.dayNames : this.dayNames) || this.dayNames,
            startDay : options.startDay || this.startDay,
            dayChars : options.dayChars || this.dayChars, 
            format: options.format || this.format,
            yearStart: options.yearStart || this.yearStart,
            yearRange: options.yearRange || this.yearRange,
            yearOrder: options.yearOrder || this.yearOrder,
            yearOffset: options.yearOffset || this.yearOffset
        };
        
        // Finds the entered date, or uses the current date
        if(dp.value != '') {
        	// handle dd-mm-YYYY date format as that is invalid for Date()
        	if (dp.options.format == 'dd-mm-YYYY hh:mm:00' || dp.options.format == 'dd-mm-YYYY') {
        		var dateVals = dp.value.split(' ');
        		var dateParts = dateVals[0].split('-');
        		dp.thenvalue = dateParts[1] + '/' + dateParts[0] + '/' + dateParts[2];
        		if (dateVals[1]) dp.thenvalue = dp.thenvalue + ' ' + dateVals[1];
        	} else {
        		dp.thenvalue = dp.value;
        	}
            dp.then = new Date(dp.thenvalue);
            dp.today = new Date();
        } else {
            dp.then = dp.today = new Date();
        }
        // Set beginning time and today, remember the original
        dp.oldYear = dp.year = dp.then.getFullYear();
        dp.oldMonth = dp.month = dp.then.getMonth();
        dp.oldDay = dp.then.getDate();
        dp.nowYear = dp.today.getFullYear();
        dp.nowMonth = dp.today.getMonth();
        dp.nowDay = dp.today.getDate();
        
        dp.setProperties({'id':dp.getProperty('name'), 'autocomplete': 'off'});
        dp.container = false;
        dp.calendar = false;
        dp.interval = null;
        dp.active = false;
        dp.onclick = dp.onfocus = this.create.pass(dp, this);
    },
    
    close: function(e) {
        if (!$(this.dp.id + 'dp_container')) return;
        e = new Event(e);

        var clickOutside = ($chk(e) && e.target != this.dp && e.target != this.dp.container && !$(this.dp.id + 'dp_container').hasChild(e.target));
        if (clickOutside) {
            this.remove(this.dp);
        }
    },

    /* create the calendar */
    create: function(dp){
        this.dp = dp;
        if (dp.calendar) return false;
        // Hide select boxes while calendar is up
        if(window.ie6){
            $$('select').addClass('dp_hide');
        }
        
        /* create the outer container */
        dp.container = new Element('div', {'class':'dp_container', 'id': dp.id + 'dp_container'}).injectBefore(dp);
        
        document.addEvent('mousedown', this.close.bind(this));

        document.addEvents({
            'keydown': function(e) {
                e = new Event(e);
                if ((e.code== 9 && !e.shift) || e.code == 27) {
                    this.remove(this.dp);
                }
            }.bind(this)
        });
        
        /* create the calendar */
        dp.calendar = new Element('div', {'class':'dp_cal'}).injectInside(dp.container);
        
        /* create the date object */
        var date = new Date();
        
        /* create the date object */
        if (dp.month && dp.year) {
            date.setFullYear(dp.year, dp.month, 1);
        } else {
            dp.month = date.getMonth();
            dp.year = date.getFullYear();
            date.setDate(1);
        }
        dp.year % 4 == 0 ? dp.options.daysInMonth[1] = 29 : dp.options.daysInMonth[1] = 28;
        
        /* set the day to first of the month */
        var firstDay = (1-(7+date.getDay()-dp.options.startDay)%7);
        
        
        
        /* create the month select box */
        monthSel = new Element('select', {'id':dp.id + '_monthSelect'});
        for (var m = 0; m < dp.options.monthNames.length; m++){
            monthSel.options[m] = new Option(dp.options.monthNames[m], m);
            if (dp.month == m) monthSel.options[m].selected = true;
        }
        
        /* create the year select box */
        yearSel = new Element('select', {'id':dp.id + '_yearSelect'});
        i = 0;
        dp.options.yearStart ? dp.options.yearStart : dp.options.yearStart = date.getFullYear();
        if (dp.options.yearOrder == 'desc'){
            for (var y = dp.options.yearStart - dp.options.yearOffset; y > (dp.options.yearStart - dp.options.yearRange - 1); y--){
                yearSel.options[i] = new Option(y, y);
                if (dp.year == y) yearSel.options[i].selected = true;
                i++;
            }
        } else {
            for (var y = dp.options.yearStart + dp.options.yearOffset; y < (dp.options.yearStart + dp.options.yearRange + 1); y++){
                yearSel.options[i] = new Option(y, y);
                if (dp.year == y) yearSel.options[i].selected = true;
                i++;
            }
        }
        
        /* create time textbox */
        if (!dp.time) {
            var d = new Date();
            var minutes = d.getMinutes();
            if (minutes < 10) {
                minutes = '0' + minutes;
            }
            var time = d.getHours() + ':' + minutes;
        } else {
            var time = dp.time;
        }
        timeTextBox = new Element('input', {'id':dp.id + '_timeTextBox', 'class':'cal_timeTextBox', 'type':'text', 'value':time});
        
        /* start creating calendar */
        calTable = new Element('table');
        calTableThead = new Element('thead');
        calSelRow = new Element('tr');
        calSelCell = new Element('th', {'colspan':'7'});
        monthSel.injectInside(calSelCell);
        yearSel.injectInside(calSelCell);
        calSelCell.injectInside(calSelRow);
        calSelRow.injectInside(calTableThead);
        calTableTbody = new Element('tbody');
        
        /* create day names */
        calDayNameRow = new Element('tr');
        for (var i = 0; i < dp.options.dayNames.length; i++) {
            calDayNameCell = new Element('th');
            calDayNameCell.appendText(dp.options.dayNames[(dp.options.startDay+i)%7].substr(0, dp.options.dayChars)); 
            calDayNameCell.injectInside(calDayNameRow);
        }
        calDayNameRow.injectInside(calTableTbody);
        
        /* create the day cells */
        while (firstDay <= dp.options.daysInMonth[dp.month]){
            calDayRow = new Element('tr');
            for (i = 0; i < 7; i++){
                if ((firstDay <= dp.options.daysInMonth[dp.month]) && (firstDay > 0)){
                    calDayCell = new Element('td', {'class':dp.id + '_calDay', 'axis':dp.year + '|' + (parseInt(dp.month) + 1) + '|' + firstDay}).appendText(firstDay).injectInside(calDayRow);
                } else {
                    calDayCell = new Element('td', {'class':'dp_empty'}).appendText(' ').injectInside(calDayRow);
                }
                // Show the previous day
                if ( (firstDay == dp.oldDay) && (dp.month == dp.oldMonth ) && (dp.year == dp.oldYear) ) {
                    calDayCell.addClass('dp_selected');
                }
                // Show today
                if ( (firstDay == dp.nowDay) && (dp.month == dp.nowMonth ) && (dp.year == dp.nowYear) ) {
                    calDayCell.addClass('dp_today');
                }
                firstDay++;
            }
            calDayRow.injectInside(calTableTbody);
        }
        
        /* table into the calendar div */
        calTableThead.injectInside(calTable);
        calTableTbody.injectInside(calTable);
        
        /* time box */
        //calTimeRow = new Element('tr');
        calTimePara = new Element('p', {'class':dp.id + '_calTime'});
        timeTextBox.injectInside(calTimePara);
        //calTimeCell.injectInside(calTimeRow);
        
        calTable.injectInside(dp.calendar);
        calTimePara.injectInside(dp.calendar);
        
        /* set the onmouseover events for all calendar days */
        $$('td.' + dp.id + '_calDay').each(function(el){
            el.onmouseover = function(){
                el.addClass('dp_roll');
            }.bind(this);
        }.bind(this));
        
        /* set the onmouseout events for all calendar days */
        $$('td.' + dp.id + '_calDay').each(function(el){
            el.onmouseout = function(){
                el.removeClass('dp_roll');
            }.bind(this);
        }.bind(this));
        
        /* set the onclick events for all calendar days */
        $$('td.' + dp.id + '_calDay').each(function(el){
            el.onclick = function(){
                ds = el.axis.split('|');
                dp.value = this.formatValue(dp, ds[0], ds[1], ds[2]);
                this.remove(dp);
            }.bind(this);
        }.bind(this));
        
        /* set the onchange event for the month & year select boxes */
        monthSel.onfocus = function(){ dp.active = true; };
        monthSel.onblur = function() {
           dp.active = true;
        }.bind(this);      
        monthSel.onchange = function(){
            dp.month = monthSel.value;
            dp.year = yearSel.value;
            this.remove(dp);
            this.create(dp);
        }.bind(this);
        
        yearSel.onfocus = function(){ dp.active = true; };
        yearSel.onblur = function() {
           dp.active = true;
        }.bind(this);
        yearSel.onchange = function(){
            dp.month = monthSel.value;
            dp.year = yearSel.value;
            this.remove(dp);
            this.create(dp);
        }.bind(this);
        
        /* set the onchange event for the month & year select boxes */
        timeTextBox.onfocus = function(){ dp.active = true; };        
        timeTextBox.onkeypress = function(e) {
          e = new Event(e);
          if (e.code == 13) {          
	            this.dp.value = this.formatValue(this.dp, this.dp.nowYear, parseInt(this.dp.nowMonth) + 1, this.dp.nowDay);
	            this.remove(this.dp);
	            return false;
            }
        }.bind(this);
    },
    
    /* Format the returning date value according to the selected formation */
    formatValue: function(dp, year, month, day) {
        /* setup the date string variable */
        var dateStr = '';
        
        /* get time */
        var time = $(dp.id + '_timeTextBox').value.split(':');

        if (time[0] < 0 || time[0] > 23) {
            alert('Invalid hours value: ' + time[0] + '\nAllowed range is 00-23');
            return '';
        }
        if (time[1] < 0 || time[1] > 59 || time[1].length !== 2) {
            alert('Invalid minutes value: ' + time[1] + '\nAllowed range is 00-59');
            return '';
        }
        
        /* check the length of day */
        if (day < 10) day = '0' + day;
        if (month < 10) month = '0' + month;
        
        /* check the format & replace parts // thanks O'Rey */
        dateStr = dp.options.format.replace( /dd/i, day ).replace( /mm/i, month ).replace( /yyyy/i, year ).replace(/hh/, time[0]).replace(/mm/, time[1]);
        dp.month = dp.oldMonth = '' + (month - 1) + '';
        dp.year = dp.oldYear = year;
        dp.oldDay = day;
        
        /* return the date string value */
        return dateStr;
    },
    
    /* Remove the calendar from the page */
    remove: function(dp){
        dp.active = false;
        if (dp.container) dp.container.remove();
        dp.calendar = false;
        dp.container = false;
        $$('select.dp_hide').removeClass('dp_hide');
    }
});
