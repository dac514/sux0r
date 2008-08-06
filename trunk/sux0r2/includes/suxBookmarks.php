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
    protected $db_driver;
    // InnoDB
    protected $db_table = 'bookmarks';
    protected $db_table_hist = 'bookmarks_history';



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
    function getBookmark($id, $unpub = false) {

        $col = 'id';
        if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1) {
            $col = 'url';
            $id = suxFunct::canonicalizeUrl($id);
        }

        $query = "SELECT * FROM {$this->db_table} WHERE {$col} = ? ";
        if (!$unpub) {
            // Only show published items
            $query .= "AND draft = 0 ";
            if ($this->db_driver == 'mysql') {
                // MySql
                $query .= "AND NOT published_on > '" . date('Y-m-d H:i:s') . "' ";
            }
            else {
                throw new Exception('Unsupported database driver');
            }
        }
        $query .= 'LIMIT 1 ';

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
    * @param array $url required keys => (url, title, body) optional keys => (published_on, draft)
    * @param int $trusted passed on to sanitizeHtml()
    * @return int insert id
    */
    function saveBookmark($users_id, array $url, $trusted = -1) {

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
        require_once(dirname(__FILE__) . '/suxHtml2UTF8.php');
        $converter = new suxHtml2UTF8($clean['body_html']);
        $clean['body_plaintext']  = $converter->getText();

        // Publish date
        if (isset($url['published_on'])) {
            // ISO 8601 date format
            // regex must match '2008-06-18 16:53:29' or '2008-06-18T16:53:29-04:00'
            $regex = '/^(\d{4})-(0[0-9]|1[0,1,2])-([0,1,2][0-9]|3[0,1]).+(\d{2}):(\d{2}):(\d{2})/';
            if (!preg_match($regex, $url['published_on'])) throw new Exception('Invalid date');
            $clean['published_on'] = $url['published_on'];
        }
        else $clean['published_on'] = date('c');

        // Draft, boolean / tinyint
        $clean['draft'] = 0;
        if (isset($url['draft'])) $clean['draft'] = 1;

        // We now have the $clean[] array

        // --------------------------------------------------------------------
        // Go!
        // --------------------------------------------------------------------

        // Begin transaction
        $this->db->beginTransaction();
        $this->inTransaction = true;

        // Get $edit[] array in order to keep a history
        $query = "SELECT id, url, title, body_html, body_plaintext FROM {$this->db_table} WHERE url = ? LIMIT 1 ";
        $st = $this->db->prepare($query);
        $st->execute(array($clean['url']));
        $edit = $st->fetch(PDO::FETCH_ASSOC);

        if ($edit) {

            // UPDATE

            $id = $edit['id'];
            $edit['users_id'] = $clean['users_id'];
            $edit['edited_on'] = date('c');

            $query = suxDB::prepareInsertQuery($this->db_table_hist, $edit);
            $st = $this->db->prepare($query);
            $st->execute($edit);

            unset($clean['users_id']); // Don't override the original publisher

            $query = suxDB::prepareUpdateQuery($this->db_table, $clean, 'url');
            $st = $this->db->prepare($query);
            $st->execute($clean);

        }
        else {

            // INSERT

            $query = suxDB::prepareInsertQuery($this->db_table, $clean);
            $st = $this->db->prepare($query);
            $st->execute($clean);
            // MySQL InnoDB with transaction reports the last insert id as 0 after
            // commit, the real ids are only reported before committing.
            $id = $this->db->lastInsertId();

        }

        // Commit
        $this->db->commit();
        $this->inTransaction = false;

        return $id;

    }


    /**
    * Delete bookmark
    *
    * @param int $id bookmarks_id
    */
    function deleteBookmark($id) {

        if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1) return false;

        // Begin transaction
        $this->db->beginTransaction();
        $this->inTransaction = true;

        $st = $this->db->prepare("DELETE FROM {$this->db_table} WHERE id = ? LIMIT 1 ");
        $st->execute(array($id));

        $st = $this->db->prepare("DELETE FROM {$this->db_table_hist} WHERE bookmarks_id = ? ");
        $st->execute(array($id));

        // Commit
        $this->db->commit();
        $this->inTransaction = false;

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