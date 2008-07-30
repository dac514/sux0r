<?php

/**
* suxPhoto
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
*/

class suxPhoto {

    // Database stuff
    protected $db;
    protected $inTransaction = false;
    protected $db_photos = 'photos';
    protected $db_albums = 'photoalbums';
    protected $db_driver; // database type


    /**
    * Constructor
    */
    function __construct() {

    	$this->db = suxDB::get();
        $this->db_driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        set_exception_handler(array($this, 'exceptionHandler'));

    }

    // ------------------------------------------------------------------------
    // Photoalbum functions
    // ------------------------------------------------------------------------

    /**
    * Get an album by id
    *
    * @param int $id photoalbums id
    * @param bool $unpub select un-published?
    * @return array|false
    */
    function getAlbum($id, $unpub = false) {

        // Sanity check
        if (!filter_var($id, FILTER_VALIDATE_INT)) throw new Exception('Invalid album id');

        $query = "SELECT * FROM {$this->db_albums} WHERE id = ? ";
        if (!$unpub) {
            // Only show published items
            $query .= "AND draft = 0 ";
            if ($this->db_driver == 'mysql') {
                // MySql
                $query .= "AND NOT published_on > '" . date('Y-m-d H:i:s') . "' ";
            }
            else {
                throw new Exception('Unsupported database driver');
            }
        }
        $query .= 'LIMIT 1 ';


        $st = $this->db->prepare($query);
        $st->execute(array($id));

        $album = $st->fetch(PDO::FETCH_ASSOC);
        if ($album) return $album;
        else return false;


    }


    /**
    * Get an album by id
    *
    * @param int $id photoalbums id
    * @param int $limit sql limit value
    * @param int $start sql start of limit value
    * @param bool $unpub select un-published?
    * @return array|false
    */
    function getAlbums($users_id = null, $limit = null, $start = 0, $unpub = false) {

        // Sanity check
        if ($users_id && !filter_var($users_id, FILTER_VALIDATE_INT)) throw new Exception('Invalid users id');

        $query = "SELECT * FROM {$this->db_albums} ";
        if ($users_id) $query .= 'WHERE users_id = ? ';

        if (!$unpub) {
            // Only show published items
            $query .= $users_id ? 'AND ' : 'WHERE ';
            $query .= 'draft = 0 ';
            if ($this->db_driver == 'mysql') {
                // MySql
                $query .= "AND NOT published_on > '" . date('Y-m-d H:i:s') . "' ";
            }
            else {
                throw new Exception('Unsupported database driver');
            }
        }
        $query .= 'ORDER BY published_on DESC ';

        // Limit
        if ($start && $limit) $query .= "LIMIT {$start}, {$limit} ";
        elseif ($limit) $query .= "LIMIT {$limit} ";

        $st = $this->db->prepare($query);
        $st->execute($users_id ? array($users_id) : array());

        $album = $st->fetchAll(PDO::FETCH_ASSOC);
        if ($album) return $album;
        else return false;


    }


    /**
    * Count albums
    *
    * @param int $id photoalbums id
    * @param bool $unpub select un-published?
    * @return array|false
    */
    function countAlbums($users_id = null, $unpub = false) {

        // Sanity check
        if ($users_id && !filter_var($users_id, FILTER_VALIDATE_INT)) throw new Exception('Invalid users id');

        $query = "SELECT COUNT(*) FROM {$this->db_albums} ";
        if ($users_id) $query .= 'WHERE users_id = ? ';

        if (!$unpub) {
            // Only show published items
            $query .= $users_id ? 'AND ' : 'WHERE ';
            $query .= 'draft = 0 ';
            if ($this->db_driver == 'mysql') {
                // MySql
                $query .= "AND NOT published_on > '" . date('Y-m-d H:i:s') . "' ";
            }
            else {
                throw new Exception('Unsupported database driver');
            }
        }

        $st = $this->db->prepare($query);
        $st->execute($users_id ? array($users_id) : array());
        return $st->fetchColumn();

    }


    /**
    * Saves an album to the database
    *
    * @param int $users_id users_id
    * @param array $album required keys => (url, title, body) optional keys => (draft)
    * @param int $trusted passed on to sanitizeHtml()
    * @return int insert id
    */
    function saveAlbum($users_id, array $album, $trusted = -1) {

        // -------------------------------------------------------------------
        // Sanitize
        // -------------------------------------------------------------------

        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id <= 0) throw new Exception('Invalid user id');
        if (!isset($album['title']) || !isset($album['body'])) throw new Exception('Invalid $album array');

