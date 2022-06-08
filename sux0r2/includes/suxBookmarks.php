<?php

/**
* suxBookmarks
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class suxBookmarks {

    // Database suff
    protected $db;
    protected $inTransaction = false;
    protected $db_driver;
    // Tables
    protected $db_table = 'bookmarks';

    // Object properties, with defaults
    protected $published = true;
    protected $order = array('published_on', 'DESC');


    /**
    * Constructor
    */
    function __construct() {

        $this->db = suxDB::get();
        $this->db_driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        set_exception_handler(array($this, 'exceptionHandler'));

    }


    /**
    * Set published property of object
    *
    * @param bool $published
    */
    public function setPublished($published) {

        // Three options:
        // True, False, or Null

        $this->published = $published;
    }


    /**
    * Set order property of object
    *
    * @param string $col
    * @param string $way
    */
    public function setOrder($col, $way = 'ASC') {

        if (!preg_match('/^[A-Za-z0-9_,\s]+$/', $col)) throw new Exception('Invalid column(s)');
        $way = (mb_strtolower($way) == 'asc') ? 'ASC' : 'DESC';

        $arr = array($col, $way);
        $this->order = $arr;

    }


    /**
    * Return published SQL
    *
    * @return string
    */
    public function sqlPublished() {

        // Published   : draft = FALSE AND published_on < NOW
        // Unpublished : draft = TRUE OR published_on >= NOW
        // Null = SELECT ALL, not sure what the best way to represent this is, id = id?

        // PgSql / MySql
        if ($this->published === true) $query = "draft = false AND published_on <= '" . date('Y-m-d H:i:s') . "' ";
        elseif ($this->published === false) $query = $query = "draft = true OR published_on > '" . date('Y-m-d H:i:s') . "' ";
        else $query = "id = id "; // select all

        return $query;
    }


    /**
    * Return order SQL
    *
    * @return string
    */
    public function sqlOrder() {
        // PgSql / MySql
        $query = "{$this->order[0]} {$this->order[1]} ";
        return $query;
    }


    /**
    * Count bookmarks
    *
    * @return int
    */
    function count() {

        // SQL Query
        $query = "SELECT COUNT(*) FROM {$this->db_table} ";

        // Publish / Draft
        if (is_bool($this->published)) $query .= 'WHERE ' . $this->sqlPublished();

        // Execute
        $st = $this->db->prepare($query);
        $st->execute();
        return $st->fetchColumn();

    }



    /**
    * Get bookmarks
    *
    * @param int $limit sql limit value
    * @param int $start sql start of limit value
    * @return array
    */
    function get($limit = null, $start = 0) {

        // SQL Query
        $query = "SELECT * FROM {$this->db_table} ";

        // Publish / Draft
        if (is_bool($this->published)) $query .= 'WHERE ' . $this->sqlPublished();

        // Order
        $query .= 'ORDER BY ' . $this->sqlOrder();

        // Limit
        if ($start && $limit) $query .= "LIMIT {$limit} OFFSET {$start} ";
        elseif ($limit) $query .= "LIMIT {$limit} ";

        // Execute
        $st = $this->db->prepare($query);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }



    /**
    * Get a bookmark by id or URL
    *
    * @param int|string $id bookmard id or url
    * @return array|false
    */
    function getByID($id) {

        $col = 'id';
        if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1) {
            $col = 'url';
            $id = suxFunct::canonicalizeUrl($id);
        }

        $query = "SELECT * FROM {$this->db_table} WHERE {$col} = ? ";

        // Publish / Draft
        if (is_bool($this->published)) $query .= 'AND ' . $this->sqlPublished();

        $st = $this->db->prepare($query);
        $st->execute(array($id));

        $bookmark = $st->fetch(PDO::FETCH_ASSOC);
        if ($bookmark) return $bookmark;
        else return false;

    }



    /**
    * Saves a bookmark to the database
    *
    * @param int $users_id users_id
    * @param array $url required keys => (url, title, body) optional keys => (id, published_on, draft)
    * @param int $trusted passed on to sanitizeHtml()
    * @return int insert id
    */
    function save($users_id, array $url, $trusted = -1) {

        $clean = [];
        // -------------------------------------------------------------------
        // Sanitize
        // -------------------------------------------------------------------

        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1) throw new Exception('Invalid user id');
        if (!isset($url['url']) || !isset($url['title']) || !isset($url['body'])) throw new Exception('Invalid $url array');
        if (!filter_var($url['url'], FILTER_VALIDATE_URL)) throw new Exception('Invalid url');

        // Users id
        $clean['users_id'] = $users_id;

        // Canonicalize Url
        $clean['url'] = suxFunct::canonicalizeUrl($url['url']);

        // No HTML in title
        $clean['title'] = strip_tags($url['title']);

        // Sanitize HTML in body
        $clean['body_html'] = suxFunct::sanitizeHtml($url['body'], $trusted);

        // Convert and copy body to UTF-8 plaintext
        $converter = new suxHtml2UTF8($clean['body_html']);
        $clean['body_plaintext']  = $converter->getText();

        // Id
        if (isset($url['id'])) {
            if (!filter_var($url['id'], FILTER_VALIDATE_INT) || $url['id'] < 1) throw new Exception('Invalid id');
            else $clean['id'] = $url['id'];
        }
        else {
            $query = "SELECT id FROM {$this->db_table} WHERE url = ? ";
            $st = $this->db->prepare($query);
            $st->execute(array($clean['url']));
            $edit = $st->fetch(PDO::FETCH_ASSOC);
            if ($edit) $clean['id'] = $edit['id'];
        }

        // Publish date
        if (isset($url['published_on'])) {
            // ISO 8601 date format
            // regex must match '2008-06-18 16:53:29' or '2008-06-18T16:53:29-04:00'
            $regex = '/^(\d{4})-(0[0-9]|1[0,1,2])-([0,1,2][0-9]|3[0,1]).+(\d{2}):(\d{2}):(\d{2})/';
            if (!preg_match($regex, (string) $url['published_on'])) throw new Exception('Invalid date');
            $clean['published_on'] = $url['published_on'];
        }
        else $clean['published_on'] = date('Y-m-d H:i:s');

        // Draft, boolean / tinyint
        $clean['draft'] = false;
        if (isset($url['draft']) && $url['draft']) $clean['draft'] = true;

        // We now have the $clean[] array

        // --------------------------------------------------------------------
        // Go!
        // --------------------------------------------------------------------

        // http://bugs.php.net/bug.php?id=44597
        // As of 5.2.6 you still can't use this function's $input_parameters to
        // pass a boolean to PostgreSQL. To do that, you'll have to call
        // bindParam() with explicit types for *each* parameter in the query.
        // Annoying much? This sucks more than you can imagine.

        if (isset($clean['id'])) {

            // UPDATE
            unset($clean['users_id']); // Don't override the original submitter
            $query = suxDB::prepareUpdateQuery($this->db_table, $clean);
            $st = $this->db->prepare($query);

            if  ($this->db_driver == 'pgsql') {
                $st->bindParam(':id', $clean['id'], PDO::PARAM_INT);
                $st->bindParam(':url', $clean['url'], PDO::PARAM_STR);
                $st->bindParam(':title', $clean['title'], PDO::PARAM_STR);
                $st->bindParam(':body_html', $clean['body_html'], PDO::PARAM_STR);
                $st->bindParam(':body_plaintext', $clean['body_plaintext'], PDO::PARAM_STR);
                $st->bindParam(':published_on', $clean['published_on'], PDO::PARAM_STR);
                $st->bindParam(':draft', $clean['draft'], PDO::PARAM_BOOL);
                $st->execute();
            }
            else {
                $st->execute($clean);
            }

        }
        else {

            // INSERT
            $query = suxDB::prepareInsertQuery($this->db_table, $clean);
            $st = $this->db->prepare($query);

            if  ($this->db_driver == 'pgsql') {
                $st->bindParam(':users_id', $clean['users_id'], PDO::PARAM_INT);
                $st->bindParam(':url', $clean['url'], PDO::PARAM_STR);
                $st->bindParam(':title', $clean['title'], PDO::PARAM_STR);
                $st->bindParam(':body_html', $clean['body_html'], PDO::PARAM_STR);
                $st->bindParam(':body_plaintext', $clean['body_plaintext'], PDO::PARAM_STR);
                $st->bindParam(':published_on', $clean['published_on'], PDO::PARAM_STR);
                $st->bindParam(':draft', $clean['draft'], PDO::PARAM_BOOL);
                $st->execute();
            }
            else {
                $st->execute($clean);
            }

            if ($this->db_driver == 'pgsql') $clean['id'] = $this->db->lastInsertId("{$this->db_table}_id_seq"); // PgSql
            else $clean['id'] = $this->db->lastInsertId();

        }

        return $clean['id'];

    }


    /**
    * Delete bookmark
    *
    * @param int $id bookmarks_id
    */
    function delete($id) {

        if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1) return false;

        $tid = suxDB::requestTransaction();
        $this->inTransaction = true;

        $st = $this->db->prepare("DELETE FROM {$this->db_table} WHERE id = ? ");
        $st->execute(array($id));

        // Delete links, too
        $link = new suxLink();
        $links = $link->getLinkTables('bookmarks');
        foreach ($links as $table) {
            $link->deleteLink($table, 'bookmarks', $id);
        }

        suxDB::commitTransaction($tid);
        $this->inTransaction = false;

    }


    /**
    * @param int $id bookmark id
    * @param bool $bool
    */
    function draft($id, $bool) {

        if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1) return false;

        if ($bool) $query = "UPDATE {$this->db_table} SET draft = true WHERE id = ? ";
        else $query = "UPDATE {$this->db_table} SET draft = false WHERE id = ? ";

        $st = $this->db->prepare($query);
        $st->execute(array($id));

    }


    /**
    * Fetch a bookmark
    *
    * @param string $url a URL to an RSS Feed
    * @return array|false
    */
    function fetchUrlInfo($url) {

        // Sanity check
        if (!filter_var($url, FILTER_VALIDATE_URL)) return false;


        // Search the webpage for info we can use
        if (ini_get('allow_url_fopen')) {
            // file_get_contents
            $webpage = @file_get_contents($url, null, null, 0, 8192); // Quit after 8 kilobytes
        }
        elseif(function_exists('curl_init')) {
            // cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            // There is no CURLOPT_MAXFILESIZE...
            $webpage = curl_exec($ch);
            curl_close($ch);
        }
        else {
            throw new Exception('No way to retrieve bookmark');
        }


        $title = null;
        $description = null;

        // <title>
        $found = array();
        if (preg_match('/<title>(.*?)<\/title>/is', (string) $webpage, $found)) {
            $title = html_entity_decode(strip_tags($found[1]), ENT_QUOTES, 'UTF-8');
        }
        // Meta description
        if (preg_match('/<meta[^>]+name="description"[^>]+content="([^"]*)"[^>]*>/i', (string) $webpage, $found)) {
            $description = html_entity_decode(strip_tags($found[1]), ENT_QUOTES, 'UTF-8');
        }

        return array(
            'title' => $title,
            'description' => $description,
            );

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

