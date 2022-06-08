<?php

/**
* suxCalendar
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

// Work in progress, not finished.
// Based on the micformats hCalendar specification
// See: http://microformats.org/wiki/hcalendar

class suxCalendar {

    // Database suff
    protected $db;
    protected $inTransaction = false;
    protected $db_driver;
    // InnoDB
    protected $db_table = 'calendar';
    protected $db_table_dates = 'calendar_dates';


    /**
    * Constructor
    */
    function __construct() {

    	$this->db = suxDB::get();
        $this->db_driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        set_exception_handler(array($this, 'exceptionHandler'));

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


/*

CREATE TABLE `calendar` (
  `id` int(11) NOT NULL auto_increment,
  `users_id` int(11) NOT NULL,
  `summary` varchar(255) NOT NULL,
  `description_html` text,
  `description_plaintext` text,
  `location` text,
  `url` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- Dates are stored in a separate table in order to allow the same event the ability to reoccur.

CREATE TABLE `calendar_dates` (
  `id` int(11) NOT NULL auto_increment,
  `calendar_id` int(11) NOT NULL,
  `dtstart` datetime NOT NULL,
  `dtend` datetime default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

*/