        // Album id
        if (isset($album['id'])) {
            if (!filter_var($album['id'], FILTER_VALIDATE_INT) || $album['id'] <= 0) throw new Exception('Invalid album id');
            else $clean['id'] = $album['id'];
        }

        // Users id
        $clean['users_id'] = $users_id;

        // No HTML in title
        $clean['title'] = strip_tags($album['title']);

        // Sanitize HTML in body
        $clean['body_html'] = suxFunct::sanitizeHtml($album['body'], $trusted);

        // Convert and copy body to UTF-8 plaintext
        require_once(dirname(__FILE__) . '/suxHtml2UTF8.php');
        $converter = new suxHtml2UTF8($clean['body_html']);
        $clean['body_plaintext']  = $converter->getText();

        // Draft, boolean / tinyint
        $clean['draft'] = 0;
        if (isset($album['draft'])) $clean['draft'] = 1;

        // Publish date
        if (isset($album['published_on'])) {
            // ISO 8601 date format
            // regex must match '2008-06-18 16:53:29' or '2008-06-18T16:53:29-04:00'
            $regex = '/^(\d{4})-(0[0-9]|1[0,1,2])-([0,1,2][0-9]|3[0,1]).+(\d{2}):(\d{2}):(\d{2})/';
            if (!preg_match($regex, $album['published_on'])) throw new Exception('Invalid date');
            $clean['published_on'] = $album['published_on'];
        }
        else $clean['published_on'] = date('c');


        // We now have the $clean[] array

        // --------------------------------------------------------------------
        // Go!
        // --------------------------------------------------------------------


        if (isset($clean['id'])) {

            // UPDATE
            $query = suxDB::prepareUpdateQuery($this->db_albums, $clean);
            $st = $this->db->prepare($query);
            $st->execute($clean);
            $id = $clean['id'];

        }
        else {

            // INSERT
            $query = suxDB::prepareInsertQuery($this->db_albums, $clean);
            $st = $this->db->prepare($query);
            $st->execute($clean);
            $id = $this->db->lastInsertId();

        }

