<?php

/**
* suxFunct
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as
* published by the Free Software Foundation, either version 3 of the
* License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @copyright  2008 sux0r development group
* @license    http://www.gnu.org/licenses/agpl.html
*
*/

class suxFunct {

    // Static class, no cloning or instantiating allowed
    final private function __construct() { }
    final private function __clone() { }

    // ------------------------------------------------------------------------
    // Static Functions
    // ------------------------------------------------------------------------


    /**
    * Kill $_SESSION
    */
    static function killSession() {

        $_SESSION = array();
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time()-42000, '/');
        }
        session_destroy();
    }



    /**
    * Redirection
    *
    * @param string $href a uniform resource locator (url)
    */
    static function redirect($href) {

        $href = filter_var($href, FILTER_SANITIZE_URL);

        if (!headers_sent()) {
            header("Location: $href");
        }
        else {
            // Javascript hack
            echo "
            <script type='text/javascript'>
            <!--
            window.location = '{$href}';
            //-->
            </script>
            ";
        }

        exit; // Quit script
    }


    /**
    * Use output buffering to include a file into a string
    *
    * @param string $filename the file path and name to your PHP file.
    * @return string|bool the file contents if successful, false if file not found
    */
    static function getIncludeContents($filename) {
        if (is_file($filename)) {
            ob_start();
            include($filename);
            $contents = ob_get_contents();
            ob_end_clean();
            return $contents;
        }
        return false;
    }


    // isValidEmail()
    // use filter_var($href, FILTER_VALIDATE_EMAIL) ? true : false;
    // See: http://ca3.php.net/manual/en/book.filter.php


    /**
    * Assign a unique id to an image file name. Returns an array of filename
    * conventions
    *
    * @param string $name the name of an image file
    * @return array
    */
    static function renameImage($filename) {

        $pattern = '/(\.jpe?g|\.gif|\.png)$/i';
        $uniqid = time() . substr(md5(microtime()), 0, rand(5, 12));

        $replacement = "_{$uniqid}" . "$1";
        $resize = preg_replace($pattern, $replacement, $filename);

        $replacement = "_{$uniqid}_fullsize" . "$1";
        $fullsize = preg_replace($pattern, $replacement, $filename);

        return array($resize, $fullsize);

    }


    /**
    * Proprtionally crop and resize an image file
    *
    * @param string $format expect jpg, jpeg, gif, or png
    * @param string $filein path to read image file
    * @param string $fileout path to write converted image file
    * @param int $imagethumbsize_w width for conversion
    * @param int $imagethumbsize_h height for conversion
    * @param int $red RGB red value for imagecolorallocate()
    * @param int $green RGB green for imagecolorallocate()
    * @param int $blue RGB blue for imagecolorallocate()
    */
    static function resizeImage($format, $filein, $fileout, $imagethumbsize_w, $imagethumbsize_h, $red = 255, $green = 255, $blue = 255) {

        $format = strtolower($format);
        if ($format == 'jpg') $format = 'jpeg'; // fix stupid mistake
        if (!($format == 'jpeg' || $format == 'gif' || $format == 'png'))  {
            throw new Exception('Invalid image format');
        }

        // --------------------------------------------------------------------
        // Try to avoid problems with memory limit
        // --------------------------------------------------------------------

        $size = getimagesize($filein);

        if ($format == 'jpeg') {
            $fudge = 1.65; // This is a guestimate, your mileage may very
            $memoryNeeded = round(($size[0] * $size[1] * $size['bits'] * $size['channels'] / 8 + Pow(2, 16)) * $fudge);
        }
        else {
            $memoryNeeded = $size[0] * $size[1];
            if (isset($size['bits'])) $memoryNeeded = $memoryNeeded * $size['bits'];
            $memoryNeeded = round($memoryNeeded);
        }

        if (memory_get_usage() + $memoryNeeded > (int) ini_get('memory_limit') * pow(1024, 2)) {
            trigger_error('Image is too big, attempting to compensate...', E_USER_WARNING);
            ini_set('memory_limit', (int) ini_get('memory_limit') + ceil(((memory_get_usage() + $memoryNeeded) - (int) ini_get('memory_limit') * pow(1024, 2)) / pow(1024, 2)) . 'M');
        }

        // --------------------------------------------------------------------
        // Proceed with resizing
        // --------------------------------------------------------------------

        $func = 'imagecreatefrom' . $format;
        $image = $func($filein);
        if (!$image) throw new Exception('Invalid image format');

        $width = $imagethumbsize_w;
        $height = $imagethumbsize_h;
        list($width_orig, $height_orig) = getimagesize($filein);

        if ($width_orig < $height_orig) {
            $height = ($imagethumbsize_w / $width_orig) * $height_orig;
        }
        else {
            $width = ($imagethumbsize_h / $height_orig) * $width_orig;
        }

        //if the width is smaller than supplied thumbnail size
        if ($width < $imagethumbsize_w) {
            $width = $imagethumbsize_w;
            $height = ($imagethumbsize_w/ $width_orig) * $height_orig;
        }

        //if the height is smaller than supplied thumbnail size
        if ($height < $imagethumbsize_h) {
            $height = $imagethumbsize_h;
            $width = ($imagethumbsize_h / $height_orig) * $width_orig;
        }

        // Original, proportionally modified
        $thumb = imagecreatetruecolor($width, $height);
        $bgcolor = imagecolorallocate($thumb, $red, $green, $blue);
        ImageFilledRectangle($thumb, 0, 0, $width, $height, $bgcolor);
        imagealphablending($thumb, true);
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);

        imagedestroy($image); // Free memory

        // Thumbnail
        $thumb2 = imagecreatetruecolor($imagethumbsize_w, $imagethumbsize_h);
        $white = imagecolorallocate($thumb2, 255, 255, 255);
        ImageFilledRectangle($thumb2, 0, 0, $imagethumbsize_w , $imagethumbsize_h , $white);
        imagealphablending($thumb2, true);
        $w1 = ($width/2) - ($imagethumbsize_w/2);
        $h1 = ($height/2) - ($imagethumbsize_h/2);
        imagecopyresampled($thumb2, $thumb, 0, 0, $w1, $h1, $imagethumbsize_w , $imagethumbsize_h ,$imagethumbsize_w, $imagethumbsize_h);

        imagedestroy($thumb); // Free memory

        $func = 'image' . $format;
        $func($thumb2, $fileout);

        imagedestroy($thumb2); // Free memory

    }



    /**
    * Sanitize HTML
    *
    * @param string $html the html to sanitize
    * @param bool $style allow most attributes
    * @return string sanitized html
    */
    static function sanitizeHtml($html, $trusted = false) {

        if ($trusted) {
            // Exclude script and iframe, let the rest pass
            $config = array(
                'elements' => '*-script-iframe',
                );
        }
        else {
            // Safe
            $config = array(
                'safe' => 1,
                'deny_attribute' => 'on*,style,',
                );
        }

        require_once(dirname(__FILE__) . '/symbionts/htmLawed/htmLawed.php');
        return htmLawed($html, $config);

    }


    /**
    * Get available locales on a *nix system
    * @return array list of locales
    */
    static function getLocales(){

        $output = array();
        exec('locale -a', $output);
        return $output;

    }


    /**
    * Set locale in a platform-independent way
    *
    * @param  string $locale  the locale name ('en_US', 'uk_UA', 'fr_FR' etc)
    */
    static function setLocale($locale) {

        @list($lang, $cty) = explode('_', $locale);
        $locales = array("$locale.UTF-8", "$locale.utf8", $lang);
        $result = setlocale(LC_ALL, $locales);

        if(!$result)
            throw new Exception("Unknown Locale name $locale");

        // See if we have successfully set it to UTF-8
        $result = mb_strtolower($result);
        if(!mb_strpos($result, 'utf-8') && !mb_strpos($result, 'utf8'))
            throw new Exception("$locale is not UTF-8: $result");

    }


    /**
    * Canonicalize url
    *
    * @param  string $url
    */
    static function canonicalizeUrl($url) {

        // remove trailing slash
        $url = rtrim(trim($url), '/');

        // Add http:// if it's missing
        if (!preg_match('#^https?://#i', $url)) {
            // Remove ftp://, gopher://, fake://, etc
            if (mb_strpos($url, '://')) list($garbage, $url) = mb_split('://', $url);
            // Prepend http
            $url = 'http://' . $url;
        }

        // protocol and domain to lowercase (but NOT the rest of the URL),
        $scheme = @parse_url($url, PHP_URL_SCHEME);
        $url = preg_replace("/$scheme/", mb_strtolower($scheme), $url, 1);
        $host = @parse_url($url, PHP_URL_HOST);
        $url = preg_replace("/$host/", mb_strtolower($host), $url, 1);

        // Sanitize for good measure
        $url = filter_var($url, FILTER_SANITIZE_URL);

        return $url;

    }


    /**
    * Make url based on $CONFIG['CLEAN'] setting
    *
    * @global string $CONFIG['URL']
    * @global string $CONFIG['CLEAN_URL']
    * @param string $path controller value in /this/style
    * @param array $query http_build_query compatible array
    * @param bool $full return full url?
    * @return string url
    */
    static function makeUrl($path, $query = null, $full = false) {

        // Fix stupidties
        $path = trim($path);
        $path = ltrim($path, '/');
        $path = rtrim($path, '/');

        $tmp = '';
        if ($full) {
            // Autodetect ourself
            $s = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 's' : '';
            $host = $_SERVER['SERVER_NAME'];
            $port = $_SERVER['SERVER_PORT'];
            if (($s && $port == "443") || (!$s && $port == "80") || preg_match("/:$port\$/", $host)) {
                $p = '';
            }
            else {
                $p = ':' . $port;
            }
            $tmp .= "http$s://$host$p";
        }
        $tmp .= $GLOBALS['CONFIG']['URL'];
        $tmp .= ($GLOBALS['CONFIG']['CLEAN_URL'] ? '/' : '/index.php?c=');
        $tmp .= $path;

        if (is_array($query) && count($query)) {
            $q = mb_strpos($tmp, '?') ? '&' : '?';
            $tmp .= $q . http_build_query($query);
        }

        return $tmp;

    }


    /**
    * Get this user's previous URL
    *
    * @param string $preg regular expression
    * @return bool
    */
    static function getPreviousURL($preg) {

        $url = suxFunct::makeUrl('/home'); // Some default

        if (isset($_SESSION['breadcrumbs'])) foreach($_SESSION['breadcrumbs'] as $val) {
            if (!preg_match($preg, $val)) {
                $url = suxFunct::makeUrl($val); // Overwrite
                break;
            }
        }

        return $url;

    }


    /**
    * Get the user's language
    *
    * @global string $CONFIG['PATH']
    * @global string $CONFIG['LANGUAGE']
    * @return array $gtext
    */
    static function gtext($module = null) {

        $gtext = array();

        if (!empty($_SESSION['language'])) $lang = $_SESSION['language'];
        else $lang = $GLOBALS['CONFIG']['LANGUAGE'];

        if ($module) {

            $default = $GLOBALS['CONFIG']['PATH'] . "/modules/{$module}/languages/en.php";
            $requested = $GLOBALS['CONFIG']['PATH'] . "/modules/{$module}/languages/$lang.php";

        }
        else {

            $default = dirname(__FILE__) . "/languages/en.php";
            $requested = dirname(__FILE__) . "/languages/$lang.php";

        }

        if (!is_readable($default)) return false; // no default, something is wrong
        else include($default);

        if ($lang != 'en' && is_readable($requested)) include($requested);

        if (!is_array($gtext) || !count($gtext)) return false; // something is wrong
        else return $gtext;

    }



    /**
    * Get a key => value array of ISO 639 two letter language codes
    *
    * @see http://www.loc.gov/standards/iso639-2/php/code_list.php
    * @return array languages
    */
    static function getLanguages() {

        return array(
            'aa' => 'Afar',
            'ab' => 'Abkhazian',
            'af' => 'Afrikaans',
            'am' => 'Amharic',
            'ar' => 'Arabic',
            'as' => 'Assamese',
            'ay' => 'Aymara',
            'az' => 'Azerbaijani',
            'ba' => 'Bashkir',
            'be' => 'Byelorussian',
            'bg' => 'Bulgarian',
            'bh' => 'Bihari',
            'bi' => 'Bislama',
            'bn' => 'Bengali',
            'bo' => 'Tibetan',
            'br' => 'Breton',
            'ca' => 'Catalan',
            'co' => 'Corsican',
            'cs' => 'Czech',
            'cy' => 'Welsh',
            'da' => 'Danish',
            'de' => 'German',
            'dz' => 'Bhutani',
            'el' => 'Greek',
            'en' => 'English',
            'eo' => 'Esperanto',
            'es' => 'Spanish',
            'et' => 'Estonian',
            'eu' => 'Basque',
            'fa' => 'Persian',
            'fi' => 'Finnish',
            'fj' => 'Fiji',
            'fo' => 'Faeroese',
            'fr' => 'French',
            'fy' => 'Frisian',
            'ga' => 'Irish',
            'gd' => 'Gaelic',
            'gl' => 'Galician',
            'gn' => 'Guarani',
            'gu' => 'Gujarati',
            'ha' => 'Hausa',
            'hi' => 'Hindi',
            'hr' => 'Croatian',
            'hu' => 'Hungarian',
            'hy' => 'Armenian',
            'ia' => 'Interlingua',
            'ie' => 'Interlingue',
            'ik' => 'Inupiak',
            'in' => 'Indonesian',
            'is' => 'Icelandic',
            'it' => 'Italian',
            'iw' => 'Hebrew',
            'ja' => 'Japanese',
            'ji' => 'Yiddish',
            'jw' => 'Javanese',
            'ka' => 'Georgian',
            'kk' => 'Kazakh',
            'kl' => 'Greenlandic',
            'km' => 'Cambodian',
            'kn' => 'Kannada',
            'ko' => 'Korean',
            'ks' => 'Kashmiri',
            'ku' => 'Kurdish',
            'ky' => 'Kirghiz',
            'la' => 'Latin',
            'ln' => 'Lingala',
            'lo' => 'Laothian',
            'lt' => 'Lithuanian',
            'lv' => 'Latvian',
            'mg' => 'Malagasy',
            'mi' => 'Maori',
            'mk' => 'Macedonian',
            'ml' => 'Malayalam',
            'mn' => 'Mongolian',
            'mo' => 'Moldavian',
            'mr' => 'Marathi',
            'ms' => 'Malay',
            'mt' => 'Maltese',
            'my' => 'Burmese',
            'na' => 'Nauru',
            'ne' => 'Nepali',
            'nl' => 'Dutch',
            'no' => 'Norwegian',
            'oc' => 'Occitan',
            'om' => 'Oromo',
            'or' => 'Oriya',
            'pa' => 'Punjabi',
            'pl' => 'Polish',
            'ps' => 'Pashto',
            'pt' => 'Portuguese',
            'qu' => 'Quechua',
            'rm' => 'Rhaeto-Romance',
            'rn' => 'Kirundi',
            'ro' => 'Romanian',
            'ru' => 'Russian',
            'rw' => 'Kinyarwanda',
            'sa' => 'Sanskrit',
            'sd' => 'Sindhi',
            'sg' => 'Sangro',
            'sh' => 'Serbo-Croatian',
            'si' => 'Singhalese',
            'sk' => 'Slovak',
            'sl' => 'Slovenian',
            'sm' => 'Samoan',
            'sn' => 'Shona',
            'so' => 'Somali',
            'sq' => 'Albanian',
            'sr' => 'Serbian',
            'ss' => 'Siswati',
            'st' => 'Sesotho',
            'su' => 'Sudanese',
            'sv' => 'Swedish',
            'sw' => 'Swahili',
            'ta' => 'Tamil',
            'te' => 'Tegulu',
            'tg' => 'Tajik',
            'th' => 'Thai',
            'ti' => 'Tigrinya',
            'tk' => 'Turkmen',
            'tl' => 'Tagalog',
            'tn' => 'Setswana',
            'to' => 'Tonga',
            'tr' => 'Turkish',
            'ts' => 'Tsonga',
            'tt' => 'Tatar',
            'tw' => 'Twi',
            'uk' => 'Ukrainian',
            'ur' => 'Urdu',
            'uz' => 'Uzbek',
            'vi' => 'Vietnamese',
            'vo' => 'Volapuk',
            'wo' => 'Wolof',
            'xh' => 'Xhosa',
            'yo' => 'Yoruba',
            'zh' => 'Chinese',
            'zu' => 'Zulu',
            );

    }



    /**
    * Get a key => value array of ISO 3166-1 two letter country codes
    *
    * @see http://www.iso.org/iso/list-en1-semic-2.txt
    * @return array languages
    */
    static function getCountries() {

        return array(
            'af' => 'Afghanistan',
            'ax' => 'Ãland Islands',
            'al' => 'Albania',
            'dz' => 'Algeria',
            'as' => 'American Samoa',
            'ad' => 'Andorra',
            'ao' => 'Angola',
            'ai' => 'Anguilla',
            'aq' => 'Antarctica',
            'ag' => 'Antigua and Barbuda',
            'ar' => 'Argentina',
            'am' => 'Armenia',
            'aw' => 'Aruba',
            'au' => 'Australia',
            'at' => 'Austria',
            'az' => 'Azerbaijan',
            'bs' => 'Bahamas',
            'bh' => 'Bahrain',
            'bd' => 'Bangladesh',
            'bb' => 'Barbados',
            'by' => 'Belarus',
            'be' => 'Belgium',
            'bz' => 'Belize',
            'bj' => 'Benin',
            'bm' => 'Bermuda',
            'bt' => 'Bhutan',
            'bo' => 'Bolivia',
            'ba' => 'Bosnia and Herzegovina',
            'bw' => 'Botswana',
            'bv' => 'Bouvet Island',
            'br' => 'Brazil',
            'io' => 'British Indian Ocean Territory',
            'bn' => 'Brunei Darussalam',
            'bg' => 'Bulgaria',
            'bf' => 'Burkina Faso',
            'bi' => 'Burundi',
            'kh' => 'Cambodia',
            'cm' => 'Cameroon',
            'ca' => 'Canada',
            'cv' => 'Cape Verde',
            'ky' => 'Cayman Islands',
            'cf' => 'Central African Republic',
            'td' => 'Chad',
            'cl' => 'Chile',
            'cn' => 'China',
            'cx' => 'Christmas Island',
            'cc' => 'Cocos (Keeling) Islands',
            'co' => 'Colombia',
            'km' => 'Comoros',
            'cg' => 'Congo',
            'cd' => 'Congo, the Democratic Republic of the',
            'ck' => 'Cook Islands',
            'cr' => 'Costa Rica',
            'ci' => 'CÃ´te d\'Ivoire',
            'hr' => 'Croatia',
            'cu' => 'Cuba',
            'cy' => 'Cyprus',
            'cz' => 'Czech Republic',
            'dk' => 'Denmark',
            'dj' => 'Djibouti',
            'dm' => 'Dominica',
            'do' => 'Dominican Republic',
            'ec' => 'Ecuador',
            'eg' => 'Egypt',
            'sv' => 'El Salvador',
            'gq' => 'Equatorial Guinea',
            'er' => 'Eritrea',
            'ee' => 'Estonia',
            'et' => 'Ethiopia',
            'fk' => 'Falkland Islands (Malvinas)',
            'fo' => 'Faroe Islands',
            'fj' => 'Fiji',
            'fi' => 'Finland',
            'fr' => 'France',
            'gf' => 'French Guiana',
            'pf' => 'French Polynesia',
            'tf' => 'French Southern Territories',
            'ga' => 'Gabon',
            'gm' => 'Gambia',
            'ge' => 'Georgia',
            'de' => 'Germany',
            'gh' => 'Ghana',
            'gi' => 'Gibraltar',
            'gr' => 'Greece',
            'gl' => 'Greenland',
            'gd' => 'Grenada',
            'gp' => 'Guadeloupe',
            'gu' => 'Guam',
            'gt' => 'Guatemala',
            'gg' => 'Guernsey',
            'gn' => 'Guinea',
            'gw' => 'Guinea-Bissau',
            'gy' => 'Guyana',
            'ht' => 'Haiti',
            'hm' => 'Heard Island and Mcdonald Islands',
            'va' => 'Holy See (Vatican City State)',
            'hn' => 'Honduras',
            'hk' => 'Hong Kong',
            'hu' => 'Hungary',
            'is' => 'Iceland',
            'in' => 'India',
            'id' => 'Indonesia',
            'ir' => 'Iran',
            'iq' => 'Iraq',
            'ie' => 'Ireland',
            'im' => 'Isle of Man',
            'il' => 'Israel',
            'it' => 'Italy',
            'jm' => 'Jamaica',
            'jp' => 'Japan',
            'je' => 'Jersey',
            'jo' => 'Jordan',
            'kz' => 'Kazakhstan',
            'ke' => 'Kenya',
            'ki' => 'Kiribati',
            'kp' => 'Korea, Democratic People\'s Republic of (North)',
            'kr' => 'Korea, Republic of (South)',
            'kw' => 'Kuwait',
            'kg' => 'Kyrgyzstan',
            'la' => 'Lao People\'s Democratic Republic',
            'lv' => 'Latvia',
            'lb' => 'Lebanon',
            'ls' => 'Lesotho',
            'lr' => 'Liberia',
            'ly' => 'Libyan arab Jamahiriya',
            'li' => 'Liechtenstein',
            'lt' => 'Lithuania',
            'lu' => 'Luxembourg',
            'mo' => 'Macao',
            'mk' => 'Macedonia, the Former Yugoslav Republic of',
            'mg' => 'Madagascar',
            'mw' => 'Malawi',
            'my' => 'Malaysia',
            'mv' => 'Maldives',
            'ml' => 'Mali',
            'mt' => 'Malta',
            'mh' => 'Marshall islands',
            'mq' => 'Martinique',
            'mr' => 'Mauritania',
            'mu' => 'Mauritius',
            'yt' => 'Mayotte',
            'mx' => 'Mexico',
            'fm' => 'Micronesia, Federated States of',
            'md' => 'Moldova, Republic of',
            'mc' => 'Monaco',
            'mn' => 'Mongolia',
            'me' => 'Montenegro',
            'ms' => 'Montserrat',
            'ma' => 'Morocco',
            'mz' => 'Mozambique',
            'mm' => 'Myanmar',
            'na' => 'Namibia',
            'nr' => 'Nauru',
            'np' => 'Nepal',
            'nl' => 'Netherlands',
            'an' => 'Netherlands Antilles',
            'nc' => 'New Caledonia',
            'nz' => 'New Zealand',
            'ni' => 'Nicaragua',
            'ne' => 'Niger',
            'ng' => 'Nigeria',
            'nu' => 'Niue',
            'nf' => 'Norfolk Island',
            'mp' => 'Northern Mariana Islands',
            'no' => 'Norway',
            'om' => 'Oman',
            'pk' => 'Pakistan',
            'pw' => 'Palau',
            'ps' => 'Palestinian Territory',
            'pa' => 'Panama',
            'pg' => 'Papua New Guinea',
            'py' => 'Paraguay',
            'pe' => 'Peru',
            'ph' => 'Philippines',
            'pn' => 'Pitcairn',
            'pl' => 'Poland',
            'pt' => 'Portugal',
            'pr' => 'Puerto Rico',
            'qa' => 'Qatar',
            're' => 'Reunion',
            'ro' => 'Romania',
            'ru' => 'Russian Federation',
            'rw' => 'Rwanda',
            'bl' => 'Saint BarthÃ©lemy',
            'sh' => 'Saint Helena',
            'kn' => 'Saint Kitts and Nevis',
            'lc' => 'Saint Lucia',
            'mf' => 'Saint Martin',
            'pm' => 'Saint Pierre and Miquelon',
            'vc' => 'Saint Vincent and the Grenadines',
            'ws' => 'Samoa',
            'sm' => 'San Marino',
            'st' => 'Sao Tome and Principe',
            'sa' => 'Saudi Arabia',
            'sn' => 'Senegal',
            'rs' => 'Serbia',
            'sc' => 'Seychelles',
            'sl' => 'Sierra Leone',
            'sg' => 'Singapore',
            'sk' => 'Slovakia',
            'si' => 'Slovenia',
            'sb' => 'Solomon Islands',
            'so' => 'Somalia',
            'za' => 'South Africa',
            'gs' => 'South Georgia and the South Sandwich Islands',
            'es' => 'Spain',
            'lk' => 'Sri Lanka',
            'sd' => 'Sudan',
            'sr' => 'Suriname',
            'sj' => 'Svalbard and Jan Mayen',
            'sz' => 'Swaziland',
            'se' => 'Sweden',
            'ch' => 'Switzerland',
            'sy' => 'Syrian Arab Republic',
            'tw' => 'Taiwan',
            'tj' => 'Tajikistan',
            'tz' => 'Tanzania, United Republic of',
            'th' => 'Thailand',
            'tl' => 'Timor-Leste',
            'tg' => 'Togo',
            'tk' => 'Tokelau',
            'to' => 'Tonga',
            'tt' => 'Trinidad and Tobago',
            'tn' => 'Tunisia',
            'tr' => 'Turkey',
            'tm' => 'Turkmenistan',
            'tc' => 'Turks and Caicos Islands',
            'tv' => 'Tuvalu',
            'ug' => 'Uganda',
            'ua' => 'Ukraine',
            'ae' => 'United Arab Emirates',
            'gb' => 'United Kingdom',
            'us' => 'United States',
            'um' => 'United States Minor Outlying Islands',
            'uy' => 'Uruguay',
            'uz' => 'Uzbekistan',
            'vu' => 'Vanuatu',
            've' => 'Venezuela',
            'vn' => 'Viet Nam',
            'vg' => 'Virgin Islands, British',
            'vi' => 'Virgin Islands, U.S.',
            'wf' => 'Wallis and futuna',
            'eh' => 'Western Sahara',
            'ye' => 'Yemen',
            'zm' => 'Zambia',
            'zw' => 'Zimbabwe',
            );

    }



}


?>