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
* Inspired by:
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
	public $cache_time = 3600;

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
	public $date_format = '';

    // getRSS Tags
    protected $channeltags = array('title', 'link', 'description', 'language', 'copyright', 'managingEditor', 'webMaster', 'lastBuildDate', 'rating', 'docs');
	protected $itemtags = array('title', 'link', 'description', 'author', 'category', 'comments', 'enclosure', 'guid', 'pubDate', 'source');
	protected $imagetags = array('title', 'url', 'link', 'width', 'height');
	protected $textinputtags = array('title', 'description', 'name', 'link');

    // RSS Code Page / Encoding
    private $rsscp;

    // Channel
    private $channel;

    // --------------------------------------------------------------------
    // Functions
    // --------------------------------------------------------------------


    /**
    * Constructor
    *
    */
    function __construct() {

        parent::__construct(); // DOMDocument
        $this->formatOutput = true; // DOMDocument

        // Cache
        $this->cache_dir = dirname(__FILE__)  . '/../temporary/rss_cache';

    }


    /**
    * set RSS feed, example usage:
    *
    * $rss = new suxRSS();
    * $rss->setRSS('Channel Title', 'http://www.example.org', 'Channel Description');
    * $rss->addItem('Item 1', 'http://www.example.org/item1', 'Item 1 Description');
    * $rss->addItem('Item 2', 'http://www.example.org/item2', 'Item 2 Description');
    * echo $rss->saveXML();
    *
    * @param string $title the channel title
    * @param string $link the URL to the source of the RSS feed, i.e. the website home page
    * @param string $description the channel description
    */
    function setRSS($title, $link, $description) {

        $root = $this->appendChild($this->createElement('rss'));
        $root->setAttribute('version', '2.0');

        $channel= $root->appendChild($this->createElement('channel'));

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
    public function addItem($title, $link, $description) {

        $item = $this->createElement('item');
        $item->appendChild($this->createElement('title', htmlspecialchars($title, ENT_QUOTES, 'UTF-8', false)));
        $item->appendChild($this->createElement('link', htmlspecialchars($link, ENT_QUOTES, 'UTF-8', false)));
        $item->appendChild($this->createElement('description', htmlspecialchars($description, ENT_QUOTES, 'UTF-8', false)));

        $this->channel->appendChild($item);
    }


	/**
	* Get an RSS feed
    *
    * @param string $rss_url a URL to an RSS Feed
    * @return array
	*/
	function getRSS($rss_url) {

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

            touch($cache_file); // Reset time for caching
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
    * @param string $rss_url
    * @param int $timestamp unix timestamp
    * @return array|false
	*/
	private function parse($rss_url, $timestamp = null) {

        // Sanity check
        $timestamp = filter_var($timestamp, FILTER_VALIDATE_INT);

        // --------------------------------------------------------------------
        // Extablish Conditional GET
        // --------------------------------------------------------------------

        if ($timestamp) $modified = gmdate('D, d M Y H:i:s', $timestamp) . ' GMT';
        else $modified = null;

        $opts = array(
            'http'=> array(
                'header' => "If-Modified-Since: $modified\r\n",
                )
            );

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
			if ($this->date_format != '' && ($timestamp = strtotime($result['lastBuildDate'])) !== -1) {
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
					if ($this->date_format != '' && ($timestamp = strtotime($result['items'][$i]['pubDate'])) !==-1) {
						// convert pubDate to specified date format
						$result['items'][$i]['pubDate'] = date($this->date_format, $timestamp);
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
        // Sanitize
        $value = suxFunct::sanitizeHtml($value);

    }

}

?>