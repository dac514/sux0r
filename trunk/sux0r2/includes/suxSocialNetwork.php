<?php

/**
* suxSocialNetwork
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

// Based on version 1.1 of the XFN meta data profile
// See: http://www.gmpg.org/xfn/index

class suxSocialNetwork {

    // Database suff
    protected $db;
    protected $inTransaction = false;
    protected $db_table = 'socialnetwork';

    // Enum (zero or one value)
    private $xfn_identity = array('me');
    private $xfn_friendship = array('contact', 'acquaintance', 'friend');
    private $xfn_geographical = array('co-resident', 'neighbor');
    private $xfn_family = array('child', 'parent', 'sibling', 'spouse', 'kin');
    // Set (zero or more values)
    private $xfn_physical = array('met');
    private $xfn_professional = array('co-worker', 'colleague');
    private $xfn_romantic = array('muse', 'crush', 'date', 'sweetheart');


    /**
    * @param string $key a key from our suxDB DSN
    */
    function __construct($key = null) {

        $this->db = suxDB::get($key);
        set_exception_handler(array($this, 'logAndDie'));

    }


    function setRelationship($uid, $fid, $rel) {

        // --------------------------------------------------------------------
        // Sanitize
        // --------------------------------------------------------------------

        if (!filter_var($uid, FILTER_VALIDATE_INT)) throw new Exception('Invalid user id');
        if (!filter_var($fid, FILTER_VALIDATE_INT)) throw new Exception('Invalid friend id');

        $rel = strip_tags($rel); // Strip tags
        $rel = mb_strtolower($rel);
        $rel = mb_split("\W", $rel); // \w means alphanumeric characters, \W is the negated version of \w

        $identity = '';
        $friendship = '';
        $geographical = '';
        $family = '';
        $physical = '';
        $professional = '';
        $romantic = '';

        foreach ($rel as $val) {

            $val = trim($val);

            // This is me, abort
            if (in_array($val, $this->xfn_identity)) {
                $identity = "$val ";
                break;
            }
            // Enum, overwrite
            elseif (in_array($val, $this->xfn_friendship)) $friendship = "$val ";
            elseif (in_array($val, $this->xfn_geographical)) $geographical = "$val ";
            elseif (in_array($val, $this->xfn_family)) $family = "$val ";
            // Set, append
            elseif (in_array($val, $this->xfn_physical)) $physical .= "$val ";
            elseif (in_array($val, $this->xfn_professional)) $professional .= "$val ";
            elseif (in_array($val, $this->xfn_romantic)) $romantic .= "$val ";

        }

        if ($identity) {
            $rel = rtrim($identity);
        }
        else {
            $rel = rtrim($friendship . $physical . $professional . $geographical . $family . $romantic);
        }


        // --------------------------------------------------------------------
        // Go!
        // --------------------------------------------------------------------

        $st = $this->db->prepare("SELECT COUNT(*) FROM {$this->db_table} WHERE users_id = ? AND friend_users_id = ? ");
        $st->execute(array($uid, $fid));

        $socialnetwork = array(
            'users_id' => $uid,
            'friend_users_id' => $fid,
            'relationship' => $rel,
            );

        if ($st->fetchColumn() > 0) {
            // UPDATE
            $query = "UPDATE {$this->db_table} SET relationship = :relationship WHERE users_id = :users_id AND friend_users_id = :friend_users_id ";
            $st = $this->db->prepare($query);
            return $st->execute($socialnetwork);

        }
        else {
            // INSERT
            $query = suxDB::prepareInsertQuery($this->db_table, $socialnetwork);
            $st = $this->db->prepare($query);
            return $st->execute($socialnetwork);
        }


    }


    function deleteRelationship($id) {

        if (!filter_var($id, FILTER_VALIDATE_INT)) return false;

        $st = $this->db->prepare("DELETE FROM {$this->db_table} WHERE id = ? LIMIT 1 ");
        return $st->execute(array($id));

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

        $message = "suxSocialNetwork Error: \n";
        $message .= $e->getMessage() . "\n";
        $message .= "File: " . $e->getFile() . "\n";
        $message .= "Line: " . $e->getLine() . "\n\n";
        $message .= "Backtrace: \n" . print_r($e->getTrace(), true) . "\n\n";
        die("<pre>{$message}</pre>");

    }


}

/*

-- Database

CREATE TABLE `socialnetwork` (
  `id` int(11) NOT NULL auto_increment,
  `users_id` int(11) NOT NULL,
  `friend_users_id` int(11) NOT NULL,
  `relationship` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `friendship` (`users_id`,`friend_users_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

*/

?>