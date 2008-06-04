<?php

/**
* suxNbUser
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

require_once(dirname(__FILE__) . '/../../includes/suxNaiveBayesian.php');

class suxNbUser extends suxNaiveBayesian {

    protected $db_table_auth = 'users_bayes';

    private $module = 'bayes'; // Module

    /**
    * Constructor
    */
    function __construct() {
        parent::__construct(); // Call parent
    }


    /**
    * @param int $vector_id vector id
    * @param int $users_id users id
    * @return bool
    */
    function isVectorOwner($vector_id, $users_id) {

        if (!filter_var($vector_id, FILTER_VALIDATE_INT)) return false;
        if (!filter_var($users_id, FILTER_VALIDATE_INT)) return false;

        $query = "SELECT COUNT(*) FROM {$this->db_table_auth}
        WHERE bayes_vectors_id = ? AND users_id = ? AND owner = 1 LIMIT 1 ";

        $st = $this->db->prepare($query);
        $st->execute(array($vector_id, $users_id));
        return ($st->fetchColumn() > 0 ? true : false);

    }


    /**
    * @param int $category_id category id
    * @param int $users_id users id
    * @return bool
    */
    function isCategoryOwner($category_id, $users_id) {

        if (!filter_var($category_id, FILTER_VALIDATE_INT)) return false;
        if (!filter_var($users_id, FILTER_VALIDATE_INT)) return false;

        $query = "SELECT COUNT(*) FROM {$this->db_table_auth}
        INNER JOIN {$this->db_table_cat} ON {$this->db_table_auth}.bayes_vectors_id = {$this->db_table_cat}.bayes_vectors_id
        WHERE {$this->db_table_cat}.id = ? AND {$this->db_table_auth}.users_id = ? AND {$this->db_table_auth}.owner = 1 LIMIT 1 ";

        $st = $this->db->prepare($query);
        $st->execute(array($category_id, $users_id));
        return ($st->fetchColumn() > 0 ? true : false);

    }


    /**
    * @param int $document_id document id
    * @param int $users_id users id
    * @return bool
    */
    function isDocumentOwner($document_id, $users_id) {

        if (!filter_var($document_id, FILTER_VALIDATE_INT)) return false;
        if (!filter_var($users_id, FILTER_VALIDATE_INT)) return false;

        $query = "SELECT COUNT(*) FROM {$this->db_table_auth}
        INNER JOIN {$this->db_table_cat} ON {$this->db_table_auth}.bayes_vectors_id = {$this->db_table_cat}.bayes_vectors_id
        INNER JOIN {$this->db_table_doc} ON {$this->db_table_cat}.id = {$this->db_table_doc}.bayes_categories_id
        WHERE {$this->db_table_doc}.id = ? AND {$this->db_table_auth}.users_id = ? AND {$this->db_table_auth}.owner = 1 LIMIT 1 ";

        $st = $this->db->prepare($query);
        $st->execute(array($document_id, $users_id));
        return ($st->fetchColumn() > 0 ? true : false);

    }


    /**
    * @param int $category_id category id
    * @param int $users_id users id
    * @return bool
    */
    function isTrainer($category_id, $users_id) {

        if (!filter_var($category_id, FILTER_VALIDATE_INT)) return false;
        if (!filter_var($users_id, FILTER_VALIDATE_INT)) return false;

        $query = "SELECT COUNT(*) FROM {$this->db_table_auth}
        INNER JOIN {$this->db_table_cat} ON {$this->db_table_auth}.bayes_vectors_id = {$this->db_table_cat}.bayes_vectors_id
        WHERE {$this->db_table_cat}.id = ? AND {$this->db_table_auth}.users_id = ?
        AND {$this->db_table_auth}.owner = 1 OR {$this->db_table_auth}.trainer = 1 LIMIT 1 ";

        $st = $this->db->prepare($query);
        $st->execute(array($category_id, $users_id));
        return ($st->fetchColumn() > 0 ? true : false);

    }



    /**
    * @param string $vector vector
    * @return bool
    */
    function addVectorWithUser($vector, $users_id) {

        if (mb_strlen($vector) > $this->getMaxVectorLength()) return false;
        if (!filter_var($users_id, FILTER_VALIDATE_INT)) return false;

        $vector = strip_tags($vector); // Sanitize

        $this->db->beginTransaction();
        $this->inTransaction = true;

        $st = $this->db->prepare("INSERT INTO {$this->db_table_vec} (vector) VALUES (?) ");
        $st->execute(array($vector));
        $vector_id = $this->db->lastInsertId();
        $st = $this->db->prepare("INSERT INTO {$this->db_table_auth} (bayes_vectors_id, users_id, owner) VALUES (?, ?, 1) ");
        $st->execute(array($vector_id, $users_id));

        $this->db->commit();
        $this->inTransaction = false;

        return true;

    }


    /**
    * @param string $vector vector
    */
    function removeVectorWithUsers($vector_id) {

        if (!filter_var($vector_id, FILTER_VALIDATE_INT)) return false;

        $this->removeVector($vector_id);
        $st = $this->db->prepare("DELETE FROM {$this->db_table_auth} WHERE bayes_vectors_id = ? ");
        $st->execute(array($vector_id));

    }




}


?>