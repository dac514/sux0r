<?php

require_once(dirname(__FILE__)  . '/../config.php');
require_once(dirname(__FILE__)  . '/../initialize.php');
require_once(dirname(__FILE__)  . '/../includes/suxFunct.php');
require_once(dirname(__FILE__)  . '/../includes/suxLink.php');

set_time_limit(900); // Set the timeout to 15 minutes.
$db = suxDB::get();


// ----------------------------------------------------------------------------
// Purge orphaned link tables
// ----------------------------------------------------------------------------

// Scan for missing links, push them in $not_found
$link = new suxLink();
$link_tables = $link->getLinkTables();
$not_found = array();

foreach ($link_tables as $val) {

    $parts = explode('_', $val);
    if (count($parts) != 3) die('Unexpected result, ejecting early to avoid catastrophe...');

    $st = $db->query("SELECT * FROM {$val} ");
    $tmp =  $st->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tmp as $val2) {

        $tmp2 = $link->getLinkColumnName($val, $parts[1]) . '_id';
        $tmp3 = $link->getLinkColumnName($val, $parts[2]) . '_id';

        // Table 1
        $query = 'SELECT id FROM ' . $link->getLinkColumnName($val, $parts[1]) . " WHERE id = {$val2[$tmp2]} ";
        $st = $db->query($query);
        if ($st->fetchColumn() <= 0) {
            $not_found[] = array($val, $tmp2, $val2[$tmp2], $tmp3, $val2[$tmp3]);
            continue;
        }

        // Table 2
        $query = 'SELECT id FROM ' . $link->getLinkColumnName($val, $parts[2]) . " WHERE id = {$val2[$tmp3]} ";
        $st = $db->query($query);
        if ($st->fetchColumn() <= 0) {
            $not_found[] = array($val, $tmp3, $val2[$tmp3], $tmp2, $val2[$tmp2]);
            continue;
        }

    }

}

// Delete dead links
$count = 0;
$tid = suxDB::requestTransaction();
foreach ($not_found as $val) {

    $query = "DELETE FROM {$val[0]} WHERE {$val[1]} = {$val[2]} AND {$val[3]} = {$val[4]} ";
    $count += $db->exec($query);
    echo $query . "; <br />";

}
suxDB::commitTransaction($tid);
echo "$count links deleted <br />";

// ----------------------------------------------------------------------------
// Purge orphaned photos
// ----------------------------------------------------------------------------

// TODO


?>
