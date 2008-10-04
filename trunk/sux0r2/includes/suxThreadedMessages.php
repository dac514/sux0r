<?php

/**
* suxThreadedMessages
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
*/

require_once(dirname(__FILE__) . '/suxLink.php');

class suxThreadedMessages {

    // Database stuff
    protected $db;
    protected $inTransaction = false;
    protected $db_driver;
    // InnoDB
    protected $db_table = 'messages';
    protected $db_table_hist = 'messages_history';


    /*
    Currently:
    blog -> first post in thread is blog, everything else is a comment

    Todo:
    forum -> Regular threaded messages
    wiki -> First post in thread is wiki, everything else is discussion
    slideshow -> Powerpoint style, sequence of messages defined by thread order,
    */

    private $types = array('blog', 'forum', 'wiki', 'slideshow');


    /**
    * Constructor
    */
    function __construct() {

    	$this->db = suxDB::get();
        $this->db_driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        set_exception_handler(array($this, 'exceptionHandler'));

    }

    // -------------------------------------------------------------------
    // Individual messages
    // -------------------------------------------------------------------


    /**
    * Get a message by id
    *
    * @param int $id messages_id
    * @param bool $unpub select un-published?
    * @return array|false
    */
    function getMessage($id, $unpub = false) {

        // Sanity check
        if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1)
            throw new Exception('Invalid message id');
        
        $query = "SELECT * FROM {$this->db_table} WHERE id = ? ";
        
        // Publish date / draft
        if (!$unpub) {
            if ($this->db_driver == 'mysql' || $this->db_driver == 'pgsql') {
                // MySql / PgSql
                $query .= "AND draft = false ";
                $query .= "AND NOT published_on > '" . date('Y-m-d H:i:s') . "' ";
            }             
            else {
                throw new Exception('Unsupported database driver');
            }
        }
        
        $query .= 'LIMIT 1 ';
        
        $st = $this->db->prepare($query);
        $st->execute(array($id));

