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
* Inspired by:
* PHP Cookbook Second Edition, Recipe 10.16
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @copyright  2008 sux0r development group
* @license    http://www.gnu.org/licenses/agpl.html
*/

class suxThreadedMessages {

    // Database stuff
    protected $db;
    protected $inTransaction = false;
    protected $db_table = 'messages';
    protected $db_table_hist = 'messages_history';

    /*
    Conventions:
    forum -> Regular threaded messages
    blog -> first message in thread is blog, everything else is a "comment"
    wiki -> First message in thread is wiki, everything else is "discussion"

    Future:
    slideshow -> Sequence of messages defined by thread
    */

    private $types = array('forum', 'blog', 'wiki');


    /**
    * Constructor
    *
    * @global array $CONFIG['DSN']
    * @param string $key a key from our suxDB DSN
    */
    function __construct($key = null) {

        if (!$key && !empty($GLOBALS['CONFIG']['DSN']['messages'])) $key = 'messages';
    	$this->db = suxDB::get($key);
        set_exception_handler(array($this, 'exceptionHandler'));

    }

    // -------------------------------------------------------------------
    // Individual messages
    // -------------------------------------------------------------------


    /**
    * Get a message by id
    *
    * @param int $id messages_id
    * @return array
    */
    function getMessage($id) {

        // Sanity check
        if (!filter_var($id, FILTER_VALIDATE_INT)) throw new Exception('Invalid message id');

        $st = $this->db->prepare("SELECT * FROM {$this->db_table} WHERE id = ? LIMIT 1 ");
        $st->execute(array($id));

        $message = $st->fetch(PDO::FETCH_ASSOC);
        if (!$message) {
            throw new Exception('Invalid message id');
        }

        return $message;

    }


    /**
    * Saves a message to the database
    *
    * @param int $users_id users_id
    * @param array $msg required keys => (title, body, [forum|blog|wiki]) optional keys => (published_on)
    * @param int $parent_id messages_id of parent
    * @param bool $trusted passed on to sanitizeHtml
    * @return int insert id
    */
    function saveMessage($users_id, array $msg, $parent_id = 0, $trusted = false) {

        /*
        The first message in a thread has thread_pos = 0.

        For a new message N, if there are no messages in the thread with the same
        parent as N, N's thread_pos is one greater than its parent's thread_pos.

        For a new message N, if there are messages in the thread with the same
        parent as N, N's thread_pos is one greater than the biggest thread_pos
        of all the messages with the same parent as N.

        After new message N's thread_pos is determined, all messages in the same
        thread with a thread_pos value greater than or equal to N's have their
        thread_pos value incremented by 1 (to make room for N).
        */

        // -------------------------------------------------------------------
        // Sanitize
        // -------------------------------------------------------------------

        if (!filter_var($users_id, FILTER_VALIDATE_INT)) throw new Exception('Invalid user id');
        if (!isset($msg['title']) || !isset($msg['body'])) throw new Exception('Invalid $msg array');

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
            // TODO: Check ISO 8601 date date format
            $clean['published_on'] = $msg['published_on'];
        }
        else $clean['published_on'] = date('c');

        // Draft, boolean / tinyint
        $clean['draft'] = 0;
        if (isset($msg['draft'])) $clean['draft'] = 1;

        // Types of threaded messages
        $clean['forum'] = 0;
        $clean['blog'] = 0;
        $clean['wiki'] = 0;
        if (isset($msg['forum'])) $clean['forum'] = 1;
        if (isset($msg['blog'])) $clean['blog'] = 1;
        if (isset($msg['wiki'])) $clean['wiki'] = 1;

        if (!$clean['forum'] && !$clean['blog'] && !$clean['wiki'])
            throw new Exception('No message type specified?');

        // We now have the $clean[] array

        // -------------------------------------------------------------------
        // Go!
        // -------------------------------------------------------------------

        // Begin transaction
        $this->db->beginTransaction();
        $this->inTransaction = true;

        if ($clean['parent_id']) {

            // Get thread_id, level, and thread_pos from parent
            $st = $this->db->prepare("SELECT thread_id, level, thread_pos FROM {$this->db_table} WHERE id = ? ");
            $st->execute(array($clean['parent_id']));
            $parent = $st->fetch(PDO::FETCH_ASSOC);

            // a reply's level is one greater than its parent's
            $clean['level'] = $parent['level'] + 1;

            // what is the biggest thread_pos in this thread among messages with the same parent?
            $st = $this->db->prepare("SELECT MAX(thread_pos) FROM {$this->db_table} WHERE thread_id = ? AND parent_id = ? ");
            $st->execute(array($parent['thread_id'], $clean['parent_id']));
            $clean['thread_pos'] = $st->fetchColumn(0);

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
        $this->db->commit();
        $this->inTransaction = false;

        return $insert_id;

    }


