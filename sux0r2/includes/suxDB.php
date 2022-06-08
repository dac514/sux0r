<?php

/**
* suxDB
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class suxDB {

    /*
    * Access a PDO database connection from anywhere in the program without
    * worrying about variable scope. Prevent more than one connection from
    * being created. Manage connections to multiple database servers.
    *
    * Example:
    * suxDB::$dsn = array(
    *   'sux0r' => array('mysql:host=localhost', 'user', 'password'),
    *   'stats' => array('oci:statistics', 'user', 'password'),
    *   'dev' => 'sqlite:/tmp/blogs.db',
    * );
    * $db = suxDB::get(); // Defaults to first item in DSN array, i.e. sux0r
    * $db2 = suxDB::get('stats');
    */

    // Sux0r is theoretically able to span multiple databases but in practice
    // LEFT JOIN and INNER JOIN queries accross multiple tables make this very
    // difficult to manage. The option is here for future developement.

    public static $dsn = array();
    private static $supported = array('mysql', 'pgsql');
    private static $db = array();
    private static $transaction = array();


    // Static class, no cloning or instantiating allowed
    final private function __construct() { }
    private function __clone() { }

    // ------------------------------------------------------------------------
    // Static Functions
    // ------------------------------------------------------------------------

    /**
    * Get a PDO database connection
    *
    * @param string $key PDO dsn key
    */
    static function get($key = null) {

        if (!$key) {
            // Assume we want the first key from the DSN
            $key = array_keys(self::$dsn);
            $key = array_shift($key);
        }

        if (!isset(self::$dsn[$key])) throw new Exception("Unknown DSN: $key");

        // Connect if not already connected
        if (!isset(self::$db[$key])) {

            try {

                // Figure out what kind of PDO DSN this is supposed to be
                if (is_array(self::$dsn[$key])) {
                    // Call appropriate PDO constructor and provide array arguments
                    $c = new ReflectionClass('PDO');
                    self::$db[$key] = $c->newInstanceArgs(self::$dsn[$key]);
                }
                else {
                    // Call PDO with a string
                    self::$db[$key] = new PDO(self::$dsn[$key]);
                }

                // Throw exceptions every time an error is encountered
                self::$db[$key]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Check if we support this database
                if (!in_array(self::$db[$key]->getAttribute(PDO::ATTR_DRIVER_NAME), self::$supported)) {
                    throw new Exception('Unsupported database driver');
                }

                // MySQL Specfic
                if (self::$db[$key]->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql') {
                    // Force UTF-8
                    self::$db[$key]->query("SET NAMES 'utf8' ");
                    // Clear SQL Modes to avoid problems with boolean values in transactions
                    self::$db[$key]->query("SET SESSION sql_mode='' ");
                    // Let PDO handle MySql's (lack of) caching
                    if (defined('PDO::ATTR_EMULATE_PREPARES'))
                        self::$db[$key]->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
                }

            }
            catch (Exception $e) {
                $message = 'There was a problem connecting to the database. ';
                $message .= $e->getMessage();
                throw new Exception($message);
            }

        }

        // Return the connection
        return self::$db[$key];

    }


    /**
    * Request transaction
    *
    * @param string $key PDO dsn key
    * @return string unique id
    */
    static function requestTransaction($key = null) {

        $tid = uniqid();
        if (empty(self::$transaction[$key])) {
            self::$transaction[$key] = $tid;
            $db = self::get($key);
            $db->beginTransaction();
        }

        return $tid;

    }


    /**
    * Commit transaction
    *
    * @param string $tid unique id
    * @param string $key PDO dsn key
    */
    static function commitTransaction($tid, $key = null) {

        if (empty(self::$transaction[$key])) throw new Exception("Transaction was never initiated for: $key");

        if($tid == self::$transaction[$key]) {
            $db = self::get($key);
            $db->commit();
            unset(self::$transaction[$key]);
        }

    }


    /**
    * Show Tables SQL query
    *
    * @param string $key PDO dsn key
    */
    static function showTablesQuery($key = null) {

        $db = self::get($key);
        switch($db->getAttribute(PDO::ATTR_DRIVER_NAME))
        {

        case 'mysql':
            $q = "SHOW TABLES ";
            break;

        case 'pgsql':
            $q = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' and table_type = 'BASE TABLE' ";
            break;

        default:
            throw new Exception('Unsupported database driver');

        }

        return $q;

    }


    /**
    * Show Columns SQL query
    *
    * @param string $key PDO dsn key
    */
    static function showColumnsQuery($table, $key = null) {

        if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) throw new Exception('Invalid table name');

        $db = self::get($key);
        switch($db->getAttribute(PDO::ATTR_DRIVER_NAME))

        {

        case 'mysql':
            $q = "SHOW COLUMNS FROM {$table} ";
            break;

        case 'pgsql':
            $q = "SELECT column_name FROM information_schema.columns WHERE table_name = '{$table}' ";
            break;

        default:
            throw new Exception('Unsupported database driver');

        }

        return $q;

    }



    /**
    * Autogenerate SQL COUNT query with PDO named placeholders
    *
    * @param string $table the name of a table to insert into
    * @param array $form a list where the keys (optionally values) are database column names and placeholders
    * @param bool $useValues use the keys or the values as placeholders? Default is keys
    * @return string PDO formated prepared statement
    */
    static function prepareCountQuery($table, array $form, $useValues = false) {

        $query = "SELECT COUNT(*) FROM {$table} WHERE ";

        foreach ($form as $key => $value ) {
            $query .= ($useValues ? "$value = :$value " : "$key = :$key ");
            $query .= 'AND ';
        }

        $query = rtrim($query, 'AND '); // Remove trailing AND

        return "$query "; // Add space, just incase
    }


    /**
    * Autogenerate SQL INSERT query with PDO named placeholders
    *
    * @param string $table the name of a table to insert into
    * @param array $form a list where the keys (optionally values) are database column names and placeholders
    * @param bool $useValues use the keys or the values as placeholders? Default is keys
    * @return string PDO formated prepared statement
    */
    static function prepareInsertQuery($table, array $form, $useValues = false) {

        $data = '';
        $query    = 'INSERT INTO ';
        $column = "$table (";
        $placeholders = 'VALUES (';

        foreach ($form as $key => $value ) {
            $column .= ($useValues ? "$value, " : "$key, ");
            $placeholders .= ($useValues ? ":$value, " : ":$key, ");
        }

        $column = rtrim($column, ', '); // Remove trailing Coma
        $placeholders = rtrim($placeholders, ', ');
        $query = $query . $column . ') ' . $placeholders . ') ';

        return "$query "; // Add space, just incase
    }


    /**
    * Autogenerate SQL UPDATE query with PDO named placeholders using a table
    * name, an associative array, an and id column name
    *
    * @param string $table the name of a table to insert into
    * @param array $form a list where the keys (optionally values) are database column names and placeholders
    * @param string $id_column the name of the column to use as id
    * @param bool $useValues use the keys or the values as placeholders? Default is keys
    * @return string PDO formated prepared statement
    */
    static function prepareUpdateQuery($table, array $form, $id_column = 'id', $useValues = false) {

        $data = '';
        $query    = 'UPDATE ';
        $column = "$table SET ";
        $placeholders   = '';
        $where = '';

        foreach($form as $key => $value ) {
            $placeholders .= ($useValues ? "$value = :$value, " : "$key = :$key, ");
        }

        $where = " WHERE $id_column = :$id_column";
        $placeholders = rtrim($placeholders, ', '); // Remove trailing Coma
        $query = $query . $column . $placeholders . $where;

        return "$query "; // Add space, just incase

    }



    /**
    * Autogenerate cheap SQL SEARCH query, for a table with `title`
    * and `body_plaintext` columns
    *
    * @param string $table the name of a table to insert into
    * @param string $string search query
    * @param string $op SQL operator, AND/OR
    * @param string $key PDO dsn key
    * @return string|false SQL query
    */
    static function prepareSearchQuery($table, $string, $where = '', $op = 'AND', $key = null) {

        $tokens = suxFunct::parseTokens($string);

        $op = mb_strtoupper($op);
        if ($op != 'AND') $op = 'OR'; // Enforce OR/AND

        $db = self::get($key);
        $q = "SELECT * FROM {$table} WHERE ( ";
        foreach ($tokens as $string) {
            //quote
            $string = $db->quote($string);
            // replace the first character
            $tmp = substr($string, 0, 1);
            $string = substr_replace($string, "{$tmp}%", 0, 1);
            // replace the last character
            $tmp = substr($string, -1, 1);
            $string = substr_replace($string, "%{$tmp}", -1, 1);
            // append to query
            $q .= "(title LIKE {$string} OR body_plaintext LIKE {$string}) $op ";
        }
        $q = rtrim($q, "$op "); // Remove trailing OR
        if (trim($where)) $q .= "AND $where "; // Append additional $where query
        $q .= ') ';

        return $q;

    }


}

?>