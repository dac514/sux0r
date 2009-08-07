<?php

/**
* suxUser
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

class suxUser {

    protected $db;
    protected $inTransaction = false;

    /**
    * @param string $key a key from our suxDB DSN
    */
    function __construct($key = null) {
    	$this->db = suxDB::get($key);
        set_exception_handler(array($this, 'logAndDie'));
    }


    function getUser($id, $full_profile = false) {

        if (!ctype_digit(strval($id))) throw new Exception('Invalid user id');

        $st = $this->db->prepare('SELECT id, nickname, email FROM users WHERE id = ? ');
        $st->execute(array($id));
        $user = $st->fetch(PDO::FETCH_ASSOC);

        if ($full_profile) {
            $st = $this->db->prepare('SELECT * FROM users_info WHERE users_id = ? ');
            $st->execute(array($id));
            $tmp = $st->fetch(PDO::FETCH_ASSOC);
            unset($tmp['id'], $tmp['users_id']); // Unset ids
            $user = array_merge($user, $tmp); // Merge
        }

        return $user;

    }


    function getUserByNickame($nickname, $full_profile = false) {

        $st = $this->db->prepare('SELECT id FROM users WHERE nickname = ? ');
        $st->execute(array($nickname));
        $id = $st->fetchColumn();

        if (ctype_digit(strval($id))) return $this->getUser($id, $full_profile);
        else return false;

    }


    function getUserByEmail($email, $full_profile = false) {

        $st = $this->db->prepare('SELECT id FROM users WHERE email = ? ');
        $st->execute(array($email));
        $id = $st->fetchColumn();

        if (ctype_digit(strval($id))) return $this->getUser($id, $full_profile);
        else return false;

    }


    function getUsers() {

        $q = '
        SELECT
        users.id,
        users.nickname,
        users.email,
        users_info.given_name,
        users_info.family_name,
        users_info.street_address,
        users_info.locality,
        users_info.region,
        users_info.postcode,
        users_info.country,
        users_info.tel,
        users_info.url,
        users_info.dob,
        users_info.gender,
        users_info.language,
        users_info.timezone,
        users_info.pavatar,
        users_info.microid
        FROM users LEFT JOIN users_info
        ON users.id = users_info.users_id
        ';

        $st = $this->db->query($q);
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }



    function setUser(array $info, $id = null) {

        // Sanity check
        if ($id != null && !ctype_digit(strval($id))) throw new Exception('Invalid user id');
        unset($info['id'], $info['users_id']); // Don't allow spoofing of the id in the array
        unset($info['accesslevel']); // Don't allow accesslevel changes with this function


        // Encrypt the password
        if (!empty($info['password'])) {
            if (empty($info['nickname'])) throw new Exception('No nickname provided');
            $info['password'] = suxFunct::encrypt_pw($info['nickname'], $info['password']);
        }


        // Move users table info to $user array
        $user = array();
        // Nickname
        if (!empty($info['nickname'])) $user['nickname'] = $info['nickname'];
        unset($info['nickname']);
        // Email
        if (!empty($info['email'])) $user['email'] = $info['email'];
        unset($info['email']);
        // Encrypted password
        if (!empty($info['password'])) $user['password'] = $info['password'];
        unset($info['password']);

        // ----------------------------------------------------------------------------
        // Sql
        // ----------------------------------------------------------------------------

        // Begin transaction
        $this->db->beginTransaction();
        $this->inTransaction = true;

        try {
            if (!$id) {

                // Insert user

                $query = suxDB::prepareInsertQuery('users', $user);
                $st = $this->db->prepare($query);
                $st->execute($user);

                $id = $this->db->lastInsertId();
                $info['users_id'] = $id;

                $query = suxDB::prepareInsertQuery('users_info', $info);
                $st = $this->db->prepare($query);
                $st->execute($info);

            }
            else {

                // Update user

                $query = suxDB::prepareUpdateQuery('users', $user);
                $st = $this->db->prepare($query);
                $st->execute($user);

                $info['users_id'] = $id;

                $query = suxDB::prepareUpdateQuery('users_info', $info, 'users_id');
                $st = $this->db->prepare($query);
                $st->execute($info);

            }

        }
        catch (Exception $e) {
            if ($st->errorCode() == 23000) {
                // SQLSTATE 23000: Constraint violations
                $this->db->rollback();
                $this->inTransaction = false;
                return false;
            }
            else throw ($e); // Hot potato
        }

        // Commit
        $this->db->commit();
        $this->inTransaction = false;

        return true;

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

        $message = "suxUser Error: \n";
        $message .= $e->getMessage() . "\n";
        $message .= "File: " . $e->getFile() . "\n";
        $message .= "Line: " . $e->getLine() . "\n\n";
        $message .= "Backtrace: \n" . print_r($e->getTrace(), true) . "\n\n";
        die("<pre>{$message}</pre>");

    }


}

/*

-- Database

CREATE TABLE `users` (
  `id` int(11) NOT NULL auto_increment,
  `nickname` varchar(64) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `accesslevel` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `nickname` (`nickname`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE `users_info` (
  `id` int(11) NOT NULL auto_increment,
  `users_id` int(11) NOT NULL,
  `given_name` varchar(255) default NULL,
  `family_name` varchar(255) default NULL,
  `street_address` varchar(255) default NULL,
  `locality` varchar(255) default NULL,
  `region` varchar(255) default NULL,
  `postcode` varchar(255) default NULL,
  `country` char(2) default NULL,
  `tel` varchar(255) default NULL,
  `url` varchar(255) default NULL,
  `dob` char(10) default NULL,
  `gender` char(1) default NULL,
  `language` char(2) default NULL,
  `timezone` varchar(255) default NULL,
  `pavatar` varchar(255) default NULL,
  `microid` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

*/

?>