    /**
    * Edit a message already in the database, backup changes in history table
    *
    * @param int $messages_id messages_id
    * @param int $users_id users_id
    * @param array $msg required keys => (title, body) optional keys => (published_on)
    * @param bool $trusted passed on to sanitizeHtml
    */
    function editMessage($messages_id, $users_id, array $msg, $trusted = false) {

        // -------------------------------------------------------------------
        // Sanitize
        // -------------------------------------------------------------------

        if (!filter_var($messages_id, FILTER_VALIDATE_INT)) throw new Exception('Invalid message id');
        if (!filter_var($users_id, FILTER_VALIDATE_INT)) throw new Exception('Invalid user id');
        if (!isset($msg['title']) || !isset($msg['body'])) throw new Exception('Invalid $msg array');

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
            // TODO: Check ISO 8601 date date format
            $clean['published_on'] = $msg['published_on'];
        }

        // Draft, boolean / tinyint
        $clean['draft'] = 0;
        if (isset($msg['draft'])) $clean['draft'] = 1;


        // Types of threaded messages
        if (isset($msg['forum'])) {
            if ($msg['forum'] == 0) $clean['forum'] = 0;
            else $clean['forum'] = 1;
        }
        if (isset($msg['blog'])) {
            if ($msg['blog'] == 0) $clean['blog'] = 0;
            else $clean['blog'] = 1;
        }
        if (isset($msg['wiki'])) {
            if ($msg['wiki'] == 0) $clean['wiki'] = 0;
            else $clean['wiki'] = 1;
        }


        // We now have the $clean[] array

        // -------------------------------------------------------------------
        // Go!
        // -------------------------------------------------------------------

        // Get $edit[] array in order to keep a history (wiki style)
        $query = "SELECT title, image, body_html, body_plaintext FROM {$this->db_table} WHERE id = ? ";
        $st = $this->db->prepare($query);
        $st->execute(array($clean['id']));
        $edit = $st->fetch(PDO::FETCH_ASSOC);

        if (!$edit) throw new Exception('No message to edit?');

