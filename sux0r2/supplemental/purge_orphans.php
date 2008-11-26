<?php

require_once(dirname(__FILE__)  . '/../config.php');
require_once(dirname(__FILE__)  . '/../initialize.php');
require_once(dirname(__FILE__)  . '/../includes/suxFunct.php');
require_once(dirname(__FILE__)  . '/../includes/suxLink.php');
require_once(dirname(__FILE__)  . '/../includes/suxPhoto.php');
set_time_limit(900); // Set the timeout to 15 minutes.

// ----------------------------------------------------------------------------
// Set debug mode, if true nothing actually gets deleted
// ----------------------------------------------------------------------------

$debug = true;

// ----------------------------------------------------------------------------
// Purge orphaned link tables
// ----------------------------------------------------------------------------

if ($debug) echo "> Debug mode = true, nothing will be deleted. <br />\n";

$db = suxDB::get();

// Scan for missing links, push them in $not_found array
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

    // $val[0] -> link_table_name
    // $val[1] -> column_name_1
    // $val[2] -> column_id_1
    // $val[3] -> column_name_2
    // $val[4] -> column_id_2

    $query = "DELETE FROM {$val[0]} WHERE {$val[1]} = {$val[2]} AND {$val[3]} = {$val[4]} ";
    if (!$debug) $count += $db->exec($query);
    echo $query . "; <br /> \n";

}

suxDB::commitTransaction($tid);
echo "> $count links deleted <br /> \n";


// ----------------------------------------------------------------------------
// Purge orphaned images
// ----------------------------------------------------------------------------

// image dir => db table
$image_dirs = array(
    'blog' => 'messages',
    'photos' => 'photos',
    'user' => 'users_info',
    );

$not_found = array();
foreach($image_dirs as $dir => $table) {
    $path = $CONFIG['PATH'] . "/data/$dir";
    if (is_dir($path)) foreach (new DirectoryIterator($path) as $file) {

        $pattern = '/[^_fullsize](\.jpe?g|\.gif|\.png)$/i';
        if ($file->isFile() && preg_match($pattern, $file)) {
            // Query
            $query = "SELECT id FROM {$table} WHERE image = " . $db->quote("$file");
            $st = $db->query($query);
            if ($st->fetchColumn() <= 0) {
                $not_found[] = "$path/$file";
            }
        }

    }

}


// Purge
$count = 0;
foreach ($not_found as $file) {

    if (!$debug) {
        if (is_file($file)) unlink($file);
        if (is_file(suxPhoto::t2fImage($file))) unlink(suxPhoto::t2fImage($file));
        ++$count;
    }

    echo "unlink() $file <br />\n";

}

echo "> $count images deleted <br /> \n";

?>
