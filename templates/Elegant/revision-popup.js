// This div is generated by header.tmpl; remove it to display only on mouseover
var popup = document.getElementById('rev-popup');
document.getElementById('wrapper').removeChild(popup);
popup.style.display = 'block';

// Add event listeners to display/hide the popup when hovering the revnum header
var revnum = document.getElementById('revnum');
addEvent(revnum, 'mouseover', function() {this.parentNode.appendChild(popup)});
addEvent(revnum, 'mouseout',  function() {this.parentNode.removeChild(popup)});

function addEvent(obj, type, func) {
  if (obj.addEventListener) {
    obj.addEventListener(type, func, false);
    return true;
  } else if (obj.attachEvent) {
    return obj.attachEvent('on'+type, func);
  } else {
    return false;
  }
}
