<script type="text/javascript" src="media/calendar/datepicker.js"></script>
<script>
	var dpOffset = [(datepicker_offset)];
	var dpformat = '[(datetime_format)] hh:mm:00';
	var dpdayNames = [%dp_dayNames%];
	var dpmonthNames = [%dp_monthNames%];
	var dpstartDay = [%dp_startDay%];
	var DatePickers = document.querySelectorAll('input.DatePicker');
	if(DatePickers) {
		for(var i = 0; i < DatePickers.length; i++) {
			let format = DatePickers[i].getAttribute("data-format");
			new DatePicker(DatePickers[i], {
				yearOffset: dpOffset,
				format: format !== null ? format : dpformat,
				dayNames: dpdayNames,
				monthNames: dpmonthNames,
				startDay: dpstartDay
			});
		}
	}
</script>
