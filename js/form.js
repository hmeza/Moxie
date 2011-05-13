function init() {
	var now = new Date();
	var currentMonth;
	var currentDay;
	var els = getElementsByClassName(document, '*','date');
	var dateSel = document.createElement('input');
	var label = document.createElement('label');
	label.appendChild(document.createTextNode('Date'));
	label.setAttribute('for','dob');
	
	removeChildren(els[0]);	
	els[0].appendChild(label);
	els[0].appendChild(document.createTextNode(' '));
	
	dateSel.type='text';
	dateSel.id='date';
	dateSel.name='date';
	dateSel.defaultValue="YYYY";
	dateSel.className+=' default';
	
	els[0].appendChild(dateSel);
	
	date = new calendarInput(dateSel);
	currentMonth = now.getMonth()+1;
	if (currentMonth<10) currentMonth = "0"+currentMonth;
	currentDay = now.getDate();
	if (currentDay<10) currentDay = "0"+currentDay;
	dateSel.value = now.getRealYear()+"-"+currentMonth+"-"+currentDay;
}

AttachEvent(window,'load',init,false);