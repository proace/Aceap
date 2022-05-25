var nCurIdx = 0;

function HighlightSrting(nIdx){
  var children = document.getElementById("r"+nIdx).childNodes;
  for (var i = 0; i < children.length; i++) {
    if (children[i].tagName=='TD') {
      children[i].style.backgroundColor="#FFDDDD";
    }
  }
}

function ReturnSrting(nIdx){
  var lnk = document.getElementById("link"+nIdx);
  lnk.style.color="";

  el = document.getElementById("r"+nIdx);
  var children = el.childNodes;
  for (var i = 0; i < children.length; i++) {
    if (children[i].tagName=='TD'){ 
      if (children[i].className=='firstcol') 
        children[i].style.backgroundColor='#CCCCCC';
      else
        children[i].style.backgroundColor='';
    }
  }
}

function MarkCurrent(nNum){
  if (nIdx>1) {
    ReturnSrting(nCurIdx);
  }

  nCurIdx = nNum;

  HighlightSrting(nIdx)
}
