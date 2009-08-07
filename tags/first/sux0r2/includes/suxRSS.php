<?php

/**
* suxRSS
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

    // Folder in which cached data should be stored
    public $cache_dir = '/tmp';

    // Allows to set how to proceed CDATA information.
    // nochange = default value; don't make any changes
    // strip = completely strip CDATA information
    // content = get CDATA content (without CDATA tag)
	public $CDATA = 'nochange';

    // Allows limit number of returned items. 0 (zero) means "no limit"
	public $items_limit = 0;

    // Set stripHTML = true to strip HTML code from RSS content.
	public $stripHTML = false;

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


    function __construct() {

        // extends DOMDocument
        parent::__construct();
        $this->formatOutput = true;

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

        $channel->appendChild($this->createElement('title', $title));
        $channel->appendChild($this->createElement('link', $link));
        $channel->appendChild($this->createElement('description', $description));

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
        @$item->appendChild($this->createElement('title', $title));
        @$item->appendChild($this->createElement('link', $link));
        @$item->appendChild($this->createElement('description', $description));

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
        if (!$this->cache_dir || !is_dir($this->cache_dir)) {
            throw new Exception('Invalid cache directory');
        }

        // Go
        $cache_file = $this->cache_dir . '/rsscache_' . md5($rss_url);
        $result = false;
        $timefile = null;

        if (is_file($cache_file)) {
            // Found Cache
            $timefile = filemtime($cache_file);
            $timedif = time() - $timefile;
            if ($timedif < $this->cache_time) {
                // USe Cache
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

		// if there is some result... process it and return it
		if(isset($out[1])) {

			if ($this->CDATA == 'content') {
                // Get CDATA content (without CDATA tag)
				$out[1] = strtr($out[1], array('<![CDATA['=>'', ']]>'=>''));
			}
            elseif ($this->CDATA == 'strip') {
                // Strip CDATA
				$out[1] = strtr($out[1], array('<![CDATA['=>'', ']]>'=>''));
			}

			// If not UTF-8, convert to UTF-8
			if (mb_strtoupper($this->rsscp) != 'UTF-8')
                $out[1] = iconv($this->rsscp, 'UTF-8//TRANSLIT', $out[1]);

            // Return result
			return trim($out[1]);
		}
        else {
            // if there is NO result, return empty string
			return '';
		}
	}


	/**
	* Replace HTML entities &something; by real characters
    *
    * @param string $string
    * @return string
	*/
	private function unhtmlentities($string) {
		// Get HTML entities table
		$trans_tbl = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES);
		// Flip keys<==>values
		$trans_tbl = array_flip($trans_tbl);
		// Add support for &apos; entity (missing in HTML_ENTITIES)
		$trans_tbl += array('&apos;' => "'");
		// Replace entities by values
		return strtr($string, $trans_tbl);
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
        if (!ctype_digit(strval($timestamp))) $timestamp = null;

        // --------------------------------------------------------------------
        // Extablish Conditional GET and Timeout context
        // --------------------------------------------------------------------

        if ($timestamp) $modified = gmdate('D, d M Y H:i:s', $timestamp) . ' GMT';
        else $modified = gmdate('D, d M Y H:i:s') . ' GMT';

        $opts = array(
            'http'=> array(
                'header' => "If-Modified-Since: $modified\r\n",
                'timeout' => 120,
                )
            );

        $ctx = stream_context_create($opts);

        // --------------------------------------------------------------------
        // Parse
        // --------------------------------------------------------------------

        if ($rss_content = file_get_contents($rss_url, null, $ctx)) {

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
				$temp = $this->myPregMatch("'<$channeltag.*?>(.*?)</$channeltag>'si", $out_channel[1]);
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
						$temp = $this->myPregMatch("'<$itemtag.*?>(.*?)</$itemtag>'si", $rss_item);
						if ($temp != '') $result['items'][$i][$itemtag] = $temp; // Set only if not empty
					}

                    // Strip HTML tags and other bullshit from DESCRIPTION
					if ($this->stripHTML && $result['items'][$i]['description'])
						$result['items'][$i]['description'] = strip_tags($this->unhtmlentities(strip_tags($result['items'][$i]['description'])));

                    // Strip HTML tags and other bullshit from TITLE
					if ($this->stripHTML && $result['items'][$i]['title'])
						$result['items'][$i]['title'] = strip_tags($this->unhtmlentities(strip_tags($result['items'][$i]['title'])));

                    // If date_format is specified and pubDate is valid
					if ($this->date_format != '' && ($timestamp = strtotime($result['items'][$i]['pubDate'])) !==-1) {
						// convert pubDate to specified date format
						$result['items'][$i]['pubDate'] = date($this->date_format, $timestamp);
					}

					// Item counter
					$i++;
				}
			}

			$result['items_count'] = $i;
			return $result;
		}
		else {
            // Error in opening return False
			return false;
		}
	}
}

?>