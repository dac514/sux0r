<?php

/**
* suxUser
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
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @copyright  2008 sux0r development group
* @license    http://www.gnu.org/licenses/agpl.html
*
*/

class suxUser {


    public $max_failures = 4; // Maximum authetication failures allowed

    // Database suff
    protected $db;
    protected $inTransaction = false;
    protected $db_table = 'users';
    protected $db_table_info = 'users_info';
    protected $db_table_openid = 'users_openid';


    /**
    * @param string $key a key from our suxDB DSN
    */
    function __construct($key = null) {

    	$this->db = suxDB::get($key);
        set_exception_handler(array($this, 'logAndDie'));

    }



    function getUser($id = null, $full_profile = false) {

        // This user
        if (!$id) {
            if ($this->loginCheck()) $id = $_SESSION['users_id'];
            else return false;
        }

        // Any user
        if (!filter_var($id, FILTER_VALIDATE_INT)) throw new Exception('Invalid user id');

        $st = $this->db->prepare("SELECT * FROM {$this->db_table} WHERE id = ? ");
        $st->execute(array($id));
        $user = $st->fetch(PDO::FETCH_ASSOC);

        if ($full_profile) {
            $st = $this->db->prepare("SELECT * FROM {$this->db_table_info} WHERE users_id = ? ");
            $st->execute(array($id));
            $tmp = $st->fetch(PDO::FETCH_ASSOC);
            if (is_array($tmp)) {
                unset($tmp['id'], $tmp['users_id']); // Unset ids
                $user = array_merge($user, $tmp); // Merge
            }
        }

        // Rename id key
        $user['users_id'] = $user['id'];
        unset($user['id']);

        return $user;

    }


    function getUserByNickname($nickname, $full_profile = false) {

        $st = $this->db->prepare("SELECT id FROM {$this->db_table} WHERE nickname = ? ");
        $st->execute(array($nickname));
        $id = $st->fetchColumn();

        if (filter_var($id, FILTER_VALIDATE_INT)) return $this->getUser($id, $full_profile);
        else return false;

    }


    function getUserByEmail($email, $full_profile = false) {

        $st = $this->db->prepare("SELECT id FROM {$this->db_table} WHERE email = ? ");
        $st->execute(array($email));
        $id = $st->fetchColumn();

        if (filter_var($id, FILTER_VALIDATE_INT)) return $this->getUser($id, $full_profile);
        else return false;

    }


    function getUsers() {

        $q = "
        SELECT
        {$this->db_table}.id,
        {$this->db_table}.nickname,
        {$this->db_table}.email,
        {$this->db_table_info}.given_name,
        {$this->db_table_info}.family_name,
        {$this->db_table_info}.street_address,
        {$this->db_table_info}.locality,
        {$this->db_table_info}.region,
        {$this->db_table_info}.postcode,
        {$this->db_table_info}.country,
        {$this->db_table_info}.tel,
        {$this->db_table_info}.url,
        {$this->db_table_info}.dob,
        {$this->db_table_info}.gender,
        {$this->db_table_info}.language,
        {$this->db_table_info}.timezone,
        {$this->db_table_info}.pavatar,
        {$this->db_table_info}.microid
        FROM {$this->db_table} LEFT JOIN {$this->db_table_info}
        ON {$this->db_table}.id = {$this->db_table_info}.users_id
        ";

        $st = $this->db->query($q);
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }


