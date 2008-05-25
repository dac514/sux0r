<?php

// TODO
// Make this accessible for the visually impaired

require_once(dirname(__FILE__) . '/../../includes/symbionts/jpgraph/src/jpgraph_antispam.php');

function getChallenge() {

    // Note: Neither '0' (digit) or 'O' (letter) can be used to avoid confusion
    $possible = '123456789ABCDEFGHIJKLMNPQRSTUVWXYZabcdefghijklmnpqrstuvwxyz';
    $code = '';
    for ($i = 0; $i < 5; ++$i) {
        $code .= substr($possible, mt_rand(0, strlen($possible)-1), 1);
    }
    return $code;

}

ini_set('session.use_only_cookies', true);
session_start();

$captcha = new AntiSpam();

$challenge = getChallenge();
$chars = $captcha->Set($challenge);

if ($captcha->Stroke() === false ) {
    die('Illegal or no data to plot');
}

$_SESSION['captcha'] = $challenge;

?>