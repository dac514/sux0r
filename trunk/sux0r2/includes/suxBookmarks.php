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

require_once(dirname(__FILE__) . '/suxLink.php');

class suxBookmarks {

    // Database suff
    protected $db;
    protected $inTransaction = false;
    protected $db_driver;
    // MyISAM (faster, no rollback)
    protected $db_table = 'bookmarks';


    /**
    * Constructor
    */
    function __construct() {

    	$this->db = suxDB::get();
        $this->db_driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        set_exception_handler(array($this, 'exceptionHandler'));

    }


    /**
    * Count bookmarks
    *
    * @param bool $unpub select un-published?
    * @return int
    */
    function countBookmarks($unpub = false) {

        // SQL Query
        $query = "SELECT COUNT(*) FROM {$this->db_table} ";

        // Publish / Draft
        if (!$unpub) {
            if ($this->db_driver == 'pgsql' || $this->db_driver == 'mysql') {
                // PgSql / MySql
                $query .= "WHERE draft = false ";
                $query .= "AND NOT published_on > '" . date('Y-m-d H:i:s') . "' ";
            }
            else {
                throw new Exception('Unsupported database driver');
            }
        }

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
    * @param bool $alphasort sort alphabetically?
    * @param bool $unpub select un-published?
    * @return array
    */
    function getBookmarks($limit = null, $start = 0, $alphasort = false, $unpub = false) {

        // SQL Query
        $query = "SELECT * FROM {$this->db_table} ";

        // Publish / Draft
        if (!$unpub) {
            if ($this->db_driver == 'pgsql' || $this->db_driver == 'mysql') {
                // PgSql / MySql
                $query .= "WHERE draft = false ";
                $query .= "AND NOT published_on > '" . date('Y-m-d H:i:s') . "' ";
            }
            else {
                throw new Exception('Unsupported database driver');
            }
        }

        // Order
        if ($alphasort) $query .= 'ORDER BY title ASC ';
        else $query .= 'ORDER BY published_on DESC ';

        // Limit
        if ($start && $limit) $query .= "LIMIT {$start}, {$limit} ";
        elseif ($limit) $query .= "LIMIT {$limit} ";

        // Execute
        $st = $this->db->prepare($query);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }


    /**
    * Get all published feeds
    *
    * @return array|false
    */
    function getUnpublishedBookmarks() {

        $q = "SELECT * FROM {$this->db_table} WHERE draft = true ORDER BY title ASC ";
        $st = $this->db->query($q);
        return $st->fetchAll(PDO::FETCH_ASSOC);

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
        
        // Publish / Draft
        if (!$unpub) {
            if ($this->db_driver == 'pgsql' || $this->db_driver == 'mysql') {
                // PgSql / MySql
                $query .= "AND draft = true ";
                $query .= "AND NOT published_on > '" . date('Y-m-d H:i:s') . "' ";
            }
            else {
                throw new Exception('Unsupported database driver');
            }
        }

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
            if (!preg_match($regex, $url['published_on'])) throw new Exception('Invalid date');
            $clean['published_on'] = $url['published_on'];
        }
        else $clean['published_on'] = date('c');

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
    function deleteBookmark($id) {

        if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1) return false;

        $tid = suxDB::requestTransaction();
        $this->inTransaction = true;

        $st = $this->db->prepare("DELETE FROM {$this->db_table} WHERE id = ? ");
        $st->execute(array($id));

        // Delete links, too
        $link = new suxLink();
        $links = $link->getLinkTables('bookmarks');
        foreach ($links as $table) {
            $link->deleteLink($table, $link->getLinkColumnName($table, 'bookmarks'), $id);
        }

        suxDB::commitTransaction($tid);
        $this->inTransaction = false;

    }


    /**
    * @param int $id bookmark id
    */
    function approveBookmark($id) {

        if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1) return false;

        $st = $this->db->prepare("UPDATE {$this->db_table} SET draft = false WHERE id = ? ");
        $st->execute(array($id));

    }


	/**
	* Fetch a bookmark
    *
    * @param string $url a URL to an RSS Feed
    * @return array|false
	*/
	function fetchBookmark($url) {

        // Search the webpage for info we can use
        $webpage = @file_get_contents($url, null, null, 0, 16384); // Quit after 16 kilobytes

        $title = null;
        $description = null;

        // <title>
        $found = array();
        if (preg_match('/<title>(.*?)<\/title>/is', $webpage, $found)) {
            $title = html_entity_decode(strip_tags($found[1]), ENT_QUOTES, 'UTF-8');
        }
        // TODO: preg the meta data for description?

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
    function exceptionHandler(Exception $e) {

        if ($this->db && $this->inTransaction) {
            $this->db->rollback();
            $this->inTransaction = false;
        }

        throw($e); // Hot potato!

    }


}

?>