<?php

// Ajax
// Train a document using genericBayesInterface()

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/../../initialize.php');

// ---------------------------------------------------------------------------
// Error checking
// ---------------------------------------------------------------------------

$valid_links = array('messages');

if (!isset($_SESSION['users_id'])) exit;
if (!isset($_POST['link']) || !in_array($_POST['link'], $valid_links)) exit;
if (!isset($_POST['id']) || !filter_var($_POST['id'], FILTER_VALIDATE_INT)) exit;
if (!isset($_POST['cat_id']) || !filter_var($_POST['cat_id'], FILTER_VALIDATE_INT)) exit;

$link = $_POST['link'];
$id = $_POST['id'];
$cat_id = $_POST['cat_id'];

// ---------------------------------------------------------------------------
// Secondary error checking
// ---------------------------------------------------------------------------

require_once(dirname(__FILE__) . '/../../includes/suxLink.php');
require_once(dirname(__FILE__) . '/../../includes/suxUser.php');
require_once('bayesUser.php');

$suxLink = new suxLink();
$nb = new bayesUser();
$user = new suxUser();

if (!$user->loginCheck()) exit; // Something is wrong, abort

if (!$nb->isCategoryTrainer($cat_id, $_SESSION['users_id'])) exit; // Something is wrong, abort

$body = false;
if ($link == 'messages') {
    require_once(dirname(__FILE__) . '/../../includes/suxThreadedMessages.php');
    $msg = new suxThreadedMessages();
    $body = $msg->getMessage($id);
    $body = $body['body_html'];
}
if ($body === false) exit; // Something is wrong, abort...

// ---------------------------------------------------------------------------
// Go!
// ---------------------------------------------------------------------------

// Get all the bayes_documents linked to this message where user is trainer
// Also get associated vectors

$link_table = $suxLink->getLinkTableName($link, 'bayes');
$innerjoin = "
INNER JOIN {$link_table} ON {$link_table}.bayes_documents_id = bayes_documents.id
INNER JOIN {$link} ON {$link_table}.{$link}_id = {$link}.id
INNER JOIN bayes_categories ON bayes_categories.id = bayes_documents.bayes_categories_id
INNER JOIN bayes_auth ON bayes_categories.bayes_vectors_id = bayes_auth.bayes_vectors_id
";

$query = "
SELECT bayes_documents.id, bayes_auth.bayes_vectors_id FROM bayes_documents
{$innerjoin}
WHERE {$link}.id = ?
AND bayes_auth.users_id = ? AND (bayes_auth.owner = 1 OR bayes_auth.trainer = 1)
"; // Note: bayes_auth WHERE condition equivilant to nb->isCategoryTrainer()

$db = suxDB::get();
$st = $db->prepare($query);
$st->execute(array($id, $_SESSION['users_id']));
$tmp = $st->fetchAll(PDO::FETCH_ASSOC);

// Since we are only training one category/vector at a time, we need to make
// sure we don't untrain other unrlated vectors here.

$vec_id = $nb->getVectorsByCategory($cat_id);
foreach ($tmp as $val) {
    if (isset($vec_id[$val['bayes_vectors_id']])) {
        $nb->untrainDocument($val['id']);
        $suxLink->deleteLink($link_table, 'bayes_documents', $val['id']);
    }
}

// Recategorize

$doc_id = $nb->trainDocument($body, $cat_id);
$suxLink->setLink($link_table, 'bayes_documents', $doc_id, $link, $id);


?>