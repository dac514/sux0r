http://www.sux0r.org/
包括拼写错误都将作为最终结果保留，并不可更改。


### 许可 ###

Sux0r是免费软件：您可以在Free Software Foundation发布的GNU许可协议下对Sux0r进行修改或者重开发，
您可以选择遵循GNU第三版本或者之后的版本。

开发本程序是希望它能发挥应有的作用，但是不提供任何担保，也不提供任何包含适用于商业或者个人意图的担保。

请参阅：http://www.fsf.org/licensing/licenses/gpl-3.0.html


### 例外 ###

目录'templates'和'media'中的文件可以遵循一些简单的许可协议，也就是说你可以选用任何合适的LGPL许可。

在名为“symbionts”的子目录中，包含有第三方的开源产品，它们可能不遵循GNU协议。这些附加的开源代码库，
将遵循它们自己的许可协议，所以您应根据各自情况分别对待。


### 系统需求 ###

* PHP 5.2.3 或更高版本，并带有mb_和PDO扩展支持
* MySQL 5.0+ 或 PostgreSQL 8.3+, UTF enabled
* Apache服务器


### 安装 ###

1. 将./supplemental/db-mysql.sql 导入 MySQL数据库
   (或) 将./supplemental/db-pgsql.sql 导入 PostgreSQL数据库

2. 更改./data读写权限为777

3. 更改./temporary读写权限为777

4. 编辑配置 ./config.php ./.htaccess

5. 使用浏览器访问'http://YOURSITE/supplemental/root.php'，生成root
   管理员帐号

6. 从服务器中删除./supplemental目录

7. 获取并生成聚合内容，请每隔15分钟左右使用浏览器访问
   'http://YOURSITE/modules/feeds/cron.php'
   (例如: /bin/nice /usr/bin/wget -q -O /dev/null "http://YOURSITE/modules/feeds/cron.php")


### 支持/帮助 (英语语言) ###

@see: https://sourceforge.net/forum/forum.php?forum_id=447216


### 脚注 ###

- 页面已通过W3C验证

- 测试运行环境: (OS X) Firefox 2.0.0.17, (OS X) Safari 3.1.2, Opera 9.2 (OS X)
  (Win XP) Internet Explorer 6, (Win XP) Firefox 3.0.1


### 致谢 ###
