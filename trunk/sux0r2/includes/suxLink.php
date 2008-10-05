<?php

/**
* suxLink
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

class suxLink {

    /*
    Link tables are special tables that join two other tables using a 1 to 1
    relationship with primary keys. The table name is "link_" followed by two
    other representative names. The order of the two other tables should be in
    alphabetical order. An example of a link table for "messages" and "bayes" :

    CREATE TABLE `link_bayes_messages` (
    `messages_id` int(11) NOT NULL,
    `bayes_documents_id` int(11) NOT NULL,
    UNIQUE KEY `idx` (`messages_id`,`bayes_documents_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    NB: Because we need rollback, link tables are InnoDB
    */


    // Database suff
    protected $db;
    protected $inTransaction = false;
    protected $db_driver;


    /**
    * Constructor
    */
    function __construct() {

    	$this->db = suxDB::get();
        $this->db_driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        set_exception_handler(array($this, 'exceptionHandler'));

    }


    /**
    * @param string $table name of a first table
    * @param string $table name of a second table
    * @return string
    **/
    function getLinkTableName($table1, $table2) {

        // Convention
        // Link tables should be named "link_table_table"
        // where table_table is in alphabetical order

        $tmp = array($table1, $table2);
        natsort($tmp);
        $link = 'link_' . implode('_', $tmp);
        return $link;

    }


    /**
    * Some link columns break naming conventions. Use this function to get the
    * correct linked table name.
    *
    * @param string $table name of a link table
    * @param string $link name of column in the link table
    * @return string
    **/
    function getLinkColumnName($table, $link) {

        if ($link == 'bayes')
            return 'bayes_documents';

        elseif ($table == 'link_bayes_rss' && $link == 'rss')
            return 'rss_items';

        elseif ($table == 'link_rss_users' && $link == 'rss')
            return 'rss_feeds';

        return $link;

    }


    /**
    * Get list of link tables
    *
    * @param string $match
    * @return array
    */
    function getLinkTables($match = null) {

        $return = array();

        switch($this->db_driver)
        {

        case 'mysql':
            $q = "SHOW TABLES ";
            break;

        case 'sqlite':
            $q =   "SELECT name FROM sqlite_master WHERE type = 'table' ";
            break;

        case 'pgsql':
            $q = "SELECT * FROM information_schema.tables WHERE table_schema = 'public' and table_type = 'BASE TABLE' ";
            break;

        default:
            throw new Exception('Unsupported database driver');

        }

        $st = $this->db->query($q);
        foreach ($st->fetchAll(PDO::FETCH_NUM) as $val) {
            if (preg_match('/^link_/', $val[0]) && (!$match || mb_strpos($val[0], "_{$match}")))
                $return[] = $val[0];
        }
        return $return;

    }


    /**
    * Get links
    *
    * @param string $link name of the link table
    * @param string $table name of the table
    * @param int $id a key
    * @return array
    */
    function getLinks($link, $table, $id) {

        $st = $this->db->prepare("SELECT * FROM {$link} WHERE {$table}_id = ? ");
        $st->execute(array($id));
        $return = array();
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            unset($row["{$table}_id"]);
            $return[] = array_pop($row);
        }

        return $return;

    }



    /**
    * Set links
    *
    * @param string $link name of the link table
    * @param string $table1 name of the first table
    * @param int $id1 a primary key
    * @param string $table2 name of the second table
    * @param int|array $id2 either a primary key, or an array of primary keys
    * @param bool if true, use the key of $id2 as the data
    */
    function saveLink($link, $table1, $id1, $table2, $id2, $onkey = false) {

        // One to many mapping
        // $id1 = One
        // $id2 = Many

        if (!is_array($id2)) {
            $tmp = $id2;
            unset($id2);
            $id2[] = $tmp;
        }

        $tid = suxDB::requestTransaction();
        $this->inTransaction = true;

        foreach ($id2 as $key => $val) {

            $form = array();
            $form["{$table1}_id"] = $id1;
            if ($onkey) $form["{$table2}_id"] = $key;
            else $form["{$table2}_id"] = $val;

            if ($form["{$table2}_id"]) {

                // Make sure this doesn't already exist
                $query = suxDB::prepareCountQuery($link, $form);
                $st = $this->db->prepare($query);
                $st->execute($form);

                if (!$st->fetchColumn()) {
                    // It's new, insert it
                    $query = suxDB::prepareInsertQuery($link, $form);
                    $st = $this->db->prepare($query);
                    $st->execute($form);
                }
            }
        }

        suxDB::commitTransaction($tid);
        $this->inTransaction = false;

    }


    /**
    * Delete link
    *
    * @param string $link name of the link table
    * @param string $table name of the table
    * @param int|array $id either a primary key, or an array of primary keys
    * @param bool if true, use the key of $id as the data
    */
    function deleteLink($link, $table, $id, $onkey = false) {

        if (!is_array($id)) {
            $tmp = $id;
            unset($id);
            $id[] = $tmp;
        }

        $tid = suxDB::requestTransaction();
        $this->inTransaction = true;

        foreach ($id as $key => $val) {
            $st = $this->db->prepare("DELETE FROM {$link} WHERE {$table}_id = ? ");
            if ($onkey) $st->execute(array($key));
            else $st->execute(array($val));
        }

        suxDB::commitTransaction($tid);
        $this->inTransaction = false;

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