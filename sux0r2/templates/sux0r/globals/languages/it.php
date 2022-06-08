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

$gtext['copyright'] = '<a href="http://sux0r.trotch.com/">sux0r</a> è copyleft &copy;
<a href="http://www.trotch.com/">Trotch.com</a> ' . date('Y') . ' ed è distribuito sotto licenza
<a href="http://www.fsf.org/licensing/licenses/gpl-3.0.html">GNU General Public License</a>.';

$gtext['data_license'] = 'Salvo diversamente specificato, i contenuti del sito sono copyright dei
rispettivi autori e disponibili sotto licenza <br />
<a href="http://creativecommons.org/licenses/by/3.0/">Creative Commons Attribution 3.0</a>.
Gli autori devono essere menzionati (nome completo o nick).';

/* Now back our regular scheduled program */

$gtext['404_continue'] = 'Click qui per continuare';
$gtext['404_h1'] = 'Oops, pagina non trovata! (Errore 404)';
$gtext['404_p1'] = 'Per qualche motivo (URL scritto male, riferimento sbagliato da un altro sito, elenchi datati di motori di ricerca o semplicemente per la cancellazione del file) la pagina che stavate cercando non è disponibile.';
$gtext['admin'] = 'Amministrazione';
$gtext['banned_continue'] = 'Click qui per continuare';
$gtext['banned_h1'] = 'Bannato';
$gtext['banned_p1'] = 'Sei stato molto, molto cattivo.';
$gtext['continue'] = 'Continua';
$gtext['home'] = 'Home';
$gtext['login'] = 'Collegati';
$gtext['logout'] = 'Esci';
$gtext['register'] = 'Registrati';
$gtext['welcome'] = 'Benvenuti';

