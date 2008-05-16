<?php

/**
* suxOpenID
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as
* published by the Free Software Foundation, either version 3 of the
* License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
* Inspired by:
* CJ Niemira: http://siege.org/projects/phpMyID/
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @copyright  2008 sux0r development group
* @license    http://www.gnu.org/licenses/agpl.html
*
*/

require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once(dirname(__FILE__) . '/../../includes/suxRenderer.php');

class suxOpenID {

    public $gtext = array(); // Language
    public $tpl; // Template

    public $profile = array();
    public $sreg = array();

    // Database suff
    protected $db;
    protected $inTransaction = false;
    protected $db_table_sec = 'openid_secrets';
    protected $db_table_trust = 'openid_trusted';

    protected $assoc_types = array();
    protected $session_types = array();
    protected $bcmath_types = array();

    private $g;
    private $p;

    private $user; // suxUser

    /**
    * Constructor
    *
    * @global array $CONFIG['DSN']
    * @global string $CONFIG['PARTITION']
    * @global string $CONFIG['LANGUAGE']
    * @param object $suxUser suxUser
    * @param string $key PDO dsn key
    */
    function __construct(suxUser $user, $key = null) {

        if (!$key && !empty($GLOBALS['CONFIG']['DSN']['openid'])) $key = 'openid';
        $this->db = suxDB::get($key); // Db
        set_exception_handler(array($this, 'logAndDie')); // Exception

        $this->user = $user; // User
        $this->tpl = new suxTemplate('openid', $GLOBALS['CONFIG']['PARTITION']); // Template
        $this->gtext = $this->tpl->getLanguage($GLOBALS['CONFIG']['LANGUAGE']); // Language

        /*
        Note:
        As this module is much more than a user interface,
        suxRenderer is called only when needed.
        */

        // Defined by OpenID spec
        // http://openid.net/specs/openid-authentication-1_1.html
        // http://openid.net/specs/openid-authentication-1_1.html#pvalue
        $this->assoc_types = array('HMAC-SHA1');
        $this->session_types = array('', 'DH-SHA1');
        $this->g = 2;
        $this->p =
        '1551728981814736974712322577637155' . '3991572480196691540447970779531405' .
        '7629378541917580651227423698188993' . '7278161526466314385615958256881888' .
        '8995127215884267541995034125870655' . '6549803580104870537681476726513255' .
        '7470407658574792912915723345106432' . '4509471500722962109419434978392598' .
        '4760375594985848253359305585439638443';

        // OpenID Setup user
        $this->profile = array(

            // Set a default IDP URL
            'my_url'	=>	suxFunct::makeUrl('openid', null, true),
            // lifetime of shared secret
            'lifetime'	=>	1440,
            // Use bcmath?
            'use_bcmath' => true,

            // Debug
            'debug'		=>	false,
            'logfile'	=>	'/tmp/suxOpenID.debug.log',

            // Determine the requested URL, DO NOT OVERRIDE
            'req_url' => $this->getReqUrl(),

            );

    }


    // ----------------------------------------------------------------------------
    // Runmode functions / OpenID Authentication 1.1 / are not camelCase()
    // ----------------------------------------------------------------------------

    /**
    * Perform an association with a consumer, establish a shared secret
    */
    function associate_mode() {

        // Validate the request
        if (empty($_POST['openid_mode']) || $_POST['openid_mode'] != 'associate') {
            $this->error400();
        }

        /* Get the OpenID Request Parameters */

        $assoc_type = 'HMAC-SHA1';
        if (!empty($_POST['openid_assoc_type']) && in_array($_POST['openid_assoc_type'], $this->assoc_types)) {
            $assoc_type = $_POST['openid_assoc_type'];
        }

        $session_type = '';
        if (!empty($_POST['openid_session_type']) && in_array($_POST['openid_session_type'], $this->session_types)) {
            $session_type = $_POST['openid_session_type'];
        }

        $dh_modulus = null;
        if (!empty($_POST['openid_dh_modulus'])) {
            $dh_modulus = $this->long(base64_decode($_POST['openid_dh_modulus']));
        }
        else if ($session_type == 'DH-SHA1') {
            $dh_modulus = $this->p;
        }

        $dh_gen = null;
        if (!empty($_POST['openid_dh_gen'])) {
            $dh_gen = $this->long(base64_decode($_POST['openid_dh_gen']));
        }
        else if ($session_type == 'DH-SHA1') {
            $dh_gen = $this->g;
        }

        $dh_consumer_public = null;
        if (!empty($_POST['openid_dh_consumer_public'])) {
            $dh_consumer_public = $_POST['openid_dh_consumer_public'];
        }
        else if ($session_type == 'DH-SHA1') {
            $this->errorPost('dh_consumer_public was not specified');
        }

        $lifetime = time() + $this->profile['lifetime'];

        // Create standard keys
        $keys = array(
            'assoc_type' => $assoc_type,
            'expires_in' => $this->profile['lifetime']
            );


        // If I don't handle bcmath, default to plaintext sessions
        if ($this->profile['use_bcmath'] === false) {
            $session_type = '';
        }

        // Add response keys based on the session type
        switch ($session_type) {

        case 'DH-SHA1':
            // Create the associate id and shared secret now
            list ($assoc_handle, $shared_secret) = $this->newAssoc($lifetime);

            // Compute the Diffie-Hellman stuff
            $private_key = $this->random($dh_modulus);
            $public_key = bcpowmod($dh_gen, $private_key, $dh_modulus);
            $remote_key = $this->long(base64_decode($dh_consumer_public));
            $ss = bcpowmod($remote_key, $private_key, $dh_modulus);

            $keys['assoc_handle'] = $assoc_handle;
            $keys['session_type'] = $session_type;
            $keys['dh_server_public'] = base64_encode($this->bin($public_key));
            $keys['enc_mac_key'] = base64_encode($this->x_or(sha1($this->bin($ss), true), $shared_secret));

            break;

        default:

            // Create the associate id and shared secret now
            list ($assoc_handle, $shared_secret) = $this->newAssoc($lifetime);

            $keys['assoc_handle'] = $assoc_handle;
            $keys['mac_key'] = base64_encode($shared_secret);

        }

        // Return the keys
        $this->wrapKv($keys);
    }


