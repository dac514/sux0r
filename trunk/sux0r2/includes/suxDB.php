<?php

/**
* suxDB
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
*/

class suxDB {

    /*
    * Access a PDO database connection from anywhere in the program without
    * worrying about variable scope. Prevent more than one connection from
    * being created. Manage connections to multiple database servers.
    *
    * Example:
    * suxDB::$dsn = array(
    *   'blogs' => 'sqlite:/tmp/blogs.db',
    *   'users' => array('mysql:host=localhost', 'user', 'password'),
    *   'admin' => array('mysql:host=db.example.com', 'user', 'password'),
    *   'stats' => array('oci:statistics', 'user', 'password'),
    * );
    * $db = suxDB::get(); // Defaults to first item in DSN array, i.e. blogs
    * $db2 = suxDB::get('stats');
    */

    public static $dsn = array();
    private static $db = array();

    // Static class, no cloning or instantiating allowed
    final private function __construct() { }
    final private function __clone() { }

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

        if (!isset(self::$dsn[$key])) {
            throw new Exception("Unknown DSN: $key");
        }

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

                // MySQL Specfic
                if (self::$db[$key]->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql') {
                    self::$db[$key]->query("SET NAMES 'utf8'"); // Force UTF-8
                }

            }
            catch (Exception $e) {
                $message = "suxDB Error: \n";
                $message .= "There was a problem initializing the connection to the database.\n";
                $message .= $e->getMessage();
                die("<pre>{$message}</pre>");
            }

        }

        // Return the connection
        return self::$db[$key];

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


}

?>