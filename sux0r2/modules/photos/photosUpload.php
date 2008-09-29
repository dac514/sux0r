<?php

/**
* photosUpload
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

require_once(dirname(__FILE__) . '/../../includes/suxPhoto.php');
require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once(dirname(__FILE__) . '/../../includes/suxValidate.php');
require_once('photosRenderer.php');

class photosUpload  {

    // Variables
    public $gtext = array();
    private $extensions = 'jpg,jpeg,gif,png,zip'; // Supported extensions
    private $module = 'photos';


    // Objects
    public $tpl;
    public $r;
    private $user;
    private $photo;

    /**
    * Constructor
    *
    */
    function __construct($id = null) {

        if ($id) {
            if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1)
                suxFunct::redirect(suxFunct::makeURL('/photos')); // Invalid id
        }

        $this->user = new suxUser(); // User
        $this->photo = new suxPhoto($this->module); // Photos
        $this->tpl = new suxTemplate($this->module); // Template
        $this->r = new photosRenderer($this->module); // Renderer
        $this->tpl->assign_by_ref('r', $this->r); // Renderer referenced in template
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->r->text =& $this->gtext;
        suxValidate::register_object('this', $this); // Register self to validator

        // Redirect if not logged in
        if (empty($_SESSION['users_id'])) suxFunct::redirect(suxFunct::makeUrl('/user/register'));

        // This module has config variables, load them
        $this->tpl->config_load('my.conf', $this->module);

        // Check that the user is allowed to upload photos
        if (!$this->user->isRoot()) {
            $access = $this->user->getAccess($this->module);
            if ($access < $GLOBALS['CONFIG']['ACCESS'][$this->module]['admin']) {
                if ($access < $GLOBALS['CONFIG']['ACCESS'][$this->module]['publisher'])
                    suxFunct::redirect(suxFunct::makeURL('/photos'));
            }
        }

        // Assign id to template
        if (filter_var($id, FILTER_VALIDATE_INT) && $id > 0) $this->tpl->assign('album', $id);

    }


    /**
    * Validate the form
    *
    * @param array $dirty reference to unverified $_POST
    * @return bool
    */
    function formValidate(&$dirty) {
        return suxValidate::formValidate($dirty, $this->tpl);
    }


    /**
    * Build the form and show the template
    *
    * @param array $dirty reference to unverified $_POST
    */
    function formBuild(&$dirty) {

        if (!empty($dirty)) $this->tpl->assign($dirty);
        else suxValidate::disconnect();

        if (!suxValidate::is_registered_form()) {

            suxValidate::connect($this->tpl, true); // Reset connection

            // Register our validators
            // register_validator($id, $field, $criteria, $empty = false, $halt = false, $transform = null, $form = 'default')

            suxValidate::register_validator('image', 'image:' . $this->extensions, 'isFileType');
            suxValidate::register_validator('image2','image:' . ini_get('upload_max_filesize'),'isFileSize');
            suxValidate::register_validator('album', 'album', 'isInt', false, false, 'trim');

        }


        // Additional
        $this->r->text['upload_max_filesize'] =  ini_get('upload_max_filesize');
        $this->r->text['supported'] =  $this->extensions;


        // Urls
        $this->r->text['form_url'] = suxFunct::makeUrl('/photos/upload');
        $this->r->text['back_url'] = suxFunct::getPreviousURL();

        $this->r->title .= " | {$this->r->text['upload']}";

        // Template
        $this->tpl->display('upload.tpl');

    }


    /**
    * Process the form
    *
    * @param array $clean reference to validated $_POST
    */
    function formProcess(&$clean) {

        if (!isset($_FILES['image']) || !is_uploaded_file($_FILES['image']['tmp_name']))
            throw new Exception('No file uploaded?');

        // Check that the user is allowed to upload photos
        if (!$this->user->isRoot()) {
            $access = $this->user->getAccess($this->module);
            if ($access < $GLOBALS['CONFIG']['ACCESS'][$this->module]['admin']) {
                if ($access < $GLOBALS['CONFIG']['ACCESS'][$this->module]['publisher']) suxFunct::redirect(suxFunct::makeURL('/photos'));
                elseif (!$this->photo->isAlbumOwner($clean['album'], $_SESSION['users_id'])) suxFunct::redirect(suxFunct::makeURL('/photos'));
            }
        }

        // Commence collecting $photo array
        $photo['photoalbums_id'] = $clean['album'];

        // Get extension
        $format = explode('.', $_FILES['image']['name']);
        $format = strtolower(end($format));

        // Set the data dir
        $data_dir = suxFunct::dataDir($this->module);

        if ($format != 'zip') {

            // ----------------------------------------------------------------
            // Image file
            // ----------------------------------------------------------------

            list($resize, $fullsize) = suxPhoto::renameImage($_FILES['image']['name']);
            $photo['image'] = $resize; // Add image to $photo array
            $resize =  $data_dir . "/{$resize}";
            $fullsize = $data_dir . "/{$fullsize}";
            $md5 = md5_file($_FILES['image']['tmp_name']);

            if (!$this->photo->isDupe($md5, $_SESSION['users_id'], $photo['photoalbums_id'])) {

                suxPhoto::resizeImage($format, $_FILES['image']['tmp_name'], $resize,
                    $this->tpl->get_config_vars('thumbnailWidth'),
                    $this->tpl->get_config_vars('thumbnailHeight')
                    );
                move_uploaded_file($_FILES['image']['tmp_name'], $fullsize);

                // Insert $photo into database
                $photo['md5'] = $md5;
                $this->photo->savePhoto($_SESSION['users_id'], $photo);
            }

        }
        else {

            // ----------------------------------------------------------------
            // Zip file
            // ----------------------------------------------------------------

            $tmp_dir = $GLOBALS['CONFIG']['PATH'] . '/temporary/' . md5(uniqid(mt_rand(), true));
            if (!is_dir($tmp_dir) && !mkdir($tmp_dir, 0777, true)) throw new Exception('Can\'t create temp dir ' . $tmp_dir);

            if (suxFunct::unzip($_FILES['image']['tmp_name'], $tmp_dir)) {

                $files = scandir($tmp_dir);
                $valid_formats = array('jpg', 'jpeg', 'png', 'gif');

                foreach($files as $file) {

                    $format = explode('.', $file);
                    $format = strtolower(end($format));
                    if (!in_array($format, $valid_formats)) continue; // Skip

                    list($resize, $fullsize) = suxPhoto::renameImage($file);
                    $photo['image'] = $resize; // Add image to $photo array
                    $resize =  $data_dir . "/{$resize}";
                    $fullsize = $data_dir . "/{$fullsize}";
                    $md5 = md5_file("{$tmp_dir}/{$file}");

                    if (!$this->photo->isDupe($md5, $_SESSION['users_id'], $photo['photoalbums_id'])) {

                        suxPhoto::resizeImage($format, "{$tmp_dir}/{$file}", $resize,
                            $this->tpl->get_config_vars('thumbnailWidth'),
                            $this->tpl->get_config_vars('thumbnailHeight')
                            );
                        copy("{$tmp_dir}/{$file}", $fullsize);

                        // Insert $photo into database
                        $photo['md5'] = $md5;
                        $this->photo->savePhoto($_SESSION['users_id'], $photo);
                    }

                }

            }

            suxFunct::obliterateDir($tmp_dir);

        }

        $this->user->log("sux0r::photosUpload() photoalbums_id: {$photo['photoalbums_id']}", $_SESSION['users_id'], 1); // Private

        $tmp = $this->photo->getAlbum($photo['photoalbums_id']); // Is actually published?
        if ($tmp) {

            // Clear all caches, cheap and easy
            $this->tpl->clear_all_cache();

            // Log message
            $log = '';
            $url = suxFunct::makeUrl("/user/profile/{$_SESSION['nickname']}", null, true);
            $log .= "<a href='$url'>{$_SESSION['nickname']}</a> ";
            $log .= mb_strtolower($this->r->text['uploaded_images']);
            $url = suxFunct::makeUrl("/photos/album/{$tmp['id']}", null, true);
            $log .= " <a href='$url'>{$tmp['title']}</a>";

            // Log
            $this->user->log($log);

            // Clear cache
            $tpl = new suxTemplate('user');
            $tpl->clear_cache('profile.tpl', $_SESSION['nickname']);
        }

    }


    /**
    * The form was successfuly processed
    */
    function formSuccess() {

        // Template
        $this->r->text['back_url'] = suxFunct::getPreviousURL();
        $this->r->title .= " | {$this->r->text['success']}";

        $this->tpl->display('success.tpl');

    }



}


?>