    function setUser(array $info, $id = null) {

        // --------------------------------------------------------------------
        // Sanitize
        // --------------------------------------------------------------------

        if ($id != null && !filter_var($id, FILTER_VALIDATE_INT)) throw new Exception('Invalid user id');

        unset($info['id'], $info['users_id']); // Don't allow spoofing of the id in the array
        unset($info['accesslevel']); // Don't allow accesslevel changes with this function

        // Encrypt the password
        if (!empty($info['password'])) {
            if (empty($info['nickname'])) throw new Exception('No nickname provided');
            $info['password'] = $this->encryptPw($info['nickname'], $info['password']);
        }

        // Move users table info to $user array
        $user = array();

        // Nickname
        if (!empty($info['nickname'])) $user['nickname'] = strip_tags($info['nickname']);
        unset($info['nickname']);

        // Email
        if (!empty($info['email'])) $user['email'] = filter_var($info['email'], FILTER_SANITIZE_EMAIL);
        unset($info['email']);

        // Encrypted password
        if (!empty($info['password'])) $user['password'] = $info['password'];
        unset($info['password']);

        // The rest
        foreach ($info as $key => $val) {
            if ($key == 'url') $info[$key] = filter_var($val, FILTER_SANITIZE_URL);
            else $info[$key] = strip_tags($val);
        }


        // --------------------------------------------------------------------
        // Go!
        // --------------------------------------------------------------------

        // Begin transaction
        $this->db->beginTransaction();
        $this->inTransaction = true;

        if (!$id) {

            // Insert user

            $query = suxDB::prepareInsertQuery($this->db_table, $user);
            $st = $this->db->prepare($query);
            $st->execute($user);

            $id = $this->db->lastInsertId();
            $info['users_id'] = $id;

            $query = suxDB::prepareInsertQuery($this->db_table_info, $info);
            $st = $this->db->prepare($query);
            $st->execute($info);

        }
        else {

            // Update user

            $query = suxDB::prepareUpdateQuery($this->db_table, $user);
            $st = $this->db->prepare($query);
            $st->execute($user);

            $info['users_id'] = $id;

            $query = suxDB::prepareUpdateQuery($this->db_table_info, $info, 'users_id');
            $st = $this->db->prepare($query);
            $st->execute($info);

        }


        // Commit
        $this->db->commit();
        $this->inTransaction = false;

        return true;

    }


    // -----------------------------------------------------------------------
    // Open ID
    // -----------------------------------------------------------------------


    function getUserByOpenID($openid_url, $full_profile = false) {

        // TODO: Improve SANITIZE_URL to canonicalized form for robust lookup
        // (i.e. so if users enter their OpenID slightly differently, we can still map it to their account).
        $openid_url = filter_var($openid_url, FILTER_SANITIZE_URL);

        $st = $this->db->prepare("SELECT users_id FROM {$this->db_table_openid} WHERE openid_url = ? ");
        $st->execute(array($openid_url));
        $id = $st->fetchColumn();

        if (filter_var($id, FILTER_VALIDATE_INT)) return $this->getUser($id, $full_profile);
        else return false;

    }


    function getOpenIDs($id = null) {

        // This user
        if (!$id) {
            if ($this->loginCheck()) $id = $_SESSION['users_id'];
            else return false;
        }

        // Any user
        if (!filter_var($id, FILTER_VALIDATE_INT)) throw new Exception('Invalid user id');

        // Get the Ids
        $st = $this->db->prepare("SELECT id, openid_url FROM {$this->db_table_openid} WHERE users_id = ? ");
        $st->execute(array($id));
        $openids = $st->fetchAll(PDO::FETCH_ASSOC);

        return $openids;

    }


    function attachOpenID($openid_url, $id = null) {

        // This user
        if (!$id) {
            if ($this->loginCheck()) $id = $_SESSION['users_id'];
            else return false;
        }

        // Any user
        if (!filter_var($id, FILTER_VALIDATE_INT)) throw new Exception('Invalid user id');

        // TODO: Improve SANITIZE_URL to canonicalized form for robust lookup
        // (i.e. so if users enter their OpenID slightly differently, we can still map it to their account).
        $openid_url = filter_var($openid_url, FILTER_SANITIZE_URL);

        // Sql
        $oid = array(
            'users_id' => $id,
            'openid_url' => $openid_url,
            );

        $query = suxDB::prepareCountQuery($this->db_table_openid, $oid) . 'LIMIT 1 ';
        $st = $this->db->prepare($query);
        $st->execute($oid);

        if (!$st->fetchColumn()) {
            // Insert
            $query = suxDB::prepareInsertQuery($this->db_table_openid, $oid);
            $st = $this->db->prepare($query);
            $st->execute($oid);
        }


    }


