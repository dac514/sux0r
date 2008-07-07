<?php

require_once '../config.php';
require_once '../initialize.php';
include_once 'suxRSS.php';

$rss = new suxRSS();


$res = $rss->getRSS('http://blogs.microsoft.co.il/blogs/MainFeed.aspx');
// $res = $rss->getRSS('http://www.people.com.cn/rss/politics.xml');
// $res = $rss->getRSS('http://news8.thdo.bbc.co.uk/rss/russian/news/rss.xml');
// $res = $rss->getRSS('http://www.bulletins-electroniques.com/rss/be_afriquedusud.xml');
// $res = $rss->getRSS('http://blogs.yahoo.co.jp/chomperzfight/rss.xml');
// $res = $rss->getRSS('http://ekidangirl.exblog.jp/index.xml');
// $res = $rss->getRSS('http://rss.slashdot.org/Slashdot/slashdot');
// $res = $rss->getRSS('http://jerryenmange.blogspot.com/feeds/posts/default?alt=rss');
// $res = $rss->getRSS('http://www.trotch.com/');

new dBug($res);

?>