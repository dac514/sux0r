<?php

/* ------------------------------------------------------------------------- */
/* Error reporting and debugging */
/* ------------------------------------------------------------------------- */

$CONFIG['DEBUG'] = false;

error_reporting(E_ALL | E_STRICT); // Development
// error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING); // Hosting

/* ------------------------------------------------------------------------- */
/* Configuration variables */
/* ------------------------------------------------------------------------- */

// Database parameters, PDO compatible, NB: first key is default
$CONFIG['DSN'] =  array(
    'sux0r' => array('mysql:host=localhost;dbname=sux0r', 'root', 'root'),
    // 'sux0r' => array('pgsql:host=localhost;dbname=sux0r', 'postgres', 'root'),
    );

// Site title
$CONFIG['TITLE'] = 'sux0r';

// The auto-detected path to your sux0r installation.
// If you set this yourself, no trailing slash!

$CONFIG['PATH'] = dirname(__FILE__);

// The url suffix to your site. For example, if your site is
// http://www.sux0r.org/ then '' is appropriate. If your site is
// http://domain.com/my/sux0r/ then '/my/sux0r' is the correct value.
// No trailing slash!

$CONFIG['URL'] = '/sux0r2';

// Default language for site, uses a 2 letter l10n ISO-CODE naming convention
// See: http://www.loc.gov/standards/iso639-2/php/code_list.php

$CONFIG['LANGUAGE'] = 'en';

// Default country for site, uses a 2 letter l10n ISO-CODE naming convention
// See: http://www.iso.org/iso/list-en1-semic-2.txt

$CONFIG['COUNTRY'] = 'ca';

// Default partition for site

$CONFIG['PARTITION'] = 'sux0r';

// Use clean Url?
// If apache rewrite rules aren't working for you, change to false

$CONFIG['CLEAN_URL'] = true;

// The realm for Digest Access Authentication. This value, along with a
// username, encrypts and stores passwords as HA1 = MD5(username:realm:password)
// The realm should contain at least the name of the host performing the
// authentication and might additionally indicate the collection of users who
// have access. An example might be 'users@sux0r.org'
//
// *** WARNING ***
// If you change this value after the fact, all stored passwords will
// become invalid and need to be reset. GET THIS RIGHT THE FIRST TIME!
//
// For more infom see:
// http://en.wikipedia.org/wiki/Digest_access_authentication

$CONFIG['REALM'] = 'users@sux0r.org';

// A salt used to create data verification hashes. Any random word will do.
// If a malicious user discovers your salt, this offers no real protection.
// Guard the salt zealously, change it as needed.

$CONFIG['SALT'] = 'flyingturtle';

// Sux0r modules may cache templates, set the duration in seconds below.

$CONFIG['CACHE_LIFETIME'] = 900;

// Timzeone, pick yours from the list available at http://php.net/manual/en/timezones.php

$CONFIG['TIMEZONE'] = 'America/Montreal';

/* ------------------------------------------------------------------------- */
/* Advanced configuration variables */
/* Don't modify these unless you know what you are doing */
/* ------------------------------------------------------------------------- */

require(dirname(__FILE__)  . '/config.advanced.php');

// Override below...

?>