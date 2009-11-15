http://www.sux0r.org/
All spelling mistakes are final and will not be refunded


### LICENSE ###

Sux0r is free software: you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation,
either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.

@see: http://www.fsf.org/licensing/licenses/gpl-3.0.html


### EXCEPTIONS ###

Files in the 'templates' and 'media' directories can be licensed under simple
permissive terms i.e. your pick of any LGPL compatible license.

A subdirectory named 'symbionts' contains files from 3rd party open source
vendors that may or may not be licensed under the GNU GPL. These additional
open source libraries have their own licensing requirements and, as such, should
be dealt with according to their own licenses.


### REQUIREMENTS ###

* PHP 5.2.3 or higher with mb, gd, and PDO extensions
* MySQL 5.0+ or PostgreSQL 8.3+, UTF enabled
* Apache webserver (but it also works on IIS7, too)


### INSTALL ###

1. Import ./supplemental/sql/db-mysql.sql into MySQL
   (or) Import ./supplemental/sql/db-postgres.sql into PostgreSQL

2. chmod 777 ./data
   chmod 777 ./temporary

3. mv ./sample-config.php ./config.php
   mv ./sample-.htaccess ./.htaccess

4. Edit ./config.php and ./.htaccess appropriately

5. Check dependencies, e.g.
   http://YOURSITE/supplemental/dependencies.php

6. Point your web browser to 'http://YOURSITE/supplemental/root.php' and make
   yourself a root user

7. Setup a CRON job to get RSS feeds by calling
   'http://YOURSITE/modules/feeds/cron.php' every =~ 15 minutes
   (example: /bin/nice /usr/bin/wget -q -O /dev/null "http://YOURSITE/modules/feeds/cron.php")

8. Delete the ./supplemental directory from the webserver.


### SUPPORT/HELP ###

@see: https://sourceforge.net/forum/forum.php?forum_id=447216


### FOOTNOTES ###

- Pages pass W3C Validation (as best I can test)

- Tested with many web browsers, but certainly not all of them.


### THANKS! ###