        return $id;

    }


    // ------------------------------------------------------------------------
    // Photo functions
    // ------------------------------------------------------------------------

    /**
    * Get a photo by id
    *
    * @param int $id photoalbums id
    * @return array|false
    */
    function getPhoto($id) {

        // Sanity check
        if (!filter_var($id, FILTER_VALIDATE_INT)) throw new Exception('Invalid photo id');

        $query = "SELECT * FROM {$this->db_photos} WHERE id = ? LIMIT 1 ";
        $st = $this->db->prepare($query);
        $st->execute(array($id));

        $album = $st->fetch(PDO::FETCH_ASSOC);
        if ($album) return $album;
        else return false;

    }


    /**
    * Get photos by photoalbums_id
    *
    * @param int $photoalbums_id photoalbums id
    * @return array|false
    */
    function getPhotos($photoalbums_id) {

        // Sanity check
        if (!filter_var($photoalbums_id, FILTER_VALIDATE_INT)) throw new Exception('Invalid photoalbums id');

        $query = "SELECT * FROM {$this->db_photos} WHERE photoalbums_id = ? ORDER BY image ";

        $st = $this->db->prepare($query);
        $st->execute(array($photoalbums_id));

        $album = $st->fetchAll(PDO::FETCH_ASSOC);
        if ($album) return $album;
        else return false;


    }


    /**
    * Get photos by users_id
    *
    * @param int $users_id photoalbums id
    * @return array|false
    */
    function getPhotosByUser($users_id) {

        // Sanity check
        if (!filter_var($users_id, FILTER_VALIDATE_INT)) throw new Exception('Invalid users id');

        $query = "SELECT * FROM {$this->db_photos} WHERE users_id = ? ORDER BY photoalbums_id, image ";

        $st = $this->db->prepare($query);
        $st->execute(array($users_id));

        $album = $st->fetchAll(PDO::FETCH_ASSOC);
        if ($album) return $album;
        else return false;


    }


    /**
    * Count photos by photoalbums_id
    *
    * @param int $photoalbums_id photoalbums id
    * @return array|false
    */
    function countPhotos($photoalbums_id) {

        // Sanity check
        if (!filter_var($photoalbums_id, FILTER_VALIDATE_INT)) throw new Exception('Invalid photoalbums id');

        $query = "SELECT COUNT(*) FROM {$this->db_photos} WHERE photoalbums_id = ? ";

        $st = $this->db->prepare($query);
        $st->execute(array($photoalbums_id));
        return $st->fetchColumn();


    }


    /**
    * Check if the photo is already in this album
    *
    * @param string $md5
    * @param int $users_id
    * @param int $photoalbums_id
    * @return bool
    */
    function isDupe($md5, $users_id, $photoalbums_id) {

        $q = "SELECT COUNT(*) FROM {$this->db_photos} WHERE md5 = ? AND users_id = ? AND photoalbums_id = ? LIMIT 1 ";
        $st = $this->db->prepare($q);
        $st->execute(array($md5, $users_id, $photoalbums_id));
        if ($st->fetchColumn() <= 0) return false;
        else return true;

    }


    /**
    * Saves a photo to the database
    *
    * @param int $users_id users_id
    * @param array $album required keys => (photoalbums_id, image, md5) optional keys => (body)
    * @param int $trusted passed on to sanitizeHtml()
    * @return int insert id
    */
    function savePhoto($users_id, array $photo, $trusted = -1) {

        // -------------------------------------------------------------------
        // Sanitize
        // -------------------------------------------------------------------

        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id <= 0) throw new Exception('Invalid user id');
        if (!isset($photo['photoalbums_id']) || !isset($photo['image']) || !isset($photo['md5'])) throw new Exception('Invalid $photo array');

        // photo id
        if (isset($photo['id'])) {
            if (!filter_var($photo['id'], FILTER_VALIDATE_INT) || $photo['id'] <= 0) throw new Exception('Invalid photo id');
            else $clean['id'] = $photo['id'];
        }

        // Begin collecting $clean array
        $clean['users_id'] = $users_id;
        $clean['image'] = $photo['image'];
        $clean['photoalbums_id'] = $photo['photoalbums_id'];
        $clean['md5'] = $photo['md5'];

        // Set an empty string if empty
        if (!isset($album['body'])) $album['body'] = '';

        // Sanitize HTML in body
        $clean['body_html'] = suxFunct::sanitizeHtml($album['body'], $trusted);

        // Convert and copy body to UTF-8 plaintext
        require_once(dirname(__FILE__) . '/suxHtml2UTF8.php');
        $converter = new suxHtml2UTF8($clean['body_html']);
        $clean['body_plaintext']  = $converter->getText();


        // We now have the $clean[] array

        // --------------------------------------------------------------------
        // Go!
        // --------------------------------------------------------------------


        if (isset($clean['id'])) {

            // UPDATE
            $query = suxDB::prepareUpdateQuery($this->db_photos, $clean);
            $st = $this->db->prepare($query);
            $st->execute($clean);
            $id = $clean['id'];

        }
        else {

            // INSERT
            $query = suxDB::prepareInsertQuery($this->db_photos, $clean);
            $st = $this->db->prepare($query);
            $st->execute($clean);
            $id = $this->db->lastInsertId();

        }

        return $id;

    }



    // ------------------------------------------------------------------------
    // Image Functions
    // ------------------------------------------------------------------------

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
    * Convert thumbail filename to fullsize filename
    * i.e. filename_12345.jpg to filename_12345_fullsize.jpg
    *
    * @param string $name the name of an image file
    * @return string
    */
    static function t2fImage($filename) {

        $pattern = '/(\.jpe?g|\.gif|\.png)$/i';
        $replacement = '_fullsize' . "$1";
        $fullsize = preg_replace($pattern, $replacement, $filename);
        return $fullsize;

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

        /* Try to avoid problems with memory limit */

        $size = getimagesize($filein);

        if ($format == 'jpeg') {
            // Jpeg
            $fudge = 1.65; // This is a guestimate, your mileage may very
            $memoryNeeded = round(($size[0] * $size[1] * $size['bits'] * $size['channels'] / 8 + Pow(2, 16)) * $fudge);
        }
        else {
            // Not Sure
            $memoryNeeded = $size[0] * $size[1];
            if (isset($size['bits'])) $memoryNeeded = $memoryNeeded * $size['bits'];
            $memoryNeeded = round($memoryNeeded);
        }

        if (memory_get_usage() + $memoryNeeded > (int) ini_get('memory_limit') * pow(1024, 2)) {
            trigger_error('Image is too big, attempting to compensate...', E_USER_WARNING);
            ini_set('memory_limit', (int) ini_get('memory_limit') + ceil(((memory_get_usage() + $memoryNeeded) - (int) ini_get('memory_limit') * pow(1024, 2)) / pow(1024, 2)) . 'M');
        }

        /* Proceed with resizing */

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


    // ----------------------------------------------------------------------------
    // Exception Handler
    // ----------------------------------------------------------------------------


    /**
    * @param Exception $e an Exception class
    */
    function exceptionHandler(Exception $e) {

        if ($this->db && $this->inTransaction) {
            $this->db->rollback();
            $this->inTransaction = false;
        }

        throw($e); // Hot potato!

    }


}

?>