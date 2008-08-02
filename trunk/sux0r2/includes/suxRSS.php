<?php

/**
* suxRSS
*
* This program is free software: you can redistribute it and/or modifyc
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
* Forked from / Inspired by:
* Vojtech Semecky: http://lastrss.oslab.net/
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @copyright  2008 sux0r development group
* @license    http://www.gnu.org/licenses/agpl.html
*
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

    // fetchRSS Tags
    protected $channeltags = array('title', 'link', 'description', 'language', 'copyright', 'managingEditor', 'webMaster', 'lastBuildDate', 'rating', 'docs');
	protected $itemtags = array('title', 'link', 'description', 'author', 'category', 'comments', 'enclosure', 'guid', 'pubDate', 'source');
	protected $imagetags = array('title', 'url', 'link', 'width', 'height');
	protected $textinputtags = array('title', 'description', 'name', 'link');

    // RSS Code Page / Encoding
    private $rsscp;

    // Channel
    private $channel;

    // --------------------------------------------------------------------
    // Database stuff
    // --------------------------------------------------------------------

    protected $db;
    protected $inTransaction = false;
    protected $db_feeds = 'rss_feeds';
    protected $db_items = 'rss_items';
    protected $db_driver; // database type


    /**
    * Constructor
    */
    function __construct() {

        parent::__construct(); // DOMDocument
        $this->formatOutput = true; // DOMDocument

        // Cache
        $this->cache_dir = dirname(__FILE__)  . '/../temporary/rss_cache';

        // Db
    	$this->db = suxDB::get();
        $this->db_driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        set_exception_handler(array($this, 'exceptionHandler'));

    }


    // --------------------------------------------------------------------
    // Database accesors
    // --------------------------------------------------------------------

    /**
    * Cron, fetch RSS items and insert them into the database
    */
    function cron() {

        require_once('suxHtml2UTF8.php');

        $q = "SELECT id, url FROM {$this->db_feeds} WHERE draft = 0 ";
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
                        $st2->execute(array($clean['url']));
                        if ($st2->fetchColumn() > 0) continue; // Already in DB, skip

                        // Set the rest of our array
                        $clean['rss_feeds_id'] = $row['id'];
                        $clean['title'] = strip_tags($item['title']);
                        $clean['body_html'] = $item['description']; // suxRSS() sanitzes HTML
                        $converter = new suxHtml2UTF8($clean['body_html']);
                        $clean['body_plaintext']  = $converter->getText();
                        if (!empty($item['pubDate'])) $clean['published_on'] = $item['pubDate'];
                        else $clean['published_on'] = date('c');

                        // Insert
                        $q3 = suxDB::prepareInsertQuery($this->db_items, $clean);
                        $st3 = $this->db->prepare($q3);
                        $st3->execute($clean);

                    }
                }
            }
        }
    }


    /**
    * Get all published feeds
    *
    * @return array|false
    */
    function getFeeds() {

        $q = "SELECT * FROM {$this->db_feeds} WHERE draft = 0 ORDER BY title ASC ";
        $st = $this->db->query($q);
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }


    /**
    * Get a feed by id or url
    *
    * @param int|string $id feed id or url
    * @param bool $unpub select un-published?
    * @return array|false
    */
    function getFeed($id) {

        // Pick a query
        if (filter_var($id, FILTER_VALIDATE_INT) && $id > 0) {
            $query = "SELECT * FROM {$this->db_feeds} WHERE id = ? LIMIT 1 ";
        }
        else {
            $id = suxFunct::canonicalizeUrl($id);
            $query = "SELECT * FROM {$this->db_feeds} WHERE url = ? LIMIT 1 ";
        }

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
    * @param array $url required keys => (url, title, body) optional keys => (draft)
    * @param int $trusted passed on to sanitizeHtml()
    * @return int insert id
    */
    function saveFeed($users_id, array $url, $trusted = -1) {

        // -------------------------------------------------------------------
        // Sanitize
        // -------------------------------------------------------------------

        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id <= 0)
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
        require_once(dirname(__FILE__) . '/suxHtml2UTF8.php');
        $converter = new suxHtml2UTF8($clean['body_html']);
        $clean['body_plaintext']  = $converter->getText();

        // Draft, boolean / tinyint
        $clean['draft'] = 0;
        if (isset($url['draft'])) $clean['draft'] = 1;

        // We now have the $clean[] array

        // --------------------------------------------------------------------
        // Go!
        // --------------------------------------------------------------------

        // Check if this is an insert or an update
        $query = "SELECT id FROM {$this->db_feeds} WHERE url = ? LIMIT 1 ";
        $st = $this->db->prepare($query);
        $st->execute(array($clean['url']));
        $edit = $st->fetch(PDO::FETCH_ASSOC);

        if ($edit) {

            // UPDATE
            $query = suxDB::prepareUpdateQuery($this->db_feeds, $clean, 'url');
            $st = $this->db->prepare($query);
            $st->execute($clean);
            $id = $edit['id'];

        }
        else {

            // INSERT
            $query = suxDB::prepareInsertQuery($this->db_feeds, $clean);
            $st = $this->db->prepare($query);
            $st->execute($clean);
            $id = $this->db->lastInsertId();

        }

        return $id;

    }


    /**
    * Count RSS items
    *
    * @param int $feed_id feed id
    * @param string $type forum, blog, wiki, or slideshow
    * @param bool $unpub select un-published?
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
    * Get a item by id
    *
    * @param int $id messages_id
    * @param bool $unpub select un-published?
    * @return array|false
    */
    function getItem($id) {

        // Sanity check
        if (!filter_var($id, FILTER_VALIDATE_INT) || $id <= 0)
            throw new Exception('Invalid message id');

        $query = "SELECT * FROM {$this->db_items} WHERE id = ? LIMIT 1 ";
        $st = $this->db->prepare($query);
        $st->execute(array($id));

        $item = $st->fetch(PDO::FETCH_ASSOC);
        if ($item) return $item;
        else return false;

    }


    /**
    * Get RSS items
    *
    * @param int $feed_id feed id
    * @param int $limit sql limit value
    * @param int $start sql start of limit value
    * @return array
    */
    function getItems($feed_id = null, $limit = null, $start = 0) {

        $query = "SELECT * FROM {$this->db_items} ";
        if ($feed_id) {
            if (filter_var($feed_id, FILTER_VALIDATE_INT) && $feed_id > 0) {
                $query .= "WHERE rss_feeds_id = $feed_id ";
            }
            else throw new Exception('Invalid feed id');
        }
        $query .= "ORDER BY published_on DESC, id DESC "; // Order

        // Limit
        if ($start && $limit) $query .= "LIMIT {$start}, {$limit} ";
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
            $out[1] = @mb_convert_encoding($out[1], 'UTF-8', $this->rsscp);
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
            $concat = @mb_convert_encoding($concat, 'UTF-8', $this->rsscp);
        }

        // Return result
        return trim($concat);

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
            // Headers for Conditional GET
            $opts = array(
                'http'=> array(
                    'header' => "If-Modified-Since: $modified\r\n",
                    )
                );
        }

        ini_set('default_socket_timeout', 30);
        $ctx = stream_context_create($opts);

        // --------------------------------------------------------------------
        // Parse
        // --------------------------------------------------------------------

        if ($rss_content = @file_get_contents($rss_url, null, $ctx)) {

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

			// Parse CHANNEL info
            $out_channel = array();
			preg_match("'<channel.*?>(.*?)</channel>'si", $rss_content, $out_channel);
			foreach($this->channeltags as $channeltag) {
				$temp = $this->myPregMatch("'<$channeltag.*?>(.*?)</$channeltag>'si", @$out_channel[1]);
				if ($temp != '') $result[$channeltag] = $temp; // Set only if not empty
			}

			// If date_format is specified and lastBuildDate is valid
			if ($this->date_format != '' && isset($result['lastBuildDate']) && ($timestamp = strtotime($result['lastBuildDate'])) !== -1) {
                // convert lastBuildDate to specified date format
                $result['lastBuildDate'] = date($this->date_format, $timestamp);
			}

			// Parse TEXTINPUT info
            $out_textinfo = array();
			preg_match("'<textinput(|[^>]*[^/])>(.*?)</textinput>'si", $rss_content, $out_textinfo);

            // This a little strange regexp means:
            // Look for tag <textinput> with or without any attributes, but skip truncated version <textinput /> (it's not beggining tag)
			if (isset($out_textinfo[2])) {
				foreach($this->textinputtags as $textinputtag) {
					$temp = $this->myPregMatch("'<$textinputtag.*?>(.*?)</$textinputtag>'si", $out_textinfo[2]);
					if ($temp != '') $result['textinput_'.$textinputtag] = $temp; // Set only if not empty
				}
			}

			// Parse IMAGE info
            $out_imageinfo = array();
			preg_match("'<image.*?>(.*?)</image>'si", $rss_content, $out_imageinfo);
			if (isset($out_imageinfo[1])) {
				foreach($this->imagetags as $imagetag) {
					$temp = $this->myPregMatch("'<$imagetag.*?>(.*?)</$imagetag>'si", $out_imageinfo[1]);
					if ($temp != '') $result['image_'.$imagetag] = $temp; // Set only if not empty
				}
			}

			// Parse ITEMS
            $items = array();
			preg_match_all("'<item(| .*?)>(.*?)</item>'si", $rss_content, $items);
			$rss_items = $items[2];
			$i = 0;
			$result['items'] = array(); // create array even if there are no items
			foreach($rss_items as $rss_item) {

				// If number of items is lower then limit: Parse one item
				if ($i < $this->items_limit || $this->items_limit == 0) {

                    foreach($this->itemtags as $itemtag) {

                        $pattern = "'<$itemtag.*?>(.*?)</$itemtag>'si";
                        if ($itemtag == 'category') {
                            // Concatenate for category
                            $temp = $this->myPregMatchAll($pattern, $rss_item);
                        }
                        else $temp = $this->myPregMatch($pattern, $rss_item);

                        if (!empty($temp)) $result['items'][$i][$itemtag] = $temp; // Stack

					}

                    // If date_format is specified and pubDate is valid
					if ($this->date_format != '' && isset($result['items'][$i]['pubDate']) && ($timestamp = strtotime($result['items'][$i]['pubDate'])) !== -1) {
						// convert pubDate to specified date format
						$result['items'][$i]['pubDate'] = date($this->date_format, $timestamp);
					}
                    else {
                        unset($result['items'][$i]['pubDate']);
                    }

					// Item counter
					$i++;
				}
			}

            // Don't trust data from external website, sanitize everything
            array_walk_recursive($result, array($this, 'sanitizeByReference'));

			$result['items_count'] = $i;
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
    function exceptionHandler(Exception $e) {

        if ($this->db && $this->inTransaction) {
            $this->db->rollback();
            $this->inTransaction = false;
        }

        throw($e); // Hot potato!

    }

}

?>