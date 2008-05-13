<?php

/**
* suxCalendar
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

class suxCalendar {

    // Database suff
    protected $db;
    protected $inTransaction = false;
    protected $db_table = 'calendar';
    protected $db_table_dates = 'calendar_dates';


    /**
    * @param string $key a key from our suxDB DSN
    */
    function __construct($key = null) {

        if (!$key && !empty($GLOBALS['CONFIG']['DSN']['calendar'])) $key = 'calendar';
    	$this->db = suxDB::get($key);
        set_exception_handler(array($this, 'logAndDie'));

    }



    // ----------------------------------------------------------------------------
    // Exception Handler
    // ----------------------------------------------------------------------------


    /**
    * @param Exception $e an Exception class
    */
    function logAndDie(Exception $e) {

        if ($this->db && $this->inTransaction) {
            $this->db->rollback();
            $this->inTransaction = false;
        }

        $message = "suxCalendar Error: \n";
        $message .= $e->getMessage() . "\n";
        $message .= "File: " . $e->getFile() . "\n";
        $message .= "Line: " . $e->getLine() . "\n\n";
        $message .= "Backtrace: \n" . print_r($e->getTrace(), true) . "\n\n";
        die("<pre>{$message}</pre>");

    }


}

/*

-- Database

CREATE TABLE `calendar` (
  `id` int(11) NOT NULL auto_increment,
  `summary` varchar(255) NOT NULL,
  `description_html` text,
  `description_plaintext` text,
  `location` text,
  `url` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE `calendar_dates` (
  `id` int(11) NOT NULL auto_increment,
  `calendar_id` int(11) NOT NULL,
  `dtstart` datetime NOT NULL,
  `dtend` datetime default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

*/

?>