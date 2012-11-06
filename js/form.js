function init() {
	var els = getElementsByClassName(document, '*','date');
	var dateSel = document.createElement('input');
	var label = document.createElement('label');
	var currentDate = els[0].innerHTML;
	label.appendChild(document.createTextNode('Date'));
	label.setAttribute('for','dob');
	label.setAttribute('id', 'date_label');
	
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
	dateSel.value = currentDate;
}