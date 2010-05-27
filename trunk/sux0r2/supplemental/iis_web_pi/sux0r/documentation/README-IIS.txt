Web PI cannot setup any Cron jobs.

Because of this, you will need to call the following URL to refresh RSS Feeds,
replace YOURSITE accordingly.

* http://YOURSITE/modules/feeds/cron.php

It's up to you to figure out a way to poll this link every 15 minutes, or so.
For example, when using a dedicated server, you could use the Windows Task
Scheduler and write a clever batch file to do it.

The task of setting this up is left as an exercise for the reader.