    function detachOpenID($openid_url, $id = null) {

        // This user
        if (!$id) {
            if ($this->loginCheck()) $id = $_SESSION['users_id'];
            else return false;
        }

        // Any user
        if (!filter_var($id, FILTER_VALIDATE_INT)) throw new Exception('Invalid user id');

        // TODO: Improve SANITIZE_URL to canonicalized form for robust lookup
        // (i.e. so if users enter their OpenID slightly differently, we can still map it to their account).
        $openid_url = filter_var($openid_url, FILTER_SANITIZE_URL);

        $query = "DELETE FROM {$this->db_table_openid} WHERE openid_url = ? AND users_id = ? ";
        $st = $this->db->prepare($query);
        $st->execute(array($openid_url, $id));

    }


    // -----------------------------------------------------------------------
    // Security
    // -----------------------------------------------------------------------


    /**
    * Check if a user is logged in
    *
    * @param string $redirect a URL to rediect to if the security check fails
    * @return bool
    */
    function loginCheck($redirect = null) {

        $proceed = false;

        if (!empty($_SESSION['users_id']) &&  !empty($_SESSION['nickname']) && !empty($_SESSION['token'])) {
            if ($this->tokenCheck($_SESSION['users_id'], $_SESSION['token'])) {
                $proceed = true;
            }
        }

        if (!$proceed && $redirect) {
            suxFunct::killSession();
            suxFunct::redirect($redirect);
            exit;
        }

        return $proceed;

    }


    /**
    * Perform a login using Digest Access Authentication
    *
    * @param string $auth_domain, the domain value for WWW-Authenticate
    * @return bool
    */
    function authenticate() {

        // Try to get the digest headers
        if (function_exists('apache_request_headers') && ini_get('safe_mode') == false) {
            $arh = apache_request_headers();
            $hdr = (isset($arh['Authorization']) ? $arh['Authorization'] : null);

        }
        elseif (isset($_SERVER['PHP_AUTH_DIGEST'])) {
            $hdr = $_SERVER['PHP_AUTH_DIGEST'];

        }
        elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $hdr = $_SERVER['HTTP_AUTHORIZATION'];

        }
        elseif (isset($_ENV['PHP_AUTH_DIGEST'])) {
            $hdr = $_ENV['PHP_AUTH_DIGEST'];

        }
        elseif (isset($_REQUEST['auth'])) {
            $hdr = stripslashes(urldecode($_REQUEST['auth']));

        }
        else {
            $hdr = null;
        }

        $digest = mb_substr($hdr,0,7) == 'Digest '
		? mb_substr($hdr, mb_strpos($hdr, ' ') + 1)
		: $hdr;

        $stale = false;
        $ok = '';

        // is the user trying to log in?
        if (!is_null($digest) && $this->loginCheck() === false) {

            $hdr = array();

            // decode the Digest authorization headers
            $mtx = array();
            preg_match_all('/(\w+)=(?:"([^"]+)"|([^\s,]+))/', $digest, $mtx, PREG_SET_ORDER);

            foreach ($mtx as $m)
                $hdr[$m[1]] = $m[2] ? $m[2] : $m[3];


            if (isset($_SESSION['uniqid']) && $hdr['nonce'] != $_SESSION['uniqid']) {
                $stale = true;
                unset($_SESSION['uniqid']);
            }

            if (!isset($_SESSION['failures'])) $_SESSION['failures'] = 0;

            $auth_user = $this->getUserByNickname($hdr['username']);
            if ($auth_user && !empty($auth_user['password']) && !$stale) {

                // the entity body should always be null in this case
                $entity_body = '';
                $a1 = mb_strtolower($auth_user['password']);
                $a2 = $hdr['qop'] == 'auth-int'
				? md5(implode(':', array($_SERVER['REQUEST_METHOD'], $hdr['uri'], md5($entity_body))))
				: md5(implode(':', array($_SERVER['REQUEST_METHOD'], $hdr['uri'])));
                $ok = md5(implode(':', array($a1, $hdr['nonce'], $hdr['nc'], $hdr['cnonce'], $hdr['qop'], $a2)));

                if ($hdr['response'] == $ok) {
                    // successful login!
                    unset($_SESSION['uniqid'], $_SESSION['failures']);
                    $this->setSession($hdr['username'], true);
                    return true;
                }

            }

            // Password problems, boot this user
            if (strcmp($hdr['nc'], 4) > 0 || $_SESSION['failures'] > $this->max_failures) {
                // too many failures
                return false;
            }

            // Log failed login
            $_SESSION['failures']++;

        }

