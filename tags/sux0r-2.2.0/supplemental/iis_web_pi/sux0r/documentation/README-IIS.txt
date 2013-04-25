Web PI cannot setup any Cron jobs.

Because of this, you will need to call the following URL to refresh RSS Feeds,
replace YOURSITE accordingly.

* http://YOURSITE/modules/feeds/cron.php

It's up to you to figure out a way to poll this link every 15 minutes, or so.
For example, when using a dedicated server, you could use the Windows Task
Scheduler and write a clever batch file to do it.

The task of setting this up is left as an exercise for the reader.

--[[----------------------------------------------------------------------------

There is a bug that has to do with the .htaccess rewrite translations located in
the web.config file. Here are the steps to reproduce the issue:

1) Install Sux0r via Web PI
3) Login
4) Menu -> Photos -> New Photoalbum : Create a new Photoalbum
5) Menu -> Photos -> Upload Image(s) : Upload an image into the Photoalbum
6) Navigate to the photo;
   e.g. Click Menu -> Photos, Click the Album thumbnail, Click the photo thumbnail.

At this point you will be in "view" mode, that is to say:

* http://<YOURSITE>/sux0r/photos/view/<SOME_ID>

During testing, the webpage displayed a "?" instead of the desired photo.
However, if the administrator manually adjusts the permissions to the
"sux0r/data" directory to the equivilant of "chmod 777", the photo will appear.

I have tried to do this myself in the manifest.xml file with:

<setAcl path="sux0r/data" setAclAccess="Modify" setAclUser="anonymousAuthenticationUser" />

But, it doesn't seem to be permissive enough.
