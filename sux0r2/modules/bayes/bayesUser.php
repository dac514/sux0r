<?php

/**
* bayesUser
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

class bayesUser extends suxNaiveBayesian {

    /*
    Conventions:
    An owner implies trainer, they can do anything
    A trainer is allowed to train, NOT untrain
    A user who is neither an owner nor a trainer is allowed to categorize
    */

    // Variables
    protected $db_table_auth = 'bayes_auth';
    private $module = 'bayes';


    /**
    * Constructor
    */
    function __construct() {
        parent::__construct(); // Call parent
    }

    // --------------------------------------------------------------------
    // Get vectors
    // --------------------------------------------------------------------

    /**
    * Returns a list of users and their permissions to a vector
    *
    * @param int $vector_id vectors id
    * @return array
    */
    function getVectorAuthorization($vector_id) {

        if (!filter_var($vector_id, FILTER_VALIDATE_INT) || $vector_id <= 0) return false;

        $query = "SELECT users_id, bayes_vectors_id, trainer, owner FROM {$this->db_table_auth} WHERE bayes_vectors_id = ? ";
        $st = $this->db->prepare($query);
        $st->execute(array($vector_id));

        return $st->fetchAll(PDO::FETCH_ASSOC);

    }


    /**
    * Returns a list of vectors and permissions that a user has access to
    *
    * @param int $users_id users id
    * @return array key = id, values = array(keys = vector, trainer, owner)
    */
    function getSharedVectors($users_id) {

        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id <= 0) return false;

        $query = "SELECT {$this->db_table_vec}.*, {$this->db_table_auth}.trainer, {$this->db_table_auth}.owner FROM {$this->db_table_vec}
        INNER JOIN {$this->db_table_auth} ON {$this->db_table_vec}.id = {$this->db_table_auth}.bayes_vectors_id
        WHERE {$this->db_table_auth}.users_id = ?
        ORDER BY {$this->db_table_vec}.vector ASC ";

        $st = $this->db->prepare($query);
        $st->execute(array($users_id));

        $vectors = array();
        foreach ($st->fetchAll() as $row) {
            $vectors[$row['id']] = array(
                'vector' => $row['vector'],
                'trainer' => $row['trainer'],
                'owner' => $row['owner'],
                );
        }

        return $vectors;
    }


    /**
    * @param int $users_id users id
    * @return array key = id, values = array(keys = 'vector')
    */
    function getVectorsByOwner($users_id) {

        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id <= 0) return false;

        $query = "SELECT {$this->db_table_vec}.* FROM {$this->db_table_vec}
        INNER JOIN {$this->db_table_auth} ON {$this->db_table_vec}.id = {$this->db_table_auth}.bayes_vectors_id
        WHERE {$this->db_table_auth}.users_id = ? AND {$this->db_table_auth}.owner = 1
        ORDER BY {$this->db_table_vec}.vector ASC ";

        $st = $this->db->prepare($query);
        $st->execute(array($users_id));

        $vectors = array();
        foreach ($st->fetchAll() as $row) {
            $vectors[$row['id']] = array(
                'vector' => $row['vector'],
                );
        }

        return $vectors;
    }


    /**
    * @param int $users_id users id
    * @return array key = id, values = array(keys = vector)
    */
    function getVectorsByTrainer($users_id) {

        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id <= 0) return false;

        // Note: Owner of a vector implies trainer
        $query = "SELECT {$this->db_table_vec}.* FROM {$this->db_table_vec}
        INNER JOIN {$this->db_table_auth} ON {$this->db_table_vec}.id = {$this->db_table_auth}.bayes_vectors_id
        WHERE {$this->db_table_auth}.users_id = ? AND ({$this->db_table_auth}.owner = 1 OR {$this->db_table_auth}.trainer = 1)
        ORDER BY {$this->db_table_vec}.vector ASC ";

        $st = $this->db->prepare($query);
        $st->execute(array($users_id));

        $vectors = array();
        foreach ($st->fetchAll() as $row) {
            $vectors[$row['id']] = array(
                'vector' => $row['vector'],
                );
        }

        return $vectors;
    }


    /**
    * @param int $users_id users id
    * @return array key = id, values = array(keys = vector)
    */
    function getVectorsByUser($users_id) {

        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id <= 0) return false;

        // Note: Owner of a vector implies trainer
        $query = "SELECT {$this->db_table_vec}.* FROM {$this->db_table_vec}
        INNER JOIN {$this->db_table_auth} ON {$this->db_table_vec}.id = {$this->db_table_auth}.bayes_vectors_id
        WHERE {$this->db_table_auth}.users_id = ?
        ORDER BY {$this->db_table_vec}.vector ASC ";

        $st = $this->db->prepare($query);
        $st->execute(array($users_id));

        $vectors = array();
        foreach ($st->fetchAll() as $row) {
            $vectors[$row['id']] = array(
                'vector' => $row['vector'],
                );
        }

        return $vectors;
    }


    // --------------------------------------------------------------------
    // Bool assertions
    // --------------------------------------------------------------------


    /**
    * @param int $vector_id vector id
    * @param int $users_id users id
    * @return bool
    */
    function isVectorOwner($vector_id, $users_id) {

        if (!filter_var($vector_id, FILTER_VALIDATE_INT) || $vector_id <= 0) return false;
        if (!filter_var($users_id, FILTER_VALIDATE_INT)  || $users_id <= 0) return false;

        $query = "SELECT COUNT(*) FROM {$this->db_table_auth}
        WHERE bayes_vectors_id = ? AND users_id = ? AND owner = 1 LIMIT 1 ";

        $st = $this->db->prepare($query);
        $st->execute(array($vector_id, $users_id));
        return ($st->fetchColumn() > 0 ? true : false);

    }


    /**
    * @param int $vector_id vector id
    * @param int $users_id users id
    * @return bool
    */
    function isVectorTrainer($vector_id, $users_id) {

        if (!filter_var($vector_id, FILTER_VALIDATE_INT) || $vector_id <= 0) return false;
        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id <= 0) return false;

        // Note: Owner of a vector implies trainer
        $query = "SELECT COUNT(*) FROM {$this->db_table_auth}
        INNER JOIN {$this->db_table_vec} ON {$this->db_table_auth}.bayes_vectors_id = {$this->db_table_vec}.id
        WHERE {$this->db_table_auth}.bayes_vectors_id = ? AND {$this->db_table_auth}.users_id = ?
        AND ({$this->db_table_auth}.owner = 1 OR {$this->db_table_auth}.trainer = 1) LIMIT 1 ";

        $st = $this->db->prepare($query);
        $st->execute(array($vector_id, $users_id));
        return ($st->fetchColumn() > 0 ? true : false);

    }


    /**
    * @param int $vector_id vector id
    * @param int $users_id users id
    * @return bool
    */
    function isVectorUser($vector_id, $users_id) {

        if (!filter_var($vector_id, FILTER_VALIDATE_INT) || $vector_id <= 0) return false;
        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id <= 0) return false;

        // Note: Owner of a vector implies trainer
        $query = "SELECT COUNT(*) FROM {$this->db_table_auth}
        INNER JOIN {$this->db_table_vec} ON {$this->db_table_auth}.bayes_vectors_id = {$this->db_table_vec}.id
        WHERE {$this->db_table_auth}.bayes_vectors_id = ? AND {$this->db_table_auth}.users_id = ?
        LIMIT 1 ";

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

        if (!filter_var($category_id, FILTER_VALIDATE_INT) || $category_id <= 0) return false;
        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id <= 0) return false;

        $query = "SELECT COUNT(*) FROM {$this->db_table_auth}
        INNER JOIN {$this->db_table_cat} ON {$this->db_table_auth}.bayes_vectors_id = {$this->db_table_cat}.bayes_vectors_id
        WHERE {$this->db_table_cat}.id = ? AND {$this->db_table_auth}.users_id = ? AND {$this->db_table_auth}.owner = 1 LIMIT 1 ";

        $st = $this->db->prepare($query);
        $st->execute(array($category_id, $users_id));
        return ($st->fetchColumn() > 0 ? true : false);

    }


    /**
    * @param int $category_id category id
    * @param int $users_id users id
    * @return bool
    */
    function isCategoryTrainer($category_id, $users_id) {

        if (!filter_var($category_id, FILTER_VALIDATE_INT) || $category_id <= 0) return false;
        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id <= 0) return false;

        // Note: Owner of a vector implies trainer
        $query = "SELECT COUNT(*) FROM {$this->db_table_auth}
        INNER JOIN {$this->db_table_cat} ON {$this->db_table_auth}.bayes_vectors_id = {$this->db_table_cat}.bayes_vectors_id
        WHERE {$this->db_table_cat}.id = ? AND {$this->db_table_auth}.users_id = ?
        AND ({$this->db_table_auth}.owner = 1 OR {$this->db_table_auth}.trainer = 1) LIMIT 1 ";

        $st = $this->db->prepare($query);
        $st->execute(array($category_id, $users_id));
        return ($st->fetchColumn() > 0 ? true : false);

    }


    /**
    * @param int $category_id category id
    * @param int $users_id users id
    * @return bool
    */
    function isCategoryUser($category_id, $users_id) {

        if (!filter_var($category_id, FILTER_VALIDATE_INT) || $category_id <= 0) return false;
        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id <= 0) return false;

        // Note: Owner of a vector implies trainer
        $query = "SELECT COUNT(*) FROM {$this->db_table_auth}
        INNER JOIN {$this->db_table_cat} ON {$this->db_table_auth}.bayes_vectors_id = {$this->db_table_cat}.bayes_vectors_id
        WHERE {$this->db_table_cat}.id = ? AND {$this->db_table_auth}.users_id = ?
        LIMIT 1 ";

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

        if (!filter_var($document_id, FILTER_VALIDATE_INT) || $document_id <= 0) return false;
        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id <= 0) return false;

        $query = "SELECT COUNT(*) FROM {$this->db_table_auth}
        INNER JOIN {$this->db_table_cat} ON {$this->db_table_auth}.bayes_vectors_id = {$this->db_table_cat}.bayes_vectors_id
        INNER JOIN {$this->db_table_doc} ON {$this->db_table_cat}.id = {$this->db_table_doc}.bayes_categories_id
        WHERE {$this->db_table_doc}.id = ? AND {$this->db_table_auth}.users_id = ? AND {$this->db_table_auth}.owner = 1 LIMIT 1 ";

        $st = $this->db->prepare($query);
        $st->execute(array($document_id, $users_id));
        return ($st->fetchColumn() > 0 ? true : false);

    }


    /**
    * @param int $document_id document id
    * @param int $users_id users id
    * @return bool
    */
    function isDocumentTrainer($document_id, $users_id) {

        if (!filter_var($document_id, FILTER_VALIDATE_INT) || $document_id <= 0) return false;
        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id <= 0) return false;

        // Note: Owner of a vector implies trainer
        $query = "SELECT COUNT(*) FROM {$this->db_table_auth}
        INNER JOIN {$this->db_table_cat} ON {$this->db_table_auth}.bayes_vectors_id = {$this->db_table_cat}.bayes_vectors_id
        INNER JOIN {$this->db_table_doc} ON {$this->db_table_cat}.id = {$this->db_table_doc}.bayes_categories_id
        WHERE {$this->db_table_doc}.id = ? AND {$this->db_table_auth}.users_id = ?
        AND ({$this->db_table_auth}.owner = 1 OR {$this->db_table_auth}.trainer = 1) LIMIT 1 ";

        $st = $this->db->prepare($query);
        $st->execute(array($document_id, $users_id));
        return ($st->fetchColumn() > 0 ? true : false);

    }


    /**
    * @param int $document_id document id
    * @param int $users_id users id
    * @return bool
    */
    function isDocumentUser($document_id, $users_id) {

        if (!filter_var($document_id, FILTER_VALIDATE_INT) || $document_id <= 0) return false;
        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id <= 0) return false;

        // Note: Owner of a vector implies trainer
        $query = "SELECT COUNT(*) FROM {$this->db_table_auth}
        INNER JOIN {$this->db_table_cat} ON {$this->db_table_auth}.bayes_vectors_id = {$this->db_table_cat}.bayes_vectors_id
        INNER JOIN {$this->db_table_doc} ON {$this->db_table_cat}.id = {$this->db_table_doc}.bayes_categories_id
        WHERE {$this->db_table_doc}.id = ? AND {$this->db_table_auth}.users_id = ?
        LIMIT 1 ";

        $st = $this->db->prepare($query);
        $st->execute(array($document_id, $users_id));
        return ($st->fetchColumn() > 0 ? true : false);

    }



    // --------------------------------------------------------------------
    // Share / Unshare
    // --------------------------------------------------------------------


    /**
    * @param string $users_id users id
    * @param string $vector_id vector id
    * @param int $trainer either 0/null or 1
    * @param int $owner either 0/null or 1
    * @return bool
    */
    function shareVector($users_id, $vector_id, $trainer, $owner) {

        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id <= 0) return false;
        if (!filter_var($vector_id, FILTER_VALIDATE_INT) || $vector_id <= 0) return false;
        if ($users_id == $_SESSION['users_id']) return false; // Cannot share a vector with one's self
        if ($trainer != 1) $trainer = 0;
        if ($owner != 1) $owner = 0;

        // --------------------------------------------------------------------
        // Go!
        // --------------------------------------------------------------------

        $st = $this->db->prepare("SELECT COUNT(*) FROM {$this->db_table_auth} WHERE users_id = ? AND bayes_vectors_id = ? LIMIT 1 ");
        $st->execute(array($users_id, $vector_id));

        $shared = array(
            'users_id' => $users_id,
            'bayes_vectors_id' => $vector_id,
            'trainer' => $trainer,
            'owner' => $owner,
            );

        if ($st->fetchColumn() > 0) {

            // Don't allow un-ownership if there is only one owner, probably due to a race condition
            $st = $this->db->prepare("SELECT COUNT(*) FROM {$this->db_table_auth} WHERE bayes_vectors_id = ? ");
            $st->execute(array($vector_id));
            if (!$owner && $st->fetchColumn() <= 1) return false;

           // UPDATE
            $query = "UPDATE {$this->db_table_auth}
            SET trainer = :trainer, owner = :owner
            WHERE users_id = :users_id AND bayes_vectors_id = :bayes_vectors_id ";
            $st = $this->db->prepare($query);
            return $st->execute($shared);

        }
        else {

            // INSERT
            $query = suxDB::prepareInsertQuery($this->db_table_auth, $shared);
            $st = $this->db->prepare($query);
            return $st->execute($shared);

        }


    }


    /**
    * @param string $users_id users id
    * @param string $vector_id vector id
    * @return bool
    */
    function unshareVector($users_id, $vector_id) {

        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id <= 0) return false;
        if (!filter_var($vector_id, FILTER_VALIDATE_INT) || $vector_id <= 0) return false;

        // Don't allow unsharing if there is only one owner, probably due to a race condition
        $st = $this->db->prepare("SELECT COUNT(*) FROM {$this->db_table_auth} WHERE bayes_vectors_id = ? ");
        $st->execute(array($vector_id));
        if ($st->fetchColumn() <= 1) return false;

        // DELETE
        $st = $this->db->prepare("DELETE FROM {$this->db_table_auth} WHERE users_id = ? AND bayes_vectors_id = ? LIMIT 1 ");
        return $st->execute(array($users_id, $vector_id));

    }


    /**
    * Verify that $_GET values for filter are valid
    *
    * @return false|array($vec_id, $cat_id, $threshold, $start)
    */
    function isValidFilter() {

        function failure() {
            unset($_GET['filter'], $_GET['threshold']);
            return false;
        }

        if (!isset($_GET['filter'])) return failure();
        if (!filter_var($_GET['filter'], FILTER_VALIDATE_INT) || $_GET['filter'] <= 0) return failure();
        if ($_GET['filter'] < 0) return failure();

        if (!isset($_GET['threshold'])) $_GET['threshold'] = false;
        else {
            if ($_GET['threshold'] != '0') {
                if (!filter_var($_GET['threshold'], FILTER_VALIDATE_FLOAT)) return failure();
            }
            if ($_GET['threshold'] < 0 || $_GET['threshold'] > 1) return failure();
        }

        $vec_id = $this->getVectorByCategory($_GET['filter']);
        if (!$vec_id) return failure();
        reset($vec_id);
        $vec_id = key($vec_id);
        if (@!$this->isVectorUser($vec_id, $_SESSION['users_id'])) return failure();

        if (!isset($_GET['start'])) $_GET['start'] = 0;
        else if (!filter_var($_GET['start'], FILTER_VALIDATE_INT) || $_GET['start'] <= 0) $_GET['start'] = 0;

        return array($vec_id, $_GET['filter'], $_GET['threshold'], $_GET['start']);

    }


    // --------------------------------------------------------------------
    // Override parent methods
    // --------------------------------------------------------------------


    /**
    * @param string $vector vector
    * @return bool
    */
    function addVectorWithUser($vector, $users_id) {

        /*
        We rewrite the entire function instead of calling parent because
        we need to use transactions i.e. we don't want risk failing and
        leave a floating vector without any owner

        Furthermore, E_STRICT complains that the parent should be compatible
        if we do a regular override (we have a different number of
        required parameters)
        */

        if (mb_strlen($vector) > $this->getMaxVectorLength()) return false;
        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id <= 0) return false;

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
    * @param string $vector_id vector id
    */
    function removeVector($vector_id) {

        /*
        We call the parent with transactions then do one final delete from the
        auth table i.e. we are willing to risk failure on the last step because
        we are lazy
        */

        parent::removeVector($vector_id);
        $st = $this->db->prepare("DELETE FROM {$this->db_table_auth} WHERE bayes_vectors_id = ? ");
        $st->execute(array($vector_id));

    }


}


?>