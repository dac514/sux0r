<?php

/**
* suxLink
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
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
    function buildTableName($table1, $table2) {

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
    function buildColumnName($table, $link) {

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

        $st = $this->db->query(suxDB::showTablesQuery());
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