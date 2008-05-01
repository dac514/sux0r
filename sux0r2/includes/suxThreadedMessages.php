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

    public $db_table = 'messages';

    protected $db;
    protected $inTransaction = false;

    /**
    * @param string $key a key from our suxDB DSN
    */
    function __construct($key = null) {
    	$this->db = suxDB::get($key);
        set_exception_handler(array($this, 'logAndDie'));
    }


    // Saves a message to the database
    function saveMessage($users_id, $subject, $body, $parent_id = null ) {

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

        // Sanity check
        $parent_id = filter_var($parent_id, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);

        // Begin transaction
        $this->db->beginTransaction();
        $this->inTransaction = true;

        if ($parent_id) {

            // Get thread_id, level, and thread_pos from parent
            $st = $this->db->prepare("SELECT thread_id, level, thread_pos FROM {$this->db_table} WHERE id = ? ");
            $st->execute(array($parent_id));
            $parent = $st->fetch();

            // a reply's level is one greater than its parent's
            $level = $parent['level'] + 1;

            // what is the biggest thread_pos in this thread among messages with the same parent?
            $st = $this->db->prepare("SELECT MAX(thread_pos) FROM {$this->db_table} WHERE thread_id = ? AND parent_id = ? ");
            $st->execute(array($parent['thread_id'], $parent_id));
            $thread_pos = $st->fetchColumn(0);

            if ($thread_pos) {
                // this thread_pos goes after the biggest existing one
                $thread_pos++;
            }
            else {
                // this is the first reply, so put it right after the parent
                $thread_pos = $parent['thread_pos'] + 1;
            }

            // increment the thread_pos of all messages in the thread that come after this one
            $st = $this->db->prepare("UPDATE {$this->db_table} SET thread_pos = thread_pos + 1 WHERE thread_id = ? AND thread_pos >= ? ");
            $st->execute(array($parent['thread_id'], $thread_pos));

            // the new message should be saved with the parent's thread_id
            $thread_id = $parent['thread_id'];

        }
        else {

            // The message is not a reply, so it's the start of a new thread
            $level = 0;
            $thread_pos = 0;
            $thread_id = $this->db->query("SELECT MAX(thread_id) + 1 FROM {$this->db_table} ")->fetchColumn(0);

        }

        // Sanity check
        if(!$thread_id) $thread_id = 1;

        // Convert to UTF-8 plaintext
        include_once(dirname(__FILE__) . '/suxHtml2UTF8.php');
        $converter = new suxHtml2UTF8($body);
        $body_plaintext = $converter->getText();

        // Insert the message into the database
        $insert = array(
            'thread_id' => $thread_id,
            'parent_id' => $parent_id,
            'thread_pos' => $thread_pos,
            'posted_on' => date('c'),
            'level' => $level,
            'users_id' => $users_id,
            'subject' => $subject,
            'body_html' => $body,
            'body_plaintext' => $body_plaintext,
            );
        $query = suxDB::prepareInsertQuery($this->db_table, $insert);
        $st = $this->db->prepare($query);
        $st->execute($insert);

        // Commit
        $this->db->commit();
        $this->inTransaction = false;

    }


    // Gets an array of all messages
    function getThread($thread_id = null) {

        // Sanity check
        $thread_id = filter_var($thread_id, FILTER_VALIDATE_INT);

        // order the messages by their thread_id and their position
        $query = "SELECT id, users_id, subject, LENGTH(body) AS body_length, posted_on, level FROM {$this->db_table} ";
        if ($thread_id) $query .= 'WHERE thread_id = ? ';
        $query .= 'ORDER BY thread_id, thread_pos ';

        if ($thread_id) {
            $st = $this->db->prepare($query);
            $st->execute(array($thread_id));
        }
        else {
            $st = $this->db->query($query);
        }

        // Get the messages
        $messages = array();
        foreach ($st->fetchAll() as $row) {
            $messages[] = $row;
        }

        return $messages;

    }


    // Get a message by id
    function getMessage($id) {

        // Sanity check
        if (!filter_var($id, FILTER_VALIDATE_INT)) throw new Exception('Invalid message id');

        $st = $this->db->prepare("SELECT * FROM {$this->db_table} WHERE id = ? ");
        $st->execute(array($id));

        $message = $st->fetch();
        if (!$message) {
            throw new Exception('Invalid message id');
        }

        return $message;

    }


    // Gets an array of all the users messgaes
    function getUserMessages($users_id) {

        // Sanity check
        if (!filter_var($users_id, FILTER_VALIDATE_INT)) throw new Exception('Invalid user id');

        // order the messages by their thread_id and their position
        $query = "SELECT id, thread_id, users_id, subject, LENGTH(body) AS body_length, posted_on FROM {$this->db_table} ";
        $query .= 'WHERE users_id = ? ';
        $query .= 'ORDER BY posted_on DESC ';

        $st = $this->db->prepare($query);
        $st->execute(array($users_id));

        // Get the messages
        $messages = array();
        foreach ($st->fetchAll() as $row) {
            $messages[] = $row;
        }

        return $messages;

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

        $message = "suxThreadedMessages Error: \n";
        $message .= $e->getMessage() . "\n";
        $message .= "File: " . $e->getFile() . "\n";
        $message .= "Line: " . $e->getLine() . "\n\n";
        $message .= "Backtrace: \n" . print_r($e->getTrace(), true) . "\n\n";
        die("<pre>{$message}</pre>");

    }


}

/*

-- Database

CREATE TABLE `messages` (
  `id` int(11) NOT NULL auto_increment,
  `posted_on` datetime NOT NULL,
  `users_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body_html` text NOT NULL,
  `body_plaintext` text NOT NULL,
  `thread_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `level` int(11) NOT NULL,
  `thread_pos` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


*/

?>