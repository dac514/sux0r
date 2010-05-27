This directory stores files to package sux0r for Microsoft IIS Web PI.

The package should be a ZIP that looks like:

./install.sql
./manifest.xml
./parameters.xml
./sux0r/<regular sux0r files>
./suxor/web.config

More info about packaging for IIS Web PI:

* http://learn.iis.net/page.aspx/722/reference-for-the-web-application-package/
* http://learn.iis.net/page.aspx/578/package-an-application-for-the-windows-web-application-gallery/
* http://learn.iis.net/page.aspx/616/using-the-microsoft-web-platform-installer/
* http://learn.iis.net/page.aspx/605/windows-web-application-gallery-principles/

TODO, automate and fix:

In addition to the Web Platform Installer, the following steps remain:

1) Point your web browser to `http://YOURSITE/supplemental/root.php` and make
   an admin user.

2) Download wget, create a batch file, contents along the lines of:
   `c:/path/to/wget.exe -O C:/Windows/Temp/junk.txt "http:/YOURSITE/sux0r/modules/feeds/cron.php"`
   Add this batch file to "Task Scheduler"

3) The /data directory needs `chmod 777` permissions. setAclUser="anonymousAuthenticationUser"
   isn't enough. You will eventually run into an issue where you can't view large photos. Not
   sure what is going on here. If you manually set the permissions so that "Users" can
   "Modify" then it works.
