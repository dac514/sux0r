<?php

/**
* suxLog
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class suxLog {

    // Database stuff
    protected $db;
    protected $inTransaction = false;
    protected $db_driver;
    // Tables
    protected $db_table = 'users_log';

    // Object properties, with defaults
    protected $published = true;
    protected $order = array('ts', 'DESC');


    /**
    * Constructor
    */
    function __construct() {

        $this->db = suxDB::get();
        $this->db_driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        set_exception_handler(array($this, 'exceptionHandler'));

    }


    /**
    * Set published property of object
    *
    * @param bool $published
    */
    public function setPublished($published) {

        // Three options:
        // True, False, or Null

        $this->published = $published;
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
    * Return published SQL
    *
    * @return string
    */
    public function sqlPublished() {

        // Published   : private = false
        // Unpublished : private = true
        // Null = SELECT ALL, not sure what the best way to represent this is, id = id?

        // PgSql / MySql
        if ($this->published === true) $query = "private = false ";
        else $query = "id = id "; // select all

        return $query;
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
    * Count log
    *
    * @param int $users_id
    * @return int
    */
    function count($users_id = null) {

        $query = "SELECT COUNT(*) FROM {$this->db_table} ";

        if ($users_id) {
            if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1) throw new Exception('Invalid user id');
            $query .= "WHERE users_id = {$users_id} ";
        }

        // Publish / Draft
        if (is_bool($this->published)) {
            $query .= $users_id ? 'AND ' : 'WHERE ';
            $query .= $this->sqlPublished();
        }

        $st = $this->db->query($query);
        return $st->fetchColumn();

    }


    /**
    * Get log
    *
    * @param int $limit sql limit value
    * @param int $start sql start of limit value
    * @param int $users_id
    * @return array|false
    */
    function get($limit = null, $start = 0, $users_id = null) {

        $query = "SELECT * FROM {$this->db_table} ";

        if ($users_id) {
            if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1) throw new Exception('Invalid user id');
            $query .= "WHERE users_id = {$users_id} ";
        }

        // Publish / Draft
        if (is_bool($this->published)) {
            $query .= $users_id ? 'AND ' : 'WHERE ';
            $query .= $this->sqlPublished();
        }

        $query .= 'ORDER BY ' . $this->sqlOrder();

        // Limit
        if ($start && $limit) $query .= "LIMIT {$limit} OFFSET {$start} ";
        elseif ($limit) $query .= "LIMIT {$limit} ";

        $st = $this->db->query($query);
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }


    /**
    * Write something to the users_log table
    *
    * @param string $body_html
    * @param int $users_id
    * @param int $private
    */
    function write($users_id, $body_html, $private = false) {

        $clean = [];
        // Any user
        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1)
            throw new Exception('Invalid user id');

        $private = $private ? true : false;

        $clean['users_id'] = $users_id;
        $clean['private'] = $private;
        $clean['body_html'] = suxFunct::sanitizeHtml($body_html, -1);

        // Convert and copy body to UTF-8 plaintext
        $converter = new suxHtml2UTF8($clean['body_html']);
        $clean['body_plaintext']  = $converter->getText();

        // Timestamp
        $clean['ts'] = date('Y-m-d H:i:s');

        // INSERT
        $query = suxDB::prepareInsertQuery($this->db_table, $clean);
        $st = $this->db->prepare($query);

        // http://bugs.php.net/bug.php?id=44597
        // As of 5.2.6 you still can't use this function's $input_parameters to
        // pass a boolean to PostgreSQL. To do that, you'll have to call
        // bindParam() with explicit types for *each* parameter in the query.
        // Annoying much? This sucks more than you can imagine.

        if  ($this->db_driver == 'pgsql') {
            $st->bindParam(':users_id', $clean['users_id'], PDO::PARAM_INT);
            $st->bindParam(':private', $clean['private'], PDO::PARAM_BOOL);
            $st->bindParam(':body_html', $clean['body_html'], PDO::PARAM_STR);
            $st->bindParam(':body_plaintext', $clean['body_plaintext'], PDO::PARAM_STR);
            $st->bindParam(':ts', $clean['ts'], PDO::PARAM_STR);
            $st->execute();
        }
        else {
            $st->execute($clean);
        }

    }


    /**
    * Togle the private flag on a log table entry
    *
    * @param int $id users_log id
    * @return int flag (0 or 1)
    */
    function toggleLogPrivateFlag($id) {

        if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1) throw new Exception('Invalid id');

        $query = "SELECT private FROM {$this->db_table} WHERE id = ? ";
        $st = $this->db->prepare($query);
        $st->execute(array($id));

        $flag = true;
        if ($st->fetchColumn()) $flag = false;

        $query = "UPDATE {$this->db_table} SET private = ? WHERE id = ? ";
        $st = $this->db->prepare($query);

        // http://bugs.php.net/bug.php?id=44597
        // As of 5.2.6 you still can't use this function's $input_parameters to
        // pass a boolean to PostgreSQL. To do that, you'll have to call
        // bindParam() with explicit types for *each* parameter in the query.
        // Annoying much? This sucks more than you can imagine.

        if  ($this->db_driver == 'pgsql') {
            $st->bindParam(1, $flag, PDO::PARAM_BOOL);
            $st->bindParam(2, $id, PDO::PARAM_INT);
            $st->execute();
        }
        else {
            $st->execute(array($flag, $id));
        }

        return $flag;

    }


    /**
    * Purge logs
    *
    * @param string $date YYYY-MM-DD
    * @param int $users_id optional users id
    */
    function purge($date, $users_id = null) {

        // This will purge private logs, It will not purge public logs

        if (filter_var($users_id, FILTER_VALIDATE_INT) && $users_id > 0) {
            // With users_id
            $query = "DELETE FROM {$this->db_table} WHERE private = true AND ts < ? AND users_id = ? ";
            $st = $this->db->prepare($query);
            $st->execute(array($date, $users_id));
        }
        else {
            // Without
            $query = "DELETE FROM {$this->db_table} WHERE private = true AND ts < ? ";
            $st = $this->db->prepare($query);
            $st->execute(array($date));
        }

    }




    // ----------------------------------------------------------------------------
    // Exception Handler
    // ----------------------------------------------------------------------------


    /**
    * @param Exception $e an Exception class
    */
    function exceptionHandler(\Throwable $e) {

        if ($this->db && $this->inTransaction) {
            $this->db->rollback();
            $this->inTransaction = false;
        }

        throw($e); // Hot potato!

    }


}

