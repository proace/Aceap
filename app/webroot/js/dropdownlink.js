var dropdowns = new Array();
function attach(target, link)
{
	//Acquire Target
	obj = document.getElementById(target);

	//Initialize the data structure
	var i = dropdowns.length;
	dropdowns[i] = new Array();
	dropdowns[i][0] = target;
	dropdowns[i][1] = link;
	dropdowns[i][2] = obj.selectedIndex;	//last value before selection

	//Add Edit Link
	addOption(obj, "[edit]", -99999);

	//Add Code Reacting on Change
	obj.onchange = "if (this.value == -99999) { window }";
}



function addOption(selectbox,text,value )
{
	var optn = document.createElement("OPTION");
	optn.text = text;
	optn.value = value;
	selectbox.options.add(optn);
}