    /**
    * Handle a consumer's request to see if the user is already logged in
    */
    function checkid_immediate_mode () {
        if (empty($_GET['openid_mode']) || $_GET['openid_mode'] != 'checkid_immediate') {
            $this->error500();
        }
        $this->checkid($wait = false);
    }


    /**
    * Handle a consumer's request to see if the user is logged in, but be willing
    * to wait for them to perform a login if they're not
    */
    function checkid_setup_mode () {
        if (empty($_GET['openid_mode']) || $_GET['openid_mode'] != 'checkid_setup') {
            $this->error500();
        }
        $this->checkid($wait = true);
    }


    /**
    * Handle a consumer's request to see if the end user is logged in
    * @param bool $wait
    */
    private function checkid($wait) {

        $this->debug("checkid: wait? $wait");


        /* Get the OpenID Request Parameters */

        $identity = $_GET['openid_identity'];
        if (empty($identity)) {
            $this->errorGet('Missing identity');
        }

        $assoc_handle = null;
        if (!empty($_GET['openid_assoc_handle'])) {
            $assoc_handle = $_GET['openid_assoc_handle'];
        }

        $return_to = $_GET['openid_return_to'];
        if (empty($return_to)) {
            $this->error400('Missing return_to');
        }

        $trust_root = $return_to;
        if (!empty($_GET['openid_trust_root'])) {
            $trust_root = $_GET['openid_trust_root'];
        }

        $sreg_required = '';
        if (!empty($_GET['openid_sreg_required'])) {
            $sreg_required = $_GET['openid_sreg_required'];
        }

        $sreg_optional = '';
        if (!empty($_GET['openid_sreg_optional'])) {
            $sreg_optional = $_GET['openid_sreg_optional'];
        }

        // concatenate required and optional, if they want it we give it
        $sreg_requested = $sreg_required . ',' . $sreg_optional;

        // do the trust_root analysis
        if ($trust_root != $return_to) {
            // the urls are not the same, be sure return decends from trust
            if (! $this->urlDescends($return_to, $trust_root))
                $this->error500('Invalid trust_root: "' . $trust_root . '"');
        }

        // make sure i am this identifier
        //if (suxFunct::canonicalizeUrl($identity) != suxFunct::canonicalizeUrl($this->profile['my_url'])) {
        if (!mb_strpos($identity, 'user/profile') ||
            !$this->urlDescends(suxFunct::canonicalizeUrl($identity), suxFunct::canonicalizeUrl(suxFunct::makeURL('/', null, true)))) {

            $this->debug("Invalid identity: $identity");
            $this->debug("IdP URL: " . $this->profile['my_url']);

            $this->errorGet($return_to, "Invalid identity: '$identity'");

        }


        // Establish trust
        if ($this->user->loginCheck() && $this->checkTrusted($_SESSION['users_id'], $trust_root)) {

            // The user trusts this URL
            $_SESSION['openid_accepted_url'] = $trust_root;

        }
        else if ($wait && (! session_is_registered('openid_accepted_url') || $_SESSION['openid_accepted_url'] != $trust_root)) {

            // checkid_setup_mode()

            $_SESSION['openid_cancel_accept_url'] = $return_to;
            $_SESSION['openid_post_accept_url'] = $this->profile['req_url'];
            $_SESSION['openid_unaccepted_url'] = $trust_root;

            $this->debug('Transferring to acceptance mode.');
            $this->debug('Cancel URL: ' . $_SESSION['openid_cancel_accept_url']);
            $this->debug('Post URL: ' . $_SESSION['openid_post_accept_url']);

            $q = mb_strpos($this->profile['my_url'], '?') ? '&' : '?';
            $this->wrapRefresh($this->profile['my_url'] . $q . 'openid.mode=accept');
        }


        // begin setting up return keys
        $keys = array(
            'mode' => 'id_res'
            );

        // if the user is not logged in, transfer to the authorization mode
        if ($this->user->loginCheck() === false) {

            if ($wait) {

                unset($_SESSION['openid_uniqid']);
                $_SESSION['openid_cancel_auth_url'] = $return_to;
                $_SESSION['openid_post_auth_url'] = $this->profile['req_url'];

                $this->debug('Transferring to authorization mode.');
                $this->debug('Cancel URL: ' . $_SESSION['openid_cancel_auth_url']);
                $this->debug('Post URL: ' . $_SESSION['openid_post_auth_url']);

                $q = mb_strpos($this->profile['my_url'], '?') ? '&' : '?';
                $this->wrapRefresh($this->profile['my_url'] . $q . 'openid.mode=authorize');
            }
            else {

                $keys['user_setup_url'] = $this->profile['my_url'];
            }


        }
        else {

            // Trust URL
            if (isset($_SESSION['openid_always_trust']) && $_SESSION['openid_always_trust'] == 'yes') {
                $this->trustUrl($_SESSION['users_id'], $_SESSION['openid_accepted_url']);
            }

            // the user is logged in
            // remove the refresh URLs if set
            unset($_SESSION['openid_cancel_auth_url']);
            unset($_SESSION['openid_post_auth_url']);

            // check the assoc handle
            list($shared_secret, $expires) = $this->secret($assoc_handle);

            // if I can't verify the assoc_handle, or if it's expired
            if (!$shared_secret || (is_numeric($expires) && $expires < time())) {

                $this->debug("Session expired or missing key: $expires < " . time());

                if ($assoc_handle != null) {
                    $keys['invalidate_handle'] = $assoc_handle;
                    $this->destroyAssocHandle($assoc_handle);
                }

                $lifetime = time() + $this->profile['lifetime'];
                list ($assoc_handle, $shared_secret) = $this->newAssoc($lifetime);
            }

            $keys['identity'] = $this->profile['my_url'];
            $keys['assoc_handle'] = $assoc_handle;
            $keys['return_to'] = $return_to;

            $fields = array_keys($keys);
            $tokens = '';
            foreach ($fields as $key)
                $tokens .= sprintf("%s:%s\n", $key, $keys[$key]);

            // add sreg keys
            if ($this->user->loginCheck()) {

                $u = $this->user->getUser($_SESSION['users_id'], true);

                $sreg = @array (
                    'nickname' => $u['nickname'],
                    'email' => $u['email'],
                    'fullname' => "{$u['given_name']} {$u['family_name']}",
                    'dob' => $u['dob'],
                    'gender' => $u['gender'],
                    'postcode' => $u['postcode'],
                    'country' => $u['country'],
                    'language' => $u['language'],
                    'timezone' => $u['timezone'],
                    );

                // Unset empties
                foreach ($sreg as $key => $val) {
                    $val = trim($val);
                    if (empty($val)) {
                        unset($sreg[$key]);
                    }
                }

                // Sign keys
                foreach (explode(',', $sreg_requested) as $key) {
                    $skey = 'sreg.' . $key;
                    if (!empty($sreg[$key])) {
                        $tokens .= sprintf("%s:%s\n", $skey, $sreg[$key]);
                        $keys[$skey] = $sreg[$key];
                        $fields[] = $skey;
                    }
                }

            }

            $keys['signed'] = implode(',', $fields);
            $keys['sig'] = base64_encode(hash_hmac('sha1', $tokens, $shared_secret, true));
        }

        $this->wrapLocation($return_to, $keys);
    }


