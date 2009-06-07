<?php

/**
* suxTags
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class suxTags {

    // Database suff
    protected $db;
    protected $inTransaction = false;
    protected $db_driver;
    // Tables
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
    * Get a tag by id or word
    *
    * @param int|string $id id or word
    * @return array|false
    */
    function getByID($id) {

        $col = 'id';
        if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1) $col = 'tag';

        $query = "SELECT * FROM {$this->db_table} WHERE {$col} = ? ";
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
    function save($users_id, $tag) {

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

        $query = "SELECT id FROM {$this->db_table} WHERE tag = ? ";
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

            if ($this->db_driver == 'pgsql') $id = $this->db->lastInsertId("{$this->db_table}_id_seq"); // PgSql
            else $id = $this->db->lastInsertId();

        }

        return $id;

    }


    /**
    * Delete tag
    *
    * @param int $id tag id
    */
    function delete($id) {

        if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1) return false;

        $tid = suxDB::requestTransaction();
        $this->inTransaction = true;

        $st = $this->db->prepare("DELETE FROM {$this->db_table} WHERE id = ? ");
        $st->execute(array($id));

        // Delete links, too
        $link = new suxLink();
        $links = $link->getLinkTables('tags');
        foreach ($links as $table) {
            $link->deleteLink($table, 'tags', $id);
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
    function cloud($query) {

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