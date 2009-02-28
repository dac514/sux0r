sux0r 2.0.3
http://www.sux0r.org/
包括拼写错误都将作为最终结果保留，并不可更改。


### 许可 ###

sux0r遵循GNU AGPL协议。这意味着，如果您运行着sux0r网站，
您将有义务告知您的用户他们可以得到源代码。
此外，当他们要求获取源代码的时候，您将有义务提供给他们。

遵循上述约定最简单的方法就是链接到我们的主页http://www.sux0r.org 。
您对代码所做的修改不受上述约定的作用，希望没有人向您要求获取修改后的版本。
我们给予您修改和重新发布本代码的自由裁量权，并希望您能慎重对待。

更多信息参阅：
http://www.fsf.org/licensing/licenses/agpl-3.0.html


### 例外 ###

在名为“symbionts”的子目录中，包含有第三方的开源产品，它们可能不遵循GNU AGPL
协议。
这些附加的开源代码库，将遵循它们自己的许可协议，所以应根据各自情况分别处理。

目录'templates'和'media'中的文件可以遵循一些简单的许可协议，也就是说你可以选用任何合适的GPL许可。


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
