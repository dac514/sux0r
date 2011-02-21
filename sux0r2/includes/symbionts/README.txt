------------------------------------------------------------------------------
About symbionts
------------------------------------------------------------------------------

Symbionts are 3rd party open source vendors that may or may not be licensed
under the GNU General Public License (version 3)

These additional open source libraries have their own licensing requirements
and, as such, should not be directly modified by the sux0r development group.

------------------------------------------------------------------------------
Rationale
------------------------------------------------------------------------------

Sux0r is far removed from the world of compiled languages that many of the
FSF licenses are meant for. Since everything sux0r deals with are human readable
scripts, we hope everyone can agree that we are being respectful and allowing
for exceptions where necessary. If you disgree please contact us so we can
resolve the issue.

------------------------------------------------------------------------------
Pro-tip
------------------------------------------------------------------------------

Use rsync when updating 3rd party vendors.

Example for TinyMCE:

$ rsync -rcvb --cvs-exclude --backup-dir=/tmp ~/Desktop/tinymce_language_pack/ ~/Desktop/tinymce/jscripts/tiny_mce/
$ rsync -rcvb --cvs-exclude --delete --backup-dir=/tmp ~/Desktop/tinymce/ ~/Sites/sux0r2/includes/symbionts/tinymce/

------------------------------------------------------------------------------
3rd Party Vendors, in alphabetical order
------------------------------------------------------------------------------

TODO:
* jQuery-UI info, Jcrop info.


Cropper
* Path: ./cropper/
* Version: 1.2.0
* Licence: BSD
* Website: http://www.defusion.org.uk/


dBug
* Path: ./dBug.php
* Version: Dec 04, 2007
* Licence: GPL, (version unspecified, assuming GPL 3)
* Website: http://dbug.ospinto.com/
* Notes: The PHP file says March 22, 2007 but the website says Dec 04, 2007


htmLawed
* Path: ./htmLawed/
* Version: 1.1.9.3
* Licence: LGPL 3
* Website: http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed/index.php


PHP Calendar
* Path: ./calendar.php
* Version: 2.3
* Licence: Artistic License
* Website: http://keithdevens.com/software/php_calendar


Scriptaculous
* Path: ./scriptaculous/
* Version: 1.8.3
* Licence: MIT
* Website: http://script.aculo.us/


Securimage
* Path: ./securimage/
* Version: 1.0.3.1
* Licence: GPL 2.1 or any later version
* Website: http://www.phpcaptcha.org/


Smarty
* Path: ./Smarty/
* Version: 3.0.7
* Licence: LGPL 2.1
* Website: http://www.smarty.net/


SmartyValidate
* Path: ./SmartyAddons/libs/SmartyValidate.class.php
        ./SmartyAddons/plugins/%validate%
        ./SmartyAddons/docs/SmartyValidate/
* Version: 3.0.2 (beta)
* Licence: LGPL 2.1
* Website: http://www.phpinsider.com/php/code/SmartyValidate/


Stopwords
* Path: ./stopwords/
* Version: Unknown
* Licence: BSD
* Website: http://members.unine.ch/jacques.savoy/clef/


TinyMCE (jQuery package)
* Path: ./tinymce/
* Version: 3.3.9.3 (+ language files)
* Licence: LGPL 2.1
* Website: http://tinymce.moxiecode.com/
