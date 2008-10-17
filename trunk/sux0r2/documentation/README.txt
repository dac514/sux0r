sux0r 2.0 _Beta2
http://www.sux0r.org/
All spelling mistakes are final and will not be refunded

### LICENSE ###

sux0r is licensed under the GNU AGPL. This means that if you run a sux0r
website you are obligated to tell your users that they can have access to the
source code. Furthermore, you are obligated it to give access to the source
code if they ask for it.

The easiest way to comply is to link to http://www.sux0r.org and hope no one
asks you for any changes you aren't committing back to us. I leave it to your
discretion to not be an asshole about this.

More info:
http://www.fsf.org/licensing/licenses/agpl-3.0.html


### EXCEPTIONS ###

A subdirectory named 'symbionts' contains files from 3rd party open source
vendors that may or may not be licensed under the GNU AGPL.

These additional open source libraries have their own licensing requirements
and, as such, should be dealt with according to their own licenses.


### REQUIREMENTS ###

* PHP 5.2.3 or higher with mb_ and PDO extensions
* MySQL 5.0+ or PostgreSQL 8.3+, UTF enabled
* Apache webserver


### INSTALL ###

1. Import ./supplemental/db-mysql.sql into MySQL
   (or) Import ./supplemental/db-pgsql.sql into PostgreSQL

2. chmod 777 ./data

3. chmod 777 ./temporary

4. Edit ./config.php and ./.htaccess appropriately

5. Point your web browser to 'http://YOURSITE/supplemental/root.php' and make
   yourself a root user

6. Delete the ./supplemental directory from the webserver

7. Setup a CRON job to get RSS feeds by calling
   'http://YOURSITE/modules/feeds/cron.php' every =~ 15 minutes
   (example: /bin/nice /usr/bin/wget -q -O /dev/null "http://YOURSITE/modules/feeds/cron.php")


### FOOTNOTES ###

- Pages pass W3C Validation (as best I can test)

- Tested with: (OS X) Firefox 2.0.0.17, (OS X) Safari 3.1.2, Opera 9.2 (OS X)
  (Win XP) Internet Explorer 6, (Win XP) Firefox 3.0.1

- Maybe it works with other configurations? I don't know yet, hence why this
  is  Beta

### THANKS! ###
