<?php

/**
* suxBookmarks
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

class suxBookmarks {

    // Database suff
    protected $db;
    protected $inTransaction = false;
    protected $db_table = 'bookmarks';


    /**
    * @param string $key a key from our suxDB DSN
    */
    function __construct($key = null) {
    	$this->db = suxDB::get($key);
        set_exception_handler(array($this, 'logAndDie'));
    }


  function setBookmark($url, $title, $desc) {

        // --------------------------------------------------------------------
        // Sanitize
        // --------------------------------------------------------------------

        $url = filter_var($url, FILTER_SANITIZE_URL);

        // No HTML in title
        $title = strip_tags($title);

        // Sanitize HTML in desc
        require_once(dirname(__FILE__) . '/suxFunct.php');
        $desc = suxFunct::sanitizeHtml($desc);

        // Convert and copy desc to UTF-8 plaintext
        require_once(dirname(__FILE__) . '/suxHtml2UTF8.php');
        $converter = new suxHtml2UTF8($desc);
        $desc_plaintext = $converter->getText();


        // --------------------------------------------------------------------
        // Go!
        // --------------------------------------------------------------------

        $st = $this->db->prepare("SELECT COUNT(*) FROM {$this->db_table} WHERE url = ? LIMIT 1 ");
        $st->execute(array($url));

        $bookmark = array(
            'url' => $url,
            'title' => $title,
            'description_html' => $desc,
            'description_plaintext' => $desc_plaintext,
            );

        if ($st->fetchColumn() > 0) {
            // UPDATE
            $query = suxDB::prepareUpdateQuery($this->db_table, $bookmark, 'url');
            $st = $this->db->prepare($query);
            return $st->execute($bookmark);

        }
        else {
            // INSERT
            $query = suxDB::prepareInsertQuery($this->db_table, $bookmark);
            $st = $this->db->prepare($query);
            return $st->execute($bookmark);
        }


    }


    function deleteBookmark($id) {

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

        $message = "suxBookmarks Error: \n";
        $message .= $e->getMessage() . "\n";
        $message .= "File: " . $e->getFile() . "\n";
        $message .= "Line: " . $e->getLine() . "\n\n";
        $message .= "Backtrace: \n" . print_r($e->getTrace(), true) . "\n\n";
        die("<pre>{$message}</pre>");

    }


}

/*

-- Database

CREATE TABLE `bookmarks` (
  `id` int(11) NOT NULL auto_increment,
  `url` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description_html` text,
  `description_plaintext` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `url` (`url`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


*/

?>