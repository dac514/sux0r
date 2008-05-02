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

    // No cloning or instantiating allowed
    final private function __construct() { }
    final private function __clone() { }

    // ------------------------------------------------------------------------
    // Static Functions
    // ------------------------------------------------------------------------

    /**
    * Perform one-way encryption of a password
    *
    * @param string the username
    * @param string the password to encrypt
    * @return string
    */
    static function encryptPw($nickname, $password) {

        if (!isset($GLOBALS['CONFIG']['REALM'])) {
            die("Something is wrong, can't encrypt password without realm.");
        }
        return md5("{$nickname}:{$GLOBALS['CONFIG']['REALM']}:{$password}");

    }


    /**
    * Generate a random password
    *
    * @return string
    */
    static function generatePw() {

        $new_pw = '';
        for ($i = 0; $i < 8; $i++) {
            $new_pw .= chr(mt_rand(33, 126));
        }
        return $new_pw;

    }


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

        // Thumbnail
        $thumb2 = imagecreatetruecolor($imagethumbsize_w, $imagethumbsize_h);
        $white = imagecolorallocate($thumb2, 255, 255, 255);
        ImageFilledRectangle($thumb2, 0, 0, $imagethumbsize_w , $imagethumbsize_h , $white);
        imagealphablending($thumb2, true);
        $w1 = ($width/2) - ($imagethumbsize_w/2);
        $h1 = ($height/2) - ($imagethumbsize_h/2);
        imagecopyresampled($thumb2, $thumb, 0, 0, $w1, $h1, $imagethumbsize_w , $imagethumbsize_h ,$imagethumbsize_w, $imagethumbsize_h);

        $func = 'image' . $format;
        $func($thumb2, $fileout);

    }



    /**
    * Sanitize HTML
    *
    * @param string $html the html to sanitize
    * @return string sanitized html
    */
    static function sanitizeHtml($html) {

        $config = array(
            'safe' => 1,
            'deny_attribute' => 'on*, style',
            );

        include_once(dirname(__FILE__) . '/symbionts/htmLawed/htmLawed.php');
        return htmLawed($html, $config);

    }


}

?>