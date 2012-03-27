<?php

/**
* suxUser
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class suxUser {

    // Database stuff
    protected $db;
    protected $inTransaction = false;
    protected $db_driver;
    // Tables
    protected $db_table = 'users';
    protected $db_table_info = 'users_info';
    protected $db_table_openid = 'users_openid';
    protected $db_table_access = 'users_access';
    protected $db_table_log = 'users_log';

    // Maximum authetication failures allowed
    private $max_failures = 4;
    // If you change these, then you need to adjust your database columns
    private $max_access = 999;
    private $max_module_length = 32;

    // Object properties, with defaults
    protected $order = array('root DESC, nickname', 'ASC');


    /**
    * Constructor
    */
    function __construct() {

        $this->db = suxDB::get();
        $this->db_driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        set_exception_handler(array($this, 'exceptionHandler'));

    }


    /**
    * Set order property of object
    *
    * @param string $col
    * @param string $way
    */
    public function setOrder($col, $way = 'ASC') {

        if (!preg_match('/^[A-Za-z0-9_,\s]+$/', $col)) throw new Exception('Invalid column(s)');
        $way = (mb_strtolower($way) == 'asc') ? 'ASC' : 'DESC';

        $arr = array($col, $way);
        $this->order = $arr;

    }


    /**
    * Return order SQL
    *
    * @return string
    */
    public function sqlOrder() {
        // PgSql / MySql
        $query = "{$this->order[0]} {$this->order[1]} ";
        return $query;
    }


    /**
    * Get user
    *
    * @param int $users_id users_id
    * @param bool $full_profile the entire profile?
    * @return array|false
    */
    function getByID($users_id, $full_profile = false) {

        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1) throw new Exception('Invalid user id');

        $st = $this->db->prepare("SELECT * FROM {$this->db_table} WHERE id = ? ");
        $st->execute(array($users_id));
        $user = $st->fetch(PDO::FETCH_ASSOC);

        if (!$user) return false; // User doesn't exist?

        if ($full_profile) {
            $st = $this->db->prepare("SELECT * FROM {$this->db_table_info} WHERE users_id = ? ");
            $st->execute(array($users_id));
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


    /**
    * Get a user by nickname
    *
    * @param string $nickname nickname
    * @param bool $full_profile the entire profile?
    * @return array|false
    */
    function getByNickname($nickname, $full_profile = false) {

        $st = $this->db->prepare("SELECT id FROM {$this->db_table} WHERE nickname = ? ");
        $st->execute(array($nickname));
        $id = $st->fetchColumn();

        if (filter_var($id, FILTER_VALIDATE_INT)) return $this->getByID($id, $full_profile);
        else return false;

    }


    /**
    * Get a user by email
    *
    * @param string $email email
    * @param bool $full_profile the entire profile?
    * @return array|false
    */
    function getByEmail($email, $full_profile = false) {

        $st = $this->db->prepare("SELECT id FROM {$this->db_table} WHERE email = ? ");
        $st->execute(array($email));
        $id = $st->fetchColumn();

        if (filter_var($id, FILTER_VALIDATE_INT)) return $this->getByID($id, $full_profile);
        else return false;

    }


    /**
    * Count users
    *
    * @return int
    */
    function count() {

        $query = "SELECT COUNT(*) FROM {$this->db_table} ";

        $st = $this->db->query($query);
        return $st->fetchColumn();

    }


    /**
    * Get users
    *
    * @param int $limit sql limit value
    * @param int $start sql start of limit value
    * @return array|false
    */
    function get($limit = null, $start = 0) {

        $query = "
        SELECT
        {$this->db_table}.id as users_id,
        {$this->db_table}.nickname,
        {$this->db_table}.email,
        {$this->db_table}.root,
        {$this->db_table}.banned,
        MAX({$this->db_table_log}.ts) AS last_active
        FROM {$this->db_table}
        LEFT JOIN {$this->db_table_log} ON {$this->db_table}.id = {$this->db_table_log}.users_id
        GROUP BY {$this->db_table}.id, {$this->db_table}.nickname, {$this->db_table}.email, {$this->db_table}.root, {$this->db_table}.banned
        ";

        // Order
        $query .= 'ORDER BY ' . $this->sqlOrder();

        // Limit
        if ($start && $limit) $query .= "LIMIT {$limit} OFFSET {$start} ";
        elseif ($limit) $query .= "LIMIT {$limit} ";

        $st = $this->db->query($query);
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }



    /**
    * Save user
    *
    * @param int $users_id users_id
    * @param array $info keys match SQL table columns of users and users_info
    * @return int users_id
    */
    function save($users_id, array $info) {

        /* If users_id is provided, saveUser() will update an existing user.
        Otherwise it will insert a new one */

        // --------------------------------------------------------------------
        // Sanitize
        // --------------------------------------------------------------------

        if ($users_id != null && (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1))
            throw new Exception('Invalid user id');

        if (!empty($info['nickname'])) {
            $tmp = $this->getByNickname($info['nickname']);
            if ($tmp['users_id'] != $users_id) throw new Exception('Duplicate nickname');
        }

        if (!empty($info['email'])) {
            $tmp = $this->getByEmail($info['email']);
            if ($tmp && $tmp['users_id'] != $users_id) throw new Exception('Duplicate email');
        }

        unset($info['id'], $info['users_id']); // Don't allow spoofing of the id in the array
        unset($info['root']); // Don't allow root changes with this function
        unset($info['banned']); // Don't allow banned changes with this function
        unset($info['image']); // Don't allow image changes with this function


        // Encrypt the password
        if (!empty($info['password'])) {
            if (empty($info['nickname'])) throw new Exception('No nickname provided');
            $info['password'] = $this->encryptPw($info['nickname'], $info['password']);
        }

        // Move users table info to $user array
        $user = array();

        // Nickname
        if (mb_strtolower($info['nickname']) == 'nobody') throw new Exception('"nobody" is a reservered word');
        if (!empty($info['nickname'])) $user['nickname'] = strip_tags($info['nickname']);
        unset($info['nickname']);

        // Email
        if (!empty($info['email'])) $user['email'] = filter_var($info['email'], FILTER_SANITIZE_EMAIL);
        unset($info['email']);

        // Encrypted password
        if (!empty($info['password'])) $user['password'] = $info['password'];
        unset($info['password']);

        // Move openid_url to variable
        $openid_url = null;
        if (!empty($info['openid_url'])) $openid_url = filter_var($info['openid_url'], FILTER_SANITIZE_URL);
        unset($info['openid_url']);

        // The rest
        foreach ($info as $key => $val) {
            if ($key == 'url') $info[$key] = filter_var($val, FILTER_SANITIZE_URL);
            else $info[$key] = strip_tags($val);
        }

        // Date of birth
        if (empty($info['dob'])) $info['dob'] = null;


        // We now have two arrays, $user[] and $info[]

        // --------------------------------------------------------------------
        // Go!
        // --------------------------------------------------------------------


        // Begin transaction
        $tid = suxDB::requestTransaction();
        $this->inTransaction = true;

        if ($users_id) {

            // UPDATE
            $user['id'] = $users_id;
            $query = suxDB::prepareUpdateQuery($this->db_table, $user);
            $st = $this->db->prepare($query);
            $st->execute($user);

            $info['users_id'] = $users_id;

            $query = suxDB::prepareUpdateQuery($this->db_table_info, $info, 'users_id');
            $st = $this->db->prepare($query);
            $res = $st->execute($info);

        }
        else {

            // INSERT
            $query = suxDB::prepareInsertQuery($this->db_table, $user);
            $st = $this->db->prepare($query);
            $st->execute($user);

            if ($this->db_driver == 'pgsql') $users_id = $this->db->lastInsertId("{$this->db_table}_id_seq"); // PgSql
            else $users_id = $this->db->lastInsertId();

            $info['users_id'] = $users_id;

            $query = suxDB::prepareInsertQuery($this->db_table_info, $info);
            $st = $this->db->prepare($query);
            $st->execute($info);

        }

        if ($openid_url) $this->attachOpenID($openid_url, $users_id);

        // Commit
        suxDB::commitTransaction($tid);
        $this->inTransaction = false;

        return $users_id;

    }


    /**
    * Get user image
    *
    * @param int $users_id users_id
    * @return string image name
    */
    function getImage($users_id = null) {

        // This user
        if (!$users_id) {
            if (!empty($_SESSION['users_id'])) $users_id = $_SESSION['users_id'];
            else return false;
        }

        // Any user
        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1)
            throw new Exception('Invalid user id');

        $st = $this->db->prepare("SELECT image FROM {$this->db_table_info} WHERE users_id = ? ");
        $st->execute(array($users_id));
        return $st->fetchColumn();

    }


    /**
    * Save user image
    *
    * @param int $users_id users_id
    * @param string $image
    */
    function saveImage($users_id, $image) {

        if (!$users_id) return false;

        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1)
            throw new Exception('Invalid user id');

        $st = $this->db->prepare("UPDATE {$this->db_table_info} SET image = ? WHERE users_id = ? ");
        $st->execute(array($image, $users_id));

    }




    // -----------------------------------------------------------------------
    // User access
    // -----------------------------------------------------------------------

    /**
    * Check if a user is banned
    *
    * @param int $users_id
    * @return bool
    */
    function isBanned($users_id = null) {

        // This user
        if (!$users_id) {
            if (!empty($_SESSION['users_id'])) $users_id = $_SESSION['users_id'];
            else return false;
        }

        // Any user
        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1)
            throw new Exception('Invalid user id');

        $st = $this->db->prepare("SELECT banned FROM {$this->db_table} WHERE id = ? ");
        $st->execute(array($users_id));
        $banned = $st->fetchColumn();

        if ($banned) return true;
        else return false;

    }


    /**
    * Ban a user
    *
    * @param int $users_id
    * @return bool
    */
    function ban($users_id) {

        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1) throw new Exception('Invalid user id');

        $st = $this->db->prepare("UPDATE {$this->db_table} SET banned = true WHERE id = ? ");
        $st->execute(array($users_id));

    }


    /**
    * Unban a user
    *
    * @param int $users_id
    * @return bool
    */
    function unban($users_id) {

        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1) throw new Exception('Invalid user id');

        $st = $this->db->prepare("UPDATE {$this->db_table} SET banned = false WHERE id = ? ");
        $st->execute(array($users_id));

    }



    /**
    * Check if a user has root access
    *
    * @param int $users_id
    * @return bool
    */
    function isRoot($users_id = null) {

        // This user
        if (!$users_id) {
            if (!empty($_SESSION['users_id'])) $users_id = $_SESSION['users_id'];
            else return false;
        }

        // Any user
        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1)
            throw new Exception('Invalid user id');

        $st = $this->db->prepare("SELECT root FROM {$this->db_table} WHERE id = ? ");
        $st->execute(array($users_id));
        $root = $st->fetchColumn();

        if ($root) return true;
        else return false;

    }


    /**
    * Root a user
    *
    * @param int $users_id
    * @return bool
    */
    function root($users_id) {

        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1) throw new Exception('Invalid user id');

        $st = $this->db->prepare("UPDATE {$this->db_table} SET root = true WHERE id = ? ");
        $st->execute(array($users_id));

    }


    /**
    * Unroot a user
    *
    * @param int $users_id
    * @return bool
    */
    function unroot($users_id) {

        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1) throw new Exception('Invalid user id');

        $st = $this->db->prepare("UPDATE {$this->db_table} SET root = false WHERE id = ? ");
        $st->execute(array($users_id));

    }



    /**
    * Check access level for a given module
    *
    * @param string $module
    * @param int $users_id
    * @return int
    */
    function getAccess($module, $users_id = null) {

        // This user
        if (!$users_id) {
            if (!empty($_SESSION['users_id'])) $users_id = $_SESSION['users_id'];
            else return false;
        }

        // Any user
        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1)
            throw new Exception('Invalid user id');

        $q = "
        SELECT {$this->db_table_access}.accesslevel FROM {$this->db_table_access}
        INNER JOIN {$this->db_table} ON {$this->db_table_access}.users_id = {$this->db_table}.id
        WHERE {$this->db_table_access}.users_id = ? AND {$this->db_table_access}.module = ?
        ";

        $st = $this->db->prepare($q);
        $st->execute(array($users_id, $module));
        return $st->fetchColumn();

    }


    /**
    * Save a user's access level
    *
    * @param int $users_id
    * @param string $module
    * @param int $access
    */
    function saveAccess($users_id, $module, $access) {

        if (mb_strlen($module) > $this->max_module_length) throw new Exception('Module name too long');
        if (!filter_var($access, FILTER_VALIDATE_INT) || $access < 0 || $access > $this->max_access) throw new Exception('Invalid access integer');

        if (!$users_id) return false;

        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1)
            throw new Exception('Invalid user id');

        $clean['users_id'] = $users_id;
        $clean['module'] = $module;
        $clean['accesslevel'] = $access;

        $query = "SELECT id FROM {$this->db_table_access} WHERE users_id = ? AND module = ? ";
        $st = $this->db->prepare($query);
        $st->execute(array($clean['users_id'], $clean['module']));
        $edit = $st->fetch(PDO::FETCH_ASSOC);

        if ($edit['id']) {

            // UPDATE
            $clean['id'] = $edit['id'];
            $query = suxDB::prepareUpdateQuery($this->db_table_access, $clean);
            $st = $this->db->prepare($query);
            $st->execute($clean);

        }
        else {

            // INSERT
            $query = suxDB::prepareInsertQuery($this->db_table_access, $clean);
            $st = $this->db->prepare($query);
            $st->execute($clean);

        }


    }


    /**
    * Remove a user's access level
    *
    * @param string $module
    * @param int $users_id
    */
    function removeAccess($module, $users_id = null) {

        if (mb_strlen($module) > $this->max_module_length) throw new Exception('Module name too long');

        // This user
        if (!$users_id) {
            if (!empty($_SESSION['users_id'])) $users_id = $_SESSION['users_id'];
            else return false;
        }

        // Any user
        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1)
            throw new Exception('Invalid user id');

        $clean['users_id'] = $users_id;
        $clean['module'] = $module;

        $query = "DELETE FROM {$this->db_table_access} WHERE users_id = ? AND module = ? ";
        $st = $this->db->prepare($query);

        $st->execute(array($clean['users_id'], $clean['module']));

    }


    /**
    * Too many password failures?
    *
    * @return bool
    */
    function maxPasswordFailures() {

        if (isset($_SESSION['failures']) && $_SESSION['failures'] > $this->max_failures) return true;
        else return false;

    }



    // -----------------------------------------------------------------------
    // Open ID
    // -----------------------------------------------------------------------


    /**
    * Get a user by openid
    *
    * @param string $openid_url url
    * @param bool $full_profile the entire profile?
    * @return array|false
    */
    function getUserByOpenID($openid_url, $full_profile = false) {

        // Canonicalize url
        $openid_url = suxFunct::canonicalizeUrl($openid_url);

        $st = $this->db->prepare("SELECT users_id FROM {$this->db_table_openid} WHERE openid_url = ? ");
        $st->execute(array($openid_url));
        $id = $st->fetchColumn();

        if (filter_var($id, FILTER_VALIDATE_INT)) return $this->getByID($id, $full_profile);
        else return false;

    }


    /**
    * Get a list of openids by users_id
    *
    * @param int $users_id users_id
    * @return array
    */
    function getOpenIDs($users_id = null) {

        // This user
        if (!$users_id) {
            if (!empty($_SESSION['users_id'])) $users_id = $_SESSION['users_id'];
            else return false;
        }

        // Any user
        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1)
            throw new Exception('Invalid user id');

        // Get the Ids
        $st = $this->db->prepare("SELECT id, openid_url FROM {$this->db_table_openid} WHERE users_id = ? ");
        $st->execute(array($users_id));
        $openids = $st->fetchAll(PDO::FETCH_ASSOC);

        return $openids;

    }


    /**
    * Attach an openid to a user
    *
    * @param string $openid_url url
    * @param int $users_id users_id
    */
    function attachOpenID($openid_url, $users_id = null) {

        // This user
        if (!$users_id) {
            if (!empty($_SESSION['users_id'])) $users_id = $_SESSION['users_id'];
            else return false;
        }

        // Any user
        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1)
            throw new Exception('Invalid user id');

        // Canonicalize url
        $openid_url = suxFunct::canonicalizeUrl($openid_url);

        // Sql
        $oid = array(
            'users_id' => $users_id,
            'openid_url' => $openid_url,
            );

        $query = suxDB::prepareCountQuery($this->db_table_openid, $oid);
        $st = $this->db->prepare($query);
        $st->execute($oid);

        if (!$st->fetchColumn()) {
            // Insert
            $query = suxDB::prepareInsertQuery($this->db_table_openid, $oid);
            $st = $this->db->prepare($query);
            $st->execute($oid);
        }


    }


    /**
    * Detach an openid from system
    *
    * @param string $openid_url url
    */
    function detachOpenID($openid_url) {

        // Canonicalize url
        $openid_url = suxFunct::canonicalizeUrl($openid_url);

        $query = "DELETE FROM {$this->db_table_openid} WHERE openid_url = ? ";
        $st = $this->db->prepare($query);
        $st->execute(array($openid_url));

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

        if (!empty($_SESSION['users_id']) && !empty($_SESSION['nickname']) && !empty($_SESSION['token'])) {
            if ($this->tokenCheck($_SESSION['users_id'], $_SESSION['token'])) {
                $proceed = true;
            }
        }

        // Conditionally redirect
        if (!$proceed && $redirect) {
            self::killSession();
            suxFunct::redirect($redirect);
        }

        return $proceed;

    }


    /**
    * Perform a login using Digest Access Authentication
    *
    * Forked From / Inspired by:
    * CJ Niemira: http://siege.org/projects/phpMyID/
    *
    * @global string $CONFIG['REALM']
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

        // Double check
        if (!$hdr) $hdr = null;

        $digest = (mb_substr($hdr,0,7) == 'Digest ') ? mb_substr($hdr, mb_strpos($hdr, ' ') + 1) : $hdr;
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


            if (isset($_SESSION['uniqid']) && ($hdr['nonce'] != $_SESSION['uniqid'] || $_SERVER['REQUEST_TIME'] - hexdec(substr($hdr['nonce'], 0, 8)) > 300)) {
                $stale = true;
                unset($_SESSION['uniqid']);
            }

            if (!isset($_SESSION['failures'])) $_SESSION['failures'] = 0;

            $auth_user = $this->getByNickname($hdr['username']);
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
                    $u = $this->getByNickname($hdr['username']);
                    $this->setSession($u['users_id']);
                    return true;
                }

            }

            // Password problems, boot this user
            if (strcmp(hexdec($hdr['nc']), 4) > 0 || $_SESSION['failures'] > $this->max_failures) {
                // too many failures
                return false;
            }

            // Log failed login
            $_SESSION['failures']++;

        }

        // if we get this far the user is not authorized, so send the headers
        $uid = sprintf("%08x", time()) . uniqid(mt_rand(1,9));
        $_SESSION['uniqid'] = $uid;

        if (headers_sent()) {
            throw new Exception('Headers already sent');
        }

        header('HTTP/1.0 401 Unauthorized');
        header(sprintf(
            'WWW-Authenticate: Digest qop="auth-int, auth", realm="%s", domain="%s", nonce="%s", opaque="%s", stale="%s", algorithm="MD5"',
            $GLOBALS['CONFIG']['REALM'],
            $GLOBALS['CONFIG']['URL'] . '/',
            $uid,
            md5($GLOBALS['CONFIG']['REALM']),
            $stale ? 'true' : 'false'
            ));
        flush();

        return false;

    }


    /**
    * Set a user session
    *
    * @global string $CONFIG['SALT']
    * @param string $users_id
    */
    function setSession($users_id) {

        $user = $this->getByID($users_id, true);

        if (!$user) {
            self::killSession();
            return false;
        }

        @session_regenerate_id();

        $_SESSION['users_id'] = $user['users_id'];
        $_SESSION['nickname'] = $user['nickname'];
        $_SESSION['token'] = md5(date('W') . $user['password'] . @$GLOBALS['CONFIG']['SALT']);
        $_SESSION['language'] = $user['language'];

    }


    /**
    * Kill $_SESSION
    */
    static function killSession() {

        // Keep breadcrumbs
        $tmp = array();
        if (isset($_SESSION['breadcrumbs'])) $tmp = $_SESSION['breadcrumbs'];

        $_SESSION = array();
        session_destroy();

        @session_start();
        $_SESSION['breadcrumbs'] = $tmp;

    }


    /**
    * Generate a random password
    *
    * @return string
    */
    function generatePw() {

        $new_pw = '';
        for ($i = 0; $i < 10; $i++) {
            $new_pw .= chr(mt_rand(33, 126));
        }
        return $new_pw;

    }


    /**
    * Perform one-way encryption of a password
    *
    * @global string $CONFIG['REALM']
    * @param string the username
    * @param string the password to encrypt
    * @return string
    */
    function encryptPw($nickname, $password) {

        if (!isset($GLOBALS['CONFIG']['REALM'])) {
            die("Something is wrong, can't encrypt password without realm.");
        }
        return md5("{$nickname}:{$GLOBALS['CONFIG']['REALM']}:{$password}");

    }


    /**
    * Check if a token is valid
    *
    * @global string $CONFIG['SALT']
    * @param int $users_id user id
    * @param string $token token
    * @return bool
    */
    private function tokenCheck($users_id, $token) {

        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1) return false;

        $st = $this->db->prepare("SELECT banned, password FROM {$this->db_table} WHERE id = ? ");
        $st->execute(array($users_id));
        $row = $st->fetch();

        // Forecfully redirect a banned user
        if ($row['banned']) {
            self::killSession();
            suxFunct::redirect(suxFunct::makeUrl('/banned'));
        }

        if (empty($row['password'])) {
            return false;
        }
        elseif ($token != md5(date('W') . $row['password'] . @$GLOBALS['CONFIG']['SALT'])) {
            return false;
        }

        return true;

    }


    // ----------------------------------------------------------------------------
    // Exception Handler
    // ----------------------------------------------------------------------------


    /**
    * @param Exception $e an Exception class
    */
    function exceptionHandler(Exception $e) {

        if ($this->db && $this->inTransaction) {
            $this->db->rollback();
            $this->inTransaction = false;
        }

        throw($e); // Hot potato!

    }


}

?>