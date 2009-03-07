<?php

/**
* photosEdit
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.gnu.org/licenses/agpl.html
*/

require_once(dirname(__FILE__) . '/../../includes/suxPhoto.php');
require_once(dirname(__FILE__) . '/../../includes/suxPager.php');
require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once(dirname(__FILE__) . '/../../includes/suxValidate.php');
require_once('photosRenderer.php');

class photosEdit {

    // Variables
    public $per_page; // Photos per page
    public $gtext = array();
    private $id;
    private $module = 'photos';

    // Objects
    public $tpl;
    public $r;
    private $user;
    private $photo;
    private $pager;


    /**
    * Constructor
    *
    * @param int $id album id
    */
    function __construct($id) {

        if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1)
            suxFunct::redirect(suxFunct::makeURL('/photos')); // Invalid id

        $this->user = new suxUser(); // User
        $this->photo = new suxPhoto($this->module); // Photos
        $this->tpl = new suxTemplate($this->module); // Template
        $this->r = new photosRenderer($this->module); // Renderer
        $this->tpl->assign_by_ref('r', $this->r); // Renderer referenced in template
        suxValidate::register_object('this', $this); // Register self to validator
        $this->pager = new suxPager();

        // Redirect if not logged in
        if (empty($_SESSION['users_id'])) suxFunct::redirect(suxFunct::makeUrl('/user/register'));

        // Security check
        if (!$this->user->isRoot()) {
            $access = $this->user->getAccess($this->module);
            if ($access < $GLOBALS['CONFIG']['ACCESS'][$this->module]['admin']) {
                if ($access < $GLOBALS['CONFIG']['ACCESS'][$this->module]['publisher']) suxFunct::redirect(suxFunct::makeURL('/photos'));
                elseif (!$this->photo->isAlbumOwner($id, $_SESSION['users_id'])) suxFunct::redirect(suxFunct::makeURL('/photos'));
            }
        }

        // Assign id
        $this->id = $id;

        // This module has config variables, load them
        $this->tpl->config_load('my.conf', $this->module);
        $this->per_page = $this->tpl->get_config_vars('perPage');

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

        $photoalbum = array();

        // Editing a photoalbum
        $tmp = $this->photo->getAlbum($this->id, false);
        if (!$tmp) suxFunct::redirect(suxFunct::makeURL('/photos')); // Invalid id

        $photoalbum['id'] = $tmp['id'];
        $photoalbum['cover'] = $tmp['thumbnail'];

        // Don't allow spoofing
        unset($dirty['id']);

        $this->tpl->assign($photoalbum);

        // --------------------------------------------------------------------
        // Form logic
        // --------------------------------------------------------------------

        if (!empty($dirty)) $this->tpl->assign($dirty);
        else suxValidate::disconnect();

        if (!suxValidate::is_registered_form()) {

            suxValidate::connect($this->tpl, true); // Reset connection

            // Register our validators
            suxValidate::register_validator('integrity', 'integrity:id', 'hasIntegrity');

        }

        // --------------------------------------------------------------------
        // Templating
        // --------------------------------------------------------------------

        // Start pager
        $this->pager->limit = $this->per_page;
        $this->pager->setStart();

        $this->pager->setPages($this->photo->countPhotos($this->id));
        $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl("/photos/album/annotate/{$this->id}"));
        $this->r->arr['photos'] = $this->photo->getPhotos($this->id, $this->pager->limit, $this->pager->start);

        $this->r->text['form_url'] = suxFunct::makeUrl('/photos/album/annotate/' . $this->id, array('page' => $_GET['page']));
        $this->r->text['back_url'] = suxFunct::getPreviousURL();

        $this->r->title .= " | {$this->r->gtext['annotate_2']}";

        $this->tpl->display('annotate.tpl');

    }


    /**
    * Process form
    *
    * @param array $clean reference to validated $_POST
    */
    function formProcess(&$clean) {

        // Set cover
        if (isset($clean['cover'])) $this->photo->saveThumbnail($clean['id'], $clean['cover']);

        // Remove photos from database
        // Notice: This does not remove photos from disk!
        if (isset($clean['delete'])) foreach ($clean['delete'] as $val) {
            if ($this->photo->isPhotoOwner($val, $_SESSION['users_id'])) {
                $this->photo->deletePhoto($val);
            }
        }

        // Clear all caches, cheap and easy
        $this->tpl->clear_all_cache();

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


?>