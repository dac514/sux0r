<?php

/* Navigation menu */

$gtext['navcontainer'] = array(
    'Startseite' => suxFunct::makeUrl('/home'),
  /*  'Blog' => array(
		suxFunct::makeUrl('/blog'),
        suxFunct::getModuleMenu('blog'),
		),*/
    'Feeds' => array(
		suxFunct::makeUrl('/feeds'),
        suxFunct::getModuleMenu('feeds'),
		),
  /*  'Lesezeichen' => array(
		suxFunct::makeUrl('/bookmarks'),
        suxFunct::getModuleMenu('bookmarks'),
		),
    'Fotos' => array(
		suxFunct::makeUrl('/photos'),
        suxFunct::getModuleMenu('photos'),
		),
    'Quellcode' => 'http://sourceforge.net/projects/sux0r/',*/
	);


/* Copyright */

$gtext['copyright'] = '<a href="http://www.sux0r.org/">sux0r</a> is copyleft &copy;
<a href="http://www.trotch.com/">Trotch Inc</a> ' . date('Y') . ' and is distributed under
the <a href="http://www.fsf.org/licensing/licenses/gpl-3.0.html">GNU General Public License</a>.
Hosting by <a href="http://www.networkredux.com/">Network Redux</a>.';

$gtext['data_license'] = 'Unless otherwise specified, contents of this site are copyright by the contributors and available under the <br />
<a href="http://creativecommons.org/licenses/by/3.0/">Creative Commons Attribution 3.0</a> .
Contributors should be attributed by full name or nickname.';

/* Now back our regular scheduled program */

$gtext['404_continue'] = 'Hier klicken, um fortzufahren';
$gtext['404_h1'] = 'Hoppla, Seite nicht gefunden (Fehler 404)';
$gtext['404_p1'] = 'Aus irgendeinem Grund (falsch geschriebene URL, fehlerhafte Umleitung von einer anderen Seite, nicht mehr aktuelle Suchmaschienen-Auflistung oder wir haben einfach eine Datei gelöscht) war die Seite, die Sie gesucht haben, nicht hier.';
$gtext['admin'] = 'Administration';
$gtext['banned_continue'] = 'Hier klicken, um fortzufahren';
$gtext['banned_h1'] = 'Gesperrt';
$gtext['banned_p1'] = 'Sie sind eine sehr böse, eine sehr sehr böse Person gewesen.';
$gtext['continue'] = 'Fortfahren';
$gtext['home'] = 'Startseite';
$gtext['login'] = 'Einloggen';
$gtext['logout'] = 'Ausloggen';
$gtext['register'] = 'Registrieren';
$gtext['welcome'] = 'Willkommen';

?>