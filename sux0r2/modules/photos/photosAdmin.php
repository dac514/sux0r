<?php

/**
* photosAdmin
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class photosAdmin extends component {

    // Module name
    protected $module = 'photos';

    // Object: suxPhoto()
    protected $photo;

    // Var
    public $per_page = 50;


    /**
    * Constructor
    *
    */
    function __construct() {

        // Declare objects
        $this->photo = new suxPhoto(); // Photos
        $this->r = new photosRenderer($this->module); // Renderer
        suxValidate::register_object('this', $this); // Register self to validator
        parent::__construct(); // Let the parent do the rest

        // Declare Properties
        $this->photo->setPublished(null);

        // Redirect if not logged in
        if (empty($_SESSION['users_id'])) suxFunct::redirect(suxFunct::makeUrl('/user/register'));

        // Security check
        if (!$this->user->isRoot()) {
            $access = $this->user->getAccess($this->module);
            if ($access < $GLOBALS['CONFIG']['ACCESS'][$this->module]['admin'])
                suxFunct::redirect(suxFunct::makeUrl('/home'));
        }

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


    function formBuild(&$dirty) {

        // --------------------------------------------------------------------
        // Form logic
        // --------------------------------------------------------------------

        if (!empty($dirty)) $this->tpl->assign($dirty);
        else suxValidate::disconnect();

        if (!suxValidate::is_registered_form()) {

            suxValidate::connect($this->tpl, true); // Reset connection

            // Register our validators
            suxValidate::register_validator('integrity', 'integrity:users_id:nickname', 'hasIntegrity');

        }

        // --------------------------------------------------------------------
        // Template
        // --------------------------------------------------------------------

        $this->tpl->assign('nickname', $_SESSION['nickname']);
        $this->tpl->assign('users_id', $_SESSION['users_id']);
        $this->r->text['form_url'] = suxFunct::makeUrl("/{$this->module}/admin");

        // Pager
        $this->pager->limit = $this->per_page;
        $this->pager->setStart();

        $this->pager->setPages($this->photo->countAlbums());
        $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl("/{$this->module}/admin"));
        $this->r->arr['photos'] = $this->photo->getAlbums($this->pager->limit, $this->pager->start);

        // Additional variables
        if (!$this->r->arr['photos']) unset($this->r->arr['photos']);
        else foreach ($this->r->arr['photos'] as $key => $val) {
            $u = $this->user->getByID($val['users_id']);
            $this->r->arr['photos'][$key]['nickname'] = $u['nickname'];
            $this->r->arr['photos'][$key]['photos_count'] = $this->photo->countPhotos($val['id']);
        }


        $this->r->title .= " | {$this->r->gtext['photos']} | {$this->r->gtext['admin']}";

        // Display
        $this->tpl->display('admin.tpl');

    }


    /**
    * Process the form
    *
    * @param array $clean reference to validated $_POST
    */
    function formProcess(&$clean) {

        if (isset($clean['delete'])) foreach($clean['delete'] as $id => $val) {
            $this->photo->deleteAlbum($id);
            $this->log->write($_SESSION['users_id'], "sux0r::photosAdmin() deleted photoalbums_id: $id", 1); // Private
        }

        // Clear caches, cheap and easy
        $this->tpl->clear_all_cache();

    }


    /**
    * The form was successfuly processed
    */
    function formSuccess() {

        suxFunct::redirect(suxFunct::makeUrl("/{$this->module}/admin/"));
    }


}


?>