        // if we get this far the user is not authorized, so send the headers
        $uid = uniqid(mt_rand(1,9));
        $_SESSION['uniqid'] = $uid;

        if (headers_sent())
            throw new Exception('Headers already sent');

        header('HTTP/1.0 401 Unauthorized');
        header(sprintf('WWW-Authenticate: Digest qop="auth-int, auth", realm="%s", domain="%s", nonce="%s", opaque="%s", stale="%s", algorithm="MD5"', $GLOBALS['CONFIG']['REALM'], $GLOBALS['CONFIG']['URL'] . '/', $uid, md5($GLOBALS['CONFIG']['REALM']), $stale ? 'true' : 'false'));

        return false;

    }


    /**
    * Check if a token is valid
    *
    * @param int $id user id
    * @param string $id token
    * @return bool
    */
    private function tokenCheck($id, $token) {

        if (!filter_var($id, FILTER_VALIDATE_INT)) return false;

        $st = $this->db->prepare("SELECT password FROM {$this->db_table} WHERE id = ? ");
        $st->execute(array($id));
        $row = $st->fetch();

        if (empty($row['password'])) {
            // TODO, No password because this user is Open ID Enabled?
            return false;
        }
        elseif ($token != md5(date('W') . $row['password'] . @$GLOBALS['CONFIG']['SALT'])) {
            return false;
        }

        return true;

    }


    private function setSession($id, $nickname = false) {

        if ($nickname) $user = $this->getUserByNickname($id);
        else $user = $this->getUser($id);

        session_regenerate_id();
        $_SESSION['users_id'] = $user['users_id'];
        $_SESSION['nickname'] = $user['nickname'];
        $_SESSION['token'] = md5(date('W') . $user['password'] . @$GLOBALS['CONFIG']['SALT']);

    }


    /**
    * Perform one-way encryption of a password
    *
    * @param string the username
    * @param string the password to encrypt
    * @return string
    */
    private function encryptPw($nickname, $password) {

        if (!isset($GLOBALS['CONFIG']['REALM'])) {
            die("Something is wrong, can't encrypt password without realm.");
        }
        return md5("{$nickname}:{$GLOBALS['CONFIG']['REALM']}:{$password}");

    }


    /**
    * Generate a random password
    *
    * @return string
    */
    private function generatePw() {

        $new_pw = '';
        for ($i = 0; $i < 10; $i++) {
            $new_pw .= chr(mt_rand(33, 126));
        }
        return $new_pw;

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

        $message = "suxUser Error: \n";
        $message .= $e->getMessage() . "\n";
        $message .= "File: " . $e->getFile() . "\n";
        $message .= "Line: " . $e->getLine() . "\n\n";
        $message .= "Backtrace: \n" . print_r($e->getTrace(), true) . "\n\n";
        die("<pre>{$message}</pre>");

    }


}

/*

-- Database

CREATE TABLE `users` (
  `id` int(11) NOT NULL auto_increment,
  `nickname` varchar(64) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `accesslevel` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `nickname` (`nickname`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE `users_info` (
  `id` int(11) NOT NULL auto_increment,
  `users_id` int(11) NOT NULL,
  `given_name` varchar(255) default NULL,
  `family_name` varchar(255) default NULL,
  `street_address` varchar(255) default NULL,
  `locality` varchar(255) default NULL,
  `region` varchar(255) default NULL,
  `postcode` varchar(255) default NULL,
  `country` char(2) default NULL,
  `tel` varchar(255) default NULL,
  `url` varchar(255) default NULL,
  `dob` date default NULL,
  `gender` char(1) default NULL,
  `language` char(2) default NULL,
  `timezone` varchar(255) default NULL,
  `pavatar` varchar(255) default NULL,
  `microid` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `users_id` (`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE `users_openid` (
  `id` int(11) NOT NULL auto_increment,
  `openid_url` varchar(255) NOT NULL,
  `users_id` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `openid_url` (`openid_url`),
  KEY `users_id` (`users_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

*/

?>