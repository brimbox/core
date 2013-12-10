//this is the module submit javascript function
//each time a tab is clicked this function executes
function bb_submit_form(button, target, passthis)
    {
    //button, target, and passthis are optional
    
    //all three parameters are optional, this nasty little piece of javascript works in all
    //current major browsers, it is a very important piece of code
    var frmobj=document.forms['bb_form'];
    //action set to self, always through the controller
    frmobj.action = "";

    //temporarily disable the calling object to prevent double submits
    //to use this you have to pass "this" refering to the button element
    if (passthis!=undefined)
        {
        passthis.disabled = true;
        }    
    
    //because multiple submit buttons are handled inconsistantly in major browsers
    //set a hidden value to keep track of which buttons are selected
    if (button==undefined)
        {
        frmobj.elements['bb_button'].value = 0;
        }
    else
        {
        frmobj.elements['bb_button'].value = button;   
        }
        
    //module is current module always, this for submitting form vars
    module=frmobj.elements['bb_module'].value;
    
    //once module is set for form module submission, set bb_module for target
    //if undefined, target is current module, actual target is always index.php
    if (target!=undefined)
        {
        frmobj['bb_module'].value = target;
        }   
        
    for (var i=0; i<frmobj.length; i++)
        {
        //rel attribute from form element tag
        //ignore attribute so the elements need not have the current module prepended
        //to the name of the variable, for global form variables
        rel_attrib = frmobj.elements[i].getAttribute('rel');
        if (rel_attrib != 'ignore')
            {
            //rename form object with module name prefix
            //this make global post variables have a unique name
            frmobj.elements[i].name = module + "_" + frmobj.elements[i].name;
            }
        }
    //submit the form, all major browsers!
    frmobj.submit();
	return false; //for Internet Explorer
    }
	
//submit form, e is a javascript var, frmele is a on the fly form element 
//of is the offset for the next database page
function bb_page_selector(e,of)
	{
	var frmobj = document.forms['bb_form'];
	var frmele = frmobj.elements[e];
	frmele.value = of;
	bb_submit_form(0);
	return false;
	}


//links javascript
var bb_links = new Object();

	bb_links.standard = function(k,rt,tg) {
		var frmobj=document.forms['bb_form'];
		
		frmobj.bb_post_key.value = k;
		frmobj.bb_row_type.value = rt;
		bb_submit_form(0,tg);
		return false;
	}
	
	bb_links.input = function(k,rj,rt,tg) {
		var frmobj=document.forms['bb_form'];
		
		frmobj.bb_post_key.value = k;
		frmobj.bb_row_type.value = rt;
		frmobj.bb_row_join.value = rj;
		bb_submit_form(0,tg);
		return false;
	}

//reports javascript
var bb_reports = new Object();

	bb_reports.clear_report = function()
		{
		//offset and button carried through
		if (document.bb_form.report_type.value == 0)
			{
			bb_submit_form(0);
			}
		return false;
		}
	bb_reports.paginate_table = function(p)
		{
		//this runs on next link
		//offset and button carried through
		//offset previously incremented, offset 0 if next link not chosen
		document.bb_form.page.value = p;
		bb_submit_form(0);
		return false;
		}
	bb_reports.clear_textarea = function()
		{
		//clear and select textarea
		//had to use id for some reason
		var myObj = document.getElementById("txtarea");
		myObj.value = "";
		return false;
		}    
	bb_reports.select_textarea = function()
		{
		//had to use id for some reason
		var myObj = document.getElementById("txtarea");
		myObj.select();
		return false;
		}

