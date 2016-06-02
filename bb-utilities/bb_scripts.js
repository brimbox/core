/*
Copyright (C) Brimbox LLC

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License Version 3 (�GNU GPL v3�)
as published by the Free Software Foundation. 

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU GPL v3 for more details. 

You should have received a copy of the GNU GPL v3 along with this program.
If not, see http://www.gnu.org/licenses/
 */

//this is the module submit javascript function
//each time a tab is clicked this function executes
function bb_submit_form(params) {
	// need defined but empty javascript array
	// cannot get undefined item value from undefined array
	params = typeof params !== 'undefined' ? params : [];
	// disable submit button asap
	// button, target, and passthis are optional
	// can only be this or undefined
	var passthis = params[2];
	// temporarily disable the calling object to prevent double submits
	// to use this you have to pass "this" refering to the button element
	if (passthis != undefined) {
		passthis.disabled = true;
	}

	// default button = 0
	var button = params[0];
	// undefined target and slug will result in postback
	var target = params[1];
	// all three parameters are optional, this nasty little piece of javascript
	// works in all
	// current major browsers, it is a very important piece of code
	// required form
	var frmobj = document.forms['bb_form'];

	// because multiple submit buttons are handled inconsistantly in major
	// browsers
	// set a hidden value to keep track of which buttons are selected
	if (button == undefined) {
		frmobj.elements['bb_button'].value = 0;
	} else {
		frmobj.elements['bb_button'].value = button;
	}

	// update bb_submit with module coming from
	// form objects bb_submit and bb_module should be equal on postback
	module = frmobj.elements['bb_module'].value;
	frmobj['bb_submit'].value = frmobj['bb_module'].value;

	if (target != undefined) {
		// update bb_module with module going to
		// set bb_module as target module
		frmobj['bb_module'].value = target;
	}	
	
	for (var i = 0; i < frmobj.length; i++) {
		// rel attribute from form element tag
		// ignore attribute so the elements need not have the current module
		// prepended
		// to the name of the variable, for global form variables
		rel_attrib = frmobj.elements[i].getAttribute('rel');
		if (rel_attrib != 'ignore') {
			// rename form object with module name prefix
			// this make global post variables have a unique name
			frmobj.elements[i].name = module + "_" + frmobj.elements[i].name;
		}
	}
	// submit the form, all major browsers!
	frmobj.submit();
	return false; // for Internet Explorer
}

// submit form, e is a javascript var, frmele is a on the fly form element
// of is the offset for the next database page
function bb_page_selector(e, of) {
	var frmobj = document.forms['bb_form'];
	var frmele = frmobj.elements[e];
	frmele.value = of;
	bb_submit_form();
	return false;
}

function bb_logout_selector(i) {
	var frmobj = document.forms['bb_form'];
	var frmele = frmobj.elements['bb_userrole'];
	frmele.value = i;
	bb_submit_form([ 0, 'bb_logout' ]);
	return false;
}

function bb_submit_object(f, k) {
	var frmobj = document.forms["bb_form"];
	var a = frmobj.action;

	frmobj.elements['bb_object'].value = k;
	frmobj.action = f;
	// straight submit without bb_submit form
	frmobj.submit();
	// set the action back
	frmobj.action = a;
	return false;
}

function bb_submit_link(f) {
	var frmobj = document.forms["bb_form"];
	var a = frmobj.action;

	frmobj.action = f;
	// straight submit without bb_submit form
	frmobj.submit();
	// set the action back
	frmobj.action = a;
	return false;
}

// links javascript
var bb_links = new Object();

bb_links.standard = function(k, rt, tg) {
	var frmobj = document.forms['bb_form'];

	frmobj.bb_post_key.value = k;
	frmobj.bb_row_type.value = rt;
	frmobj.bb_row_join.value = rt;
	bb_submit_form([ 0, tg]);
	return false;
}

bb_links.join = function(k, rt, rj, tg) {
	var frmobj = document.forms['bb_form'];

	frmobj.bb_post_key.value = k;
	frmobj.bb_row_type.value = rt;
	frmobj.bb_row_join.value = rj;
	bb_submit_form([ 0, tg]);
	return false;
}

bb_links.relate = function(rl, tg) {
	var frmobj = document.forms['bb_form'];

	frmobj.bb_relate.value = rl;
	bb_submit_form([ 0, tg ]);
	return false;
}

// reports javascript
var bb_reports = new Object();

bb_reports.clear_report = function() {
	// offset and button carried through
	if (document.bb_form.report_type.value == 0) {
		bb_submit_form();
	}
	return false;
}
bb_reports.paginate_table = function(n, p, s, o) {
	// this runs on next link
	// offset and button carried through
	// offset previously incremented, offset 0 if next link not chosen
	document.bb_form.page.value = p;
	document.bb_form.sort.value = s;
	document.bb_form.order.value = o;
	bb_submit_form(n);
	return false;
}
bb_reports.sort_order = function(n, s, o) {
	// this runs on next link
	// offset and button carried through
	// offset previously incremented, offset 0 if next link not chosen
	document.bb_form.sort.value = s;
	document.bb_form.order.value = o;
	bb_submit_form(n);
	return false;
}

bb_reports.clear_textarea = function() {
	// clear and select textarea
	// had to use id for some reason
	var myObj = document.getElementById("txtarea");
	myObj.value = "";
	return false;
}
bb_reports.select_textarea = function() {
	// had to use id for some reason
	var myObj = document.getElementById("txtarea");
	myObj.select();
	return false;
}
