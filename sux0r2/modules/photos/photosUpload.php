<?php

/**
* photosUpload
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class photosUpload extends component {

    // Module name
    protected $module = 'photos';

    // Form name
    protected $form_name = 'photosUpload';

    // Object: suxPhoto()
    protected $photo;

    // Var: supported extensions
    private $extensions = 'jpg,jpeg,gif,png,zip';


    /**
    * Constructor
    *
    */
    function __construct($id = null) {

        if ($id) {
            if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1)
                suxFunct::redirect(suxFunct::makeURL('/photos')); // Invalid id
        }

        // Declare objects
        $this->photo = new suxPhoto(); // Photos
        $this->r = new photosRenderer($this->module); // Renderer
        suxValidate::register_object('this', $this); // Register self to validator
        parent::__construct(); // Let the parent do the rest

        // Declare properties
        $this->photo->setPublished(null);

        // Redirect if not logged in
        if (empty($_SESSION['users_id'])) suxFunct::redirect(suxFunct::makeUrl('/user/register'));

        // Security check
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

        $this->r->title .= " | {$this->r->gtext['upload']}";

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

        // Check that the user is allowed to upload photos / Security check #2
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
                    $this->tpl->getConfigVars('thumbnailWidth'),
                    $this->tpl->getConfigVars('thumbnailHeight')
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

                $valid_formats = array('jpg', 'jpeg', 'png', 'gif');

                $files = array();
                foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmp_dir)) as $file) {
                    if (!$file->isFile()) continue;
                    if (mb_strpos($file->getPathname(), '__MACOSX') !== false) continue;
                    $files[$file->getPathname()] = $file->getFilename();
                }

                foreach($files as $filepath => $file) {

                    $format = explode('.', $file);
                    $format = strtolower(end($format));
                    if (!in_array($format, $valid_formats)) continue; // Skip

                    list($resize, $fullsize) = suxPhoto::renameImage($file);
                    $photo['image'] = $resize; // Add image to $photo array
                    $resize =  $data_dir . "/{$resize}";
                    $fullsize = $data_dir . "/{$fullsize}";
                    $md5 = md5_file($filepath);

                    if (!$this->photo->isDupe($md5, $_SESSION['users_id'], $photo['photoalbums_id'])) {

                        suxPhoto::resizeImage($format, $filepath, $resize,
                            $this->tpl->getConfigVars('thumbnailWidth'),
                            $this->tpl->getConfigVars('thumbnailHeight')
                            );
                        copy($filepath, $fullsize);

                        // Insert $photo into database
                        $photo['md5'] = $md5;
                        $this->photo->savePhoto($_SESSION['users_id'], $photo);
                    }

                }

            }

            suxFunct::obliterateDir($tmp_dir);

        }

        $this->log->write($_SESSION['users_id'], "sux0r::photosUpload() photoalbums_id: {$photo['photoalbums_id']}", 1); // Private

        $this->photo->setPublished(true);
        $tmp = $this->photo->getAlbumByID($photo['photoalbums_id']); // Is actually published?
        $this->photo->setPublished(null); // Revert

        if ($tmp) {

            // Clear all caches, cheap and easy
            $this->tpl->clearAllCache();

            // Log message
            $log = '';
            $url = suxFunct::makeUrl("/user/profile/{$_SESSION['nickname']}", null, true);
            $log .= "<a href='$url'>{$_SESSION['nickname']}</a> ";
            $log .= mb_strtolower($this->r->gtext['uploaded_images']);
            $url = suxFunct::makeUrl("/photos/album/{$tmp['id']}", null, true);
            $log .= " <a href='$url'>{$tmp['title']}</a>";

            // Log
            $this->log->write($_SESSION['users_id'], $log);

            // Clear caches, cheap and easy
            $tpl = new suxTemplate('user');
            $tpl->clearCache(null, $_SESSION['nickname']);
        }

    }


    /**
    * The form was successfuly processed
    */
    function formSuccess() {

        // Template
        $this->r->text['back_url'] = suxFunct::getPreviousURL();
        $this->r->title .= " | {$this->r->gtext['success']}";

        $this->tpl->display('success.tpl');

    }



}


