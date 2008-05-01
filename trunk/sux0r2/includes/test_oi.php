<?php

require_once '../config.php';
require_once '../initialize.php';
include_once 'suxOpenID.php';

$openID = new suxOpenID();

// ----------------------------------------------------------------------------
// Assign stuff to profile array?
// ----------------------------------------------------------------------------

// Determine if I should add microid stuff
if (array_key_exists('microid', $openID->profile)) {
	$hash = sha1($openID->profile['idp_url']);
	$values = is_array($openID->profile['microid']) ? $openID->profile['microid'] : array($openID->profile['microid']);

	foreach ($values as $microid) {
		preg_match('/^([a-z]+)/i', $microid, $mtx);
		$openID->profile['opt_headers'][] = sprintf('<meta name="microid" content="%s+%s:sha1:%s" />', $mtx[1], $proto, sha1(sha1($microid) . $hash));
	}
}

// Determine if I should add pavatar stuff
if (array_key_exists('pavatar', $openID->profile))
	$openID->profile['opt_headers'][] = sprintf('<link rel="pavatar" href="%s" />', $openID->profile['pavatar']);


// ----------------------------------------------------------------------------
// Acctually do something now?
// ----------------------------------------------------------------------------

// Decide which runmode, based on user request or default
$run_mode = (!empty($_REQUEST['openid_mode']) ? $_REQUEST['openid_mode'] : 'no'); // Unsafe

// Run in the determined runmode
$openID->debug('---------------------------------------------------------------------');
$openID->debug("Run mode: $run_mode at: " . date("D M j G:i:s T Y"));
$openID->debug($_REQUEST, 'Request params');

$var = $run_mode . '_mode';
$openID->$var();



?>