    /**
    * Handle a consumer's request to see if the user is authenticated
    */
    function check_authentication_mode() {

        // Validate the request
        if (empty($_POST['openid_mode']) || $_POST['openid_mode'] != 'check_authentication') {
            $this->error400();
        }

        /* Get the OpenID Request Parameters */

        $assoc_handle = $_POST['openid_assoc_handle'];
        if (empty($assoc_handle)) {
            $this->errorPost('Missing assoc_handle');
        }

        $sig = $_POST['openid_sig'];
        if (empty($sig)) {
            $this->errorPost('Missing sig');
        }

        $signed = $_POST['openid_signed'];
        if (empty($signed)) {
            $this->errorPost('Missing signed');
        }

        // Prepare the return keys
        $keys = array(
            'openid.mode' => 'id_res'
            );

        // Invalidate the assoc handle if we need to
        if (!empty($_POST['openid_invalidate_handle'])) {
            $this->destroyAssocHandle($_POST['openid_invalidate_handle']);
            $keys['invalidate_handle'] = $_POST['openid_invalidate_handle'];
        }

        // Validate the sig by recreating the kv pair and signing
        $_POST['openid_mode'] = 'id_res';
        $tokens = '';
        foreach (explode(',', $signed) as $param) {
            $post = str_replace('.', '_', $param);
            $tokens .= sprintf("%s:%s\n", $param, $_POST['openid_' . $post]);
        }

        // Look up the consumer's shared_secret and timeout
        list ($shared_secret, $expires) = $this->secret($assoc_handle);

        // if I can't verify the assoc_handle, or if it's expired
        $ok = null;
        if (!$shared_secret || (is_numeric($expires) && $expires < time())) {
            $keys['is_valid'] = 'false';
        }
        else {
            $ok = base64_encode(hash_hmac('sha1', $tokens, $shared_secret, true));
            $keys['is_valid'] = ($sig == $ok) ? 'true' : 'false';
        }

        $this->debug("\$sig: $sig == \$ok: $ok");

        // Return the keys
        $this->wrapKv($keys);
    }


   /**
    * Show a user if they are logged in or not
    */
    function id_res_mode() {

        /* Assert truthiness of openid_identity and act accordingly */

        if (!empty($_GET['openid_identity']) && $this->complete($_GET['openid_identity'])) {

            // Success
            // we have verified the identity
            // a maze of if/else follows...

            $this->destroyOpenIDSession();

            $u = $this->user->getUserByOpenID($_GET['openid_identity']);
            if ($u) {

                if ($this->user->loginCheck() && $_SESSION['users_id'] != $u['users_id']) {
                    // Wrong openid?
                    $this->wrapHtml($this->gtext['error_id_conflict']);
                }
                else {
                    // Log this user in
                    $this->user->setSession($u['users_id']);
                    suxFunct::redirect(suxFunct::makeUrl('/user/profile/' . $u['nickname']));
                }

            }
            elseif ($this->user->loginCheck()) {

                if (!$this->urlDescends($_GET['openid_identity'], $this->profile['my_url'])) {
                    // This must be this users id, attach it
                    $this->user->attachOpenID($_GET['openid_identity']);
                }

                // Send this user to their own page
                suxFunct::redirect(suxFunct::makeUrl('/user/profile/' . $_SESSION['nickname']));

            }
            else {

                // Forward to registration
                $_SESSION['openid_url_registration'] = $_GET['openid_identity'];
                $_SESSION['openid_url_integrity'] = md5($_GET['openid_identity'] . @$GLOBALS['CONFIG']['SALT']);

                // Sreg
                $query = null;
                foreach ($_REQUEST as $key => $val) {
                    if (preg_match('/^openid_sreg_/', $key)) {
                        $tmp = str_replace('openid_sreg_', '', $key);
                        $query[$tmp] = $val;
                    }
                }

                suxFunct::redirect(suxFunct::makeUrl('/user/register', $query));

            }



        }
        elseif (!empty($_GET['openid_identity'])) {

            // Failure
            $this->destroyOpenIDSession();
            $this->wrapHtml($this->gtext['error_failed'] . ': ' . $_GET['openid_identity']);

        }
        else {

            // Otherwise, provide useless info
            $this->destroyOpenIDSession();
            if ($this->user->loginCheck()) $this->wrapHtml($this->gtext['logged_in'] . ' ' . $_SESSION['nickname']);
            else $this->wrapHtml($this->gtext['not_logged_in']);

        }

    }


