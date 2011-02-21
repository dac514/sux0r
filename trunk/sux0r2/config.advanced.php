<?php

/* ------------------------------------------------------------------------- */
/* Advanced configuration variables */
/* Don't modify these unless you know what you are doing */
/* ------------------------------------------------------------------------- */

// Smarty Error Reporting
// @see: http://ca2.php.net/manual/en/errorfunc.constants.php

$CONFIG['SMARTY_ERROR_REPORTING'] = 0;

// Ability to disable features defined here, false = disabled

$CONFIG['FEATURE'] = array(
    'auto_bookmark' => true,
    'bayes' => true,
    );

// Access levels for modules defined here
// It is the responsibility of the individual module and it's author(s)
// to implement their own access levels.

$CONFIG['ACCESS'] = array(
    'blog' => array(
        'publisher' => 500,
        'admin' => 999,
        ),
    'bookmarks' => array(
        'admin' => 999,
        ),
    'feeds'  => array(
        'admin' => 999,
        ),
    'photos'  => array(
        'publisher' => 500,
        'admin' => 999,
        ),
    );

// A list of webpages to to skip when a user presses a "cancel" button
// used in tandem with the suxFunct::getPreviousURL() function

$CONFIG['PREV_SKIP'] = array(
    'admin/access',
    'admin/purge',
    'bayes',
    'blog/bookmarks',
    'blog/edit',
    'blog/reply',
    'bookmarks/approve',
    'bookmarks/edit',
    'bookmarks/suggest',
    'cropper',
    'feeds/approve',
    'feeds/edit',
    'feeds/manage',
    'feeds/purge',
    'feeds/suggest',
    'photos/album/annotate',
    'photos/album/edit',
    'photos/upload',
    'society/relationship',
    'user/avatar',
    'user/edit',
    'user/login',
    'user/logout',
    'user/openid',
    'user/register',
    'user/reset',
    );

?>
