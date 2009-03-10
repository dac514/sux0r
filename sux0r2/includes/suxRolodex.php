<?php

/**
* suxRolodex
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

// Work in progress, not finished.
// Based on the micformats hCard specification
// See: http://microformats.org/wiki/hcard

class suxRolodex {

    // Database suff
    protected $db;
    protected $inTransaction = false;
    protected $db_driver;
    // InnoDB
    protected $db_table = 'rolodex';


    /**
    * Constructor
    */
    function __construct() {

    	$this->db = suxDB::get();
        $this->db_driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        set_exception_handler(array($this, 'exceptionHandler'));

    }


    /**
    * Set rolodex
    *
    * @param array $info
    * @param int $id rolodex_id
    * @return bool
    */
    function saveRolodex(array $info, $id = null) {

        // --------------------------------------------------------------------
        // Sanitize
        // --------------------------------------------------------------------

        if ($id != null && (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1)) throw new Exception('Invalid rolodex id');

        unset($info['id']); // Don't allow spoofing of the id in the array

        foreach ($info as $key => $val) {
            if ($key == 'url') $info[$key] = suxFunct::canonicalizeUrl($val);
            elseif ($key == 'email') $info[$key] = filter_var($val, FILTER_SANITIZE_EMAIL);
            else $info[$key] = strip_tags($val); // No Html allowed
        }

        // --------------------------------------------------------------------
        // Go!
        // --------------------------------------------------------------------

        try {
            if ($id) {

                // UPDATE
                $query = suxDB::prepareUpdateQuery($this->db_table, $info);
                $st = $this->db->prepare($query);
                return $st->execute($info);

            }
            else {

                // INSERT
                $query = suxDB::prepareInsertQuery($this->db_table, $info);
                $st = $this->db->prepare($query);
                return $st->execute($info);

            }

        }
        catch (Exception $e) {
            if ($st->errorCode() == 23000) {
                // SQLSTATE 23000: Constraint violations
                return false;
            }
            else throw ($e); // Hot potato
        }


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

/*

CREATE TABLE `rolodex` (
  `id` int(11) NOT NULL auto_increment,
  `organization_name` varchar(255) NOT NULL,
  `organization_unit` varchar(255) default NULL,
  `post_office_box` varchar(255) default NULL,
  `extended_address` varchar(255) default NULL,
  `street_address` varchar(255) default NULL,
  `locality` varchar(255) default NULL,
  `region` varchar(255) default NULL,
  `postal_code` varchar(255) default NULL,
  `country_name` varchar(255) default NULL,
  `tel` varchar(255) default NULL,
  `email` varchar(255) default NULL,
  `url` varchar(255) default NULL,
  `photo` varchar(255) default NULL,
  `latitude` varchar(255) default NULL,
  `longitude` varchar(255) default NULL,
  `note` text,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

*/

?>