    // ----------------------------------------------------------------------------
    // Supplemental runmode functions accessible from controller
    // ----------------------------------------------------------------------------


    /**
    * Allow the user to accept trust on a URL
    */
    function accept_mode() {


        // the user needs refresh urls in their session to access this mode
        if (empty($_SESSION['openid_post_accept_url']) || empty($_SESSION['openid_cancel_accept_url']) || empty($_SESSION['openid_unaccepted_url']))
            $this->error500($this->gtext['error_directly']);

        // has the user accepted the trust_root?
        $accepted = (!empty($_REQUEST['accepted'])) ? $_REQUEST['accepted'] : null;

        // Unset just in case
        unset($_SESSION['openid_always_trust']);

        if ($accepted === 'always') {
            // refresh back to post_accept_url
            $_SESSION['openid_accepted_url'] = $_SESSION['openid_unaccepted_url'];
            $_SESSION['openid_always_trust'] = 'yes';
            $this->wrapRefresh($_SESSION['openid_post_accept_url']);
        }
        if ($accepted === 'yes') {
            // refresh back to post_accept_url
            $_SESSION['openid_accepted_url'] = $_SESSION['openid_unaccepted_url'];
            $this->wrapRefresh($_SESSION['openid_post_accept_url']);
        }
        elseif ($accepted === 'no') {
            // They rejected it, return to the client
            $q = mb_strpos($_SESSION['openid_cancel_accept_url'], '?') ? '&' : '?';
            $this->wrapRefresh($_SESSION['openid_cancel_accept_url'] . $q . 'openid.mode=cancel');
        }

        // if neither, offer the trust request
        $q = mb_strpos($this->profile['req_url'], '?') ? '&' : '?';
        $always = $this->profile['req_url'] . $q . 'accepted=always';
        $yes = $this->profile['req_url'] . $q . 'accepted=yes';
        $no  = $this->profile['req_url'] . $q . 'accepted=no';

        $r = new suxRenderer();
        $r->text =& $this->gtext;
        $r->text['unaccepted_url'] = $_SESSION['openid_unaccepted_url'];
        $r->text['always_url'] = $always;
        $r->text['yes_url'] = $yes;
        $r->text['no_url'] = $no;

        $r->header .= "<meta name='robots' content='noindex,nofollow' />\n";
        $r->bool['analytics'] = false;

        $this->tpl->assign_by_ref('r', $r);
        // $this->wrapHtml($this->tpl->fetch('accept.tpl'));
        $output = $this->tpl->assemble('accept.tpl');
        echo $output;
        exit;
    }


    /**
    * Perform a user authorization
    */
    function authorize_mode() {

        // the user needs refresh urls in their session to access this mode
        if (empty($_SESSION['openid_post_auth_url']) || empty($_SESSION['openid_cancel_auth_url']))
            $this->error500($this->gtext['error_directly']);

        if (!$this->user->loginCheck() && $this->user->authenticate()) {

            // Success!
            $this->wrapRefresh($_SESSION['openid_post_auth_url']);

        }
        else {

            // Too many password failures?
            if (isset($_SESSION['failures']) && $_SESSION['failures'] > $this->user->max_failures) {
                $this->errorGet($_SESSION['openid_cancel_auth_url'], $this->gtext['error_pw_fail']);
            }

            // Cancelled
            $q = mb_strpos($_SESSION['openid_cancel_auth_url'], '?') ? '&' : '?';
            $this->wrapRefresh($_SESSION['openid_cancel_auth_url'] . $q . 'openid.mode=cancel');

        }

    }


    /**
    *  Handle a consumer's request for cancellation.
    */
    function cancel_mode () {
        $this->wrapHtml($this->gtext['cancelled']);
    }


    /**
    * Handle errors
    */
    function error_mode () {
        isset($_REQUEST['openid_error']) ? $this->wrapHtml($_REQUEST['openid_error']) : $this->error500();
    }


    /**
    * Allow a user to perform a login
    *
    * @param string $openid_url
    */
    function login_mode() {

        if (empty($_REQUEST['openid_url'])) $this->error500($this->gtext['error_directly']);


        // Pass this to consumer
        $openid_url = filter_var($_REQUEST['openid_url'], FILTER_SANITIZE_URL);
        $this->forward($openid_url);

    }



    /**
    * The default information screen
    */
    function no_mode () {

        $q = mb_strpos($this->profile['my_url'], '?') ? '&' : '?';

        // Template
        $r = new suxRenderer();

        $r->header .= "<link rel='openid.server' href='{$this->profile['my_url']}' />\n";
        $r->header .= "<meta name='robots' content='noindex,nofollow' />\n";

        $r->text =& $this->gtext;
        $r->text['server_url'] = $this->profile['my_url'];
        $r->text['realm_id'] = $GLOBALS['CONFIG']['REALM'];
        $r->text['login_url'] = suxFunct::makeUrl('/user/login/openid');
        $r->text['test_url'] = $this->profile['my_url'] . $q . 'openid.mode=test';
        $r->bool['debug'] = $this->profile['debug'];

        $this->tpl->assign_by_ref('r', $r);
        $output = $this->tpl->assemble('no_mode.tpl');
        echo $output;
        exit;

    }


