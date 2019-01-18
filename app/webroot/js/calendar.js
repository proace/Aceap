// Generates HTML popup of calendar
var curdate = new Date();
var curyear = curdate.getFullYear();

var intSelectedYear  = curyear;
var intSelectedMonth = curdate.getMonth();
var intSelectedDay   = 1;

if(!intSelectedYear)
  intSelectedYear = curyear;

if(!intSelectedMonth)
  intSelectedMonth = curdate.getMonth();

var intBeginYear=1950;
var intEndYear=curyear + 15;


var name_returned_field = '';
var show_time;

var strLanguage='en';
var strCopyRight='';
var strCopyLink='';
var strCopyTarget='_new';


var intRaise = 1;
var intBorderWidth = 2;

	
var strClCellSel   = "#000000";
var strBgClCellSel = "#ffffff";

var strClCell      = "#000000";
var strBgClCell    = "#CEDFEF";

var strBgCl		   = "#FFFFFF";
var strCl		   = "#0072E1";
var strMCl = "#0072E1";

var strBorderClLight = "#3B5D89";
var strBorderClDark  = "#5383C1";

var strPos		   = "absolute";
var intLeft		   = 100;
var intTop		   = 210;

var intFontSize	= 12;
var strFontFamily	= 'Verdana';

var intCellSize	= 21;
var intCellSpace= 2;

var intSelectFontSize	=	11;
var intDaysFontSize		=	11;

var strTitleFontFamily	= 'Verdana';
var intTitleFontSize	= 12;


var intWidth=0;
var intHeight=0;
//==================================================================================================================

