// --------------------------------------------------------------------------
// Simple Drop Down Menu ; Forked from / inspired by
// http://javascript-array.com/scripts/simple_drop_down_menu
// --------------------------------------------------------------------------

// Defaults
var navlist_timeout = 500;
var navlist_closetimer = 0;
var navlist_ddmenuitem = 0;
// open hidden layer
function mopen(id) {
    // cancel close timer
    mcancelclosetime();
    // close old layer
    if (navlist_ddmenuitem) navlist_ddmenuitem.style.visibility = 'hidden';
    // get new layer and show it
    navlist_ddmenuitem = document.getElementById(id);
    navlist_ddmenuitem.style.visibility = 'visible';
}
// hide showing layer
function mclose() {
    if(navlist_ddmenuitem) navlist_ddmenuitem.style.visibility = 'hidden';
}
// go close timer
function mclosetime() {
    navlist_closetimer = window.setTimeout(mclose, navlist_timeout);
}
// cancel close timer
function mcancelclosetime() {
    if (navlist_closetimer) {
        window.clearTimeout(navlist_closetimer);
        navlist_closetimer = null;
    }
}
// close layer when click-out
document.onclick = mclose;
