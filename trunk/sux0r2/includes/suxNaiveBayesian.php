<?php

/**
* suxNaiveBayesian
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
* Loic d'Anterroches: http://www.xhtml.net/scripts/PHPNaiveBayesianFilter
* Ken Williams: http://mathforum.org/~ken/bayes/bayes.html
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @copyright  2008 sux0r development group
* @license    http://www.gnu.org/licenses/agpl.html
*
*/

class suxNaiveBayesian {


    public $lang = 'en'; // Used to include language specific stopwords file
    public $ignore_list = array(); // Stopwords list

    // Database suff
    protected $db;
    protected $inTransaction = false;
    protected $db_table_vec = 'bayes_vectors';
    protected $db_table_cat = 'bayes_categories';
    protected $db_table_doc = 'bayes_documents';
    protected $db_table_tok = 'bayes_tokens';

    // If you change these, then you need to adjust your database columns
    private $min_token_length = 3;
    private $max_token_length = 64;
    private $max_category_length = 64;
    private $max_vector_length = 64;


    /**
    * Constructor
    *
    * @global array $CONFIG['DSN']
    * @param string $key a key from our suxDB DSN
    */
    function __construct($key = null) {

        if (!$key && !empty($GLOBALS['CONFIG']['DSN']['bayes'])) $key = 'bayes';
    	$this->db = suxDB::get($key);
        set_exception_handler(array($this, 'exceptionHandler'));

    }


    // ----------------------------------------------------------------------------
    // Accessors
    // ----------------------------------------------------------------------------

    /**
    * @return int
    */
    function getMaxVectorLength() {
        return $this->max_vector_length;
    }


    /**
    * @return int
    */
    function getMaxCategoryLength() {
        return $this->max_category_length;
    }


    // ----------------------------------------------------------------------------
    // Vectors
    // ----------------------------------------------------------------------------

    /**
    * @param string $vector vector
    * @return bool
    */
    function addVector($vector) {

        if (mb_strlen($vector) > $this->max_vector_length) return false;

        // Sanitize
        $vector = strip_tags($vector);

        $st = $this->db->prepare("INSERT INTO {$this->db_table_vec} (vector) VALUES (?) ");
        return $st->execute(array($vector));

    }


    /**
    * @param int $vector_id vector id
    * @return bool
    */
    function removeVector($vector_id) {

        if (!filter_var($vector_id, FILTER_VALIDATE_INT)) return false;

        // Get the category ids for this vector
        $categories = array();
        $st = $this->db->prepare("SELECT id FROM {$this->db_table_cat} WHERE bayes_vectors_id = ? ");
        $st->execute(array($vector_id));
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $categories[] = $row['id'];
        }

        $this->db->beginTransaction();
        $this->inTransaction = true;

        $count = 0;

        $st = $this->db->prepare("DELETE FROM {$this->db_table_vec} WHERE id = ? LIMIT 1 ");
        $st->execute(array($vector_id));
        $count += $st->rowCount();

        $st = $this->db->prepare("DELETE FROM {$this->db_table_cat} WHERE bayes_vectors_id = ? ");
        $st->execute(array($vector_id));
        $count += $st->rowCount();

        foreach ($categories as $val) {
            $st = $this->db->prepare("DELETE FROM {$this->db_table_doc} WHERE bayes_categories_id = ? ");
            $st->execute(array($val));
            $count += $st->rowCount();
        }

        foreach ($categories as $val) {
            $st = $this->db->prepare("DELETE FROM {$this->db_table_tok} WHERE bayes_categories_id = ? ");
            $st->execute(array($val));
            $count += $st->rowCount();
        }

        $this->updateProbabilities();

        $this->db->commit();
        $this->inTransaction = false;