        $message = $st->fetch(PDO::FETCH_ASSOC);
        if ($message) return $message;
        else return false;


    }


    /**
    * Saves a message to the database
    *
    * @param int $users_id users_id
    * @param array $msg required keys => (title, body, [forum|blog|wiki|slideshow]) optional keys => (published_on)
    * @param int $parent_id messages_id of parent
    * @param int $trusted passed on to sanitizeHtml()
    * @return int insert id
    */
    function saveMessage($users_id, array $msg, $parent_id = 0, $trusted = -1) {

        // -------------------------------------------------------------------
        // Sanitize
        // -------------------------------------------------------------------

        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1)
            throw new Exception('Invalid user id');

        if (!isset($msg['title']) || !isset($msg['body']))
            throw new Exception('Invalid $msg array');

        // Users id
        $clean['users_id'] = $users_id;

        // Parent_id
        $clean['parent_id'] = filter_var($parent_id, FILTER_VALIDATE_INT);
        if ($clean['parent_id'] === false) $clean['parent_id'] = 0;

        // No HTML in title
        $clean['title'] = strip_tags($msg['title']);

        // Sanitize HTML in body
        $clean['body_html'] = suxFunct::sanitizeHtml($msg['body'], $trusted);

        // Convert and copy body to UTF-8 plaintext
        require_once(dirname(__FILE__) . '/suxHtml2UTF8.php');
        $converter = new suxHtml2UTF8($clean['body_html']);
        $clean['body_plaintext']  = $converter->getText();

        // Image
        if (isset($msg['image'])) {
            $clean['image'] = filter_var($msg['image'], FILTER_SANITIZE_STRING);
        }

        // Publish date
        if (isset($msg['published_on'])) {
            // ISO 8601 date format
            // regex must match '2008-06-18 16:53:29' or '2008-06-18T16:53:29-04:00'
            $regex = '/^(\d{4})-(0[0-9]|1[0,1,2])-([0,1,2][0-9]|3[0,1]).+(\d{2}):(\d{2}):(\d{2})/';
            if (!preg_match($regex, $msg['published_on'])) throw new Exception('Invalid date');
            $clean['published_on'] = $msg['published_on'];
        }
        else $clean['published_on'] = date('c');

        // Draft, boolean / tinyint
        $clean['draft'] = 0;
        if (isset($msg['draft'])) $clean['draft'] = 1;

        // Types of threaded messages
        $clean['blog'] = 0;
        $clean['forum'] = 0;
        $clean['wiki'] = 0;
        $clean['slideshow'] = 0;
        if (isset($msg['blog'])) $clean['blog'] = 1;
        if (isset($msg['forum'])) $clean['forum'] = 1;
        if (isset($msg['wiki'])) $clean['wiki'] = 1;
        if (isset($msg['slideshow'])) $clean['slideshow'] = 1;

        if (!$clean['forum'] && !$clean['blog'] && !$clean['wiki'] && !$clean['slideshow'])
            throw new Exception('No message type specified?');

        // We now have the $clean[] array

        // -------------------------------------------------------------------
        // Go!
        // -------------------------------------------------------------------

        /*
        The first message in a thread has thread_pos = 0.

        For a new message N, if there are no messages in the thread with the same
        parent as N, N's thread_pos is one greater than its parent's thread_pos.

        For a new message N, if there are messages in the thread with the same
        parent as N, N's thread_pos is one greater than the biggest thread_pos
        of all the messages with the same parent as N, recursively.

        After new message N's thread_pos is determined, all messages in the same
        thread with a thread_pos value greater than or equal to N's have their
        thread_pos value incremented by 1 (to make room for N).
        */

        // Begin transaction
        $tid = suxDB::requestTransaction();
        $this->inTransaction = true;

        if ($clean['parent_id']) {

            // Get thread_id, level, and thread_pos from parent
            $st = $this->db->prepare("SELECT thread_id, level, thread_pos FROM {$this->db_table} WHERE id = ? ");
            $st->execute(array($clean['parent_id']));
            $parent = $st->fetch(PDO::FETCH_ASSOC);

            // a reply's level is one greater than its parent's
            $clean['level'] = $parent['level'] + 1;

            // what is the biggest thread_pos in this thread among messages with the same parent, recursively?
            $clean['thread_pos'] = $this->biggestThreadPos($parent['thread_id'], $clean['parent_id']);

            if ($clean['thread_pos']) {
                // this thread_pos goes after the biggest existing one
                $clean['thread_pos']++;
            }
            else {
                // this is the first reply, so put it right after the parent
                $clean['thread_pos'] = $parent['thread_pos'] + 1;
            }

            // increment the thread_pos of all messages in the thread that come after this one
            $st = $this->db->prepare("UPDATE {$this->db_table} SET thread_pos = thread_pos + 1 WHERE thread_id = ? AND thread_pos >= ? ");
            $st->execute(array($parent['thread_id'], $clean['thread_pos']));

            // the new message should be saved with the parent's thread_id
            $clean['thread_id'] = $parent['thread_id'];

        }
        else {

            // The message is not a reply, so it's the start of a new thread
            $clean['level'] = 0;
            $clean['thread_pos'] = 0;
            $clean['thread_id'] = $this->db->query("SELECT MAX(thread_id) + 1 FROM {$this->db_table} ")->fetchColumn(0);

        }

        // Sanity check
        if(!$clean['thread_id']) $clean['thread_id'] = 1;

        $query = suxDB::prepareInsertQuery($this->db_table, $clean);
        $st = $this->db->prepare($query);
        $st->execute($clean);

        // MySQL InnoDB with transaction reports the last insert id as 0 after
        // commit, the real ids are only reported before committing.
        $insert_id = $this->db->lastInsertId();

        // Commit
        suxDB::commitTransaction($tid);
        $this->inTransaction = false;

        return $insert_id;

    }


    /**
    * This is a recursive function which finds the biggest thread_pos
    *
    * @param int $thread_id thread_d
    * @param int $parent_id parent id
    * @return int
    */
    private function biggestThreadPos($thread_id, $parent_id) {

        $st = $this->db->prepare("SELECT id, thread_pos FROM {$this->db_table} WHERE thread_id = ? AND parent_id = ? ORDER BY thread_pos DESC LIMIT 1 ");
        $st->execute(array($thread_id, $parent_id));
        $result = $st->fetch(PDO::FETCH_ASSOC);

        static $max_pos = 0;
        if ($result['thread_pos'] && $max_pos < $result['thread_pos']) {
            $max_pos = $result['thread_pos'];
            return $this->biggestThreadPos($thread_id, $result['id']);
        }
        return $max_pos;

    }


    /**
    * Edit a message already in the database, backup changes in history table
    *
    * @param int $messages_id messages_id
    * @param int $users_id users_id
    * @param array $msg required keys => (title, body) optional keys => (published_on)
    * @param int $trusted passed on to sanitizeHtml()
    */
    function editMessage($messages_id, $users_id, array $msg, $trusted = -1) {

        // -------------------------------------------------------------------
        // Sanitize
        // -------------------------------------------------------------------

        if (!filter_var($messages_id, FILTER_VALIDATE_INT) || $messages_id < 1)
            throw new Exception('Invalid message id');

        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1)
            throw new Exception('Invalid user id');

        if (!isset($msg['title']) || !isset($msg['body']))
            throw new Exception('Invalid $msg array');

        // Users id
        $clean['id'] = $messages_id;
        $clean['users_id'] = $users_id;

        // No HTML in title
        $clean['title'] = strip_tags($msg['title']);

        // Sanitize HTML in body
        $clean['body_html']  = suxFunct::sanitizeHtml($msg['body'], $trusted);

        // Convert and copy body to UTF-8 plaintext
        require_once(dirname(__FILE__) . '/suxHtml2UTF8.php');
        $converter = new suxHtml2UTF8($clean['body_html']);
        $clean['body_plaintext'] = $converter->getText();

        // Image
        if (isset($msg['image'])) {
            $clean['image'] = filter_var($msg['image'], FILTER_SANITIZE_STRING);
        }

        // Publish date
        if (isset($msg['published_on'])) {
            // ISO 8601 date format
            // regex must match '2008-06-18 16:53:29' or '2008-06-18T16:53:29-04:00'
            $regex = '/^(\d{4})-(0[0-9]|1[0,1,2])-([0,1,2][0-9]|3[0,1]).+(\d{2}):(\d{2}):(\d{2})/';
            if (!preg_match($regex, $msg['published_on'])) throw new Exception('Invalid date');
            $clean['published_on'] = $msg['published_on'];
        }

        // Draft, boolean / tinyint
        $clean['draft'] = 0;
        if (isset($msg['draft'])) $clean['draft'] = 1;


        // Types of threaded messages
        if (isset($msg['blog'])) {
            if ($msg['blog'] == 0) $clean['blog'] = 0;
            else $clean['blog'] = 1;
        }
        if (isset($msg['forum'])) {
            if ($msg['forum'] == 0) $clean['forum'] = 0;
            else $clean['forum'] = 1;
        }
        if (isset($msg['wiki'])) {
            if ($msg['wiki'] == 0) $clean['wiki'] = 0;
            else $clean['wiki'] = 1;
        }
        if (isset($msg['slideshow'])) {
            if ($msg['slideshow'] == 0) $clean['slideshow'] = 0;
            else $clean['slideshow'] = 1;
        }


        // We now have the $clean[] array

        // -------------------------------------------------------------------
        // Go!
        // -------------------------------------------------------------------

        // Get $edit[] array in order to keep a history
        $query = "SELECT title, image, body_html, body_plaintext FROM {$this->db_table} WHERE id = ? ";
        $st = $this->db->prepare($query);
        $st->execute(array($clean['id']));
        $edit = $st->fetch(PDO::FETCH_ASSOC);

        if (!$edit) throw new Exception('No message to edit?');

        // Begin transaction
        $tid = suxDB::requestTransaction();
        $this->inTransaction = true;

        $edit['messages_id'] = $clean['id'];
        $edit['users_id'] = $clean['users_id'];
        $edit['edited_on'] = date('c');
        $query = suxDB::prepareInsertQuery($this->db_table_hist, $edit);
        $st = $this->db->prepare($query);
        $st->execute($edit);

        unset($clean['users_id']); // Don't override the original publisher

        // Update the message
        $query = suxDB::prepareUpdateQuery($this->db_table, $clean);
        $st = $this->db->prepare($query);
        $st->execute($clean);

        // Commit
        suxDB::commitTransaction($tid);
        $this->inTransaction = false;

    }


    /**
    * Delete message
    *
    * @param int $id messages id
    */
    function deleteMessage($id) {

        if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1) return false;

        // Begin transaction
        $tid = suxDB::requestTransaction();
        $this->inTransaction = true;

        $st = $this->db->prepare("DELETE FROM {$this->db_table} WHERE id = ? LIMIT 1 ");
        $st->execute(array($id));

        $st = $this->db->prepare("DELETE FROM {$this->db_table_hist} WHERE messages_id = ? ");
        $st->execute(array($id));

        // Delete links, too
        $link = new suxLink();
        $links = $link->getLinkTables('messages');
        foreach ($links as $table) {
            $link->deleteLink($table, $link->getLinkColumnName($table, 'messages'), $id);
        }

        // Commit
        suxDB::commitTransaction($tid);
        $this->inTransaction = false;

    }


    /**
    * Get first post
    *
    * @param int $thread_id thread_id
    * @param bool $unpub select un-published?
    * @return array
    */
    function getFirstPost($thread_id, $unpub = false) {

        // Sanity check
        if (!filter_var($thread_id, FILTER_VALIDATE_INT) || $thread_id < 1)
            throw new Exception('Invalid thread id');

        // SQL Query
        $query = "SELECT * FROM {$this->db_table} WHERE thread_id = ? AND thread_pos = 0 ";

        // Publish date / draft
        if (!$unpub) {
            if ($this->db_driver == 'mysql' || $this->db_driver == 'pgsql') {
                // MySql / PgSql
                $query .= "AND draft = false ";
                $query .= "AND NOT published_on > '" . date('Y-m-d H:i:s') . "' ";
            }            
            else {
                throw new Exception('Unsupported database driver');
            }
        }

        $query .= "ORDER BY published_on DESC LIMIT 1";

        // Execute
        $st = $this->db->prepare($query);
        $st->execute(array($thread_id));
        return $st->fetch(PDO::FETCH_ASSOC);

    }


    // -------------------------------------------------------------------
    // Threaded messages
    // -------------------------------------------------------------------


    /**
    * Count a thread
    *
    * @param int $thread_id thread id
    * @param string $type forum, blog, wiki, or slideshow
    * @param bool $unpub select un-published?
    * @return int
    */
    function countThread($thread_id, $type = null, $unpub = false) {

        // Sanity check
        if (!filter_var($thread_id, FILTER_VALIDATE_INT) || $thread_id < 1)
            throw new Exception('Invalid thread id');

        if ($type && !in_array($type, $this->types))
            throw new Exception('Invalid type');

        // SQL Query
        else $query = "SELECT COUNT(*) FROM {$this->db_table} WHERE thread_id = ? ";

        // Publish date / draft
        if (!$unpub) {
            if ($this->db_driver == 'mysql' || $this->db_driver == 'pgsql') {
                // MySql / PgSql
                $query .= "AND draft = false ";
                $query .= "AND NOT published_on > '" . date('Y-m-d H:i:s') . "' ";
            }            
            else {
                throw new Exception('Unsupported database driver');
            }
        }

        // Type
        if ($type) $query .= "AND {$type} = true ";

        // Execute
        $st = $this->db->prepare($query);
        $st->execute(array($thread_id));
        return $st->fetchColumn();


    }
    

    /**
    * Get a thread
    *
    * @param int $thread_id thread id
    * @param string $type forum, blog, wiki, or slideshow
    * @param int $limit sql limit value
    * @param int $start sql start of limit value
    * @param bool $unpub select un-published?
    * @return array
    */
    function getThread($thread_id, $type = null, $limit = null, $start = 0, $unpub = false) {

        // Sanity check
        if (!filter_var($thread_id, FILTER_VALIDATE_INT) || $thread_id < 1)
            throw new Exception('Invalid thread id');

        if ($type && !in_array($type, $this->types))
            throw new Exception('Invalid type');

        // SQL Query
        $query = "SELECT * FROM {$this->db_table} WHERE thread_id = ? ";

        // Publish date / draft
        if (!$unpub) {
            if ($this->db_driver == 'mysql' || $this->db_driver == 'pgsql') {
                // MySql / PgSql
                $query .= "AND draft = false ";
                $query .= "AND NOT published_on > '" . date('Y-m-d H:i:s') . "' ";
            }             
            else {
                throw new Exception('Unsupported database driver');
            }
        }

        // Type
        if ($type) $query .= "AND {$type} = true ";
                
        $query .= "ORDER BY thread_id, thread_pos "; // Order
        // Limit
        if ($start && $limit) $query .= "LIMIT {$start}, {$limit} ";
        elseif ($limit) $query .= "LIMIT {$limit} ";

        // Execute
        $st = $this->db->prepare($query);
        $st->execute(array($thread_id));
        return $st->fetchAll(PDO::FETCH_ASSOC);


    }


    /**
    * Count messages by user id
    *
    * @param int $users_id users id
    * @param string $type forum, blog, wiki, or slideshow
    * @param bool $unpub select un-published?
    * @return int
    */
    function countMessagesByUser($users_id, $type = null, $unpub = false) {

        // Sanity check
        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1)
            throw new Exception('Invalid user id');

        if ($type && !in_array($type, $this->types))
            throw new Exception('Invalid type');

        // SQL Query
        $query = "SELECT COUNT(*) FROM {$this->db_table} WHERE users_id = ? ";

        // Publish date / draft
        if (!$unpub) {
            if ($this->db_driver == 'mysql' || $this->db_driver == 'pgsql') {
                // MySql / PgSql
                $query .= "AND draft = false ";
                $query .= "AND NOT published_on > '" . date('Y-m-d H:i:s') . "' ";
            }             
            else {
                throw new Exception('Unsupported database driver');
            }
        }

        // Type
        if ($type) $query .= "AND {$type} = true ";


        // Execute
        $st = $this->db->prepare($query);
        $st->execute(array($users_id));
        return $st->fetchColumn();

    }


    /**
    * Group messages by user id
    *
    * @param string $type forum, blog, wiki, or slideshow
    * @param int $limit sql limit value
    * @param int $start sql start of limit value
    * @param bool $unpub select un-published?
    * @return array
    */
    function groupMessagesByUser($type = null, $limit = null, $start = 0, $unpub = false) {

        // Sanity check
        if ($type && !in_array($type, $this->types)) throw new Exception('Invalid type');

        // Query
        if ($this->db_driver == 'mysql' || $this->db_driver == 'pgsql') {
     
            // Mysql / PgSql
            $query = "SELECT COUNT(*) AS count, users_id FROM {$this->db_table} ";
            
            if (!$unpub) {
                // Only show published items
                $query .= "AND draft = false ";
                $query .= "AND NOT published_on > '" . date('Y-m-d H:i:s') . "' "; // Don't give away the future
            } 
            
        }        
        else {
            throw new Exception('Unsupported database driver');
        }
        
        // Type
        if ($type) $query .= "AND {$type} = true "; 
        
        // Group by, order
        $query .= "GROUP BY users_id ORDER BY count DESC ";

        // Limits
        if ($start && $limit) $query .= "LIMIT {$start}, {$limit} ";
        elseif ($limit) $query .= "LIMIT {$limit} ";

        // Execute
        $st = $this->db->query($query);
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }


    /**
    * Get messages by user id
    *
    * @param int $users_id users id
    * @param string $type forum, blog, wiki, or slideshow
    * @param int $limit sql limit value
    * @param int $start sql start of limit value
    * @param bool $unpub select un-published?
    * @return array
    */
    function getMessagesByUser($users_id, $type = null, $limit = null, $start = 0, $unpub = false) {

        // Sanity check
        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1)
            throw new Exception('Invalid user id');

        if ($type && !in_array($type, $this->types))
            throw new Exception('Invalid type');

        // SQL Query
        $query = "SELECT * FROM {$this->db_table} WHERE users_id = ? ";

        // Publish date / draft
        if (!$unpub) {            
            if ($this->db_driver == 'mysql' || $this->db_driver == 'pgsql') {
                // MySql / PgSql
                $query .= "AND draft = false ";
                $query .= "AND NOT published_on > '" . date('Y-m-d H:i:s') . "' ";
            }              
            else {
                throw new Exception('Unsupported database driver');
            }
        }

        // Type
        if ($type) $query .= "AND {$type} = true "; 
        
        $query .= "ORDER BY published_on DESC "; // Order
        
        // Limit
        if ($start && $limit) $query .= "LIMIT {$start}, {$limit} ";
        elseif ($limit) $query .= "LIMIT {$limit} ";

        // Execute
        $st = $this->db->prepare($query);
        $st->execute(array($users_id));
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }


    /**
    * Count first posts
    *
    * @param string $type forum, blog, wiki, or slideshow
    * @param bool $unpub select un-published?
    * @return int
    */
    function countFirstPosts($type = null, $unpub = false) {

        // Sanity check
        if ($type && !in_array($type, $this->types)) throw new Exception('Invalid type');

        // SQL Query
        $query = "SELECT COUNT(*) FROM {$this->db_table} WHERE thread_pos = 0 ";

        // Publish date / Draft
        if (!$unpub) {
            if ($this->db_driver == 'mysql' || $this->db_driver == 'pgsql') {
                // PgSql
                $query .= "AND draft = false ";
                $query .= "AND NOT published_on > '" . date('Y-m-d H:i:s') . "' ";
            }
            else {
                throw new Exception('Unsupported database driver');
            }
        }

        // Type
        if ($type) $query .= "AND {$type} = true ";

        // Execute
        $st = $this->db->query($query);
        return $st->fetchColumn();

    }




    /**
    * Get first posts
    *
    * @param string $type forum, blog, wiki, or slideshow
    * @param int $limit sql limit value
    * @param int $start sql start of limit value
    * @param bool $unpub select un-published?
    * @return array
    */
    function getFirstPosts($type = null, $limit = null, $start = 0, $unpub = false) {

        // Sanity check
        if ($type && !in_array($type, $this->types)) throw new Exception('Invalid type');

        // SQL Query
        $query = "SELECT * FROM {$this->db_table} WHERE thread_pos = 0 ";

        // Publish date / Draft
        if (!$unpub) {
            if ($this->db_driver == 'mysql' || $this->db_driver == 'pgsql') {
                // MySql / PgSql
                $query .= "AND draft = false ";
                $query .= "AND NOT published_on > '" . date('Y-m-d H:i:s') . "' ";
            }            
            else {
                throw new Exception('Unsupported database driver');
            }
        }

        // Type
        if ($type) $query .= "AND {$type} = true ";  
            
        $query .= "ORDER BY published_on DESC "; // Order
        // Limit
        if ($start && $limit) $query .= "LIMIT {$start}, {$limit} ";
        elseif ($limit) $query .= "LIMIT {$limit} ";


        // Execute
        $st = $this->db->query($query);
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }


    /**
    * Count first posts by user id
    *
    * @param int $users_id users id
    * @param string $type forum, blog, wiki, or slideshow
    * @param bool $unpub select un-published?
    * @return int
    */
    function countFirstPostsByUser($users_id, $type = null, $unpub = false) {

        // Sanity check
        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1)
            throw new Exception('Invalid user id');

        if ($type && !in_array($type, $this->types))
            throw new Exception('Invalid type');

        // SQL Query
        $query = "SELECT COUNT(*) FROM {$this->db_table} WHERE users_id = ? AND thread_pos = 0 ";

        // Publish date / draft
        if (!$unpub) {
            if ($this->db_driver == 'mysql' || $this->db_driver == 'pgsql') {
                // MySql / PgSql
                $query .= "AND draft = false ";
                $query .= "AND NOT published_on > '" . date('Y-m-d H:i:s') . "' ";
            }            
            else {
                throw new Exception('Unsupported database driver');
            }
        }

        // Type
        if ($type) $query .= "AND {$type} = true "; 

        // Execute
        $st = $this->db->prepare($query);
        $st->execute(array($users_id));
        return $st->fetchColumn();

    }


    /**
    * Group first posts by user id
    *
    * @param string $type forum, blog, wiki, or slideshow
    * @param int $limit sql limit value
    * @param int $start sql start of limit value
    * @param bool $unpub select un-published?
    * @return array
    */
    function groupFirstPostsByUser($type = null, $limit = null, $start = 0, $unpub = false) {

        // Sanity check
        if ($type && !in_array($type, $this->types)) throw new Exception('Invalid type');

        // Query
        if ($this->db_driver == 'mysql' || $this->db_driver == 'pgsql') {
            
            // MySql / PgSql
            $query = "SELECT COUNT(*) AS count, users_id
            FROM {$this->db_table} WHERE thread_pos = 0 ";

            if (!$unpub) {
                // Only show published items
                $query .= "AND draft = false ";
                $query .= "AND NOT published_on > '" . date('Y-m-d H:i:s') . "' "; // Don't give away the future
            }            

        }        
        else {
            throw new Exception('Unsupported database driver');
        }
        
        // Type
        if ($type) $query .= "AND {$type} = true "; 
        
        // Order
        $query .= "GROUP BY users_id ORDER BY count DESC ";        

        // Limits
        if ($start && $limit) $query .= "LIMIT {$start}, {$limit} ";
        elseif ($limit) $query .= "LIMIT {$limit} ";

        // Execute
        $st = $this->db->query($query);
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }


    /**
    * Get first posts by user id
    *
    * @param int $users_id users id
    * @param string $type forum, blog, wiki, or slideshow
    * @param int $limit sql limit value
    * @param int $start sql start of limit value
    * @param bool $unpub select un-published?
    * @return array
    */
    function getFirstPostsByUser($users_id, $type = null, $limit = null, $start = 0, $unpub = false) {

        // Sanity check
        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1)
            throw new Exception('Invalid user id');

        if ($type && !in_array($type, $this->types))
            throw new Exception('Invalid type');

        // SQL Query
        $query = "SELECT * FROM {$this->db_table} WHERE users_id = ? AND thread_pos = 0 ";

        // Publish date / draft
        if (!$unpub) {
            if ($this->db_driver == 'mysql' || $this->db_driver == 'pgsql') {
                // MySql / PgSql
                $query .= "AND draft = false ";                
                $query .= "AND NOT published_on > '" . date('Y-m-d H:i:s') . "' ";
            }            
            else {
                throw new Exception('Unsupported database driver');
            }
        }

        // Type
        if ($type) $query .= "AND {$type} = true "; 
        
        $query .= "ORDER BY published_on DESC "; // Order
        
        // Limit
        if ($start && $limit) $query .= "LIMIT {$start}, {$limit} ";
        elseif ($limit) $query .= "LIMIT {$limit} ";

        // Execute
        $st = $this->db->prepare($query);
        $st->execute(array($users_id));
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }


    /**
    * Count first posts by month
    *
    * @param int $date date
    * @param string $type forum, blog, wiki, or slideshow
    * @param bool $unpub select un-published?
    * @return int
    */
    function countFirstPostsByMonth($date, $type = null, $unpub = false) {

        // Sanity check
        if ($type && !in_array($type, $this->types)) throw new Exception('Invalid type');

        // Get year and month
        // regex must match '2008-06-18 16:53:29' or '2008-06-18T16:53:29-04:00'
        $matches = array();
        $regex = '/^(\d{4})-(0[0-9]|1[0,1,2])-([0,1,2][0-9]|3[0,1]).+(\d{2}):(\d{2}):(\d{2})/';
        if (!preg_match($regex, $date, $matches)) throw new Exception('Invalid date');

        // SQL Query
        $query = "SELECT COUNT(*) FROM {$this->db_table} WHERE thread_pos = 0 ";

        // Publish date / draft
        if (!$unpub) {
            $date = "{$matches[1]}-{$matches[2]}-{$matches[3]} {$matches[4]}:{$matches[5]}:{$matches[6]}";   
            $query .= "AND draft = false "; 
            if ($this->db_driver == 'mysql') {
                // MySql
                $query .= "AND MONTH(published_on) =  MONTH('{$date}') "; // Month
                $query .= "AND YEAR(published_on) = YEAR('{$date}')"; // Year
                $query .= "AND NOT published_on > '" . date('Y-m-d H:i:s') . "' "; // Don't give away the future
            }
            elseif ($this->db_driver == 'pgsql') {                
                // PgSQL                           
                $query .= "AND EXTRACT(MONTH FROM published_on) =  EXTRACT(MONTH FROM '{$date}') "; // Month
                $query .= "AND EXTRACT(YEAR FROM published_on) = EXTRACT(YEAR FROM '{$date}')"; // Year
                $query .= "AND NOT published_on > '" . date('Y-m-d H:i:s') . "' "; // Don't give away the future
            }            
            else {
                throw new Exception('Unsupported database driver');
            }
        }

        // Type
        if ($type) $query .= "AND {$type} = true "; 

        // Execute
        $st = $this->db->query($query);
        return $st->fetchColumn();

    }


    /**
    * Group first posts by month
    *
    * @param string $type forum, blog, wiki, or slideshow
    * @param int $limit sql limit value
    * @param int $start sql start of limit value
    * @param bool $unpub select un-published?
    * @return array
    */
    function groupFirstPostsByMonths($type = null, $limit = null, $start = 0, $unpub = false) {

        // Sanity check
        if ($type && !in_array($type, $this->types)) throw new Exception('Invalid type');

        // Query
        if ($this->db_driver == 'mysql') {
            // MySql
            $query = "SELECT COUNT(*) AS count, 
            YEAR(published_on) AS year, 
            MONTH(published_on) AS month
            FROM {$this->db_table} WHERE thread_pos = 0 ";

            if (!$unpub) {
                // Only show published items
                $query .= "AND draft = false ";
                $query .= "AND NOT published_on > '" . date('Y-m-d H:i:s') . "' "; // Don't give away the future
            }
            if ($type) $query .= "AND {$type} = true "; // Type

            $query .= "GROUP BY YEAR(published_on), MONTH(published_on) ORDER BY published_on DESC ";

        }
        elseif ($this->db_driver == 'pgsql') {
            // PgSql
            
            $query = "SELECT COUNT(*) AS count, 
            EXTRACT(YEAR FROM published_on) AS year, 
            EXTRACT(MONTH FROM published_on) AS month
            FROM {$this->db_table} WHERE thread_pos = 0 ";
            
            if (!$unpub) {
                // Only show published items
                $query .= "AND draft = false ";
                $query .= "AND NOT published_on > '" . date('Y-m-d H:i:s') . "' "; // Don't give away the future
            }
            if ($type) $query .= "AND {$type} = true "; // Type
            
            $query .= "GROUP BY id,published_on ORDER BY published_on DESC ";
            
        }
        else {
            throw new Exception('Unsupported database driver');
        }       

        // Limits
        if ($start && $limit) $query .= "LIMIT {$start}, {$limit} ";
        elseif ($limit) $query .= "LIMIT {$limit} ";


        // Execute
        $st = $this->db->query($query);
        return $st->fetchAll(PDO::FETCH_ASSOC);
        

    }


    /**
    * Get first posts by month
    *
    * @param string $date date
    * @param string $type forum, blog, wiki, or slideshow
    * @param int $limit sql limit value
    * @param int $start sql start of limit value
    * @param bool $unpub select un-published?
    * @return array
    */
    function getFirstPostsByMonth($date, $type = null, $limit = null, $start = 0, $unpub = false) {

        // Sanity check
        if ($type && !in_array($type, $this->types)) throw new Exception('Invalid type');

        // Get year and month
        // regex must match '2008-06-18 16:53:29' or '2008-06-18T16:53:29-04:00'
        $matches = array();
        $regex = '/^(\d{4})-(0[0-9]|1[0,1,2])-([0,1,2][0-9]|3[0,1]).+(\d{2}):(\d{2}):(\d{2})/';
        if (!preg_match($regex, $date, $matches)) throw new Exception('Invalid date');

        // SQL Query
        $query = "SELECT * FROM {$this->db_table} WHERE thread_pos = 0 ";

        if (!$unpub) {
            // Only show published items
            $date = "{$matches[1]}-{$matches[2]}-{$matches[3]} {$matches[4]}:{$matches[5]}:{$matches[6]}";
            $query .= "AND draft = false ";
            if ($this->db_driver == 'mysql') {
                // MySql
                $query .= "AND MONTH(published_on) =  MONTH('{$date}') "; // Month
                $query .= "AND YEAR(published_on) = YEAR('{$date}') "; // Year
                $query .= "AND NOT published_on > '" . date('Y-m-d H:i:s') . "' "; // Don't give away the future
            }
            elseif ($this->db_driver == 'pgsql') {
                // PgSql
                $query .= "AND EXTRACT(MONTH FROM published_on) =  EXTRACT(MONTH FROM '{$date}')  "; // Month
                $query .= "AND EXTRACT(YEAR FROM published_on) =  EXTRACT(YEAR FROM '{$date}') "; // Year
                $query .= "AND NOT published_on > '" . date('Y-m-d H:i:s') . "' "; // Don't give away the future
            }            
            else {
                throw new Exception('Unsupported database driver');
            }
        }

        // Type
        if ($type) $query .= "AND {$type} = true ";
        
        $query .= "ORDER BY published_on DESC "; // Order
        // Limit
        if ($start && $limit) $query .= "LIMIT {$start}, {$limit} ";
        elseif ($limit) $query .= "LIMIT {$limit} ";

        // Execute
        $st = $this->db->query($query);
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }


    /**
    * Delete thread
    *
    * @param int $thread_id thread id
    */
    function deleteThread($thread_id) {

        if (!filter_var($thread_id, FILTER_VALIDATE_INT) || $thread_id < 1) return false;

        // Begin transaction
        $tid = suxDB::requestTransaction();
        $this->inTransaction = true;

        $st = $this->db->prepare("SELECT id FROM {$this->db_table} WHERE thread_id = ? ");
        $st->execute(array($thread_id));
        $result = $st->fetchAll(PDO::FETCH_ASSOC);

        foreach($result as $key => $val) {

            $st = $this->db->prepare("DELETE FROM {$this->db_table} WHERE id = ? LIMIT 1 ");
            $st->execute(array($val['id']));

            $st = $this->db->prepare("DELETE FROM {$this->db_table_hist} WHERE messages_id = ? ");
            $st->execute(array($val['id']));

        }

        // Delete links, too
        $link = new suxLink();
        $links = $link->getLinkTables('messages');
        foreach($result as $key => $val) {
            foreach ($links as $table) {
                $link->deleteLink($table, $link->getLinkColumnName($table, 'messages'), $val['id']);
            }
        }

        // Commit
        suxDB::commitTransaction($tid);
        $this->inTransaction = false;


    }



    // ----------------------------------------------------------------------------
    // Supplemental
    // ----------------------------------------------------------------------------


    /**
    * Get latest replies, i.e. thread_pos != 0
    *
    * @param string $type forum, blog, wiki, or slideshow
    * @param int $limit maximum latest replies
    * @param bool $unpub select un-published?
    * @return array
    */
    function getRececentComments($type = null, $limit = 10, $unpub = false) {

        // Sanity check
        if ($type && !in_array($type, $this->types)) throw new Exception('Invalid type');

        // SQL Query
        $query = "SELECT * FROM {$this->db_table} WHERE thread_pos != 0 ";

        // Publish date / Draft
        if (!$unpub) {
            if ($this->db_driver == 'mysql' || $this->db_driver == 'pgsql') {
                // MySql / PgSql
                $query .= "AND draft = false ";
                $query .= "AND NOT published_on > '" . date('Y-m-d H:i:s') . "' ";
            }            
            else {
                throw new Exception('Unsupported database driver');
            }
        }

        // Type
        if ($type) $query .= "AND {$type} = true ";
        
        $query .= "ORDER BY published_on DESC ";
        if ($limit) $query .= "LIMIT {$limit} ";

        // Execute
        $st = $this->db->prepare($query);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }


    /**
    * Get reply count
    *
    * @param int $thread_id thread_id
    * @param bool $unpub select un-published?
    * @return int
    */
    function getCommentsCount($thread_id, $unpub = false) {

        // Sanity check
        if (!filter_var($thread_id, FILTER_VALIDATE_INT) || $thread_id < 1)
            throw new Exception('Invalid thread id');

        // SQL Query
        $query = "SELECT COUNT(*) FROM {$this->db_table} WHERE thread_id = ? AND thread_pos != 0 ";

        // Publish date / draft
        if (!$unpub) {         
            if ($this->db_driver == 'mysql' || $this->db_driver == 'pgsql') {
                // MySql / PgSql
                $query .= "AND draft = false ";
                $query .= "AND NOT published_on > '" . date('Y-m-d H:i:s') . "' ";
            }
            else {
                throw new Exception('Unsupported database driver');
            }
        }

        // Execute
        $st = $this->db->prepare($query);
        $st->execute(array($thread_id));
        return $st->fetchColumn();

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