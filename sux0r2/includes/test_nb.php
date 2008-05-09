<?php

require_once '../config.php';
require_once '../initialize.php';
include_once 'suxNaiveBayesian.php';

$nb  = new suxNaiveBayesian();
$vec_id = 1;


?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>PHP Naive Bayesian Filter</title>
	<style>
	.succes { font-weight: 600; color: #00CC00; }
	.erreur { font-weight: 600; color: #CC0000; }
	</style>
</head>

<body>
<h1>PHP Naive Bayesian Filter</h1>
<?php

// ----------------------------------------------------------------------------
// Get rid of magic quotes
// ----------------------------------------------------------------------------

if (get_magic_quotes_gpc()) {
    $in = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
    while (list($k,$v) = each($in)) {
        foreach ($v as $key => $val) {
            if (!is_array($val)) {
                $in[$k][$key] = stripslashes($val);
                continue;
            }
            $in[] =& $in[$k][$key];
        }
    }
    unset($in);
}

if (isset($_REQUEST['action'])) {
    switch ($_REQUEST['action']) {
    case 'addvec':
        addvec();
        break;
    case 'remvec':
        remvec();
        break;
    case 'addcat':
        addcat();
        break;
    case 'remcat':
        remcat();
        break;
    case 'train':
        train();
        break;
    case 'untrain':
        untrain();
        break;
    case 'cat':
        cat();
        break;
    }
}

function addvec() {
	global $nb;
    $vec = trim(strip_tags($_REQUEST['vec']));

	if (strlen($vec) == 0) {
        echo '<p class="erreur"><strong>Erreur:</strong> Vous devez donner un nom de vecteur.</p>';
    } else {
        if ($nb->addVector($vec)) {
            echo "<p class='succes'>Le vecteur vient d'Ãªtre ajoutÃ©.</p>";
        }
        else {
            echo "<p class='erreur'>There was a problem inserting the vector.</p>";
        }
    }
}

function remvec() {
	global $nb;
	$vec_id = trim(strip_tags($_REQUEST['vec_id']));

	if (strlen($vec_id) == 0) {
        echo '<p class="erreur"><strong>Erreur:</strong> Vous devez donner un nom de vecteur.</p>';
    } else {
        if ($nb->removeVector($vec_id)) {
            echo "<p class='succes'>Le vecteur vient d'Ãªtre supprimÃ©.</p>";
        }
        else {
            echo "<p class='erreur'>There was a problem removing the vector.</p>";
        }

    }
}

function addcat() {
	global $nb;
    $cat = trim(strip_tags($_REQUEST['cat']));
    $vec_id = trim(strip_tags($_REQUEST['vec_id']));

	if (strlen($cat) == 0) {
        echo '<p class="erreur"><strong>Erreur:</strong> Vous devez donner un nom de catÃ©gorie.</p>';
    } else {
        if ($nb->addCategory($cat, $vec_id)) {
            echo "<p class='succes'>La catÃ©gorie vient d'Ãªtre ajoutÃ©e.</p>";
        }
        else {
            echo "<p class='erreur'>There was a problem inserting the category.</p>";
        }
    }
}

function remcat() {
	global $nb;
	$cat = trim(strip_tags($_REQUEST['cat']));

	if (strlen($cat) == 0) {
        echo '<p class="erreur"><strong>Erreur:</strong> Vous devez donner un nom de catÃ©gorie.</p>';
    } else {
        if ($nb->removeCategory($cat)) {
            echo "<p class='succes'>La catÃ©gorie vient d'Ãªtre supprimÃ©e.</p>";
        }
        else {
            echo "<p class='erreur'>There was a problem removing the category.</p>";
        }

    }
}

function train() {
	global $nb;

    $cat_id = trim(strip_tags($_REQUEST['cat_id']));
	if (strlen($cat_id) == 0) {
        echo '<p class="erreur"><strong>Erreur:</strong> Vous devez donner un identifiant pour la catÃ©gorie.</p>';
        return;
    }
	$doc = trim($_REQUEST['document']);
	if (strlen($doc) == 0) {
        echo '<p class="erreur"><strong>Erreur:</strong> Vous devez donner un document.</p>';
        return;
    }

    if ($nb->trainDocument($cat_id, $doc)) {
        echo "<p class='succes'>Le filtre vient d'Ãªtre entraÃ®nÃ©.</p>";
    } else {
        echo "<p class='erreur'>Erreur: Erreur dans l'entraÃ®nement du filtre.</p>";
    }
}

function untrain() {
    global $nb;
	$docid = trim(strip_tags($_REQUEST['docid']));

	if (strlen($docid) == 0) {
        echo '<p class="erreur"><strong>Erreur:</strong> Vous devez donner un identifiant pour le document.</p>';
        return;
    }

    if ($nb->untrainDocument($docid)) {
        echo "<p class='succes'>Le filtre vient d'Ãªtre dÃ©sentraÃ®nÃ©.</p>";
    } else {
        echo "<p class='erreur'>Erreur: Erreur dans le dÃ©sentraÃ®nement du filtre.</p>";
    }
}

function cat() {
	global $nb, $vec_id;

	$doc = trim($_REQUEST['document']);
	if (strlen($doc) == 0) {
        echo '<p class="erreur"><strong>Erreur:</strong> Vous devez donner un document.</p>';
        return;
    }

    $scores = $nb->categorize($doc, $vec_id);
    echo "<table border='1'>\n";
    echo "<tr><th>CatÃ©gories</th><th colspan='2'>Scores</th></tr>\n";
    foreach ($scores as $cat => $score) {
        echo "<tr><td>$cat</td><td>" . round($score*100, 2) . " %" . "</td><td>( $score )</td></tr>\n";
    }
    echo "</table>";


}

$vecs = $nb->getVectors();
$cats = $nb->getCategories($vec_id);

?>

<h2>Explications</h2>
<p>
Vous devez d'abord avoir au minimum deux catÃ©gories pour pouvoir avoir une comparaison. Par exemple <strong>spam</strong>
et <strong>nonspam</strong>. Les identifiants ne doivent pas avoir d'espaces et doivent contenir que des lettres
et des chiffres.
</p>
<p>
Ensuite vous pouvez entraÃ®ner votre filtre. Vous allez prendre une sÃ©rie de spams, choisir <strong>spam</strong>
comme catÃ©gorie et entraÃ®nez le filtre. Prenez aussi quelques mails qui ne sont pas des spams, choisissez
<strong>nonspam</strong> et entraÃ®nez le filtre.
</p>
<p>
Maintenant vous pouvez prendre un email au hasard, et essayez de voir si c'est un spam ou si c'est un email
normal. Pour cela utiliser la fonction de catÃ©gorisation. Plus le score est important, plus votre message a une
<emph>chance</emph> d'appartenir Ã  cette catÃ©gorie. Il y a une normalisation automatique, cela donne souvent
0 ou 1 si vous n'avez que 2 catÃ©gories. Si vous avez des questions, posez les sur
<a href="http://www.xhtml.net/">xhtml.net</a>.
</p>

<!-- Add Vector -->
<h2>Ajouter un vecteur</h2>
<form action='test_nb.php' method='POST'>
<fieldset>
<input type='hidden' name='action' value='addvec'/>
Identifiant du vecteur : <input type='text' name='vec' value='' />
<input type='submit'  value='Ajouter ce vecteur' />
</fieldset>
</form>


<!-- Add Categorie -->
<h2>Ajouter une catÃ©gorie</h2>
<form action='test_nb.php' method='POST'>
<fieldset>
<input type='hidden' name='action' value='addcat'/>
Vecteur: <select name='vec_id'>
<?php
foreach ($vecs as $key => $val) {
    echo "<option value='$key'>{$val['vector']}</option>\n";
}
?>
</select><br />
Identifiant de la catÃ©gorie : <input type='text' name='cat' value='' />
<input type='submit'  value='Ajouter cette catÃ©gorie' />
</fieldset>
</form>


<!-- Train -->
<h2>EntraÃ®ner le filtre</h2>
<form action='test_nb.php' method='POST'>
<fieldset>
<input type='hidden' name='action' value='train'/>
CatÃ©gorie pour le document :
<select name='cat_id'>
<?php
foreach ($cats as $key => $val ) {
    echo "<option value='$key'>{$val['category']}</option>\n";
}

?>
</select>
<br />
Copier/coller ici le document :<br />
<textarea name="document" cols='50' rows='20'></textarea><br />
<input type='submit'  value='EntraÃ®ner le filtre avec ce document' />
</fieldset>
</form>


<!-- Categorize -->
<h2>Trouver la catÃ©gorie pour un document</h2>
<form action='test_nb.php' method='POST'>
<fieldset>
<input type='hidden' name='action' value='cat'/>
Copier/coller ici le document :<br />
<textarea name="document" cols='50' rows='20'></textarea><br />
<input type='submit'  value='Trouver la catÃ©gorie de ce document' />
</fieldset>
</form>


<!-- Remove Vector -->
<h2>Supprimer un vecteur</h2>
<form action='test_nb.php' method='POST'>
<fieldset>
<input type='hidden' name='action' value='remvec'/>
Vecteur Ã  supprimer :
<select name='vec_id'>
<?php
foreach ($vecs as $key => $val) {
    echo "<option value='$key'>{$val['vector']}</option>\n";
}
?>
</select>
<input type='submit' value='Supprimer ce vecteur' />
</fieldset>
</form>


<!-- Remove Category -->
<h2>Supprimer une catÃ©gorie</h2>
<form action='test_nb.php' method='POST'>
<fieldset>
<input type='hidden' name='action' value='remcat'/>
CatÃ©gorie Ã  supprimer :
<select name='cat'>
<?php
foreach($cats as $key => $val) {
    echo "<option value='$key'>{$val['category']}</option>\n";
}
?>
</select>
<input type='submit' value='Supprimer cette catÃ©gorie' />
</fieldset>
</form>

<!-- Remove Document -->
<h2>Supprimer un document</h2>
<form action='test_nb.php' method='POST'>
<fieldset>
<input type='hidden' name='action' value='untrain'/>
Document Ã  supprimer :
<select name='docid'>
<?php
$refs = $nb->getDocumentIds();
foreach ($refs as $key => $val) {
    echo "<option value='".$key."'>".$key." - ".$val['category_id']."</option>\n";

}

?>

</select>
<input type='submit'  value='Supprimer ce document' />
</fieldset>
</form>

</body>
</html>