        return ($count > 0 ? true : false);
    }


    /**
    * @param string $vector_id vector id, must be unique
    * @return array
    */
    function getVector($vector_id) {

        $st = $this->db->prepare("SELECT * FROM {$this->db_table_vec} WHERE id = ? LIMIT 1 ");
        $st->execute(array($vector_id));

        $vector = array();
        if ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $vector['id'] = $row['id'];
            $vector['vector'] = $row['vector'];
        }
        return $vector;

    }


    /**
    * @return array key = id, values = array(keys = 'vector')
    */
    function getVectors() {

        $st = $this->db->query("SELECT * FROM {$this->db_table_vec} ORDER BY vector ASC ");

        $vectors = array();
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $vectors[$row['id']] = array(
                'vector' => $row['vector'],
                );
        }
        return $vectors;
    }


    /**
    * @param int $category_id category id
    * @return array key = id, values = array(keys = 'vector')
    */
    function getVectorsByCategory($category_id) {

        if (!filter_var($category_id, FILTER_VALIDATE_INT)) return false;

        $query = "SELECT {$this->db_table_vec}.* FROM {$this->db_table_vec}
        INNER JOIN {$this->db_table_cat} ON {$this->db_table_vec}.id = {$this->db_table_cat}.bayes_vectors_id
        WHERE {$this->db_table_cat}.id = ? ";

        $st = $this->db->prepare($query);
        $st->execute(array($category_id));

        $vectors = array();
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $vectors[$row['id']] = array(
                'vector' => $row['vector'],
                );
        }
        return $vectors;
    }


    /**
    * @param int $document_id category id
    * @return array key = id, values = array(keys = 'vector')
    */
    function getVectorsByDocument($document_id) {

        if (!filter_var($document_id, FILTER_VALIDATE_INT)) return false;

        $query = "SELECT {$this->db_table_vec}.* FROM {$this->db_table_vec}
        INNER JOIN {$this->db_table_cat} ON {$this->db_table_vec}.id = {$this->db_table_cat}.bayes_vectors_id
        INNER JOIN {$this->db_table_doc} ON {$this->db_table_cat}.id = {$this->db_table_doc}.bayes_categories_id
        WHERE {$this->db_table_doc}.id = ? ";

        $st = $this->db->prepare($query);
        $st->execute(array($document_id));

        $vectors = array();
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $vectors[$row['id']] = array(
                'vector' => $row['vector'],
                );
        }

        return $vectors;
    }


    // ----------------------------------------------------------------------------
    // Categories
    // ----------------------------------------------------------------------------


    /**
    * @param string $category category
    * @param int $vector_id vector id
    * @return bool
    */
    function addCategory($category, $vector_id) {

        if (mb_strlen($category) > $this->max_category_length) return false;
        if (!filter_var($vector_id, FILTER_VALIDATE_INT)) return false;

        // Make sure vector exists
        $st = $this->db->prepare("SELECT COUNT(*) FROM {$this->db_table_vec} WHERE id = ? LIMIT 1 ");
        $st->execute(array($vector_id));
        if ($st->fetchColumn() <= 0) return false;

        // Sanitize
        $category = strip_tags($category);

        try {
            $st = $this->db->prepare("INSERT INTO {$this->db_table_cat} (category, bayes_vectors_id) VALUES (?, ?) ");
            return $st->execute(array($category, $vector_id));
        }
        catch (Exception $e) {
            if ($st->errorCode() == 23000) return false; // SQLSTATE 23000: Constraint violations
            else throw ($e); // Hot potato
        }

    }


    /**
    * @param int $category_id category id
    * @return bool
    */
    function removeCategory($category_id) {

        if (!filter_var($category_id, FILTER_VALIDATE_INT)) return false;

        $this->db->beginTransaction();
        $this->inTransaction = true;

        $count = 0;

        $st = $this->db->prepare("DELETE FROM {$this->db_table_cat} WHERE id = ? LIMIT 1 ");
        $st->execute(array($category_id));
        $count += $st->rowCount();

        $st = $this->db->prepare("DELETE FROM {$this->db_table_doc} WHERE bayes_categories_id = ? ");
        $st->execute(array($category_id));
        $count += $st->rowCount();

        $st = $this->db->prepare("DELETE FROM {$this->db_table_tok} WHERE bayes_categories_id = ? ");
        $st->execute(array($category_id));
        $count += $st->rowCount();

        $this->updateProbabilities();

        $this->db->commit();
        $this->inTransaction = false;

        return ($count > 0 ? true : false);
    }


    /**
    * @param string $category_id category id, must be unique
    * @return array
    */
    function getCategory($category_id) {

        $st = $this->db->prepare("SELECT * FROM {$this->db_table_cat} WHERE id = ? LIMIT 1 ");
        $st->execute(array($category_id));

        $category = array();
        if ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $category['id'] = $row['id'];
            $category['category'] = $row['category'];
            $category['vector_id'] = $row['bayes_vectors_id'];
            $category['probability'] = $row['probability'];
            $category['token_count']  = $row['token_count'];
        }
        return $category;
    }


    /**
    * @return array key = id, values = array(keys = 'category', 'vector_id', 'probability', 'token_count')
    */
    function getCategories() {

        $st = $this->db->query("SELECT * FROM {$this->db_table_cat} ORDER BY category ASC ");

        $categories = array();
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $categories[$row['id']] = array(
                'category' => $row['category'],
                'vector_id' => $row['bayes_vectors_id'],
                'probability' => $row['probability'],
                'token_count'  => $row['token_count'],
                );
        }
        return $categories;
    }


    /**
    * @param int $vector_id vector id
    * @return array key = category, values = array(keys = 'category', 'vector_id', 'probability', 'token_count')
    */
    function getCategoriesByVector($vector_id) {

        if (!filter_var($vector_id, FILTER_VALIDATE_INT)) return false;

        $categories = array();
        $st = $this->db->prepare("SELECT * FROM {$this->db_table_cat} WHERE bayes_vectors_id = ? ORDER BY category ASC ");
        $st->execute(array($vector_id));

        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $categories[$row['id']] = array(
                'category' => $row['category'],
                'vector_id' => $row['bayes_vectors_id'],
                'probability' => $row['probability'],
                'token_count'  => $row['token_count'],
                );
        }

        return $categories;
    }


    /**
    * @param int $vector_id vector id
    * @return array key = category, values = array(keys = 'category', 'vector_id', 'probability', 'token_count')
    */
    function getCategoriesByDocument($document_id) {

        if (!filter_var($document_id, FILTER_VALIDATE_INT)) return false;

        $query = "SELECT
        {$this->db_table_cat}.* FROM {$this->db_table_cat}
        INNER JOIN {$this->db_table_doc} ON {$this->db_table_cat}.id = {$this->db_table_doc}.bayes_categories_id
        WHERE {$this->db_table_doc}.id = ? ORDER BY category ASC ";

        $st = $this->db->prepare($query);
        $st->execute(array($document_id));

        $categories = array();
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {

            $categories[$row['id']] = array(
                'category' => $row['category'],
                'vector_id' => $row['bayes_vectors_id'],
                'probability' => $row['probability'],
                'token_count'  => $row['token_count'],
                );
        }

        return $categories;
    }



    // ----------------------------------------------------------------------------
    // Documents
    // ----------------------------------------------------------------------------


    /**
    * @param string $category_id the category id in which the document should be
    * @param string $content content of the document
    * @return bool
    * @return int insert id
    */
    function trainDocument($content, $category_id) {

        if (!filter_var($category_id, FILTER_VALIDATE_INT)) return false;

        // Make sure category exists
        $st = $this->db->prepare("SELECT COUNT(*) FROM {$this->db_table_cat} WHERE id = ? LIMIT 1 ");
        $st->execute(array($category_id));
        if ($st->fetchColumn() <= 0) return false;

        // Sanitize to UTF-8 plaintext
        require_once(dirname(__FILE__) . '/suxHtml2UTF8.php');
        $converter = new suxHtml2UTF8($content);
        $content = $converter->getText();

        $this->db->beginTransaction();
        $this->inTransaction = true;

    	$tokens = $this->parseTokens($content);
        foreach($tokens as $token => $count) {
            $this->updateToken($token, $count, $category_id);
        }
        $this->saveDocument($category_id, $content);

        // MySQL InnoDB with transaction reports the last insert id as 0 after
        // commit, the real ids are only reported before committing.
        $insert_id = $this->db->lastInsertId();

        $this->updateProbabilities();

        $this->db->commit();
        $this->inTransaction = false;

        return $insert_id;

    }


    /**
    * @param string $document_id document id, must be unique
    * @return bool
    */
    function untrainDocument($document_id) {

        if (!filter_var($document_id, FILTER_VALIDATE_INT)) return false;

        $this->db->beginTransaction();
        $this->inTransaction = true;

        $ref = $this->getDocument($document_id);
        if (count($ref)) {
            $tokens = $this->parseTokens($ref['body']);
            foreach($tokens as $token => $count) {
                $this->removeToken($token, $count, $ref['category_id']);
            }
        }
        $this->removeDocument($document_id);
        $this->updateProbabilities();

        $this->db->commit();
        $this->inTransaction = false;

        return true;
    }


    /**
    * @param string $document_id document id, must be unique
    * @return array
    */
    function getDocument($document_id) {

        $st = $this->db->prepare("SELECT * FROM {$this->db_table_doc} WHERE id = ? ");
        $st->execute(array($document_id));

        $document = array();
        if ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $document['id'] = $row['id'];
            $document['category_id'] = $row['bayes_categories_id'];
            $document['body'] = $row['body_plaintext'];
        }
        return $document;
    }


    /**
    * @return array key = ids, values = array(keys = 'category_id', 'body')
    */
    function getDocuments() {

        $st = $this->db->query("SELECT * FROM {$this->db_table_doc} ORDER BY id ASC ");

        $documents = array();
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $documents[$row['id']] = array(
                'category_id' => $row['bayes_categories_id'],
                'body' => $row['body_plaintext'],
                );
        }
        return $documents;
    }


    /**
    * @param int $vector_id vector id
    * @return array key = ids, values = array(keys = 'category_id', 'body (optional)')
    */
    function getDocumentsByVector($vector_id, $full = false) {

        if (!filter_var($vector_id, FILTER_VALIDATE_INT)) return false;

        if ($full) $query = "SELECT {$this->db_table_doc}.* ";
        else {
            $query = "SELECT
            {$this->db_table_doc}.id,
            {$this->db_table_doc}.bayes_categories_id ";
        }

        $query .= "FROM {$this->db_table_doc}
        INNER JOIN {$this->db_table_cat} ON {$this->db_table_doc}.bayes_categories_id = {$this->db_table_cat}.id
        INNER JOIN {$this->db_table_vec} ON {$this->db_table_cat}.bayes_vectors_id = {$this->db_table_vec}.id
        WHERE {$this->db_table_vec}.id = ? ";

        $st = $this->db->prepare($query);
        $st->execute(array($vector_id));

        $documents = array();
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($full) {
                $documents[$row['id']] = array(
                    'category_id' => $row['bayes_categories_id'],
                    'body' => $row['body_plaintext'],
                    );
            }
            else {
                $documents[$row['id']] = array(
                    'category_id' => $row['bayes_categories_id'],
                    );
            }
        }

        return $documents;
    }


    /**
    * @param int $category_id category id
    * @return array key = ids, values = array(keys = 'category_id', 'category', 'body (optional)')
    */
    function getDocumentsByCategory($category_id, $full = false) {

        if (!filter_var($category_id, FILTER_VALIDATE_INT)) return false;

        if ($full) $query = "SELECT {$this->db_table_doc}.* ";
        else {
            $query = "SELECT
            {$this->db_table_doc}.id,
            {$this->db_table_doc}.bayes_categories_id ";
        }
        $query .= "FROM {$this->db_table_doc}
        INNER JOIN {$this->db_table_cat} ON {$this->db_table_doc}.bayes_categories_id = {$this->db_table_cat}.id
        WHERE {$this->db_table_cat}.id = ? ";

        $st = $this->db->prepare($query);
        $st->execute(array($category_id));

        $documents = array();
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($full) {
                $documents[$row['id']] = array(
                    'category_id' => $row['bayes_categories_id'],
                    'body' => $row['body_plaintext'],
                    );
            }
            else {
                $documents[$row['id']] = array(
                    'category_id' => $row['bayes_categories_id'],
                    );
            }
        }

        return $documents;
    }


    /**
    * @param int $category_id category id
    * @return int
    */
    function getDocumentCountByCategory($category_id) {

        if (!filter_var($category_id, FILTER_VALIDATE_INT)) return 0;

        $st = $this->db->prepare("SELECT COUNT(*) FROM {$this->db_table_doc} WHERE bayes_categories_id = ? ");
        $st->execute(array($category_id));
        return $st->fetchColumn();

    }


    // ----------------------------------------------------------------------------
    // Maths
    // ----------------------------------------------------------------------------


    /**
    * @return bool
    */
    function updateProbabilities() {

        // A vector is an array of categories. Probabilities must be
        // contrained to vector and not the entire tokens table. We need to
        // join tokens to categories, which containes vector_ids.

        // Get vector_ids that are actually being used
        $vectors = array();
        $q = "SELECT bayes_vectors_id FROM {$this->db_table_cat} GROUP BY bayes_vectors_id ";
        $st = $this->db->query($q);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $vectors[] = $row['bayes_vectors_id'];
        }

        // Join to categories
        $q = "SELECT {$this->db_table_tok}.bayes_categories_id, SUM({$this->db_table_tok}.count) AS total
        FROM {$this->db_table_tok} INNER JOIN {$this->db_table_cat}
        ON {$this->db_table_tok}.bayes_categories_id = {$this->db_table_cat}.id
        WHERE {$this->db_table_cat}.bayes_vectors_id = ?
        GROUP BY {$this->db_table_tok}.bayes_categories_id ";

        // Constrain to individual vectors
        foreach ($vectors as $vector_id) {

            // Get the total of all known tokens
            $total_tokens = 0;

            $st = $this->db->prepare($q);
            $st->execute(array($vector_id));
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $total_tokens += $row['total'];
            }

            // If there are no tokens, reset everything
            if ($total_tokens == 0) {
                $st = $this->db->prepare("UPDATE {$this->db_table_cat} SET token_count = 0, probability = 0 WHERE bayes_vectors_id = ? ");
                $st->execute(array($vector_id));
                continue;
            }

            // Get all categories
            $categories = array();
            $st = $this->db->prepare("SELECT id FROM {$this->db_table_cat} WHERE bayes_vectors_id = ? ");
            $st->execute(array($vector_id));

            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $categories[$row['id']] = true;
            }

            // Repeat $q, update probabilities
            $st = $this->db->prepare($q);
            $st->execute(array($vector_id));
            $st2 = $this->db->prepare("UPDATE {$this->db_table_cat} SET token_count = ?, probability = ? WHERE id = ? AND bayes_vectors_id = ? ");
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $proba = $row['total']/$total_tokens;
                $st2->execute(array($row['total'], $proba, $row['bayes_categories_id'], $vector_id));
                unset($categories[$row['bayes_categories_id']]);
            }

            // If there are categories with no tokens, reset those categories
            $st = $this->db->prepare("UPDATE {$this->db_table_cat} SET token_count = 0, probability = 0 WHERE id = ? AND bayes_vectors_id = ? ");
            foreach ($categories as $key => $val) {
                $st->execute(array($key, $vector_id));
            }

        }

        // TODO: This function never returns false?
        return true;

    }


    /**
    * @param string $document a document
    * @param int $vector_id vector id
    * @return array key = id, values = array(keys = 'category', 'score')
    */
    function categorize($document, $vector_id) {

        if (!filter_var($vector_id, FILTER_VALIDATE_INT)) return false;

        // Sanity check, convert to UTF-8 plaintext
        require_once(dirname(__FILE__) . '/suxHtml2UTF8.php');
        $converter = new suxHtml2UTF8($document);
        $document = $converter->getText();

        $scores = array();
        $categorized = array();
        $total_tokens = 0;
        $ncat = 0;

        $categories = $this->getCategoriesByVector($vector_id);
        $tokens = $this->parseTokens($document);

        foreach ($categories as $data) {
            $total_tokens += $data['token_count'];
            $ncat++;
        }



        foreach($categories as $category_id => $data) {

            $scores[$category_id] = $data['probability'];
            // $scores[$data['category']] = $data['probability'];

            foreach($tokens as $token => $count) {
                if ($this->tokenExists($token)) {
                    $token_count = $this->getTokenCount($token, $category_id);
                    $prob = 0;
                    if ($token_count && $data['token_count']) $prob = (float) $token_count/$data['token_count']; // Probability
                    else if ($data['token_count']) $prob = (float) 1/(2*$data['token_count']); // Fake probability, like a very infrequent word
                    $scores[$category_id] *= pow($prob, $count)*pow($total_tokens/$ncat, $count);
                    // $scores[$data['category']] *= pow($prob, $count)*pow($total_tokens/$ncat, $count);
                    // pow($total_tokens/$ncat, $count) is here to avoid underflow.
                }
            }

            // Remember
            $categorized[$category_id] = $data['category'];

        }

        $scores = $this->rescale($scores); // Rescale
        arsort($scores); // Sort

        // Reorganize into multi-dimensional array
        foreach ($scores as $key => $val) {
            // Overwrite
            $scores[$key] = array(
                'category' => $categorized[$key],
                'score' => $scores[$key]
                );
        }

        return $scores;

    }


    /**
    * @param array $scores scores (keys => category, values => scores)
    * @return array normalized scores (keys => category, values => scores)
    */
    private function rescale(array $scores)  {

        // Scale everything back to a reasonable area in
        // logspace (near zero), un-loggify, and normalize

        if (!count($scores)) return $scores;

        $total = 0.0;
        $max = (float) max($scores);

        foreach($scores as $category => $score) {
            $scores[$category] = (float) exp($score - $max);
            $total += (float) pow($scores[$category],2);
        }
        $total = (float) sqrt($total);

        foreach($scores as $category => $score) {
            $scores[$category] = (float) $scores[$category]/$total;
        }

        reset($scores);
        return $scores;
    }

    // ----------------------------------------------------------------------------
    // Tokens
    // ----------------------------------------------------------------------------


    /**
    * @param string $string the string to get the tokens from
    * @return array keys = tokens, values = count
    */
    private function parseTokens($string) {
        $rawtokens = array();
        $tokens    = array();

        $string = mb_strtolower($string);

        // \w means alphanumeric characters.
        // Usually, non-English letters and numbers are included.
        // \W is the negated version of \w
        //
        // TODO: We're splitting on "anything that isn't a word" which is good
        // for languages with punctuation and spaces. But what about Chinese,
        // Japanese, and other languages that don't use them? How do we
        // identify tokens in those cases?

        $rawtokens = mb_split("\W", $string);

        //. Get stopwords
        if (is_readable(dirname(__FILE__) . "/symbionts/stopwords/{$this->lang}.txt")) {
            $this->ignore_list = file(dirname(__FILE__) . "/symbionts/stopwords/{$this->lang}.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }

        // Append generic internet cruft for good measure
        array_push($this->ignore_list, 'http', 'https', 'mailto', 'www', 'com', 'net', 'org', 'biz', 'info');

        // remove unwanted tokens
        foreach ($rawtokens as $token) {
            $token = trim($token);
            if ($this->acceptableToken($token)) @$tokens[$token]++;
        }
        return $tokens;
    }


    /**
    * @param string $string a token to inspect
    * @return bool
    */
    private function acceptableToken($token) {

        if (!(
            (empty($token)) ||
            (mb_strlen($token) < $this->min_token_length) ||
            (mb_strlen($token) > $this->max_token_length) ||
            (ctype_digit($token)) ||
            (in_array($token, $this->ignore_list))
            )) return true;

        return false;

    }


    // ----------------------------------------------------------------------------
    // Data storage, Private
    // ----------------------------------------------------------------------------


    /**
    * @param string $token token
    * @return bool
    */
    private function tokenExists($token) {

        $st = $this->db->prepare("SELECT COUNT(*) FROM {$this->db_table_tok} WHERE token = ? LIMIT 1 ");
        $st->execute(array($token));
        return ($st->fetchColumn() > 0 ? true : false);

    }


    /** get the count of a token in a category.
    * @param string $token token
    * @param string $category_id category id
    * @return int
    */
    private function getTokenCount($token, $category_id) {

        $count = 0;

        $st = $this->db->prepare("SELECT * FROM {$this->db_table_tok} WHERE token = ? AND bayes_categories_id = ? ");
        $st->execute(array($token, $category_id));

        if ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $count = $row['count'];
        }

        return $count;
    }


    /**
    * @param string $token token
    * @param int $count count
    * @param string $category_id category id
    * @return bool
    */
    private function updateToken($token, $count, $category_id) {


        $token_count = $this->getTokenCount($token, $category_id);

        if ($token_count == 0) {

            $st = $this->db->prepare("INSERT INTO {$this->db_table_tok} (token, bayes_categories_id, count) VALUES (?, ?, ?) ");
            return $st->execute(array($token, $category_id, $count));

        } else {

            $st = $this->db->prepare("UPDATE {$this->db_table_tok} SET count = count + ? WHERE bayes_categories_id = ? AND token = ? ");
            $st->bindValue(1, $count, PDO::PARAM_INT);
            $st->bindValue(2, $category_id);
            $st->bindValue(3, $token);
            return $st->execute();

        }

    }


    /**
    * @param string $token token
    * @param int $count count
    * @param string $category_id category id
    * @return bool
    */
    private function removeToken($token, $count, $category_id) {

        $token_count = $this->getTokenCount($token, $category_id);

        if ($token_count != 0 && ($token_count-$count) <= 0) {

            $st = $this->db->prepare("DELETE FROM {$this->db_table_tok} WHERE token = ? AND bayes_categories_id = ? ");
            return $st->execute(array($token, $category_id));

        } else {

            $st = $this->db->prepare("UPDATE {$this->db_table_tok} SET count = count - ? WHERE bayes_categories_id = ? AND token = ? ");
            $st->bindValue(1, $count, PDO::PARAM_INT);
            $st->bindValue(2, $category_id);
            $st->bindValue(3, $token);
            return $st->execute();

        }
    }



    /**
    @param string $category_id category id
    @param string $content content of the document
    @return bool
    */
    private function saveDocument($category_id, $content) {

        $st = $this->db->prepare("INSERT INTO {$this->db_table_doc} (bayes_categories_id, body_plaintext) VALUES (?, ?) ");
        return $st->execute(array($category_id, $content));

    }


    /**
    * @param  string $document_id document id, must be unique
    * @return bool
    */
    private function removeDocument($document_id) {

        $st = $this->db->prepare("DELETE FROM {$this->db_table_doc} WHERE id = ? LIMIT 1 ");
        return $st->execute(array($document_id));

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