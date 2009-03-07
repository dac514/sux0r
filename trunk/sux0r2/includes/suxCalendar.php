<?php

/**
* suxCalendar
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.gnu.org/licenses/agpl.html
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
    function exceptionHandler(Exception $e) {

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
  `summary` varchar(255) NOT NULL,
  `description_html` text,
  `description_plaintext` text,
  `location` text,
  `url` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE `calendar_dates` (
  `id` int(11) NOT NULL auto_increment,
  `calendar_id` int(11) NOT NULL,
  `dtstart` datetime NOT NULL,
  `dtend` datetime default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

*/

?>