    /**
    * Testing for setup
    */
    function test_mode () {

        if (!$this->profile['debug']) {
            $this->wrapHtml('Sorry, debug mode is currently disabled.');
            exit;
        }

        @ini_set('max_execution_time', 180);

        $test_expire = time() + 120;
        $test_ss_enc = 'W7hvmld2yEYdDb0fHfSkKhQX+PM=';
        $test_ss = base64_decode($test_ss_enc);
        $test_token = "alpha:bravo\ncharlie:delta\necho:foxtrot";
        $test_server_private = '11263846781670293092494395517924811173145217135753406847875706165886322533899689335716152496005807017390233667003995430954419468996805220211293016296351031812246187748601293733816011832462964410766956326501185504714561648498549481477143603650090931135412673422192550825523386522507656442905243832471167330268';
        $test_client_public = base64_decode('AL63zqI5a5p8HdXZF5hFu8p+P9GOb816HcHuvNOhqrgkKdA3fO4XEzmldlb37nv3+xqMBgWj6gxT7vfuFerEZLBvuWyVvR7IOGZmx0BAByoq3fxYd3Fpe2Coxngs015vK37otmH8e83YyyGo5Qua/NAf13yz1PVuJ5Ctk7E+YdVc');

        $res = array();

        // bcmath
        $res['bcmath'] = extension_loaded('bcmath')
		? 'pass' : 'warn - not loaded';

        // sys_get_temp_dir
        $res['logfile'] = is_writable($this->profile['logfile'])
		? 'pass' : "warn - log is not writable";

        // secret
        list($test_assoc, $test_new_ss) = $this->newAssoc($test_expire);
        list($check, $check2) = $this->secret($test_assoc);
        $res['secret'] = ($check == $test_new_ss)
		? 'pass' : 'fail';

        // expire
        $res['expire'] = ($check2 <= $test_expire)
		? 'pass' : 'fail';

        // base64
        $res['base64'] = (base64_encode($test_ss) == $test_ss_enc)
		? 'pass' : 'fail';

        // hmac
        $test_sig = base64_decode('/VXgHvZAOdoz/OTa5+XJXzSGhjs=');
        $check = hash_hmac('sha1', $test_token, $test_ss, true);
        $res['hmac'] = ($check == $test_sig)
		? 'pass' : sprintf("fail - '%s'", base64_encode($check));

        if ($this->profile['use_bcmath']) {
            // bcmath powmod
            $test_server_public = '102773334773637418574009974502372885384288396853657336911033649141556441102566075470916498748591002884433213640712303846640842555822818660704173387461364443541327856226098159843042567251113889701110175072389560896826887426539315893475252988846151505416694218615764823146765717947374855806613410142231092856731';
            $check = bcpowmod($this->g, $test_server_private, $this->p);
            $res['bmpowmod-1'] = ($check == $test_server_public)
			? 'pass' : sprintf("fail - '%s'", $check);

            // long
            $test_client_long = '133926731803116519408547886573524294471756220428015419404483437186057383311250738749035616354107518232016420809434801736658109316293127101479053449990587221774635063166689561125137927607200322073086097478667514042144489248048756916881344442393090205172004842481037581607299263456852036730858519133859409417564';
            $res['long'] = ($this->long($test_client_public) == $test_client_long)
			? 'pass' : 'fail';

            // bcmath powmod 2
            $test_client_share = '19333275433742428703546496981182797556056709274486796259858099992516081822015362253491867310832140733686713353304595602619444380387600756677924791671971324290032515367930532292542300647858206600215875069588627551090223949962823532134061941805446571307168890255137575975911397744471376862555181588554632928402';
            $check = bcpowmod($test_client_long, $test_server_private, $this->p);
            $res['bmpowmod-2'] = ($check == $test_client_share)
			? 'pass' : sprintf("fail - '%s'", $check);

            // bin
            $test_client_mac_s1 = base64_decode('G4gQQkYM6QmAzhKbVKSBahFesPL0nL3F2MREVwEtnVRRYI0ifl9zmPklwTcvURt3QTiGBd+9Dn3ESLk5qka6IO5xnILcIoBT8nnGVPiOZvTygfuzKp4tQ2mXuIATJoa7oXRGmBWtlSdFapH5Zt6NJj4B83XF/jzZiRwdYuK4HJI=');
            $check = $this->bin($test_client_share);
            $res['bin'] = ($check == $test_client_mac_s1)
			? 'pass' : sprintf("fail - '%s'", base64_encode($check));

        } else {
            $res['bcmath'] = 'fail - big math functions are not available.';
        }

        // sha1_20
        $test_client_mac_s1 = base64_decode('G4gQQkYM6QmAzhKbVKSBahFesPL0nL3F2MREVwEtnVRRYI0ifl9zmPklwTcvURt3QTiGBd+9Dn3ESLk5qka6IO5xnILcIoBT8nnGVPiOZvTygfuzKp4tQ2mXuIATJoa7oXRGmBWtlSdFapH5Zt6NJj4B83XF/jzZiRwdYuK4HJI=');
        $test_client_mac_s2 = base64_decode('0Mb2t9d/HvAZyuhbARJPYdx3+v4=');
        $check = sha1($test_client_mac_s1, true);
        $res['sha1_20'] = ($check == $test_client_mac_s2)
		? 'pass' : sprintf("fail - '%s'", base64_encode($check));

        // x_or
        $test_client_mac_s3 = base64_decode('i36ZLYAJ1rYEx1VEHObrS8hgAg0=');
        $check = $this->x_or($test_client_mac_s2, $test_ss);
        $res['x_or'] = ($check == $test_client_mac_s3)
		? 'pass' : sprintf("fail - '%s'", base64_encode($check));

        $out = "<table border=1 cellpadding=4>\n";
        foreach ($res as $test => $stat) {
            $code = mb_substr($stat, 0, 4);
            $color = ($code == 'pass') ? '#9f9'
			: (($code == 'warn') ? '#ff9' : '#f99');
            $out .= sprintf("<tr><th>%s</th><td style='background:%s'>%s</td></tr>\n", $test, $color, $stat);
        }
        $out .= "</table>";

        $this->wrapHtml( $out );
    }


    // ----------------------------------------------------------------------------
    // Dumb stateless consumer
    // ----------------------------------------------------------------------------


