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
    relationship with primary keys. The table name is "link__" followed by two
    other representative names. The order of the two other tables should be in
    alphabetical order. An example of a link table for "messages" and "bayes" :

	CREATE TABLE IF NOT EXISTS `link__bayes_documents__messages` (
	  `messages_id` int(11) NOT NULL,
	  `bayes_documents_id` int(11) NOT NULL,
	  PRIMARY KEY (`messages_id`,`bayes_documents_id`),
	  KEY `bayes_documents_id` (`bayes_documents_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
        // Link tables should be named "link__table__table"
        // where table__table is in alphabetical order

        $tmp = array($table1, $table2);
        natsort($tmp);
        $link = 'link__' . implode('__', $tmp);
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
            if (preg_match('/^link__/', $val[0]) && (!$match || mb_strpos($val[0], "__{$match}")))
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