        // Begin transaction
        $this->db->beginTransaction();
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
        $this->db->commit();
        $this->inTransaction = false;

    }


    // -------------------------------------------------------------------
    // Threaded messages
    // -------------------------------------------------------------------


    /**
    * Get a thread
    *
    * @param int $thread_id thread id
    * @param bool $long select * or abbreviated data?
    * @return array
    */
    function getThread($thread_id, $long = false) {

        // Sanity check
        if (!filter_var($thread_id, FILTER_VALIDATE_INT)) throw new Exception('Invalid thread id');

        // SQL Query
        if ($long) $query = "SELECT * ";
        else $query = "SELECT id, users_id, thread_id, title, LENGTH(body_plaintext) AS body_length, published_on, level ";
        $query .= "FROM {$this->db_table} WHERE thread_id = ? AND draft = 0 ORDER BY thread_pos ";

        // Execute
        $st = $this->db->prepare($query);
        $st->execute(array($thread_id));
        return $st->fetchAll(PDO::FETCH_ASSOC);


    }


    /**
    * Get messages by user id
    *
    * @param int $users_id users id
    * @param string $type forum, blog, or wiki
    * @param bool $long select * or abbreviated data?
    * @return array
    */
    function getMessagesByUser($users_id, $type = null, $long = false) {

        // Sanity check
        if (!filter_var($users_id, FILTER_VALIDATE_INT)) throw new Exception('Invalid user id');
        if ($type && !in_array($type, $this->types)) throw new Exception('Invalid type');

        // SQL Query
        if ($long) $query = "SELECT *, LENGTH(body_plaintext) AS body_length ";
        else $query = "SELECT id, users_id, thread_id, title, LENGTH(body_plaintext) AS body_length, published_on, level ";
        $query .= "FROM {$this->db_table} WHERE users_id = ? AND draft = 0 ";
        if ($type) $query .= "AND {$type} = 1 ";
        $query .= "ORDER BY published_on DESC ";

        // Execute
        $st = $this->db->prepare($query);
        $st->execute(array($users_id));
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }


    /**
    * Get first post
    *
    * @param int $thread_id thread_id
    * @param bool $long select * or abbreviated data?
    * @return array
    */
    function getFirstPost($thread_id, $long = false) {

        // Sanity check
        if (!filter_var($thread_id, FILTER_VALIDATE_INT)) throw new Exception('Invalid thread id');

        // SQL Query
        if ($long) $query = "SELECT *, LENGTH(body_plaintext) AS body_length ";
        else $query = "SELECT id, users_id, thread_id, title, LENGTH(body_plaintext) AS body_length, published_on, level ";
        $query .= "FROM {$this->db_table} WHERE thread_id = ? AND thread_pos = 0 AND draft = 0 ";
        $query .= "ORDER BY published_on DESC LIMIT 1";

        // Execute
        $st = $this->db->prepare($query);
        $st->execute(array($thread_id));
        return $st->fetch(PDO::FETCH_ASSOC);

    }



    /**
    * Get first posts
    *
    * @param string $type forum, blog, or wiki
    * @param bool $long select * or abbreviated data?
    * @return array
    */
    function getFirstPosts($type = null, $long = false) {

        // Sanity check
        if ($type && !in_array($type, $this->types)) throw new Exception('Invalid type');

        // SQL Query
        if ($long) $query = "SELECT *, LENGTH(body_plaintext) AS body_length ";
        else $query = "SELECT id, users_id, thread_id, title, LENGTH(body_plaintext) AS body_length, published_on, level ";
        $query .= "FROM {$this->db_table} WHERE thread_pos = 0 AND draft = 0 ";
        if ($type) $query .= "AND {$type} = 1 ";
        $query .= "ORDER BY published_on DESC ";

        // Execute
        $st = $this->db->query($query);
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }


    /**
    * Get first posts by user id
    *
    * @param int $users_id users id
    * @param string $type forum, blog, or wiki
    * @param bool $long select * or abbreviated data?
    * @return array
    */
    function getFirstPostsByUser($users_id, $type = null, $long = false) {

        // Sanity check
        if (!filter_var($users_id, FILTER_VALIDATE_INT)) throw new Exception('Invalid user id');
        if ($type && !in_array($type, $this->types)) throw new Exception('Invalid type');

        // SQL Query
        if ($long) $query = "SELECT *, LENGTH(body_plaintext) AS body_length ";
        else $query = "SELECT id, users_id, thread_id, title, LENGTH(body_plaintext) AS body_length, published_on, level ";
        $query .= "FROM {$this->db_table} WHERE users_id = ? AND thread_pos = 0 AND draft = 0 ";
        if ($type) $query .= "AND {$type} = 1 ";
        $query .= "ORDER BY published_on DESC ";

        // Execute
        $st = $this->db->prepare($query);
        $st->execute(array($users_id));
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }


    /**
    * Get first posts by month (and year)
    *
    * @param int $date date
    * @param string $type forum, blog, or wiki
    * @param bool $long select * or abbreviated data?
    * @return array
    */
    function getFirstPostsByMonth($date, $type = null, $long = false) {

        // Sanity check
        if ($type && !in_array($type, $this->types)) throw new Exception('Invalid type');

        // Get year and month
        // regex must match '2008-06-18 16:53:29' or '2008-06-18T16:53:29-04:00'
        $matches = array();
        $regex = '/^(\d{4})-(0[0-9]|1[0,1,2])-([0,1,2][0-9]|3[0,1]).+(\d{2}):(\d{2}):(\d{2})/';
        if (!preg_match($regex, $date, $matches)) throw new Exception('Invalid date');
        else {
            $date = array();
            $date['year'] = $matches[1]; // year
            $date['month'] = $matches[2]; // month
            $date['day'] = $matches[3]; // day
            $date['hour']  = $matches[4]; // hour
            $date['minute']  = $matches[5]; // minutes
            $date['second'] = $matches[6]; //seconds

        }

        // Get database type
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);

        // SQL Query
        if ($long) $query = "SELECT *, LENGTH(body_plaintext) AS body_length ";
        else $query = "SELECT id, users_id, thread_id, title, LENGTH(body_plaintext) AS body_length, published_on, level ";
        $query .= "FROM {$this->db_table} WHERE thread_pos = 0 AND draft = 0 ";
        if ($type) $query .= "AND {$type} = 1 ";
        // Database specific
        if ($driver == 'mysql') {
            // MySQL
            $query .= "AND MONTH(published_on) = {$date['month']} AND YEAR(published_on) = {$date['year']} ";
            $query .= "AND DAY(published_on) <= {$date['day']} AND TIME(published_on) <= '{$date['hour']}:{$date['minute']}:{$date['second']}' ";
        }
        // Order
        $query .= "ORDER BY published_on DESC ";

        // Execute
        $st = $this->db->query($query);
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }



    /**
    * Get latest replies, i.e. thread_pos != 0
    *
    * @param string $type forum, blog, or wiki
    * @param bool $long select * or abbreviated data?
    * @param int $limit maximum latest replies
    * @return array
    */
    function getRececentComments($type = null, $limit = 10, $long = false) {

        // Sanity check
        if ($type && !in_array($type, $this->types)) throw new Exception('Invalid type');

        // SQL Query
        if ($long) $query = "SELECT *, LENGTH(body_plaintext) AS body_length ";
        else $query = "SELECT id, users_id, thread_id, title, LENGTH(body_plaintext) AS body_length, published_on, level ";
        $query .= "FROM {$this->db_table} WHERE thread_pos != 0 AND draft = 0 ";
        if ($type) $query .= "AND {$type} = 1 ";
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
    * @return array
    */
    function getCommentsCount($thread_id) {

        // Sanity check
        if (!filter_var($thread_id, FILTER_VALIDATE_INT)) throw new Exception('Invalid thread id');

        // SQL Query
        $query = "SELECT COUNT(*) FROM {$this->db_table} WHERE thread_id = ? AND thread_pos != 0 AND draft = 0 ";

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