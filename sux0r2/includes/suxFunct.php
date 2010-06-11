<?php

/**
* suxFunct
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class suxFunct {

    // Stopwords cache
    private static $stopwords;

    // Static class, no cloning or instantiating allowed
    final private function __construct() { }
    final private function __clone() { }


    // ------------------------------------------------------------------------
    // Get content
    // ------------------------------------------------------------------------


    /**
    * Get module menu
    *
    * @param string $module the name of a module
    * @return array|bool suxRenderer::navlist() compatible data structue
    */
    static function getModuleMenu($module) {

        $path_to_menu = dirname(__FILE__) . "/../modules/{$module}/menu.php";

        if (is_file($path_to_menu)) {
            include_once($path_to_menu);
            $funct = "{$module}_menu";
            if (function_exists($funct)) return $funct();
        }

        return false;

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


    // ------------------------------------------------------------------------
    // Miscelaneous
    // ------------------------------------------------------------------------

    /**
    * Sanitize HTML
    *
    * @param string $html the html to sanitize
    * @param int $trusted -1, 0, or 1
    * @return string sanitized html
    */
    static function sanitizeHtml($html, $trusted = -1) {

        if ($trusted > 0) {
            // Allow all (*) except -script and -iframe
            $config = array(
                'elements' => '*-script-iframe',
                );
        }
        elseif ($trusted < 0) {
            // Paranoid mode, i.e. only allow a small subset of elements to pass
            // Transform strike and u to span for better XHTML 1-strict compliance
            $config = array(
                'elements' => 'a,em,strike,strong,u,p,br,img,li,ol,ul',
                'make_tag_strict' => 1,
                'safe' => 1,
                );
        }
        else {
            // Safe
            $config = array(
                'safe' => 1,
                'deny_attribute' => 'style,class',
                );
        }

        require_once(dirname(__FILE__) . '/symbionts/htmLawed/htmLawed.php');
        return htmLawed($html, $config);

    }


    /**
    * Return data directory
    *
    * @global string $CONFIG['PATH']
    * @param string $module
    * @return string
    */
    static function dataDir($module) {

        $data_dir = $GLOBALS['CONFIG']['PATH'] . "/data/$module";
        if(!is_dir($data_dir) && !mkdir($data_dir, 0777, true)) {
            throw new Exception('Missing data dir ' . $data_dir);
        }

        return $data_dir;

    }


    /**
    * Unzip
    *
    * @param string $file
    * @param string $dir
    * @return bool
    */
    static function unzip($file, $dir) {

        if (class_exists('ZipArchive')) {

            $zip = new ZipArchive();
            if ($zip->open($file) === true) {
                $_ret = $zip->extractTo($dir);
                $zip->close();
                return $_ret;
            }
            else return false;

        }
        else {

            // Escape
            $file = escapeshellarg($file);
            $dir = escapeshellarg($dir);

            $cmd = "unzip {$file} -d {$dir}"; // Info-zip assumed to be in path

            $res = -1; // any nonzero value
            $unused = array();
            $unused2 = exec($cmd, $unused, $res);
            if ($res != 0) trigger_error("Warning: unzip return value is $res ", E_USER_WARNING);

            return ($res == 0 || $res == 1); // http://www.info-zip.org/FAQ.html#error-codes

        }

    }



    /**
    * Remove directory
    *
    * @param string $dirname
    * @param bool $empty
    * @return bool
    */
    static function obliterateDir($dirname) {

        if (!is_dir($dirname)) return false;

        if (isset($_ENV['OS']) && strripos($_ENV['OS'], "windows", 0) !== FALSE) {

            // Windows patch for buggy perimssions on some machines
            $command = 'cmd /C "rmdir /S /Q "'.str_replace('//', '\\', $dirname).'\\""';
            $wsh = new COM("WScript.Shell");
            $wsh->Run($command, 7, false);
            $wsh = null;
            return true;

        }
        else {

            $dscan = array(realpath($dirname));
            $darr = array();
            while (!empty($dscan)) {
                $dcur = array_pop($dscan);
                $darr[] = $dcur;
                if ($d = opendir($dcur)) {
                    while ($f=readdir($d)) {
                        if ($f == '.' || $f == '..') continue;
                        $f = $dcur . '/' . $f;
                        if (is_dir($f)) $dscan[] = $f;
                        else unlink($f);
                    }
                    closedir($d);
                }
            }

            for ($i=count($darr)-1; $i >= 0 ; $i--) {
                if (!rmdir($darr[$i]))
                    trigger_error("Warning: There was a problem deleting a temporary file in $dirname ", E_USER_WARNING);
            }

            return (!is_dir($dirname));

        }

    }


    // ------------------------------------------------------------------------
    // Dates and times
    // ------------------------------------------------------------------------


    /**
    * Get the last day of a month
    *
    * @return string YYYY-MM-DD
    */
    static function lastDay($month = '', $year = '') {

        if (empty($month)) $month = date('m');
        if (empty($year)) $year = date('Y');
        $result = strtotime("{$year}-{$month}-01");
        $result = strtotime('-1 second', strtotime('+1 month', $result));
        return date('Y-m-d', $result);

    }


    // ------------------------------------------------------------------------
    // URLs
    // ------------------------------------------------------------------------


    /**
    * Redirection
    *
    * @param string $href a uniform resource locator (URL)
    */
    static function redirect($href) {

        $href = filter_var($href, FILTER_SANITIZE_URL);

        if (!isset($_SESSION['birdfeed'])) $_SESSION['birdfeed'] = 0;
        if ($_SESSION['birdfeed'] > 3) $href = suxFunct::makeUrl('/home'); // Avoid infinite redirects
        ++$_SESSION['birdfeed'];

        if (!headers_sent()) {
            header("Location: $href");
        }
        else {
            // Javascript hack
            echo "
            <script type='text/javascript'>
            // <![CDATA[
            window.location = '{$href}';
            // ]]>
            </script>
            ";
        }

        exit; // Quit script
    }


    /**
    * Get the server url
    *
    * @return string http server url
    */
    static function myHttpServer() {

        // Autodetect ourself
        $s = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off' ? 's' : '';
        $host = $_SERVER['SERVER_NAME'];
        $port = $_SERVER['SERVER_PORT'];
        if (($s && $port == "443") || (!$s && $port == "80") || preg_match("/:$port\$/", $host)) {
            $p = '';
        }
        else {
            $p = ':' . $port;
        }

        return "http$s://$host$p";

    }


    /**
    * Make URL based on $CONFIG['CLEAN'] setting
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
        if ($full)  $tmp .= self::myHttpServer();
        $tmp .= $GLOBALS['CONFIG']['URL'];
        $tmp .= ($GLOBALS['CONFIG']['CLEAN_URL'] ? '/' : '/index.php?c=');
        $tmp .= $path;
        $tmp = rtrim($tmp, '/'); // In case path is null

        if (is_array($query) && count($query)) {
            $q = mb_strpos($tmp, '?') ? '&' : '?';
            $tmp .= $q . http_build_query($query);
        }

        return $tmp;

    }


    /**
    * Get this user's previous URL
    *
    * @param string|array $skip
    * @return bool
    */
    static function getPreviousURL($skip = null) {

        if ($skip == null) $skip = $GLOBALS['CONFIG']['PREV_SKIP'];
        elseif (!is_array($skip)) $skip = array($skip);

        // Sanitize and transform array into regular expressions
        foreach ($skip as $key => $val) {
            $val = str_replace('#', '', $val);
            $val = trim($val);
            $val = trim($val, '/');
            $skip[$key] = "#^{$val}#i"; // regex
        }

        if (isset($_SESSION['breadcrumbs'])) foreach($_SESSION['breadcrumbs'] as $val) {

            $ok = true;
            foreach ($skip as $val2) {
                if (preg_match($val2, $val)) $ok = false;
            }
            if ($ok) return suxFunct::makeUrl($val);

        }

        return suxFunct::makeUrl('/home'); // Some default;

    }


    /**
    * Canonicalize URL
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
            if (preg_match('#^http:///#', $url)) {
                return null; // This is wrong...
            }
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

    // ------------------------------------------------------------------------
    // Languages and locales
    // ------------------------------------------------------------------------


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
    * Get the user's language
    *
    * @global string $CONFIG['PARTITION']
    * @global string $CONFIG['LANGUAGE']
    * @global string $CONFIG['PATH']
    * @param string $module
    * @return array $gtext
    */
    static function gtext($module = 'globals') {

        // Cache
        static $gtext_cache = array();
        if (isset($gtext_cache[$module])) return $gtext_cache[$module];

        $gtext = array();

        $partition = $GLOBALS['CONFIG']['PARTITION'];

        if (!empty($_SESSION['language'])) $lang = $_SESSION['language'];
        else $lang = $GLOBALS['CONFIG']['LANGUAGE'];

        if ($module) {
            $default = $GLOBALS['CONFIG']['PATH'] . "/templates/sux0r/{$module}/languages/en.php";
            $requested = $GLOBALS['CONFIG']['PATH'] . "/templates/{$partition}/{$module}/languages/{$lang}.php";
        }

        if (!is_readable($default)) return false; // no default, something is wrong
        else include($default);

        if (is_readable($requested)) include($requested);

        if (!is_array($gtext) || !count($gtext)) return false; // something is wrong
        else {
            $gtext_cache[$module] = $gtext;
            return $gtext;
        }

    }


    /**
    * Parse a string and turn it into an array of valid tokens
    *
    * @param string $string
    * @param bool $use_stopwords
    * @return array
    */
    static function parseTokens($string, $use_stopwords = true, $count = false) {

        // \w means alphanumeric characters.
        // Usually, non-English letters and numbers are included.
        // \W is the negated version of \w
        //
        // TODO: We're splitting on "anything that isn't a word" which is good
        // for languages with punctuation and spaces. But what about Chinese,
        // Japanese, and other languages that don't use them? How do we
        // identify tokens in those cases?

        $string = mb_strtolower($string);
        $rawtokens = mb_split("\W", $string);
        if (!count($rawtokens)) return array();

        if ($use_stopwords && !is_array(self::$stopwords)) {
            //. Get stopwords
            self::$stopwords = array();
            $dir = dirname(__FILE__) . '/symbionts/stopwords';
            foreach (new DirectoryIterator($dir) as $file) {
                if (preg_match('/^[a-z]{2}\.txt$/', $file)) {
                    self::$stopwords = array_merge(self::$stopwords, file("{$dir}/{$file}", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
                }
            }
            // Add generic internet cruft for good measure
            self::$stopwords = array_merge(self::$stopwords, array('http', 'https', 'mailto', 'www', 'com', 'net', 'org', 'biz', 'info'));

            // array_flip removes duplicates and increase speed by using isset() instead of in_array()
            self::$stopwords = array_flip(self::$stopwords);

        }

        // IMPORTANT:
        // If you change the number 64 below, you need to adjust
        // suxNaiveBayesian() and the corresponding token DB column accordingly

        $tokens = array();
        foreach ($rawtokens as $val) {
            if (!(
                empty($val) ||
                (mb_strlen($val) < 3) ||
                (mb_strlen($val) > 64) ||
                ctype_digit($val) ||
                (isset(self::$stopwords[$val]))
                )) {
                    if ($count) @$tokens[$val]++;
                    else $tokens[] = $val;
                }
        }

        return $tokens;

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
            'ax' => 'Åland Islands',
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
            'ci' => 'Côte d\'Ivoire',
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
            'bl' => 'Saint Barthélemy',
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