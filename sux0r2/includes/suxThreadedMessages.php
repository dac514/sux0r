<?php

/**
* suxThreadedMessages
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class suxThreadedMessages {

    // Database stuff
    protected $db;
    protected $inTransaction = false;
    protected $db_driver;
    // InnoDB
    protected $db_table = 'messages';
    protected $db_table_hist = 'messages_history';

    // Object properties, with defaults
    protected $published = true;
    protected $order = array('published_on', 'DESC');


    /*
    Currently:
    blog -> first post in thread is blog, everything else is a comment

    Todo:
    forum -> Regular threaded messages
    wiki -> First post in thread is wiki, everything else is discussion
    slideshow -> Powerpoint style, sequence of messages defined by thread order,
    */

    protected $types = array('blog', 'forum', 'wiki', 'slideshow');


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


    // -------------------------------------------------------------------
    // Individual messages
    // -------------------------------------------------------------------


    /**
    * Get a message by id
    *
    * @param int $id messages_id
    * @return array|false
    */
    function getByID($id) {

        // Sanity check
        if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1)
            throw new Exception('Invalid message id');

        $query = "SELECT * FROM {$this->db_table} WHERE id = ? ";

        // Publish / Draft
        if (is_bool($this->published)) $query .= 'AND ' . $this->sqlPublished();

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
    * @param int $trusted passed on to sanitizeHtml()
    * @return int insert id
    */
    function save($users_id, array $msg, $trusted = -1) {

        // -------------------------------------------------------------------
        // Sanitize
        // -------------------------------------------------------------------

        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1)
            throw new Exception('Invalid user id');

        if (!isset($msg['title']) || !isset($msg['body']))
            throw new Exception('Invalid $msg array');

        // Message id
        if (isset($msg['id'])) {
            if (!filter_var($msg['id'], FILTER_VALIDATE_INT) || $msg['id'] < 1) throw new Exception('Invalid message id');
            else $clean['id'] = $msg['id'];
        }

        // Users id
        $clean['users_id'] = $users_id;

        // Parent_id
        $clean['parent_id'] = @filter_var($msg['parent_id'], FILTER_VALIDATE_INT);
        if ($clean['parent_id'] === false) $clean['parent_id'] = 0;

        // No HTML in title
        $clean['title'] = strip_tags($msg['title']);

        // Sanitize HTML in body
        $clean['body_html'] = suxFunct::sanitizeHtml($msg['body'], $trusted);

        // Convert and copy body to UTF-8 plaintext
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
        else $clean['published_on'] = date('Y-m-d H:i:s');

        // Draft, boolean / tinyint
        $clean['draft'] = false;
        if (isset($msg['draft']) && $msg['draft']) $clean['draft'] = true;

        // Types of threaded messages
        $clean['blog'] = false;
        $clean['forum'] = false;
        $clean['wiki'] = false;
        $clean['slideshow'] = false;
        if (isset($msg['blog']) && $msg['blog']) $clean['blog'] = true;
        if (isset($msg['forum']) && $msg['forum']) $clean['forum'] = true;
        if (isset($msg['wiki']) && $msg['wiki']) $clean['wiki'] = true;
        if (isset($msg['slideshow']) && $msg['slideshow']) $clean['slideshow'] = true;

        if (!$clean['forum'] && !$clean['blog'] && !$clean['wiki'] && !$clean['slideshow'])
            throw new Exception('No message type specified?');

        // We now have the $clean[] array

        // -------------------------------------------------------------------
        // Go!
        // -------------------------------------------------------------------

        // Begin transaction
        $tid = suxDB::requestTransaction();
        $this->inTransaction = true;

        if (isset($clean['id'])) {

            // UPDATE

            // Get $edit[] array in order to keep a history
            $query = "SELECT title, image, body_html, body_plaintext FROM {$this->db_table} WHERE id = ? ";
            $st = $this->db->prepare($query);
            $st->execute(array($clean['id']));
            $edit = $st->fetch(PDO::FETCH_ASSOC);

            if (!$edit) throw new Exception('No message to edit?');

            $edit['messages_id'] = $clean['id'];
            $edit['users_id'] = $clean['users_id'];
            $edit['edited_on'] = date('Y-m-d H:i:s');
            $query = suxDB::prepareInsertQuery($this->db_table_hist, $edit);
            $st = $this->db->prepare($query);
            $st->execute($edit);

            unset($clean['users_id']); // Don't override the original publisher

            // Update the message
            $query = suxDB::prepareUpdateQuery($this->db_table, $clean);

        }
        else {

            // INSERT

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

            $parent = false;
            if ($clean['parent_id']) {

                // Get thread_id, level, and thread_pos from parent
                $st = $this->db->prepare("SELECT thread_id, level, thread_pos FROM {$this->db_table} WHERE id = ? ");
                $st->execute(array($clean['parent_id']));
                $parent = $st->fetch(PDO::FETCH_ASSOC);
            }

            if ($parent) {

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

            // Insert the message
            $query = suxDB::prepareInsertQuery($this->db_table, $clean);

        }

        $st = $this->db->prepare($query);

        // http://bugs.php.net/bug.php?id=44597
        // As of 5.2.6 you still can't use this function's $input_parameters to
        // pass a boolean to PostgreSQL. To do that, you'll have to call
        // bindParam() with explicit types for *each* parameter in the query.
        // Annoying much? This sucks more than you can imagine.

        if  ($this->db_driver == 'pgsql') {

            if (isset($clean['id'])) $st->bindParam(':id', $clean['id'], PDO::PARAM_INT);
            else {
                $st->bindParam(':thread_id', $clean['thread_id'], PDO::PARAM_INT);
                $st->bindParam(':level', $clean['level'], PDO::PARAM_INT);
                $st->bindParam(':thread_pos', $clean['thread_pos'], PDO::PARAM_INT);
            }

            if (isset($clean['users_id'])) $st->bindParam(':users_id', $clean['users_id'], PDO::PARAM_INT);

            $st->bindParam(':title', $clean['title'], PDO::PARAM_STR);
            $st->bindParam(':body_html', $clean['body_html'], PDO::PARAM_STR);
            $st->bindParam(':body_plaintext', $clean['body_plaintext'], PDO::PARAM_STR);
            $st->bindParam(':draft', $clean['draft'], PDO::PARAM_BOOL);
            $st->bindParam(':parent_id', $clean['parent_id'], PDO::PARAM_INT);

            if (isset($clean['image'])) $st->bindParam(':image', $clean['image'], PDO::PARAM_STR);
            if (isset($clean['published_on'])) $st->bindParam(':published_on', $clean['published_on'], PDO::PARAM_STR);

            $st->bindParam(':forum', $clean['forum'], PDO::PARAM_BOOL);
            $st->bindParam(':blog', $clean['blog'], PDO::PARAM_BOOL);
            $st->bindParam(':wiki', $clean['wiki'], PDO::PARAM_BOOL);
            $st->bindParam(':slideshow', $clean['slideshow'], PDO::PARAM_BOOL);

            $st->execute();
        }
        else {
            $st->execute($clean);
        }

        // MySQL InnoDB with transaction reports the last insert id as 0 after
        // commit, the real ids are only reported before committing.

        if (isset($clean['id'])) $insert_id = $clean['id'];
        elseif ($this->db_driver == 'pgsql') $insert_id = $this->db->lastInsertId("{$this->db_table}_id_seq"); // PgSql
        else $insert_id = $this->db->lastInsertId();

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

        // A static variable exists only in a local function scope, but it does
        // not lose its value when program execution leaves this scope.
        // $max_pos is initialized only in first call of function
        static $max_pos = 0;

        $st = $this->db->prepare("SELECT id, thread_pos FROM {$this->db_table} WHERE thread_id = ? AND parent_id = ? ORDER BY thread_pos DESC ");
        $st->execute(array($thread_id, $parent_id));
        $result = $st->fetch(PDO::FETCH_ASSOC);

        if ($result && $result['thread_pos'] && $max_pos < $result['thread_pos']) {
            $max_pos = $result['thread_pos'];
            return $this->biggestThreadPos($thread_id, $result['id']);
        }

        return $max_pos;

    }


    /**
    * Delete message
    *
    * @param int $id messages id
    */
    function delete($id) {

        if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1) return false;

        // Begin transaction
        $tid = suxDB::requestTransaction();
        $this->inTransaction = true;

        $st = $this->db->prepare("DELETE FROM {$this->db_table} WHERE id = ? ");
        $st->execute(array($id));

        $st = $this->db->prepare("DELETE FROM {$this->db_table_hist} WHERE messages_id = ? ");
        $st->execute(array($id));

        // Delete links, too
        $link = new suxLink();
        $links = $link->getLinkTables('messages');
        foreach ($links as $table) {
            $link->deleteLink($table, 'messages', $id);
        }

        // Commit
        suxDB::commitTransaction($tid);
        $this->inTransaction = false;

    }


    /**
    * Get first post
    *
    * @param int $thread_id thread_id
    * @return array
    */
    function getFirstPost($thread_id) {

        // Sanity check
        if (!filter_var($thread_id, FILTER_VALIDATE_INT) || $thread_id < 1)
            throw new Exception('Invalid thread id');

        // SQL Query
        $query = "SELECT * FROM {$this->db_table} WHERE thread_id = ? AND thread_pos = 0 ";

        // Publish / Draft
        if (is_bool($this->published)) $query .= 'AND ' . $this->sqlPublished();

        // Order
        $query .= 'ORDER BY ' . $this->sqlOrder();

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
    * @param bool $published select un-published?
    * @return int
    */
    function countThread($thread_id, $type = null) {

        // Sanity check
        if (!filter_var($thread_id, FILTER_VALIDATE_INT) || $thread_id < 1)
            throw new Exception('Invalid thread id');

        if ($type && !in_array($type, $this->types))
            throw new Exception('Invalid type');

        // SQL Query
        else $query = "SELECT COUNT(*) FROM {$this->db_table} WHERE thread_id = ? ";

        // Publish / Draft
        if (is_bool($this->published)) $query .= 'AND ' . $this->sqlPublished();

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
    * @param int $limit sql limit value
    * @param int $start sql start of limit value
    * @param int $thread_id thread id
    * @param string $type forum, blog, wiki, or slideshow
    * @param bool $published select un-published?
    * @return array
    */
    function getThread($limit = null, $start = 0, $thread_id = null, $type = null) {

        // Sanity check
        if ($thread_id && (!filter_var($thread_id, FILTER_VALIDATE_INT) || $thread_id < 1))
            throw new Exception('Invalid thread id');

        if ($type && !in_array($type, $this->types))
            throw new Exception('Invalid type');

        // SQL Query
        $query = "SELECT * FROM {$this->db_table} ";
        if ($thread_id) $query .= 'WHERE thread_id = ? ';

        // Publish / Draft
        if (is_bool($this->published)) {
            $query .= ($thread_id) ? 'AND ' : 'WHERE ';
            $query .= $this->sqlPublished();
        }

        // Type
        if ($type) $query .= "AND {$type} = true ";

        // Order
        $query .= 'ORDER BY thread_id, thread_pos, ' . $this->sqlOrder();

        // Limit
        if ($start && $limit) $query .= "LIMIT {$limit} OFFSET {$start} ";
        elseif ($limit) $query .= "LIMIT {$limit} ";


        // Execute
        $st = $this->db->prepare($query);
        if ($thread_id) $st->execute(array($thread_id));
        else $st->execute();

        return $st->fetchAll(PDO::FETCH_ASSOC);


    }


    /**
    * Count messages by user id
    *
    * @param int $users_id users id
    * @param string $type forum, blog, wiki, or slideshow
    * @return int
    */
    function countMessagesByUser($users_id, $type = null) {

        // Sanity check
        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1)
            throw new Exception('Invalid user id');

        if ($type && !in_array($type, $this->types))
            throw new Exception('Invalid type');

        // SQL Query
        $query = "SELECT COUNT(*) FROM {$this->db_table} WHERE users_id = ? ";

        // Publish / Draft
        if (is_bool($this->published)) $query .= 'AND ' . $this->sqlPublished();

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
    * @param int $limit sql limit value
    * @param int $start sql start of limit value
    * @param string $type forum, blog, wiki, or slideshow
    * @return array
    */
    function groupMessagesByUser($limit = null, $start = 0, $type = null) {

        // Sanity check
        if ($type && !in_array($type, $this->types)) throw new Exception('Invalid type');

        // Mysql / PgSql
        $query = "SELECT COUNT(*) AS count, users_id FROM {$this->db_table} ";

        // Publish / Draft
        if (is_bool($this->published)) $query .= 'AND ' . $this->sqlPublished();

        // Type
        if ($type) $query .= "AND {$type} = true ";

        // Group by, order
        $query .= "GROUP BY users_id ORDER BY count DESC ";

        // Limits
        if ($start && $limit) $query .= "LIMIT {$limit} OFFSET {$start} ";
        elseif ($limit) $query .= "LIMIT {$limit} ";

        // Execute
        $st = $this->db->query($query);
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }


    /**
    * Get messages by user id
    *
    * @param int $users_id users id
    * @param int $limit sql limit value
    * @param int $start sql start of limit value
    * @param string $type forum, blog, wiki, or slideshow
    * @return array
    */
    function getMessagesByUser($users_id, $limit = null, $start = 0, $type = null) {

        // Sanity check
        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1)
            throw new Exception('Invalid user id');

        if ($type && !in_array($type, $this->types))
            throw new Exception('Invalid type');

        // SQL Query
        $query = "SELECT * FROM {$this->db_table} WHERE users_id = ? ";

        // Publish / Draft
        if (is_bool($this->published)) $query .= 'AND ' . $this->sqlPublished();

        // Type
        if ($type) $query .= "AND {$type} = true ";

        $query .= 'ORDER BY ' . $this->sqlOrder(); // Order

        // Limit
        if ($start && $limit) $query .= "LIMIT {$limit} OFFSET {$start} ";
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
    * @return int
    */
    function countFirstPosts($type = null) {

        // Sanity check
        if ($type && !in_array($type, $this->types)) throw new Exception('Invalid type');

        // SQL Query
        $query = "SELECT COUNT(*) FROM {$this->db_table} WHERE thread_pos = 0 ";

        // Publish / Draft
        if (is_bool($this->published)) $query .= 'AND ' . $this->sqlPublished();

        // Type
        if ($type) $query .= "AND {$type} = true ";

        // Execute
        $st = $this->db->query($query);
        return $st->fetchColumn();

    }




    /**
    * Get first posts
    *
    * @param int $limit sql limit value
    * @param int $start sql start of limit value
    * @param string $type forum, blog, wiki, or slideshow
    * @return array
    */
    function getFirstPosts($limit = null, $start = 0, $type = null) {

        // Sanity check
        if ($type && !in_array($type, $this->types)) throw new Exception('Invalid type');

        // SQL Query
        $query = "SELECT * FROM {$this->db_table} WHERE thread_pos = 0 ";

        // Publish / Draft
        if (is_bool($this->published)) $query .= 'AND ' . $this->sqlPublished();

        // Type
        if ($type) $query .= "AND {$type} = true ";

        $query .= 'ORDER BY ' . $this->sqlOrder(); // Order

        // Limit
        if ($start && $limit) $query .= "LIMIT {$limit} OFFSET {$start} ";
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
    * @return int
    */
    function countFirstPostsByUser($users_id, $type = null) {

        // Sanity check
        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1)
            throw new Exception('Invalid user id');

        if ($type && !in_array($type, $this->types))
            throw new Exception('Invalid type');

        // SQL Query
        $query = "SELECT COUNT(*) FROM {$this->db_table} WHERE users_id = ? AND thread_pos = 0 ";

        // Publish / Draft
        if (is_bool($this->published)) $query .= 'AND ' . $this->sqlPublished();

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
    * @param int $limit sql limit value
    * @param int $start sql start of limit value
    * @param string $type forum, blog, wiki, or slideshow
    * @return array
    */
    function groupFirstPostsByUser($limit = null, $start = 0, $type = null) {

        // Sanity check
        if ($type && !in_array($type, $this->types)) throw new Exception('Invalid type');

        // MySql / PgSql
        $query = "SELECT COUNT(*) AS count, users_id
        FROM {$this->db_table} WHERE thread_pos = 0 ";

        // Publish / Draft
        if (is_bool($this->published)) $query .= 'AND ' . $this->sqlPublished();

        // Type
        if ($type) $query .= "AND {$type} = true ";

        // Order
        $query .= "GROUP BY users_id ORDER BY count DESC ";

        // Limits
        if ($start && $limit) $query .= "LIMIT {$limit} OFFSET {$start} ";
        elseif ($limit) $query .= "LIMIT {$limit} ";

        // Execute
        $st = $this->db->query($query);
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }


    /**
    * Get first posts by user id
    *
    * @param int $users_id users id
    * @param int $limit sql limit value
    * @param int $start sql start of limit value
    * @param string $type forum, blog, wiki, or slideshow
    * @return array
    */
    function getFirstPostsByUser($users_id, $limit = null, $start = 0, $type = null) {

        // Sanity check
        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1)
            throw new Exception('Invalid user id');

        if ($type && !in_array($type, $this->types))
            throw new Exception('Invalid type');

        // SQL Query
        $query = "SELECT * FROM {$this->db_table} WHERE users_id = ? AND thread_pos = 0 ";

        // Publish / Draft
        if (is_bool($this->published)) $query .= 'AND ' . $this->sqlPublished();

        // Type
        if ($type) $query .= "AND {$type} = true ";

        $query .= 'ORDER BY ' . $this->sqlOrder(); // Order

        // Limit
        if ($start && $limit) $query .= "LIMIT {$limit} OFFSET {$start} ";
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
    * @return int
    */
    function countFirstPostsByMonth($date, $type = null) {

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
        if (is_bool($this->published)) $query .= 'AND ' . $this->sqlPublished();

        // Group
        $date = "{$matches[1]}-{$matches[2]}-{$matches[3]} {$matches[4]}:{$matches[5]}:{$matches[6]}";
        if ($this->db_driver == 'mysql') {
            // MySql
            $query .= "AND MONTH(published_on) =  MONTH('{$date}') "; // Month
            $query .= "AND YEAR(published_on) = YEAR('{$date}') "; // Year
        }
        elseif ($this->db_driver == 'pgsql') {
            // PgSQL
            $query .= "AND EXTRACT(MONTH FROM published_on) =  EXTRACT(MONTH FROM timestamp '{$date}') "; // Month
            $query .= "AND EXTRACT(YEAR FROM published_on) = EXTRACT(YEAR FROM timestamp '{$date}') "; // Year
        }
        else {
            throw new Exception('Unsupported database driver');
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
    * @param int $limit sql limit value
    * @param int $start sql start of limit value
    * @param string $type forum, blog, wiki, or slideshow
    * @return array
    */
    function groupFirstPostsByMonths($limit = null, $start = 0, $type = null) {

        // Sanity check
        if ($type && !in_array($type, $this->types)) throw new Exception('Invalid type');

        // Group
        if ($this->db_driver == 'mysql') {
            // MySql
            $query = "SELECT COUNT(*) AS count,
            YEAR(published_on) AS year,
            MONTH(published_on) AS month
            FROM {$this->db_table} WHERE thread_pos = 0 ";

        }
        elseif ($this->db_driver == 'pgsql') {
            // PgSql
            $query = "SELECT DISTINCT COUNT(*) AS count,
            EXTRACT(YEAR FROM published_on) AS year,
            EXTRACT(MONTH FROM published_on) AS month
            FROM {$this->db_table} WHERE thread_pos = 0 ";

        }
        else {
            throw new Exception('Unsupported database driver');
        }

        // Publish date / draft
        if (is_bool($this->published)) $query .= 'AND ' . $this->sqlPublished();

        if ($type) $query .= "AND {$type} = true "; // Type

        $query .= "GROUP BY year, month ORDER BY year DESC, month DESC "; // Group

        // Limits
        if ($start && $limit) $query .= "LIMIT {$limit} OFFSET {$start} ";
        elseif ($limit) $query .= "LIMIT {$limit} ";

        // Execute
        $st = $this->db->query($query);
        return $st->fetchAll(PDO::FETCH_ASSOC);


    }


    /**
    * Get first posts by month
    *
    * @param string $date date
    * @param int $limit sql limit value
    * @param int $start sql start of limit value
    * @param string $type forum, blog, wiki, or slideshow
    * @return array
    */
    function getFirstPostsByMonth($date, $limit = null, $start = 0, $type = null ) {

        // Sanity check
        if ($type && !in_array($type, $this->types)) throw new Exception('Invalid type');

        // Get year and month
        // regex must match '2008-06-18 16:53:29' or '2008-06-18T16:53:29-04:00'
        $matches = array();
        $regex = '/^(\d{4})-(0[0-9]|1[0,1,2])-([0,1,2][0-9]|3[0,1]).+(\d{2}):(\d{2}):(\d{2})/';
        if (!preg_match($regex, $date, $matches)) throw new Exception('Invalid date');

        // SQL Query
        $query = "SELECT * FROM {$this->db_table} WHERE thread_pos = 0 ";

        // Publish date / draft
        if (is_bool($this->published)) $query .= 'AND ' . $this->sqlPublished();

        // Group
        $date = "{$matches[1]}-{$matches[2]}-{$matches[3]} {$matches[4]}:{$matches[5]}:{$matches[6]}";
        if ($this->db_driver == 'mysql') {
            // MySql
            $query .= "AND MONTH(published_on) =  MONTH('{$date}') "; // Month
            $query .= "AND YEAR(published_on) = YEAR('{$date}') "; // Year
        }
        elseif ($this->db_driver == 'pgsql') {
            // PgSql
            $query .= "AND EXTRACT(MONTH FROM published_on) =  EXTRACT(MONTH FROM timestamp '{$date}')  "; // Month
            $query .= "AND EXTRACT(YEAR FROM published_on) =  EXTRACT(YEAR FROM timestamp '{$date}') "; // Year
        }
        else {
            throw new Exception('Unsupported database driver');
        }

        // Type
        if ($type) $query .= "AND {$type} = true ";

        $query .= 'ORDER BY ' . $this->sqlOrder(); // Order

        // Limit
        if ($start && $limit) $query .= "LIMIT {$limit} OFFSET {$start} ";
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

            $st = $this->db->prepare("DELETE FROM {$this->db_table} WHERE id = ? ");
            $st->execute(array($val['id']));

            $st = $this->db->prepare("DELETE FROM {$this->db_table_hist} WHERE messages_id = ? ");
            $st->execute(array($val['id']));

        }

        // Delete links, too
        $link = new suxLink();
        $links = $link->getLinkTables('messages');
        foreach($result as $key => $val) {
            foreach ($links as $table) {
                $link->deleteLink($table, 'messages', $val['id']);
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
    * @param int $limit maximum latest replies
    * @param string $type forum, blog, wiki, or slideshow
    * @return array
    */
    function getRececentComments($limit = 10, $type = null) {

        // Sanity check
        if ($type && !in_array($type, $this->types)) throw new Exception('Invalid type');

        // SQL Query
        $query = "SELECT * FROM {$this->db_table} WHERE thread_pos != 0 ";

        // Publish date / draft
        if (is_bool($this->published)) $query .= 'AND ' . $this->sqlPublished();

        // Type
        if ($type) $query .= "AND {$type} = true ";

        $query .= 'ORDER BY ' . $this->sqlOrder(); // Order

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
    * @return int
    */
    function getCommentsCount($thread_id) {

        // Sanity check
        if (!filter_var($thread_id, FILTER_VALIDATE_INT) || $thread_id < 1)
            throw new Exception('Invalid thread id');

        // SQL Query
        $query = "SELECT COUNT(*) FROM {$this->db_table} WHERE thread_id = ? AND thread_pos != 0 ";

        // Publish date / draft
        if (is_bool($this->published)) $query .= 'AND ' . $this->sqlPublished();

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

