<?php

/* Navigation menu */

$gtext['navcontainer'] = array(
    'Home' => suxFunct::makeUrl('/home'),
    'Blog' => array(
		suxFunct::makeUrl('/blog'),
        suxFunct::getModuleMenu('blog'),
		),
    'Feeds' => array(
		suxFunct::makeUrl('/feeds'),
        suxFunct::getModuleMenu('feeds'),
		),
    'Bookmarks' => array(
		suxFunct::makeUrl('/bookmarks'),
        suxFunct::getModuleMenu('bookmarks'),
		),
    'Photos' => array(
		suxFunct::makeUrl('/photos'),
        suxFunct::getModuleMenu('photos'),
		),
    'Source Code' => 'http://sourceforge.net/projects/sux0r/',
	);


/* Copyright */

$gtext['copyright'] = '<a href="http://www.sux0r.org/">sux0r</a> is copyright &copy;
<a href="http://www.trotch.com/">Trotch Inc</a> ' . date('Y') . ' and is distributed under
the <a href="http://www.fsf.org/licensing/licenses/gpl-3.0.html">GNU General Public License</a>.
Hosting by <a href="http://www.networkredux.com/">Network Redux</a>.';

$gtext['data_license'] = 'Unless otherwise specified, contents of this site are copyright by the contributors and available under the <br />
<a href="http://creativecommons.org/licenses/by/3.0/">Creative Commons Attribution 3.0</a>.
Contributors should be attributed by full name or nickname.';

/* Now back our regular scheduled program */

$gtext['404_continue'] = 'Click here to continue';
$gtext['404_h1'] = 'Oops, Page Not Found (Error 404)';
$gtext['404_p1'] = 'For some reason (mis-typed URL, faulty referral from another site, out-of-date search engine listing or we simply deleted a file) the page you were after is not here.';
$gtext['admin'] = 'Administration';
$gtext['banned_continue'] = 'Click here to continue';
$gtext['banned_h1'] = 'Banned';
$gtext['banned_p1'] = 'You have been a bad person, a very very bad person.';
$gtext['continue'] = 'Continue';
$gtext['home'] = 'Home';
$gtext['login'] = 'Login';
$gtext['logout'] = 'Logout';
$gtext['register'] = 'Register';
$gtext['welcome'] = 'Welcome';

?>