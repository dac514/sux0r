<?php

/* Navigation menu */

$gtext['navcontainer'] = array(
    '首页' => suxFunct::makeUrl('/home'),
    '日志' => array(
		suxFunct::makeUrl('/blog'),
        suxFunct::getModuleMenu('blog'),
		),
    'Feeds' => array(
		suxFunct::makeUrl('/feeds'),
        suxFunct::getModuleMenu('feeds'),
		),
    '书签' => array(
		suxFunct::makeUrl('/bookmarks'),
        suxFunct::getModuleMenu('bookmarks'),
		),
    '图片' => array(
		suxFunct::makeUrl('/photos'),
        suxFunct::getModuleMenu('photos'),
		),
    '源代码' => 'http://sourceforge.net/projects/sux0r/',
	);


/* Copyright */

$gtext['copyright'] = '<a href="http://www.sux0r.org/">sux0r</a> is copyright &copy;
<a href="http://www.trotch.com/">Trotch Inc</a> ' . date('Y') . ' and is distributed under
the <a href="http://www.fsf.org/licensing/licenses/gpl-3.0.html">GNU General Public License</a>.
Hosting by <a href="http://www.networkredux.com/">Network Redux</a>.';

$gtext['data_license'] = 'Unless otherwise specified, contents of this site are copyright by the contributors and available under the <br />
<a href="http://creativecommons.org/licenses/by/3.0/">Creative Commons Attribution 3.0</a> .
Contributors should be attributed by full name or nickname.';

/* Now back our regular scheduled program */

$gtext['404_continue'] = '点击此处继续';
$gtext['404_h1'] = '额，页面没有找到。(Error 404)';
$gtext['404_p1'] = '出于某些原因 (错误类型的URL，从其他网站转入出错，过期的搜索引擎信息或者我们删了个文件)页面不存在';
$gtext['admin'] = '管理';
$gtext['banned_continue'] = '点击此处继续';
$gtext['banned_h1'] = '禁用';
$gtext['banned_p1'] = '你是个坏蛋，非常非常坏。';
$gtext['home'] = '首页';
$gtext['continue'] = '继续';
$gtext['login'] = '登录';
$gtext['logout'] = '退出';
$gtext['register'] = '注册';
$gtext['welcome'] = '欢迎';

?>