    /**
    * Forward
    *
    * @param string $openid_url
    */
    private function forward($openid_url) {

        $this->debug('Initiating forward() procedure');

        if (!filter_var($openid_url, FILTER_VALIDATE_URL)) throw new Exception ('Invalid URL');

        // TODO:
        // get association, store key, fix $this->complete() to do calculations
        // locally, become a smart consumer... I need the help of someone smarter than me :(
        // WTF is dh_consumer_public = base64(btwoc(g ^ x mod p)) in PHP5? (What is x?!)
        // See: http://openid.net/specs/openid-authentication-1_1.html#mode_associate

        $keys = array(
            'mode' => 'checkid_setup',
            'identity' => $openid_url,
            'return_to' => $this->profile['my_url'],
            );

        $u = $this->user->getUserByOpenID($openid_url);
        if (!$u && !$this->urlDescends($openid_url, $this->profile['my_url'])) {
            // We don't know this user, we're also not logging into ourself, ask for sreg info
            $keys['sreg.required'] = 'nickname,email';
            $keys['sreg.optional'] = 'fullname,dob,gender,postcode,country,language,timezone';
        }

        // Check for server/delegate
        $url = $this->discover($openid_url);

        $this->debug($keys, "Server URL: $url");

        $this->wrapLocation($url, $keys);

    }


    /**
    * Complete
    *
    * @param string $openid_url
    */
    private function complete($openid_url) {

        $this->debug('Initiating complete() procedure');

        if (!filter_var($openid_url, FILTER_VALIDATE_URL)) throw new Exception ('Invalid URL');

        $auth = false;

        // Check for server/delegate, TODO: Improve this
        $url = $this->discover($openid_url);

        $keys = array(
            'mode' => 'check_authentication',
            'assoc_handle' => !empty($_GET['openid_assoc_handle']) ? $_GET['openid_assoc_handle'] : null,
            'sig' => !empty($_GET['openid_sig']) ?  $_GET['openid_sig'] : null,
            'signed' => !empty($_GET['openid_signed']) ? $_GET['openid_signed'] : null,
            );

        $this->debug($keys, "Server URL: $url");

        // Send all the openid response parameters from the openid.signed list
        if (!empty($_GET['openid_signed'])) foreach (explode(',', $_GET['openid_signed']) as $param) {
            $param = str_replace('.', '_', $param);
            if ($param != 'mode') {
                $tmp = 'openid_' . $param;
                if (isset($_GET[$tmp])) $keys[$param] = $_GET[$tmp];
            }
        }

        // Fix sreg
        foreach ($keys as $key => $val) {
            if (preg_match('/^sreg_/', $key)) {
                $tmp = str_replace('sreg_', 'sreg.', $key);
                $keys[$tmp] = $val;
                unset($keys[$key]);
                continue;
            }
        }

        $this->debug($keys, "Return signed keys:");

        $body = http_build_query($this->appendOpenID($keys));
        $opts = array(
            'http'=> array(
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $body,
                )
            );
        $ctx = stream_context_create($opts);
        $resp = @file_get_contents($url, null, $ctx);

        if (preg_match( '/is_valid:true/', $resp)) $auth = true;

        $this->debug('Valid Openid URL = ' . ($auth ? 'true' : 'false'));

        return $auth;

    }


    /**
    * Discover
    *
    * @param string $openid_url a url
    * @return string
    */
    private function discover($openid_url) {

        // TODO: Improve this
        // Check for server/delegate,
        $url = $openid_url;
        $tmp = @file_get_contents($openid_url);
        $found = array();
        // Try
        preg_match('/<link[^>]*rel=(["\'])openid.server\\1[^>]*href=(["\'])([^"\']+)\\2[^>]*\/?>/i', $tmp, $found);
        if (!empty($found[3])) $url = filter_var($found[3], FILTER_SANITIZE_URL);
        else {
            // And try again
            preg_match('/<link[^>]*href=(["\'])([^"\']+)\\1[^>]*rel=(["\'])openid.server\\3[^>]*\/?>/i', $tmp, $found);
            if (!empty($found[2])) $url = filter_var($found[2], FILTER_SANITIZE_URL);
        }

        return $url;

    }


    // ----------------------------------------------------------------------------
    // Support functions
    // ----------------------------------------------------------------------------


    /**
    * Prefix the keys of an array with  'openid.'
    * @param array $array
    * @return array
    */
    private function appendOpenID($array) {

        $r = array();
        foreach ($array as $key => $val) {
            $r['openid.' . $key] = $val;
        }
        return $r;

    }


    /**
    * Check if a url is trusted by user
    * @param int $id user id
    * @param string $id url
    * @return bool
    */
    private function checkTrusted($id, $url) {

        if (!filter_var($id, FILTER_VALIDATE_INT)) return false;

        $st = $this->db->prepare("SELECT COUNT(*) FROM {$this->db_table_trust} WHERE users_id = ? AND auth_url = ? LIMIT 1 ");
        $st->execute(array($id, $url));

        if ($st->fetchColumn() > 0) return true;
        else return false;

    }


    /**
    * trust a url
    * @param int $id user id
    * @param string $id url
    * @return bool
    */
    private function trustUrl($id, $url) {

        if (!filter_var($id, FILTER_VALIDATE_INT)) return false;
        $url = filter_var($url, FILTER_SANITIZE_URL);

        $trusted = array(
            'users_id' => $id,
            'auth_url' => $url,
            );

        $query = suxDB::prepareCountQuery($this->db_table_trust, $trusted) . 'LIMIT 1 ';
        $st = $this->db->prepare($query);
        $st->execute($trusted);

        if (!$st->fetchColumn()) {
            $query = suxDB::prepareInsertQuery($this->db_table_trust, $trusted);
            $st = $this->db->prepare($query);
            $st->execute($trusted);
        }

    }


    /**
    * Destroy a consumer's assoc handle
    * @param int $id
    */
    private function destroyAssocHandle($id) {

        if (!filter_var($id, FILTER_VALIDATE_INT)) return false;

        $this->debug("Destroying session: $id");

        // While we're in here, delete expired associations
        $st = $this->db->prepare("DELETE FROM {$this->db_table_sec} WHERE expiration < ? ");
        $st->execute(array(time()));

        // Delete association
        $st = $this->db->prepare("DELETE FROM {$this->db_table_sec} WHERE id = ? LIMIT 1 ");
        $st->execute(array($id));

    }