if(strLanguage=="fr"){
        arrMonths = new Array('Janvier', 'Fйvrier', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Aout', 'Septembre', 'Octobre', 'Novembre', 'Dйcembre');
    }else if(strLanguage=="de"){
        arrMonths = new Array('Januar', 'Februar', 'Mдrz', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember');
    }else if(strLanguage=="es"){
        arrMonths = new Array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
    }else if(strLanguage=="bg"){
        arrMonths = new Array('Януари', 'Февруари', 'Март', 'Април', 'Май', 'Юни', 'Юли', 'Август', 'Септември', 'Октомври', 'Ноемвриr', 'Декември');
    }else if(strLanguage=="ru"){
        arrMonths = new Array('Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь');
    }else if(strLanguage=="it"){
        arrMonths = new Array('Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre');
    }else{
        arrMonths = new Array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
    }
    if(strLanguage== "fr"){
        arrDays 	 = new Array('Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi');
        arrLi		 = new Array('Di', 'Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa');
    }else if(strLanguage=="de"){
        arrDays		 = new Array('Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag');
        arrLi 		 = new Array('So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa');
    }else if(strLanguage=="es"){
        arrDays		 = new Array('Domingo', 'Lunes', 'Martes', 'Miйrcoles', 'Jueves', 'Viernes', 'Sбbado')
        arrLi		 = new Array('Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa');
    }else if(strLanguage=="bg"){
        arrDays		 = new Array('Неделя', 'Понеделник', 'Вторник', 'Сряда', 'Четвъртък', 'Петък', 'Събота')
        arrLi		 = new Array('Нд', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб');
    }else if(strLanguage=="it"){
        arrDays		 = new Array('Domenica', 'Lunedм', 'Martedм', 'Mercoledм', 'Giovedм', 'Venerdм', 'Sabato')
        arrLi		 = new Array('Dm', 'Lm', 'Md', 'Ml', 'Gm', 'Vm', 'Sb');
    }else if(strLanguage=="ru"){
        arrDays		 = new Array('Воскресение','Понедельник','Вторник','Среда','Четверг','Пятница','Суббота')
        arrLi		 = new Array('Во', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб');
    }else{
        arrDays		 = new Array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
        arrLi		 = new Array('Su','Mo','Tu','We','Th','Fr','Sa');
    }
//=============================================================================
function mChangeMonth(obj){
		intSelectedMonth = document.forms['calendarfrm'].month.selectedIndex;
		initCalendar();
}
//=============================================================================
function mChangeYear(obj){
		intSelectedYear = document.forms['calendarfrm'].year.options[document.forms['calendarfrm'].year.selectedIndex].text;
		initCalendar();
}
//=============================================================================
function initCalendar(){
	var intDay = '&nbsp;';
	var blnFlag = true;
	var objDate = new Date(intSelectedYear, intSelectedMonth,1);

		for(var intCounterA = 0; intCounterA < 6; intCounterA++){
				for(var intCounterB = 0; intCounterB < 7;intCounterB++){
					bgColorLayer(strBgClCell,'A'+intCounterA+'_'+intCounterB);
					colorLayer(strClCell,'A'+intCounterA+'_'+intCounterB);
          
					if((objDate.getDay()==intCounterB)&&(blnFlag)){
						blnFlag=false;
						intDay=1;			
					}
					
					textLayer(intDay,'A'+intCounterA+'_'+intCounterB);
					
					if((!blnFlag)&&(intDay!=''))intDay++;
					if((new Date(intSelectedYear, intSelectedMonth,intDay)).getMonth()!=intSelectedMonth)intDay='&nbsp;';	
					
				}
		}
		
}
//===========================================================================================
function mOver(obj){
	colorLayer(strClCellSel,obj);
	bgColorLayer(strBgClCellSel,obj);
	self.status='';
	return true;
}
//===========================================================================================
function mOut(obj){
	colorLayer(strClCell,obj);
	bgColorLayer(strBgClCell,obj);
	self.status='';
	return true;
}
//===========================================================================================
function animate(x, y){
	posLayer(x,y);
	
	showLayer();
}

function posLayer(x,y){
	calobj = document.getElementById('calendar');
	calobj.style.top = y;
	calobj.style.left = x;
}
function showLayer(){
  /*
  if(formname){
    if(fieldname){
      v = document.forms[formname].elements[fieldname].value;
      if(v){
        intSelectedDay = parseInt(v);
        intSelectedYear = v.substr(v.length - 4);
        intSelectedMonth = v.slice(v.indexOf('/')+1,v.lastIndexOf('/'));
        initCalendar();

      }
      else
        initCalendar();
    }
    else
      initCalendar();
  }
  else
    initCalendar();
  */
  calobj = document.getElementById('calendar');
	calobj.style.visibility = 'visible';
}
function hideLayer(){
  calobj = document.getElementById('calendar');
	calobj.style.visibility = 'hidden';
}
function bgColorLayer(lBgc ,lNm ){

	document.getElementById(lNm).style.backgroundColor = lBgc;
}
function colorLayer(lC ,lNm){
	document.getElementById(lNm).style.color = lC;
}
function textLayer(layerText,lNm ){
	var strTool = intSelectedYear+' , ' + arrMonths[intSelectedMonth] + ' ' + layerText;
	calobj = document.getElementById(lNm);
	calobj.innerHTML = '<a class="day" title="'+strTool+'">'+layerText+'</a>';
}

function onClCal(obj){
var strMSG='Please, define function onClickCalendar(obj){...} to handle mouse click!';
  if(obj){
	  obj=obj.innerHTML;
	  obj=obj.split('<');
	  obj=obj[1].split('>');
	  obj=obj[1];
	  if(!parseInt(obj))return;
	  obj=new Array(obj,intSelectedMonth,intSelectedYear);
  }
	if(typeof(onClickCalendar) == 'function'){
		onClickCalendar(obj);
		initCalendar();
	}else{
		onClickCalendar=new Function('obj','alert("'+strMSG+'");');
		onClickCalendar(obj);
		initCalendar();
	}
}



function writeCalendar(i,ii){
name_returned_field = i;
show_time = ii;
var s="";
	s='<style>\n';
	s+='#A0_0,#A0_1,#A0_2,#A0_3,#A0_4,#A0_5,#A0_6,#A1_0,#A1_1,#A1_2,#A1_3,#A1_4,#A1_5,#A1_6,#A2_0,#A2_1,#A2_2,#A2_3,#A2_4,#A2_5,#A2_6,#A3_0,#A3_1,#A3_2,#A3_3,#A3_4,#A3_5,#A3_6,#A4_0,#A4_1,#A4_2,#A4_3,#A4_4,#A4_5,#A4_6,#A5_0,#A5_1,#A5_2,#A5_3,#A5_4,#A5_5,#A5_6{\n';
	s+='background-color:'+strBgClCell+';\n'; 
	s+='cursor:hand;\n';
	s+='text-align:center;\n';	
	s+='vertical-align:middle;\n';
	s+='z-index:10;\n';
	s+='position:relative;\n';
	s+='width:'+intCellSize+'px;\n';
	s+='height:'+intCellSize+'px;\n';
	s+='}\n';
	
	s+='.day{\n';
	s+='color:'+strCl+';\n';
	s+='font-family: '+strFontFamily+';\n';
	s+='font-size: '+intDaysFontSize+'px;\n';
	s+='text-decoration : none;\n';
	s+='}\n';
	
	s+='.sl{\n';
	s+='background-color:'+strBgCl+';';
	s+='color:'+strMCl+';\n';
	
	s+='width:'+parseInt((7*intCellSize+5*intCellSpace)/2)+'px;\n';
	s+='text-align:left;\n';
	s+='vertical-align:middle;\n';
	s+='font-family: '+strFontFamily+';\n';
	s+='font-size: '+intSelectFontSize+'px;\n';
	s+='text-decoration : none;\n';
	s+='}\n';


	s+='.title{\n';
	s+='background-color:'+strBgCl+';';
	s+='color:'+strMCl+';\n';
	s+='vertical-align:middle;\n';
	s+='text-align:center;\n';
	s+='z-index:10;\n';
	s+='font-family: '+strTitleFontFamily+';\n';
	s+='font-size: '+intTitleFontSize+'px;\n';
	s+='text-decoration : none;\n';
	s+='}\n';

	if(!blnVisible){
		s+='#calendar{visibility:hidden; position:'+strPos+'; z-index:5; top:'+intTop+'px; left:'+intLeft+'px;}\n';
		
	}else{
		s+='#calendar{position:'+strPos+'; z-index:0; top:'+intTop+'px; left:'+intLeft+'px;}\n';
		
	}
	s+='</style>\n';

	
	s+='\<div id="calendar">\n';
	// style="position:absolute;top:500;left:500"
	s+='<form name="calendarfrm">\n';
	s+='<table border="'+intRaise+'" bordercolor="'+strBgCl+'" bordercolordark="'+strBorderClDark+'" bordercolorlight="'+strBorderClLight+'" cellpadding="0" cellspacing="'+intBorderWidth+'" bgcolor="'+strBgCl+'">\n';
	s+='<tr>\n';
		s+='<td nowrap>\n';
	s+='<table border="0" cellpadding="0" cellspacing="0" bgcolor="'+strBgCl+'">\n';
	s+='<tr>\n';
		s+='<td align="center" nowrap>\n';
			s+='<select name="month" id="month" onchange="mChangeMonth();" class="sl">\n';
				for(var intCounter=0;intCounter<12;intCounter++){
					if(intCounter==intSelectedMonth)	
						s+='<option value="'+intCounter+'" selected>'+arrMonths[intCounter]+'\n';		
						else
						s+='<option value="'+intCounter+'">'+arrMonths[intCounter]+'\n';		
				}	
			s+='</select>\n';
			s+='<select name="year" id="year" onchange="mChangeYear();"  class="sl">\n';
				for(var intCounter=intBeginYear;intCounter<=intEndYear;intCounter++){
					if(intCounter==intSelectedYear){
						s+='<option value="'+intCounter+'" selected>'+intCounter+'\n';		
						}else
						s+='<option value="'+intCounter+'">'+intCounter+'\n';		
				}
			s+='</select>\n';
		s+='</td>\n';
	s+='</tr>\n';
	s+='<tr>\n';
		s+='<td nowrap>\n';
			s+='<table border="0" cellpadding="0" cellspacing="'+intCellSpace+'">\n';
				s+='<tr>\n';
					for(var intCounterA=0;intCounterA<arrDays.length;intCounterA++)
						s+='<td valign="middle" class="title" align="center"><a title="'+arrDays[intCounterA]+'"><font class="title">'+arrLi[intCounterA]+'</font></a></td>\n';				
					
					s+='</tr>\n';
				for(var intCounterA=0;intCounterA<6;intCounterA++){
					s+='<tr>\n';
						for(var intCounterB=0;intCounterB<7;intCounterB++)
							s+='<td bgcolor="'+strBgClCell+'" id="A'+intCounterA+'_'+intCounterB+'" onmousedown="onClCal(this);" onmouseover="mOver('+"'A"+intCounterA+"_"+intCounterB+"'"+');" onmouseout="mOut('+"'A"+intCounterA+"_"+intCounterB+"'"+');"></td>\n';
					s+='</tr>\n';
				}	

				s+='<tr><td colspan="7" class="title" align="center"><a href="javascript:onClCal(\'\')" class="title"><b>Close</b></a></td></tr>\n';

			s+='</table></td></tr></table></td></tr></table></form></div>\n';
	
	document.write(s);
	initCalendar();
}
//===================================================================================================
var blnVisible = false;
var calLeftPos = 500;
var calTopPos = 100;
var fieldname = 'ffrom';
var formname = 'filterform';

function onClickCalendar(obj){
	//var f = document.filters;
	hideLayer('calendar');
	if(obj){
	  //ss = obj[0] + "/" + (++obj[1]) + "/" + obj[2];
	  ss = obj[2]+"-"+(++obj[1]) + "-" + obj[0];
    document.forms[formname].elements[fieldname].value = ss;
  }
  fieldname = '';
}
function showCalendar(callfield,callformname){
	if (window.event)
	{
	mousex = window.event.x;
	mousey = window.event.y;
	}
	else
	{
		mousex = 300;
		mousey = 0;
	}
  if(callformname)
	  formname = callformname;
	  
	calTopPos = mousey + 80 + document.body.scrollTop-50;
	calLeftPos = mousex - 80;
	if(callfield != fieldname){
	  blnVisible = false;
	}
	fieldname = callfield;
	if(!blnVisible){
		animate(calLeftPos,calTopPos);
		blnVisible = true;
	}
	else{
		hideLayer('calendar');
		blnVisible = false;
	}
}
writeCalendar(fieldname,1);
