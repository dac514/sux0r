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

            // UPDATE, do nothing...
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
    * Delete tag
    *
    * @param int $id tag id
    */
    function deleteTag($id) {

        if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1) return false;

        $tid = suxDB::requestTransaction();
        $this->inTransaction = true;

        $st = $this->db->prepare("DELETE FROM {$this->db_table} WHERE id = ? LIMIT 1 ");
        $st->execute(array($id));

        // Delete links, too
        $link = new suxLink();
        $links = $link->getLinkTables('tags');
        foreach ($links as $table) {
            $link->deleteLink($table, $link->getLinkColumnName($table, 'tags'), $id);
        }

        suxDB::commitTransaction($tid);
        $this->inTransaction = false;

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
            $val = mb_convert_case($val, MB_CASE_TITLE, 'UTF-8');
            $val = mb_split('\W', $val); // Split on negated \w
            $val = implode(' ', $val); // Put back together with spaces
            $val = trim($val);
            if (!$val) unset($tags[$key]);
        }

        return $tags;

    }


    /**
    * Return a tag cloud data structure
    *
    * @see: http://prism-perfect.net/archive/php-tag-cloud-tutorial/
    * @param string $query
    * @return array|false
    */
    function tagcloud($query) {

        // Expects something like:
        // SELECT tags.tag AS tag, tags.id AS id, COUNT(tags.id) AS quantity FROM tags
        // INNER JOIN [...]
        $st = $this->db->query($query);

        // Put results into arrays
        $tags = array();
        $category_id = array();
        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $tags[$row['tag']] = $row['quantity'];
            $category_id[$row['tag']] = $row['id'];
        }

        if (!count($tags)) return false; // Nothing to do?

        $max_size = 250; // max font size in %
        $min_size = 100; // min font size in %

        // get the largest and smallest array values
        $max_qty = max(array_values($tags));
        $min_qty = min(array_values($tags));

        // find the range of values
        $spread = $max_qty - $min_qty;
        if (0 == $spread) { // we don't want to divide by zero
            $spread = 1;
        }

        // determine the font-size increment
        // this is the increase per tag quantity (times used)
        $step = ($max_size - $min_size)/($spread);

        // Adjust data structure
        $data = array();
        foreach ($tags as $key => $val) {
            $data[$key] = array(
                'quantity' => $val,
                'id' => $category_id[$key],
                'size' => $min_size + (($val - $min_qty) * $step),
                );

        }

        return $data;

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