    /**
    * Destroy openid session info
    */
    private function destroyOpenIDSession() {

        if (isset($_SESSION) && is_array($_SESSION)) {
            foreach ($_SESSION as $key => $val) {
                if (preg_match('/^openid_/', $key)) {
                    unset($_SESSION[$key]);
                }
            }
        }

        $this->debug('OpenID session info destroyed.');

    }


    /**
    * Create a new consumer association
    * @param integer $expiration
    * @return array
    */
    private function newAssoc($expiration) {

        if (!filter_var($expiration, FILTER_VALIDATE_INT)) return array(false, false);

        // While we're in here, delete expired associations
        $st = $this->db->prepare("DELETE FROM {$this->db_table_sec} WHERE expiration < ? ");
        $st->execute(array(time()));

        // Establish a shared secret
        $shared_secret = $this->newSecret();
        $st = $this->db->prepare("INSERT INTO {$this->db_table_sec} (expiration, shared_secret) VALUES (?, ?) ");
        $st->execute(array($expiration, base64_encode($shared_secret)));
        $id = $this->db->lastInsertId();

        $this->debug('Started new assoc session: ' . $id);

        return array($id, $shared_secret);
    }


    /**
    * Get the shared secret and expiration time for the specified assoc_handle
    * @param string $handle assoc_handle to look up
    * @return array (shared_secret, expiration_time)
    */
    private function secret($id) {

        $st = $this->db->prepare("SELECT expiration, shared_secret FROM {$this->db_table_sec} WHERE id = ? ");
        $st->execute(array($id));
        $row = $st->fetch();

        $secret = !empty($row['shared_secret'])
		? base64_decode($row['shared_secret'])
		: false;

        $expiration = !empty($row['expiration'])
		? $row['expiration']
		: null;

        $this->debug("Found key: hash = '" . md5($secret) . "', length = '" . mb_strlen($secret) . "', expiration = '$expiration'");

        return array($secret, $expiration);

    }


    /**
    * Create a new shared secret
    * @return string
    */
    private function newSecret() {

        $r = '';
        for($i=0; $i<20; $i++)
            $r .= chr(mt_rand(0, 255));

        $this->debug("Generated new key: hash = '" . md5($r) . "', length = '" . mb_strlen($r) . "'");

        return $r;

    }


    /**
    * Determine if a child URL actually decends from the parent, and that the
    * parent is a good URL.
    * THIS IS EXPERIMENTAL
    * @param string $parent
    * @param string $child
    * @return bool
    */
    private function urlDescends($child, $parent) {

        if ($child == $parent) return true;

        $keys = array();
        $parts = array();
        $req = array('scheme', 'host');
        $bad = array('fragment', 'pass', 'user');

        foreach (array('parent', 'child') as $name) {
            $parts[$name] = @parse_url($$name);
            if ($parts[$name] === false)
                return false;

            $keys[$name] = array_keys($parts[$name]);

            if (array_intersect($keys[$name], $req) != $req)
                return false;

            if (array_intersect($keys[$name], $bad) != array())
                return false;

            if (! preg_match('/^https?$/i', strtolower($parts[$name]['scheme'])))
                return false;

            if (! array_key_exists('port', $parts[$name]))
                $parts[$name]['port'] = (strtolower($parts[$name]['scheme']) == 'https') ? 443 : 80;

            if (! array_key_exists('path', $parts[$name]))
                $parts[$name]['path'] = '/';
        }

        // port and scheme must match
        if ($parts['parent']['scheme'] != $parts['child']['scheme'] ||
            $parts['parent']['port'] != $parts['child']['port'])
		return false;

        // compare the hosts by reversing the strings
        $cr_host = mb_strtolower(strrev($parts['child']['host']));
        $pr_host = mb_strtolower(strrev($parts['parent']['host']));

        $break = $this->strDiffAt($cr_host, $pr_host);
        if ($break >= 0 && ($pr_host[$break] != '*' || substr_count($pr_host, '.', 0, $break) < 2)) {
            return false;
        }

        // now compare the paths
        $break = $this->strDiffAt($parts['child']['path'], $parts['parent']['path']);
        @($pb_char = $parts['parent']['path'][$break]);
        if ($break >= 0 && ($break < strlen($parts['parent']['path']) && $pb_char != '*') || ($break > strlen($parts['child']['path']))) {
            return false;
        }

        return true;
    }


    /**
    * Look for the point of differentiation in two strings
    * @param string $a
    * @param string $b
    * @return int
    */
    private function strDiffAt($a, $b) {

        if ($a == $b) return -1;

        $a_len = mb_strlen($a);
        $b_len = mb_strlen($b);

        for ($i = 0; $i < $a_len; $i++) {
            if ($b_len <= $i || $a[$i] != $b[$i]) {
                break;
            }
        }

        if (strlen($b) > strlen($a)) $i++;

        return $i;
    }


    /**
    * Get the requested url
    * @return string url
    */
    private function getReqUrl() {

        $s = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 's' : '';
        $host = $_SERVER['HTTP_HOST'];
        $port = $_SERVER['SERVER_PORT'];
        $path = $_SERVER['REQUEST_URI'];

        if (($s && $port == "443") || (!$s && $port == "80") || preg_match("/:$port\$/", $host)) {
            $p = '';
        } else {
            $p = ':' . $port;
        }

        return "http$s://$host$p$path";
    }


    /**
    * Debug logging
    * @param mixed $x
    * @param string $m
    */
    private function debug($x, $m = null) {

        if (empty($this->profile['debug']) || $this->profile['debug'] === false) return true;

        if (is_array($x)) {
            ob_start();
            print_r($x);
            $x = $m . ($m != null ? "\n" : '') . ob_get_clean();

        } else {
            $x .= "\n";
        }

        error_log($x . "\n", 3, $this->profile['logfile']);
    }


    // ----------------------------------------------------------------------------
    // Maths
    // ----------------------------------------------------------------------------

