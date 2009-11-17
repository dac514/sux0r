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
    'Favorieten' => array(
		suxFunct::makeUrl('/bookmarks'),
        suxFunct::getModuleMenu('bookmarks'),
		),
    'Fotos' => array(
		suxFunct::makeUrl('/photos'),
        suxFunct::getModuleMenu('photos'),
		),
    'Source Code' => 'http://sourceforge.net/projects/sux0r/',
	);


/* Copyright */

$gtext['copyright'] = '<a href="http://www.sux0r.org/">sux0r</a> is copyleft &copy;
<a href="http://www.trotch.com/">Trotch Inc</a> ' . date('Y') . ' en is uitgebracht onder
de <a href="http://www.fsf.org/licensing/licenses/gpl-3.0.html">GNU General Public License</a>.
Hosting door <a href="http://www.networkredux.com/">Network Redux</a>.';

$gtext['data_license'] = 'Tenzij anders vermeld, is alle inhoud op deze site is copyleft bij de auteur en beschikbaar onder de  <br />
<a href="http://creativecommons.org/licenses/by/3.0/">Creative Commons Attribution 3.0</a> .
Auteurs worden bij hun volledige naam of nickname genoemd.';

/* Now back our regular scheduled program */

$gtext['404_continue'] = 'Klik hier om door te gaan';
$gtext['404_h1'] = 'Oops, Pagina niet gevonden (Error 404)';
$gtext['404_p1'] = 'Door een bepaalde reden (verkeerde URL, foute link vanaf een andere site, verouderde zoekmachine index of we hebben de file verwijderd) is de pagina die je zoekt niet beschikbaar.';
$gtext['admin'] = 'Administratie';
$gtext['banned_continue'] = 'Klik hier om door te gaan';
$gtext['banned_h1'] = 'Verbannen';
$gtext['banned_p1'] = 'Je bent een slecht persoon geweest, een heel slecht persoon.';
$gtext['continue'] = 'Doorgaan';
$gtext['home'] = 'Home';
$gtext['login'] = 'Inloggen';
$gtext['logout'] = 'Uitloggen';
$gtext['register'] = 'Registreren';
$gtext['welcome'] = 'Welkom';

?>