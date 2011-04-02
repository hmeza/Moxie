function init() {
	var now = new Date();
	var els = getElementsByClassName(document, '*','date');
	
	var label = document.createElement('label');
	label.appendChild(document.createTextNode('Date'));
	label.setAttribute('for','dob');
	
	removeChildren(els[0]);	
	els[0].appendChild(label);
	els[0].appendChild(document.createTextNode(' '));
	
	var dateSel = document.createElement('input');
	dateSel.type='text';
	dateSel.id='date';
	dateSel.name='date';
	dateSel.defaultValue='YYYY'; //now.format("Y"); //\\-m\\-d');
	dateSel.value=''; //now.format("d"); //'YYYY/MM/DD';
	dateSel.className+=' default';
	
	els[0].appendChild(dateSel);
	
	date = new calendarInput(dateSel);
}

AttachEvent(window,'load',init,false);