<?php

/**
* suxTags
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

class suxTags {

    // Database suff
    protected $db;
    protected $inTransaction = false;
    protected $db_driver;
    // MyISAM (faster, no rollback)
    protected $db_table = 'tags';


    /**
    * Constructor
    */
    function __construct() {

    	$this->db = suxDB::get();
        $this->db_driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        set_exception_handler(array($this, 'exceptionHandler'));

    }


    /**
    * Get a bookmark by id or URL
    *
    * @param int|string $id bookmard id or url
    * @param bool $unpub select un-published?
    * @return array|false
    */
    function getTag($id) {

        $col = 'id';
        if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1) $col = 'tag';

        $query = "SELECT * FROM {$this->db_table} WHERE {$col} = ? LIMIT 1";
        $st = $this->db->prepare($query);
        $st->execute(array($id));

        $tag = $st->fetch(PDO::FETCH_ASSOC);
        if ($tag) return $tag;
        else return false;

    }



    /**
    * Saves a tag to the database
    *
    * @param int $users_id users_id
    * @param string $tag
    * @return int insert id
    */
    function saveTag($users_id, $tag) {

        // -------------------------------------------------------------------
        // Sanitize
        // -------------------------------------------------------------------

        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1)
            throw new Exception('Invalid user id');

        $tag = strip_tags($tag);
        $tag = trim($tag);
        if (!$tag) throw new Exception('Invalid tag');

        // Clean
        $clean['users_id'] = $users_id;
        $clean['tag'] = $tag;

        // --------------------------------------------------------------------
        // Go!
        // --------------------------------------------------------------------

        $query = "SELECT id FROM {$this->db_table} WHERE tag = ? LIMIT 1 ";
        $st = $this->db->prepare($query);
        $st->execute(array($clean['tag']));
        $tag = $st->fetch(PDO::FETCH_ASSOC);

        if ($tag) {

            $id = $tag['id'];

        }
        else {

            // INSERT
            $query = suxDB::prepareInsertQuery($this->db_table, $clean);
            $st = $this->db->prepare($query);
            $st->execute($clean);
            $id = $this->db->lastInsertId();

        }

        return $id;

    }


    /**
    * Delete bookmark
    *
    * @param int $id tag id
    */
    function deleteTag($id) {

        if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1) return false;

        $st = $this->db->prepare("DELETE FROM {$this->db_table} WHERE id = ? LIMIT 1 ");
        $st->execute(array($id));

    }


    /**
    * Return an array of tags from a comma delimited string
    *
    * @param string $tags
    * @return array
    */
    static function parse($tags) {

        $tags = mb_split(',', $tags);

        foreach ($tags as $key => &$val) {
            $val = strip_tags($val);
            $val = mb_split('\W', $val); // Split on negated \w
            $val = implode(' ', $val); // Put back together with spaces
            $val = trim($val);
            if (!$val) unset($tags[$key]);
        }

        return $tags;

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