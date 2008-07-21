<?php

require_once '../config.php';
require_once '../initialize.php';
include_once 'suxRSS.php';

$rss = new suxRSS();


// $res = $rss->fetchRSS('http://blogs.microsoft.co.il/blogs/MainFeed.aspx');
// $res = $rss->fetchRSS('http://www.people.com.cn/rss/politics.xml');
// $res = $rss->fetchRSS('http://news8.thdo.bbc.co.uk/rss/russian/news/rss.xml');
// $res = $rss->fetchRSS('http://www.bulletins-electroniques.com/rss/be_afriquedusud.xml');
// $res = $rss->fetchRSS('http://blogs.yahoo.co.jp/chomperzfight/rss.xml');
// $res = $rss->fetchRSS('http://ekidangirl.exblog.jp/index.xml');
$res = $rss->fetchRSS('http://rss.slashdot.org/Slashdot/slashdot');
// $res = $rss->fetchRSS('http://jerryenmange.blogspot.com/feeds/posts/default?alt=rss');
// $res = $rss->fetchRSS('http://www.trotch.com/');

new dBug($res);

?>