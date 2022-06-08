<?php

/**
* suxSocialNetwork
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

// Based on version 1.1 of the XFN meta data profile
// See: http://www.gmpg.org/xfn/index

class suxSocialNetwork {

    // Database suff
    protected $db;
    protected $inTransaction = false;
    // Tables
    protected $db_table = 'socialnetwork';

    // Enum (zero or one value)
    private array $xfn_identity = array('me');
    private array $xfn_friendship = array('contact', 'acquaintance', 'friend');
    private array $xfn_geographical = array('co-resident', 'neighbor');
    private array $xfn_family = array('child', 'parent', 'sibling', 'spouse', 'kin');
    // Set (zero or more values)
    private array $xfn_physical = array('met');
    private array $xfn_professional = array('co-worker', 'colleague');
    private array $xfn_romantic = array('muse', 'crush', 'date', 'sweetheart');


    /**
    * Constructor
    */
    function __construct() {

        $this->db = suxDB::get();
        set_exception_handler(array($this, 'exceptionHandler'));

    }


    /**
    * Get one relationship
    *
    * @param int $uid users_id
    * @param int $fid the users_id of the friend
    * @return array
    */
    function getRelationship($uid, $fid) {

        if (!filter_var($uid, FILTER_VALIDATE_INT) || $uid < 1) throw new Exception('Invalid user id');

        $st = $this->db->prepare("SELECT id, friend_users_id, relationship FROM {$this->db_table} WHERE users_id = ? AND friend_users_id = ? ");
        $st->execute(array($uid, $fid));
        return $st->fetch(PDO::FETCH_ASSOC);

    }


    /**
    * Count relationships
    *
    * @param int $uid users_id
    * @return int
    */
    function countRelationships($uid) {

        if (!filter_var($uid, FILTER_VALIDATE_INT) || $uid < 1) throw new Exception('Invalid user id');

        // SQL Query
        $query = "SELECT COUNT(*) FROM {$this->db_table} WHERE users_id = ? ";

        // Execute
        $st = $this->db->prepare($query);
        $st->execute(array($uid));
        return $st->fetchColumn();


    }


    /**
    * Get relationships
    *
    * @param int $uid users_id
    * @param int $limit sql limit value
    * @param int $start sql start of limit value
    * @return array
    */
    function getRelationships($uid, $limit = null, $start = 0) {

        if (!filter_var($uid, FILTER_VALIDATE_INT) || $uid < 1) throw new Exception('Invalid user id');

        $query = "SELECT id, friend_users_id, relationship FROM {$this->db_table} WHERE users_id = ? ";

        // Limit
        if ($start && $limit) $query .= "LIMIT {$limit} OFFSET {$start} ";
        elseif ($limit) $query .= "LIMIT {$limit} ";

        $st = $this->db->prepare($query);
        $st->execute(array($uid));
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }


    /**
    * Count relationships
    *
    * @param int $uid users_id
    * @return int
    */
    function countStalkers($uid) {

        if (!filter_var($uid, FILTER_VALIDATE_INT) || $uid < 1) throw new Exception('Invalid user id');

        // SQL Query
        $query = "SELECT COUNT(*) FROM {$this->db_table} WHERE friend_users_id = ? ";

        // Execute
        $st = $this->db->prepare($query);
        $st->execute(array($uid));
        return $st->fetchColumn();


    }


    /**
    * Get stalkers
    *
    * @param int $uid users_id
    * @param int $limit sql limit value
    * @param int $start sql start of limit value
    * @return array
    */
    function getStalkers($uid, $limit = null, $start = 0) {

        if (!filter_var($uid, FILTER_VALIDATE_INT) || $uid < 1) throw new Exception('Invalid user id');

        $query = "SELECT id, users_id, relationship FROM {$this->db_table} WHERE friend_users_id = ? ";

        // Limit
        if ($start && $limit) $query .= "LIMIT {$limit} OFFSET {$start} ";
        elseif ($limit) $query .= "LIMIT {$limit} ";

        $st = $this->db->prepare($query);
        $st->execute(array($uid));
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }



    /**
    * Convert a relationship string into an array
    *
    * @param string relationship based on XFN
    * @return array
    */
    function relationshipArray($rel) {

        $rel = strip_tags($rel); // Strip tags
        $rel = mb_strtolower($rel);
        $rel = mb_split("\s", $rel); // split on whitespace

        $identity = '';
        $friendship = '';
        $geographical = '';
        $family = '';
        $physical = '';
        $professional = '';
        $romantic = '';

        foreach ($rel as $val) {

            $val = trim($val);

            // This is me, abort
            if (in_array($val, $this->xfn_identity)) {
                $identity = "$val ";
                break;
            }
            // Enum, overwrite
            elseif (in_array($val, $this->xfn_friendship)) $friendship = $val;
            elseif (in_array($val, $this->xfn_geographical)) $geographical = $val;
            elseif (in_array($val, $this->xfn_family)) $family = $val;
            // Set, append
            elseif (in_array($val, $this->xfn_physical)) $physical .= "$val ";
            elseif (in_array($val, $this->xfn_professional)) $professional .= "$val ";
            elseif (in_array($val, $this->xfn_romantic)) $romantic .= "$val ";

        }

        $physical = rtrim($physical);
        $professional = rtrim($professional);
        $romantic = rtrim($romantic);

        return array($identity, $friendship, $physical, $professional, $geographical, $family, $romantic);

    }


    /**
    * Save relationship
    *
    * @param int $uid users_id
    * @param int $fid the users_id of the friend
    * @param string $rel relationship based on XFN
    * @return bool
    */
    function saveRelationship($uid, $fid, $rel) {

        // --------------------------------------------------------------------
        // Sanitize
        // --------------------------------------------------------------------

        if (!filter_var($uid, FILTER_VALIDATE_INT) || $uid < 1) throw new Exception('Invalid user id');
        if (!filter_var($fid, FILTER_VALIDATE_INT) || $fid < 1) throw new Exception('Invalid friend id');

        [$identity, $friendship, $physical, $professional, $geographical, $family, $romantic] = $this->relationshipArray($rel);

        if ($identity) {
            $rel = $identity;
        }
        else {
            $rel = "$friendship $physical $professional $geographical $family $romantic";
            $rel = preg_replace('/\s+/', ' ', $rel); // Normalize whitespaces
        }
        $rel = trim($rel);

        // --------------------------------------------------------------------
        // Go!
        // --------------------------------------------------------------------

        $st = $this->db->prepare("SELECT COUNT(*) FROM {$this->db_table} WHERE users_id = ? AND friend_users_id = ? ");
        $st->execute(array($uid, $fid));

        $socialnetwork = array(
            'users_id' => $uid,
            'friend_users_id' => $fid,
            'relationship' => $rel,
            );

        if ($st->fetchColumn() > 0) {
            // UPDATE
            $query = "UPDATE {$this->db_table} SET relationship = :relationship WHERE users_id = :users_id AND friend_users_id = :friend_users_id ";
            $st = $this->db->prepare($query);
            return $st->execute($socialnetwork);

        }
        else {
            // INSERT
            $query = suxDB::prepareInsertQuery($this->db_table, $socialnetwork);
            $st = $this->db->prepare($query);
            return $st->execute($socialnetwork);
        }


    }


    /**
    * Delete relationship
    *
    * @param int $uid users_id
    * @param int $fid the users_id of the friend
    * @return bool
    */
    function deleteRelationship($uid, $fid) {

        if (!filter_var($uid, FILTER_VALIDATE_INT) || $uid < 1) throw new Exception('Invalid user id');
        if (!filter_var($fid, FILTER_VALIDATE_INT) || $fid < 1) throw new Exception('Invalid friend id');

        $st = $this->db->prepare("DELETE FROM {$this->db_table} WHERE users_id = ? AND friend_users_id = ? ");
        return $st->execute(array($uid, $fid));

    }


    /**
    * Delete relationship by id
    *
    * @param int $id primary key
    * @return bool
    */
    function deleteRelationshipById($id) {

        if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1) return false;

        $st = $this->db->prepare("DELETE FROM {$this->db_table} WHERE id = ? ");
        return $st->execute(array($id));

    }



    // ----------------------------------------------------------------------------
    // Exception Handler
    // ----------------------------------------------------------------------------


    /**
    * @param Exception $e an Exception class
    */
    function exceptionHandler(\Throwable $e) {

        if ($this->db && $this->inTransaction) {
            $this->db->rollback();
            $this->inTransaction = false;
        }

        throw($e); // Hot potato!

    }


}

