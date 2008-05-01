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


    public $l10n = 'en'; // Used to include language specific stopwords file
    public $ignore_list = array(); // Stopwords list

    protected $db;
    protected $inTransaction = false;

    // If you change these, then you need to adjust your database columns
    private $min_token_length = 3;
    private $max_token_length = 64;
    private $max_category_length = 64;
    private $max_vector_length = 255;


    /**
    * @param string $key a key from our suxDB DSN
    */
    function __construct($key = null) {
    	$this->db = suxDB::get($key);
        set_exception_handler(array($this, 'logAndDie'));
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

        try {
            $st = $this->db->prepare('INSERT INTO bayes_vectors (vector) VALUES (?) ');
            return $st->execute(array($vector));
        }
        catch (Exception $e) {
            if ($st->errorCode() == 23000) return false; // SQLSTATE 23000: Constraint violations
            else throw ($e); // Hot potato
        }

    }


    /**
    * @param int $vector_id vector id
    * @return bool
    */
    function removeVector($vector_id) {

        // Get the category ids for this vector
        $categories = array();
        $st = $this->db->prepare('SELECT id FROM bayes_categories WHERE bayes_vectors_id = ? ');
        $st->execute(array($vector_id));
        foreach ($st->fetchAll() as $row) {
            $categories[] = $row['id'];
        }

        $this->db->beginTransaction();
        $this->inTransaction = true;

        $count = 0;

        $st = $this->db->prepare('DELETE FROM bayes_vectors WHERE id = ? ');
        $st->execute(array($vector_id));
        $count += $st->rowCount();

        $st = $this->db->prepare('DELETE FROM bayes_categories WHERE bayes_vectors_id = ? ');
        $st->execute(array($vector_id));
        $count += $st->rowCount();

        foreach ($categories as $val) {
            $st = $this->db->prepare('DELETE FROM bayes_documents WHERE bayes_categories_id = ? ');
            $st->execute(array($val));
            $count += $st->rowCount();
        }

        foreach ($categories as $val) {
            $st = $this->db->prepare('DELETE FROM bayes_tokens WHERE bayes_categories_id = ? ');
            $st->execute(array($val));
            $count += $st->rowCount();
        }

        $this->updateProbabilities();

        $this->db->commit();
        $this->inTransaction = false;

        return ($count > 0 ? true : false);
    }


    /**
    * @return array key = id, values = array(keys = 'vector')
    */
    function getVectors() {

        $vectors = array();
        $st = $this->db->query('SELECT * FROM bayes_vectors ORDER BY vector ASC ');

        foreach ($st->fetchAll() as $row) {
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

        try {
            $st = $this->db->prepare('INSERT INTO bayes_categories (category, bayes_vectors_id) VALUES (?, ?) ');
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

        $this->db->beginTransaction();
        $this->inTransaction = true;

        $count = 0;

        $st = $this->db->prepare('DELETE FROM bayes_categories WHERE id = ? ');
        $st->execute(array($category_id));
        $count += $st->rowCount();

        $st = $this->db->prepare('DELETE FROM bayes_documents WHERE bayes_categories_id = ? ');
        $st->execute(array($category_id));
        $count += $st->rowCount();

        $st = $this->db->prepare('DELETE FROM bayes_tokens WHERE bayes_categories_id = ? ');
        $st->execute(array($category_id));
        $count += $st->rowCount();

        $this->updateProbabilities();

        $this->db->commit();
        $this->inTransaction = false;

        return ($count > 0 ? true : false);
    }


    /**
    * @param int $vector_id vector id
    * @return array key = category, values = array(keys = 'category', 'vector_id', 'probability', 'token_count')
    */
    function getCategories($vector_id) {

        $categories = array();
        $st = $this->db->prepare('SELECT * FROM bayes_categories WHERE bayes_vectors_id = ? ORDER BY category ASC ');
        $st->execute(array($vector_id));

        foreach ($st->fetchAll() as $row) {
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
    */
    function trainDocument($category_id, $content) {

        // Sanity check, convert to UTF-8 plaintext
        include(dirname(__FILE__) . '/suxHtml2UTF8.php');
        $converter = new suxHtml2UTF8($content);
        $content = $converter->getText();

        $this->db->beginTransaction();
        $this->inTransaction = true;

    	$tokens = $this->parseTokens($content);
        foreach($tokens as $token => $count) {
            $this->updateToken($token, $count, $category_id);
        }
        $this->saveDocument($category_id, $content);
        $this->updateProbabilities();

        $this->db->commit();
        $this->inTransaction = false;

        // TODO: Catch Exception and return false instead of logAndDie()?
        return true;
    }


    /**
    * @param string $document_id document id, must be unique
    * @return bool
    */
    function untrainDocument($document_id) {

        $this->db->beginTransaction();
        $this->inTransaction = true;

        $ref = $this->getDocument($document_id);
        if (count($ref)) {
            $tokens = $this->parseTokens($ref['content']);
            foreach($tokens as $token => $count) {
                $this->removeToken($token, $count, $ref['category_id']);
            }
        }
        $this->removeDocument($document_id);
        $this->updateProbabilities();

        $this->db->commit();
        $this->inTransaction = false;

        // TODO: Catch Exception and return false instead of logAndDie()?
        return true;
    }


    /**
    * @return array key = ids, values = array(keys = 'category_id', 'content')
    */
    function getDocumentIds() {

        $documents = array();
        $st = $this->db->query('SELECT id, bayes_categories_id FROM bayes_documents ORDER BY id ASC ');

        foreach ($st->fetchAll() as $row) {

            $documents[$row['id']] = array(
                'category_id' => $row['bayes_categories_id'],
                'body_length'  => $row['body_length'],
                );
        }

        return $documents;
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
        $q = 'SELECT bayes_vectors_id FROM bayes_categories GROUP BY bayes_vectors_id ';
        $st = $this->db->query($q);
        foreach ($st->fetchAll() as $row) {
            $vectors[] = $row['bayes_vectors_id'];
        }

        // Join to categories
        $q = 'SELECT bayes_tokens.bayes_categories_id, SUM(bayes_tokens.count) AS total
        FROM bayes_tokens INNER JOIN bayes_categories
        ON bayes_tokens.bayes_categories_id = bayes_categories.id
        WHERE bayes_categories.bayes_vectors_id = ?
        GROUP BY bayes_tokens.bayes_categories_id ';

        // Constrain to individual vectors
        foreach ($vectors as $vector_id) {

            // Get the total of all known tokens
            $total_tokens = 0;

            $st = $this->db->prepare($q);
            $st->execute(array($vector_id));
            foreach ($st->fetchAll() as $row) {
                $total_tokens += $row['total'];
            }

            // If there are no tokens, reset everything
            if ($total_tokens == 0) {
                $st = $this->db->prepare('UPDATE bayes_categories SET token_count = 0, probability = 0 WHERE bayes_vectors_id = ? ');
                $st->execute(array($vector_id));
                continue;
            }

            // Get all categories
            $categories = array();
            $st = $this->db->prepare('SELECT id FROM bayes_categories WHERE bayes_vectors_id = ? ');
            $st->execute(array($vector_id));

            foreach ($st->fetchAll() as $row) {
                $categories[$row['id']] = true;
            }

            // Repeat $q, update probabilities
            $st = $this->db->prepare($q);
            $st->execute(array($vector_id));
            $st2 = $this->db->prepare('UPDATE bayes_categories SET token_count = ?, probability = ? WHERE id = ? AND bayes_vectors_id = ? ');
            foreach ($st->fetchAll() as $row) {
                $proba = $row['total']/$total_tokens;
                $st2->execute(array($row['total'], $proba, $row['bayes_categories_id'], $vector_id));
                unset($categories[$row['bayes_categories_id']]);
            }

            // If there are categories with no tokens, reset those categories
            $st = $this->db->prepare('UPDATE bayes_categories SET token_count = 0, probability = 0 WHERE id = ? AND bayes_vectors_id = ? ');
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
    * @return array keys = category names, values = scores
    */
    function categorize($document, $vector_id) {

        $scores = array();
        $total_tokens = 0;
        $ncat = 0;

        // Sanity check, convert to UTF-8 plaintext
        include(dirname(__FILE__) . '/suxHtml2UTF8.php');
        $converter = new suxHtml2UTF8($document);
        $document = $converter->getText();

        $categories = $this->getCategories($vector_id);
        $tokens = $this->parseTokens($document);

        foreach ($categories as $data) {
            $total_tokens += $data['token_count'];
            $ncat++;
        }

        foreach($categories as $category_id => $data) {
            $scores[$data['category']] = $data['probability'];
            foreach($tokens as $token => $count) {
                if ($this->tokenExists($token)) {
                    $token_count = $this->getTokenCount($token, $category_id);
                    $prob = 0;
                    if ($token_count && $data['token_count']) $prob = (float) $token_count/$data['token_count']; // Probability
                    else if ($data['token_count']) $prob = (float) 1/(2*$data['token_count']); // Fake probability, like a very infrequent word
                    $scores[$data['category']] *= pow($prob, $count)*pow($total_tokens/$ncat, $count);
                    // pow($total_tokens/$ncat, $count) is here to avoid underflow.
                }
            }
        }

        return $this->rescale($scores);
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
        // $rawtokens = preg_split("/[^\w]+/u", $string); // Doesn't work as expected?
        $rawtokens = preg_split('/[\x00-\x2f\x3a-\x40\x5b-\x60\x7b-\x7e]+/u', $string);

        //. Get stopwords
        if (is_readable(dirname(__FILE__) . "/symbionts/stopwords/{$this->l10n}.txt")) {
            $this->ignore_list = file(dirname(__FILE__) . "/symbionts/stopwords/{$this->l10n}.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }

        // Generic internet cruft for good measure
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

        $st = $this->db->prepare('SELECT COUNT(*) FROM bayes_tokens WHERE token = ? ');
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

        $st = $this->db->prepare('SELECT * FROM bayes_tokens WHERE token = ? AND bayes_categories_id = ? ');
        $st->execute(array($token, $category_id));

        if ($row = $st->fetch()) {
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

            $st = $this->db->prepare('INSERT INTO bayes_tokens (token, bayes_categories_id, count) VALUES (?, ?, ?) ');
            return $st->execute(array($token, $category_id, $count));

        } else {

            $st = $this->db->prepare('UPDATE bayes_tokens SET count = count + ? WHERE bayes_categories_id = ? AND token = ? ');
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

            $st = $this->db->prepare('DELETE FROM bayes_tokens WHERE token = ? AND bayes_categories_id = ? ');
            return $st->execute(array($token, $category_id));

        } else {

            $st = $this->db->prepare('UPDATE bayes_tokens SET count = count - ? WHERE bayes_categories_id = ? AND token = ? ');
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

        $st = $this->db->prepare('INSERT INTO bayes_documents (bayes_categories_id, body_plaintext) VALUES (?, ?) ');
        return $st->execute(array($category_id, $content));

    }


    /**
    * @param string $document_id document id, must be unique
    * @return array keys ('category_id', 'content', 'id') values (...)
    */
    private function getDocument($document_id) {

        $ref = array();

        $st = $this->db->prepare('SELECT * FROM bayes_documents WHERE id = ?');
        $st->execute(array($document_id));

        if ($row = $st->fetch()) {
            $ref['category_id'] = $row['bayes_categories_id'];
            $ref['content'] = $row['body_plaintext'];
            $ref['id'] = $row['id'];
        }

        return $ref;
    }


    /**
    * @param  string $document_id document id, must be unique
    * @return bool
    */
    private function removeDocument($document_id) {

        $st = $this->db->prepare('DELETE FROM bayes_documents WHERE id = ? ');
        return $st->execute(array($document_id));

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

        $message = "suxNaiveBayesian Error: \n";
        $message .= $e->getMessage() . "\n";
        $message .= "File: " . $e->getFile() . "\n";
        $message .= "Line: " . $e->getLine() . "\n\n";
        $message .= "Backtrace: \n" . print_r($e->getTrace(), true) . "\n\n";
        die("<pre>{$message}</pre>");

    }


}

/*

-- Database

CREATE TABLE `bayes_categories` (
  `id` int(11) NOT NULL auto_increment,
  `category` varchar(64) NOT NULL,
  `bayes_vectors_id` int(11) NOT NULL,
  `probability` double NOT NULL default '0',
  `token_count` bigint(20) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `grouping` (`category`,`bayes_vectors_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE `bayes_documents` (
  `id` int(11) NOT NULL auto_increment,
  `bayes_categories_id` int(11) NOT NULL,
  `body_plaintext` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE `bayes_tokens` (
  `id` int(11) NOT NULL auto_increment,
  `token` varchar(64) NOT NULL,
  `bayes_categories_id` int(11) NOT NULL,
  `count` bigint(20) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `grouping` (`token`,`bayes_categories_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE `bayes_vectors` (
  `id` int(11) NOT NULL auto_increment,
  `vector` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

*/

?>