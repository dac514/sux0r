<?php

/**
* suxRolodex
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

// Based on the micformats hCard specification
// See: http://microformats.org/wiki/hcard

class suxRolodex {

    // Database suff
    protected $db;
    protected $inTransaction = false;
    protected $db_table = 'rolodex';


    /**
    * @global array $CONFIG['DSN']
    * @param string $key a key from our suxDB DSN
    */
    function __construct($key = null) {

        if (!$key && !empty($GLOBALS['CONFIG']['DSN']['rolodex'])) $key = 'rolodex';
    	$this->db = suxDB::get($key);
        set_exception_handler(array($this, 'exceptionHandler'));

    }


    /**
    * Set rolodex
    *
    * @param array $info
    * @param int $id rolodex_id
    * @return bool
    */
    function setRolodex(array $info, $id = null) {

        // --------------------------------------------------------------------
        // Sanitize
        // --------------------------------------------------------------------

        if ($id != null && !filter_var($id, FILTER_VALIDATE_INT)) throw new Exception('Invalid rolodex id');

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
            if (!$id) {

                // Insert user
                $query = suxDB::prepareInsertQuery($this->db_table, $info);
                $st = $this->db->prepare($query);
                return $st->execute($info);

            }
            else {

                // Update user
                $query = suxDB::prepareUpdateQuery($this->db_table, $info);
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

?>