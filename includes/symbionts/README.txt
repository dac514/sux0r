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

Use rsync when updating 3rd party vendors, example:

rsync -rcvb --backup-dir=/tmp --cvs-exclude ~/Desktop/tinymce/ ~/Sites/sux0r2/includes/symbionts/tinymce/

------------------------------------------------------------------------------
3rd Party Vendors, in alphabetical order
------------------------------------------------------------------------------

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
* Version: 1.1.7.2
* Licence: GPL 3
* Website: http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed/index.php


JpGraph
* Path: ./jpgraph/
* Version: 2.3.4
* Licence: QPL 1.0
* Website: http://www.aditus.nu/jpgraph/


PHP Calendar
* Path: ./calendar.php
* Version: 2.3
* Licence: Artistic License
* Website: http://keithdevens.com/software/php_calendar


Scriptaculous
* Path: ./scriptaculous/
* Version: 1.8.2
* Licence: MIT
* Website: http://script.aculo.us/

Smarty
* Path: ./Smarty/
* Version: 2.6.22
* Licence: LGPL 2.1
* Website: http://www.smarty.net/


SmartyPhoneFormatPlugin
* Path: ./SmartyAddons/plugins/modifier.phone_format.php
* Version: 0.1.1
* Licence: LGPL 2.1
* Website: http://smarty.incutio.com/?page=PhoneFormatPlugin


SmartyValidate
* Path: ./SmartyAddons/libs/SmartyValidate.class.php
        ./SmartyAddons/plugins/%validate%
        ./SmartyAddons/docs/SmartyValidate/
* Version: 2.9-dev
* Licence: LGPL 2.1
* Website: http://www.phpinsider.com/php/code/SmartyValidate/


Stopwords
* Path: ./stopwords/
* Version: Unknown
* Licence: BSD
* Website: http://members.unine.ch/jacques.savoy/clef/


TinyMCE
* Path: ./tinymce/
* Version: 3.2.1.1 (+ language files)
* Licence: LGPL 2.1
* Website: http://tinymce.moxiecode.com/
