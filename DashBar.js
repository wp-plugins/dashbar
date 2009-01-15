function checkDashBar() {
  if(!document.getElementById) {return;}
  var body = document.getElementsByTagName('BODY')[0];
  var DashBar = document.getElementById('DashBar');  
  if(!DashBar) {
    DashBar = document.createElement('p');
    DashBar.id = 'DashBar';
    DashBar.innerHTML = DashBarInner;
  }
  body.insertBefore(DashBar, body.firstChild);  
  if(document.all) {
    var elts = DashBar.getElementsByTagName('LI');
    for(i=0;i<elts.length;i++) {
      if((elts[i].parentNode.parentNode.id == 'DashBar') && (elts[i].childNodes.length > 1)) {
        elts[i].onmouseover = function() {this.className+= " over";};
        elts[i].onmouseout = function() {this.className = this.className.replace(" over","");};
      }
    }
    DashBar.onmouseover = function() {this.className+= " over";};
    DashBar.onmouseout = function() {this.className = this.className.replace(" over","");};
  }
}
function addDashBarLoadEvent(func) {if (typeof window.onload != 'function') {window.onload = func;} else {var old = window.onload;window.onload = function() {old();func();}}}
addDashBarLoadEvent(checkDashBar);