    /**
    * Random number generation
    * @param integer max
    * @return integer
    */
    private function random($max) {
        if (strlen($max) < 4)
            return mt_rand(1, $max - 1);

        $r = '';
        for($i=1; $i < strlen($max) - 1; $i++)
            $r .= mt_rand(0,9);
        $r .= mt_rand(1,9);

        return $r;
    }


    /**
    * Get a binary value
    * @param integer $n
    * @return string
    * @url http://openidenabled.com Borrowed from PHP-OpenID
    */
    private function bin($n) {
        $bytes = array();
        while (bccomp($n, 0) > 0) {
            array_unshift($bytes, bcmod($n, 256));
            $n = bcdiv($n, bcpow(2,8));
        }

        if ($bytes && ($bytes[0] > 127))
            array_unshift($bytes, 0);

        $b = '';
        foreach ($bytes as $byte)
            $b .= pack('C', $byte);

        return $b;
    }


    /**
    * Turn a binary back into a long
    * @param string $b
    * @return integer
    * @url http://openidenabled.com Borrowed from PHP-OpenID
    */
    private function long($b) {
        $bytes = array_merge(unpack('C*', $b));
        $n = 0;
        foreach ($bytes as $byte) {
            $n = bcmul($n, bcpow(2,8));
            $n = bcadd($n, $byte);
        }
        return $n;
    }


    /**
    * Implement binary x_or
    * @param string $a
    * @param string $b
    * @return string
    */
    private function x_or($a, $b) {

        $r = '';
        for ($i = 0; $i < strlen($b); $i++) {
            $r .= $a[$i] ^ $b[$i];
        }

        $this->debug("Xor size: " . strlen($r));

        return $r;
    }

    // ----------------------------------------------------------------------------
    // Wrap
    // ----------------------------------------------------------------------------

    /**
    * Return a key-value pair in plain text
    * @param array $keys
    */
    private function wrapKv($keys) {

        $this->debug($keys, 'Wrapped key/vals');

        if (headers_sent())
            throw new Exception('wrapKv: Headers already sent');

        header('Content-Type: text/plain; charset=utf-8');
        foreach ($keys as $key => $value) {
            printf("%s:%s\n", $key, $value);
        }

        exit(0);
    }


    /**
    * Return an HTML refresh, with OpenID keys
    * @param string $url
    * @param array $keys
    */
    private function wrapLocation($url, $keys) {

        $keys = $this->appendOpenID($keys);
        $this->debug($keys, 'Location keys');
        $q = mb_strpos($url, '?') ? '&' : '?';

        if (headers_sent())
            throw new Exception('wrapLocation: Headers already sent');

        header('Location: ' . $url . $q . http_build_query($keys));

        $this->debug('Location: ' . $url . $q . http_build_query($keys));

        exit(0);
    }


    /**
    * Return HTML
    * @global string $charset
    * @param string $message
    */
    private function wrapHtml($message) {

        $r = new suxRenderer();
        $r->header .= "<meta name='robots' content='noindex,nofollow' />\n";
        $r->text['message'] = $message;
        $r->bool['analytics'] = false;

        $this->tpl->assign_by_ref('r', $r);
        $output = $this->tpl->assemble('wrap_html.tpl');
        echo $output;
        exit;

    }


    /**
    * Return an HTML refresh
    * @global string $charset
    * @param string $url
    */
    private function wrapRefresh($url) {

        // Template
        $r = new suxRenderer();
        $r->text =& $this->gtext;
        $r->text['url'] = $url;

        $this->tpl->assign_by_ref('r', $r);
        $this->tpl->display('refresh.tpl');

        $this->debug('Refresh: ' . $url);

        exit;
    }


    // ----------------------------------------------------------------------------
    // Errors
    // ----------------------------------------------------------------------------

    /**
    * Return an error message to the consumer
    * @param string $message
    */
    private function errorGet($url, $message = 'Bad Request') {
        $this->wrapLocation($url, array('mode' => 'error', 'error' => $message));
    }


    /**
    * Return an error message to the consumer
    * @param string $message
    */
    private function errorPost($message = 'Bad Request') {

        if (headers_sent())
            throw new Exception('errorPost: Headers already sent');

        header("HTTP/1.1 400 Bad Request");
        echo ('error:' . $message);
        exit(0);

    }


    /**
    * Return an error message to the user
    * @param string $message
    */
    private function error400 ( $message = 'Bad Request' ) {
        header("HTTP/1.1 400 Bad Request");
        $this->wrapHtml($message);
    }


    /**
    * Return an error message to the user
    * @param string $message
    */
    private function error403 ( $message = 'Forbidden' ) {
        header("HTTP/1.1 403 Forbidden");
        $this->wrapHtml($message);
    }


    /**
    * Return an error message to the user
    * @param string $message
    */
    private function error500($message = 'Internal Server Error' ) {
        header("HTTP/1.1 500 Internal Server Error");
        $this->wrapHtml($message);
    }


    // ----------------------------------------------------------------------------
    // Exception Handler
    // ----------------------------------------------------------------------------


    /**
    * @param Exception $e an Exception class
    */
    function logAndDie(Exception $e) {

        if ($this->db && $this->inTransaction) {
            $this->db->rollback();
            $this->inTransaction = false;
        }

        $message = "suxOpenID Error: \n";
        $message .= $e->getMessage() . "\n";
        $message .= "File: " . $e->getFile() . "\n";
        $message .= "Line: " . $e->getLine() . "\n\n";
        $message .= "Backtrace: \n" . print_r($e->getTrace(), true) . "\n\n";
        die("<pre>{$message}</pre>");

    }


}


/*

-- Database

CREATE TABLE `openid_secrets` (
  `id` int(11) NOT NULL auto_increment,
  `expiration` int(11) NOT NULL,
  `shared_secret` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE `openid_trusted` (
  `id` int(11) NOT NULL auto_increment,
  `auth_url` varchar(255) NOT NULL,
  `users_id` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `authorized` (`auth_url`,`users_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


*/


?>