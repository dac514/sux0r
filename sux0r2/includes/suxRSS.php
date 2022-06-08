<?php

/**
* suxRSS
*
* Forked from / Inspired by:
* Vojtech Semecky: http://lastrss.oslab.net/
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class suxRSS extends DOMDocument {

    // --------------------------------------------------------------------
    // Variables
    // --------------------------------------------------------------------

    // Cache interval in seconds
    public $cache_time = 900;

    // Folder in which cached data should be stored, set in constructor
    public $cache_dir = null;

    // Allows to set how to proceed CDATA information.
    // content = get CDATA content (without CDATA tag)
    // nochange = don't make any changes
    public $CDATA = 'content';

    // Allows limit number of returned items. 0 (zero) means "no limit"
    public $items_limit = 0;

    // All pubDate and lastBuildDate data will be converted to specified
    // date/time format. The format is fully compatible with PHP function date()
    public $date_format = 'Y-m-d H:i:s';

    // RSS 2.0 Tags
    protected $channeltags = array('title', 'link', 'description', 'language', 'copyright', 'managingEditor', 'webMaster', 'lastBuildDate', 'rating', 'docs');
    protected $itemtags = array('title', 'link', 'description', 'author', 'category', 'comments', 'enclosure', 'guid', 'pubDate', 'source');
    protected $imagetags = array('title', 'url', 'link', 'width', 'height');
    protected $textinputtags = array('title', 'description', 'name', 'link');

    // Atom 1.0 Tags
    // Map RSS elements to equivilant Atom elements
    // @see: http://www.intertwingly.net/wiki/pie/Rss20AndAtom10Compared
    protected $channeltags_atom = array(
        'copyright' => 'rights',
        'description' => 'subtitle',
        'lastBuildDate' => 'updated',
        'managingEditor' => array('author', 'contributor'),
        );
    protected $itemtags_atom = array(
        'description' => array('content', 'summary'),
        'guid' => 'id',
        'pubDate' => 'published',
        );

    // RSS Code Page / Encoding
    private string $rsscp = '';

    // Channel
    private $channel;

    // --------------------------------------------------------------------
    // Database stuff
    // --------------------------------------------------------------------

    protected $db;
    protected $inTransaction = false;
    protected $db_driver;
    // InnoDB
    protected $db_feeds = 'rss_feeds';
    protected $db_items = 'rss_items';

    // Object properties, with defaults
    protected $published = true;
    protected $order = array('title', 'ASC');


    /**
    * Constructor
    *
    * @global string $CONFIG['PATH']
    */
    function __construct() {

        parent::__construct(); // DOMDocument
        $this->formatOutput = true; // DOMDocument

        // Cache
        $this->cache_dir = $GLOBALS['CONFIG']['PATH'] . '/temporary/rss_cache';

        // Db
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

        // Published   : draft = FALSE
        // Unpublished : draft = TRUE
        // Null = SELECT ALL, not sure what the best way to represent this is, id = id?

        // PgSql / MySql
        if ($this->published === true) $query = "draft = false ";
        elseif ($this->published === false) $query = $query = "draft = true ";
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


    // --------------------------------------------------------------------
    // Database accesors
    // --------------------------------------------------------------------

    /**
    * Cron, fetch RSS items and insert them into the database
    */
    function cron() {

        $q = "SELECT id, url FROM {$this->db_feeds} WHERE draft = false ";
        $st = $this->db->query($q);

        // Resused prepared statement
        $q2 = "SELECT COUNT(*) FROM {$this->db_items} WHERE url = ? ";
        $st2 = $this->db->prepare($q2);

        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results = $this->fetchRSS($row['url']);
            if (is_array($results) && count($results) && $results['cached'] != 1) {
                foreach ($results['items'] as $item) {
                    if (isset($item['title']) && isset($item['link']) && isset($item['description'])) {

                        $clean = array(); // Reset

                        // Check if this already exists
                        $clean['url'] = suxFunct::canonicalizeUrl($item['link']);
                        if (!$clean['url']) continue; // Garbage, skip this
                        $st2->execute(array($clean['url']));
                        if ($st2->fetchColumn() > 0) continue; // Already in DB, skip

                        // Set the rest of our array
                        $clean['rss_feeds_id'] = $row['id'];
                        $clean['title'] = strip_tags($item['title']);
                        $clean['body_html'] = $item['description']; // suxRSS() sanitzes HTML
                        $converter = new suxHtml2UTF8($clean['body_html']);
                        $clean['body_plaintext']  = $converter->getText();
                        if (!empty($item['pubDate'])) $clean['published_on'] = $item['pubDate'];
                        else $clean['published_on'] = date('Y-m-d H:i:s');

                        // Insert
                        try {
                            $q3 = suxDB::prepareInsertQuery($this->db_items, $clean);
                            $st3 = $this->db->prepare($q3);
                            $st3->execute($clean);
                        }
                        catch (Exception $e) {
                            if ($st3->errorCode() == 23000) continue; // SQLSTATE 23000: Constraint violation, we don't care, carry on
                            else throw ($e); // Hot potato
                        }

                    }
                }
            }
        }
    }


    /**
    * Get feeds
    *
    * @param int $limit sql limit value
    * @param int $start sql start of limit value
    * @return array|false
    */
    function getFeeds($limit = null, $start = 0) {

        $query = "SELECT * FROM {$this->db_feeds} ";

        // Publish / Draft
        if (is_bool($this->published)) $query .= 'WHERE ' . $this->sqlPublished();

        // Order
        $query .= 'ORDER BY ' . $this->sqlOrder();

        // Limit
        if ($start && $limit) $query .= "LIMIT {$limit} OFFSET {$start} ";
        elseif ($limit) $query .= "LIMIT {$limit} ";

        $st = $this->db->query($query);
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }


    /**
    * Count albums
    *
    * @param bool $published select un-published?
    * @return array|false
    */
    function countFeeds() {

        $query = "SELECT COUNT(*) FROM {$this->db_feeds} ";

        // Publish / Draft
        if (is_bool($this->published)) $query .= 'WHERE ' . $this->sqlPublished();

        $st = $this->db->prepare($query);
        $st->execute();
        return $st->fetchColumn();

    }




    /**
    * Get a feed by id or url
    *
    * @param int|string $id feed id or url
    * @return array|false
    */
    function getFeedByID($id) {

        // Pick a query
        if (filter_var($id, FILTER_VALIDATE_INT) && $id > 0) {
            $query = "SELECT * FROM {$this->db_feeds} WHERE id = ? ";
        }
        else {
            $id = suxFunct::canonicalizeUrl($id);
            $query = "SELECT * FROM {$this->db_feeds} WHERE url = ? ";
        }

        // Publish / Draft
        if (is_bool($this->published)) $query .= 'AND ' . $this->sqlPublished();

        $st = $this->db->prepare($query);
        $st->execute(array($id));

        $feed = $st->fetch(PDO::FETCH_ASSOC);
        if ($feed) return $feed;
        else return false;

    }


    /**
    * Saves a feed to the database
    *
    * @param int $users_id users_id
    * @param array $url required keys => (url, title, body) optional keys => (id, draft)
    * @param int $trusted passed on to sanitizeHtml()
    * @return int insert id
    */
    function saveFeed($users_id, array $url, $trusted = -1) {

        $clean = [];
        // -------------------------------------------------------------------
        // Sanitize
        // -------------------------------------------------------------------

        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1)
            throw new Exception('Invalid user id');

        if (!isset($url['url']) || !isset($url['title']) || !isset($url['body']))
            throw new Exception('Invalid $url array');

        if (!filter_var($url['url'], FILTER_VALIDATE_URL))
            throw new Exception('Invalid url');

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
            $query = "SELECT id FROM {$this->db_feeds} WHERE url = ? ";
            $st = $this->db->prepare($query);
            $st->execute(array($clean['url']));
            $edit = $st->fetch(PDO::FETCH_ASSOC);
            if ($edit) $clean['id'] = $edit['id'];
        }

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
            unset($clean['users_id']); // Don't override the original suggestor
            $query = suxDB::prepareUpdateQuery($this->db_feeds, $clean);
            $st = $this->db->prepare($query);

            if  ($this->db_driver == 'pgsql') {
                $st->bindParam(':id', $clean['id'], PDO::PARAM_INT);
                $st->bindParam(':url', $clean['url'], PDO::PARAM_STR);
                $st->bindParam(':title', $clean['title'], PDO::PARAM_STR);
                if (isset($clean['body_html'])) $st->bindParam(':body_html', $clean['body_html'], PDO::PARAM_STR);
                if (isset($clean['body_plaintext'])) $st->bindParam(':body_plaintext', $clean['body_plaintext'], PDO::PARAM_STR);
                $st->bindParam(':draft', $clean['draft'], PDO::PARAM_BOOL);
                $st->execute();
            }
            else {
                $st->execute($clean);
            }

        }
        else {

            // INSERT
            $query = suxDB::prepareInsertQuery($this->db_feeds, $clean);
            $st = $this->db->prepare($query);

            if  ($this->db_driver == 'pgsql') {
                $st->bindParam(':users_id', $clean['users_id'], PDO::PARAM_INT);
                $st->bindParam(':url', $clean['url'], PDO::PARAM_STR);
                $st->bindParam(':title', $clean['title'], PDO::PARAM_STR);
                if (isset($clean['body_html'])) $st->bindParam(':body_html', $clean['body_html'], PDO::PARAM_STR);
                if (isset($clean['body_plaintext'])) $st->bindParam(':body_plaintext', $clean['body_plaintext'], PDO::PARAM_STR);
                $st->bindParam(':draft', $clean['draft'], PDO::PARAM_BOOL);
                $st->execute();
            }
            else {
                $st->execute($clean);
            }

            if ($this->db_driver == 'pgsql') $clean['id'] = $this->db->lastInsertId("{$this->db_feeds}_id_seq"); // PgSql
            else $clean['id'] = $this->db->lastInsertId();

        }

        // Clear cache
        $this->deleteCache($clean['url']);

        return $clean['id'];

    }


    /**
    * Count RSS items
    *
    * @param int $feed_id feed id
    * @return int
    */
    function countItems($feed_id = null) {

        $query = "SELECT COUNT(*) FROM {$this->db_items} ";
        if ($feed_id) {
            if (filter_var($feed_id, FILTER_VALIDATE_INT) && $feed_id > 0) {
                $query .= "WHERE rss_feeds_id = $feed_id ";
            }
            else throw new Exception('Invalid feed id');
        }

        // Execute
        $st = $this->db->query($query);
        return $st->fetchColumn();

    }



    /**
    * @param int $id feed id
    */
    function deleteFeed($id) {

        if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1) return false;

        $tid = suxDB::requestTransaction();
        $this->inTransaction = true;

        $st = $this->db->prepare("DELETE FROM {$this->db_feeds} WHERE id = ? ");
        $st->execute(array($id));

        $st = $this->db->prepare("SELECT id FROM {$this->db_items} WHERE rss_feeds_id = ? ");
        $st->execute(array($id));
        $result = $st->fetchAll(PDO::FETCH_ASSOC); // Used with link deletion

        $st = $this->db->prepare("DELETE FROM {$this->db_items} WHERE rss_feeds_id = ? ");
        $st->execute(array($id));

        // Delete links, too
        $link = new suxLink();
        $links = $link->getLinkTables('rss_feeds');
        foreach ($links as $table) {
            $link->deleteLink($table, 'rss_feeds', $id);
        }
        $links = $link->getLinkTables('rss_items');
        foreach ($links as $table) {
            foreach($result as $key => $val) {
                $link->deleteLink($table, 'rss_items', $val['id']);
            }

        }

        suxDB::commitTransaction($tid);
        $this->inTransaction = false;

    }


    /**
    * @param int $id feed id
    */
    function approveFeed($id) {

        if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1) return false;

        $st = $this->db->prepare("UPDATE {$this->db_feeds} SET draft = false WHERE id = ? ");
        $st->execute(array($id));

        $st = $this->db->prepare("SELECT url FROM {$this->db_feeds} WHERE id = ? ");
        $st->execute(array($id));

        if ($url = $st->fetch(PDO::FETCH_ASSOC)) {
            $this->deleteCache($url['url']);
        }

    }


    /**
    * Purge feeds
    *
    * @param string $date YYYY-MM-DD
    * @param int $feed_id optional feed id
    */
    function purgeFeeds($date, $feed_id = null) {

        if (filter_var($feed_id, FILTER_VALIDATE_INT) && $feed_id > 0) {
            // With feed_id
            $query = "DELETE FROM {$this->db_items} WHERE published_on < ? AND rss_feeds_id = ? ";
            $st = $this->db->prepare($query);
            $st->execute(array($date, $feed_id));
        }
        else {
            // Without
            $query = "DELETE FROM {$this->db_items} WHERE published_on < ? ";
            $st = $this->db->prepare($query);
            $st->execute(array($date));
        }

    }


    /**
    * Get a item by id
    *
    * @param int $id messages_id
    * @return array|false
    */
    function getItemByID($id) {

        // Sanity check
        if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1)
            throw new Exception('Invalid item id');

        $query = "SELECT * FROM {$this->db_items} WHERE id = ? ";
        $st = $this->db->prepare($query);
        $st->execute(array($id));

        $item = $st->fetch(PDO::FETCH_ASSOC);
        if ($item) return $item;
        else return false;

    }


    /**
    * Get RSS items
    *
    * @param int $limit sql limit value
    * @param int $start sql start of limit value
    * @param int $feed_id feed id
    * @return array
    */
    function getItems($limit = null, $start = 0, $feed_id = null) {

        $query = "SELECT * FROM {$this->db_items} ";
        if ($feed_id) {
            if (filter_var($feed_id, FILTER_VALIDATE_INT) && $feed_id > 0) {
                $query .= "WHERE rss_feeds_id = $feed_id ";
            }
            else throw new Exception('Invalid feed id');
        }
        $query .= "ORDER BY published_on DESC, id DESC "; // Order

        // Limit
        if ($start && $limit) $query .= "LIMIT {$limit} OFFSET {$start} ";
        elseif ($limit) $query .= "LIMIT {$limit} ";

        // Execute
        $st = $this->db->query($query);
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }


    // --------------------------------------------------------------------
    // RSS Output
    // --------------------------------------------------------------------


    /**
    * Set RSS feed, example usage:
    *
    * $rss = new suxRSS();
    * $rss->outputRSS('Channel Title', 'http://www.example.org', 'Channel Description');
    * $rss->addOutputItem('Item 1', 'http://www.example.org/item1', 'Item 1 Description');
    * $rss->addOutputItem('Item 2', 'http://www.example.org/item2', 'Item 2 Description');
    * echo $rss->saveXML();
    *
    * @param string $title the channel title
    * @param string $link the URL to the source of the RSS feed, i.e. the website home page
    * @param string $description the channel description
    */
    function outputRSS($title, $link, $description) {

        $root = $this->appendChild($this->createElement('rss'));
        $root->setAttribute('version', '2.0');

        $channel = $root->appendChild($this->createElement('channel'));

        $channel->appendChild($this->createElement('title', htmlspecialchars($title, ENT_QUOTES, 'UTF-8', false)));
        $channel->appendChild($this->createElement('link', htmlspecialchars($link, ENT_QUOTES, 'UTF-8', false)));
        $channel->appendChild($this->createElement('description', htmlspecialchars($description, ENT_QUOTES, 'UTF-8', false)));

        $this->channel = $channel;

    }


    /**
    * Add an item to the feed
    *
    * @param string $title the item title
    * @param string $link the URL to the source of the RSS item, i.e. the unique content
    * @param string $description the item description
    */
    public function addOutputItem($title, $link, $description) {

        $item = $this->createElement('item');
        $item->appendChild($this->createElement('title', htmlspecialchars($title, ENT_QUOTES, 'UTF-8', false)));
        $item->appendChild($this->createElement('link', htmlspecialchars($link, ENT_QUOTES, 'UTF-8', false)));
        $item->appendChild($this->createElement('description', htmlspecialchars($description, ENT_QUOTES, 'UTF-8', false)));

        $this->channel->appendChild($item);
    }


    // --------------------------------------------------------------------
    // RSS Retrieval
    // --------------------------------------------------------------------


    /**
    * Fetch RSS feed
    *
    * @param string $rss_url a URL to an RSS Feed
    * @return array
    */
    function fetchRSS($rss_url) {

        // Sanity Check
        if (!$this->cache_dir || !is_dir($this->cache_dir) && !mkdir($this->cache_dir, 0777, true)) {
            throw new Exception('Invalid cache directory');
        }

        // Canonicalize Url
        $rss_url = suxFunct::canonicalizeUrl($rss_url);

        // Go
        $cache_file = $this->cache_dir . '/' . md5($rss_url);
        $result = false;
        $timefile = null;

        if (is_file($cache_file)) {
            // Found Cache
            $timefile = filemtime($cache_file);
            $timedif = time() - $timefile;
            if ($timedif < $this->cache_time) {
                // Use Cache
                $result = unserialize(file_get_contents($cache_file));
            }
        }


        if ($result) $result['cached'] = 1; // Succesful Cache
        else {
            // No cache was found, or used
            $result = $this->parse($rss_url, $timefile);
            if ($result)  {
                // Cache for next time
                $serialized = serialize($result);
                if (!file_put_contents($cache_file, $serialized)) {
                    throw new Exception("Unable to write $cache_file");
                }
                $result['cached'] = 0;
            }
        }


        if (!$result && is_file($cache_file)) {

            // Still no result, probably recieved a  304 (not modified)
            // response from the server, use the cache

            // touch($cache_file); // Reset time for caching?
            $result = unserialize(file_get_contents($cache_file));
            $result['cached'] = 1;

        }

        return $result;
    }


    /**
    * Delete an RSS cache
    *
    * @param string $rss_url a URL to an RSS Feed
    */
    private function deleteCache($rss_url) {

        // Canonicalize Url
        $rss_url = suxFunct::canonicalizeUrl($rss_url);

        $cache_file = $this->cache_dir . '/' . md5($rss_url);

        if (is_file($cache_file)) unlink($cache_file);

    }


    /**
    * Modification of preg_match(); return trimed field with index 1 from
    * 'classic' preg_match() array output
    *
    * @param string $pattern regular expression
    * @param string $subject subject
    * @return string
    */
    private function myPregMatch($pattern, $subject) {

        $out = array();
        preg_match($pattern, $subject, $out);

        // if there is NO result, return empty string
        if(!isset($out[1])) return '';

        // Otherwise, there is some result... process it and return it
        if ($this->CDATA == 'content') {
            // Get CDATA content (without CDATA tag)
            $out[1] = strtr($out[1], array('<![CDATA['=>'', ']]>'=>''));
        }

        // If not UTF-8, convert to UTF-8
        if (mb_strtoupper($this->rsscp) != 'UTF-8') {
            $out[1] = $this->convertToUTF8($out[1]);
        }

        // Return result
        return trim($out[1]);

    }


    /**
    * Modification of preg_match_all(), see myPregMatch()
    *
    * @param string $pattern regular expression
    * @param string $subject subject
    * @return string
    */
    private function myPregMatchAll($pattern, $subject) {

        $out = array();
        preg_match_all($pattern, $subject, $out);

        // if there is NO result, return empty string
        if(!isset($out[1])) return '';

        // Otherwise, there is some result... process it and return it
        $concat = '';
        foreach($out[1] as $val) {
            if ($this->CDATA == 'content') {
                // Get CDATA content (without CDATA tag)
                $concat .= strtr($val, array('<![CDATA['=>'', ']]>'=>''));
            }
            else {
                $concat .= $val;
            }
            $concat .= ', '; // Seperate with a comma
        }

        $concat = rtrim($concat, ', '); // Remove trailing comma

        // If not UTF-8, convert to UTF-8
        if (mb_strtoupper($this->rsscp) != 'UTF-8') {
            $concat = $this->convertToUTF8($concat);
        }

        // Return result
        return trim($concat);

    }


    /**
    * Convert to UTF-8
    *
    * @param string $text
    * @return string
    */
    private function convertToUTF8($text) {

        if (function_exists('iconv')) {
            $text = @iconv($this->rsscp, 'UTF-8//TRANSLIT', $text);
        }
        else {
            $text = @mb_convert_encoding($text, 'UTF-8', $this->rsscp);
        }

        return $text;
    }


    /**
    * Load and parse RSS file
    *
    * @global string $CONFIG['TIMEZONE']
    * @param string $rss_url
    * @param int $timestamp unix timestamp
    * @return array|false
    */
    private function parse($rss_url, $timestamp = null) {

        $result = [];
        // Sanity check
        if (!filter_var($rss_url, FILTER_VALIDATE_URL)) return false;
        $timestamp = filter_var($timestamp, FILTER_VALIDATE_INT);

        // --------------------------------------------------------------------
        // Extablish Conditional GET
        // --------------------------------------------------------------------

        $modified = null;
        $opts = array();

        if ($timestamp) {
            date_default_timezone_set('GMT');
            $modified = date('D, d M Y H:i:s', $timestamp) . ' GMT';
            date_default_timezone_set($GLOBALS['CONFIG']['TIMEZONE']);
        }

        if ($modified) {
            // file_get_contents() compatible headers for Conditional GET
            $opts = array(
                'http'=> array(
                    'header' => "If-Modified-Since: $modified\r\n",
                    )
                );
        }

        // --------------------------------------------------------------------
        // Backtrack_limit is too restrictive for complex feeds, boost it
        // --------------------------------------------------------------------

        ini_set('pcre.backtrack_limit', 999999);

        // --------------------------------------------------------------------
        // Parse
        // --------------------------------------------------------------------

        if (ini_get('allow_url_fopen')) {
            // file_get_contents
            ini_set('default_socket_timeout', 30);
            $ctx = stream_context_create($opts);
            $rss_content = @file_get_contents($rss_url, null, $ctx);
        }
        elseif(function_exists('curl_init')) {
            // cURL
            $ch = curl_init();
            if ($modified) curl_setopt($ch, CURLOPT_HTTPHEADER, $opts['http']['header']);
            curl_setopt($ch, CURLOPT_URL, $rss_url);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $rss_content = curl_exec($ch);
            curl_close($ch);
        }
        else {
            throw new Exception('No way to retrieve RSS feeds');
        }


        if ($rss_content) {

            // Parse document encoding
            $result['encoding'] = $this->myPregMatch("'encoding=[\'\"](.*?)[\'\"]'si", $rss_content);

            if ($result['encoding'] != '') {
                // if document codepage is specified, use it
                // This is used in myPregMatch()
                $this->rsscp = $result['encoding'];
            }
            else {
                // otherwise use UTF-8
                // This is used in myPregMatch()
                $this->rsscp = 'UTF-8';
            }


            // ---------------------------------------------------------------
            // Parse CHANNEL info
            // ---------------------------------------------------------------

            // Init some variables
            $is_atom = false;
            $out_channel = array();

            preg_match("'<channel.*?>(.*?)</channel>'si", (string) $rss_content, $out_channel);
            if (!count($out_channel)) {
                // Maybe this is an Atom feed? Parse FEED info
                preg_match("'<feed.*?>(.*?)</feed>'si", (string) $rss_content, $out_channel);
                if (count($out_channel)) $is_atom = true;
                else return false; // This isn't an RSS/Atom feed, abort
            }

            foreach($this->channeltags as $channeltag) {

                if ($is_atom && isset($this->channeltags_atom[$channeltag])) {
                    // Atom specific tag
                    if (is_array($this->channeltags_atom[$channeltag])) {
                        foreach ($this->channeltags_atom[$channeltag] as $tmp_tag) {
                            $temp = $this->myPregMatch("'<$tmp_tag.*?>(.*?)</$tmp_tag>'si", @$out_channel[1]);
                            if (!empty($temp)) break;
                        }
                    }
                    else {
                        $temp = $this->myPregMatch("'<{$this->channeltags_atom[$channeltag]}.*?>(.*?)</{$this->channeltags_atom[$channeltag]}>'si", @$out_channel[1]);
                    }
                }
                else {
                    if ($is_atom && $channeltag == 'link') {
                        // Yet more Atom tom-fuckery
                        $temp = $this->myPregMatch('#<link[\s]+[^>]*?href[\s]?=[\s"\']+(.*?)["\']+.*?/>#si', @$out_channel[1]);
                    }
                    else {
                        // RSS compatible channel tag
                        $temp = $this->myPregMatch("'<$channeltag.*?>(.*?)</$channeltag>'si", @$out_channel[1]);
                    }
                }

                if (!empty($temp)) $result[$channeltag] = $temp; // Set only if not empty
            }


            // If date_format is specified and lastBuildDate is valid
            if ($this->date_format != '' && isset($result['lastBuildDate']) && ($timestamp = strtotime($result['lastBuildDate'])) !== -1) {
                // convert lastBuildDate to specified date format
                $result['lastBuildDate'] = date($this->date_format, $timestamp);
            }

            // ---------------------------------------------------------------
            // Parse TEXTINPUT info
            // ---------------------------------------------------------------

            $out_textinfo = array();
            preg_match("'<textinput(|[^>]*[^/])>(.*?)</textinput>'si", (string) $rss_content, $out_textinfo);

            // This a little strange regexp means:
            // Look for tag <textinput> with or without any attributes, but skip truncated version <textinput /> (it's not beggining tag)
            if (isset($out_textinfo[2])) {
                foreach($this->textinputtags as $textinputtag) {
                    $temp = $this->myPregMatch("'<$textinputtag.*?>(.*?)</$textinputtag>'si", $out_textinfo[2]);
                    if (!empty($temp)) $result['textinput_'.$textinputtag] = $temp; // Set only if not empty
                }
            }

            // ---------------------------------------------------------------
            // Parse IMAGE info
            // ---------------------------------------------------------------

            $out_imageinfo = array();
            preg_match("'<image.*?>(.*?)</image>'si", (string) $rss_content, $out_imageinfo);
            if (isset($out_imageinfo[1])) {
                foreach($this->imagetags as $imagetag) {
                    $temp = $this->myPregMatch("'<$imagetag.*?>(.*?)</$imagetag>'si", $out_imageinfo[1]);
                    if (!empty($temp)) $result['image_'.$imagetag] = $temp; // Set only if not empty
                }
            }

            // ---------------------------------------------------------------
            // Parse ITEMS
            // ---------------------------------------------------------------

            $items = array();
            if ($is_atom) preg_match_all("'<entry(| .*?)>(.*?)</entry>'si", (string) $rss_content, $items); // Atom
            else preg_match_all("'<item(| .*?)>(.*?)</item>'si", (string) $rss_content, $items); // RSS
            $rss_items = $items[2];
            $i = 0;
            $result['items'] = array(); // create array even if there are no items

            foreach($rss_items as $rss_item) if ($i < $this->items_limit || $this->items_limit == 0) {

                // ---------------------------------------------------------------
                // Go through each $itemtags and collect the data
                // ---------------------------------------------------------------

                foreach($this->itemtags as $itemtag) {

                    if ($itemtag == 'category') $tmp_funct = 'myPregMatchAll'; // Concatenate
                    else $tmp_funct = 'myPregMatch';

                    if ($is_atom && isset($this->itemtags_atom[$itemtag])) {
                        // Atom specific tag
                        if (is_array($this->itemtags_atom[$itemtag])) {
                            foreach ($this->itemtags_atom[$itemtag] as $tmp_tag) {
                                $temp = $this->$tmp_funct("'<$tmp_tag.*?>(.*?)</$tmp_tag>'si", $rss_item);
                                if (!empty($temp)) break;
                            }
                        }
                        else {
                            $temp = $this->$tmp_funct("'<{$this->itemtags_atom[$itemtag]}.*?>(.*?)</{$this->itemtags_atom[$itemtag]}>'si", $rss_item);
                        }
                    }
                    else {
                        if ($is_atom && $itemtag == 'link') {
                            // Yet more Atom tom-fuckery
                            $temp = $this->$tmp_funct('#<link[\s]+[^>]*?href[\s]?=[\s"\']+(.*?)["\']+.*?/>#si', $rss_item);
                        }
                        else {
                            // RSS compatible item tag
                            $temp = $this->$tmp_funct("'<$itemtag.*?>(.*?)</$itemtag>'si", $rss_item);
                        }
                    }

                    // Check if link is valid
                    if ($itemtag == 'link' && !suxFunct::canonicalizeUrl($temp)) {

                        // Seriously? An invalid URL? And I'm supposed to care?
                        // Why do I feel like this is some sort abusive dad pushing
                        // their kids to play hockey against their wishes thing?

                        $pattern = '#<.*?xml:base[\s]?=[\s"\']+(.*?)["\']+#i';
                        $out_baseurl = array();
                        // Attempt 1) Look for xml:base in this node
                        preg_match($pattern, (string) $rss_item, $out_baseurl);
                        if (!isset($out_baseurl[1]) || !suxFunct::canonicalizeUrl($out_baseurl[1])) {
                            // Attempt 2) Look for xml:base anywhere, starting from the begining of the document
                            preg_match($pattern, (string) $rss_content, $out_baseurl);
                            if (!isset($out_baseurl[1]) || !suxFunct::canonicalizeUrl($out_baseurl[1])) {
                                // Attempt 3) Look for the channel <link> and see if that's a real url
                                if (isset($result['link']) && suxFunct::canonicalizeUrl($result['link'])) {
                                    $out_baseurl[1] = $result['link'];
                                }
                            }
                        }
                        if (isset($out_baseurl[1])) {

                            $temp = trim($temp);
                            $temp = trim($temp, '/');

                            $temp2 = parse_url($out_baseurl[1]);
                            if (isset($temp2['port'])) $temp2 = "{$temp2['scheme']}://{$temp['host']}:{$temp2['port']}";
                            else $temp2 = "{$temp2['scheme']}://{$temp2['host']}";

                            $temp = "{$temp2}/{$temp}";
                            $temp = suxFunct::canonicalizeUrl($temp);

                        }

                    }

                    // Stack it
                    if (!empty($temp)) $result['items'][$i][$itemtag] = $temp;

                }

                // ---------------------------------------------------------------
                // Make some adjustments
                // ---------------------------------------------------------------

                // If date_format is specified and pubDate is valid
                if ($this->date_format != '' && isset($result['items'][$i]['pubDate']) && ($timestamp = strtotime((string) $result['items'][$i]['pubDate'])) !== -1) {
                    // convert pubDate to specified date format
                    $result['items'][$i]['pubDate'] = date($this->date_format, $timestamp);
                }
                else {
                    unset($result['items'][$i]['pubDate']);
                }

                // Item counter
                $i++;

            }

            // Don't trust data from external website, sanitize everything
            array_walk_recursive($result, array($this, 'sanitizeByReference'));

            $result['items_count'] = $i;
            // new dBug($result);
            // exit;
            return $result;

        }
        else {
            // Error in opening return False
            return false;
        }
    }


    /**
    * array_walk_recursive wrapper to sanitizeHtml()
    *
    * array_walk needs to be working with the actual values of the array,
    * so the parameter of funcname is specified as a reference (i.e. &)
    *
    * @param string &$value
    */
    private function sanitizeByReference(&$value) {

        // Reverse htmlentities, we want usable html
        $value = html_entity_decode(stripslashes($value), ENT_QUOTES, 'UTF-8');
        // Get rid of font tags before handing off to htmLawed,
        // see: http://www.bioinformatics.org/phplabware/forum/viewtopic.php?id=64
        $value = preg_replace('/<font([^>]+)>/i', '', $value);
        $value = str_ireplace('</font>', '', $value);
        // Sanitize
        $value = suxFunct::sanitizeHtml($value, 0);

    }


    // --------------------------------------------------------------------
    // Exception Handler
    // --------------------------------------------------------------------


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

