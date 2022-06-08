<?php

/**
* photoalbumsEdit
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class photoalbumsEdit extends component {

    // Module name
    protected $module = 'photos';

    // Form name
    protected $form_name = 'photoalbumsEdit';

    // Object: suxPhoto()
    protected $photo;

    // Var
    private $id;


    /**
    * Constructor
    *
    * @param int $id album id
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
        $this->id = $id;


        // Redirect if not logged in
        if (empty($_SESSION['users_id'])) suxFunct::redirect(suxFunct::makeUrl('/user/register'));

        // Security check
        if (!$this->user->isRoot()) {
            $access = $this->user->getAccess($this->module);
            if ($access < $GLOBALS['CONFIG']['ACCESS'][$this->module]['admin']) {
                if ($access < $GLOBALS['CONFIG']['ACCESS'][$this->module]['publisher']) suxFunct::redirect(suxFunct::makeURL('/photos'));
                elseif ($id) {
                    if (!$this->photo->isAlbumOwner($id, $_SESSION['users_id'])) suxFunct::redirect(suxFunct::makeURL('/photos'));
                }
            }
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


    /**
    * Build the form and show the template
    *
    * @param array $dirty reference to unverified $_POST
    */
    function formBuild(&$dirty) {

        $photoalbum = array();

        if ($this->id) {

            // Editing a photoalbum
            $tmp = $this->photo->getAlbumByID($this->id);

            $photoalbum['id'] = $tmp['id'];
            $photoalbum['title'] = $tmp['title'];
            $photoalbum['body'] = htmlentities($tmp['body_html'], ENT_QUOTES, 'UTF-8'); // Textarea fix
            $photoalbum['draft'] = $tmp['draft'];

            // Get publish date
            // regex must match '2008-06-18 16:53:29' or '2008-06-18T16:53:29-04:00'
            $matches = array();
            $regex = '/^(\d{4})-(0[0-9]|1[0,1,2])-([0,1,2][0-9]|3[0,1]).+(\d{2}):(\d{2}):(\d{2})/';
            preg_match($regex, $tmp['published_on'], $matches);
            $photoalbum['Date_Year'] = @$matches[1]; // year
            $photoalbum['Date_Month'] = @$matches[2]; // month
            $photoalbum['Date_Day'] = @$matches[3]; // day
            $photoalbum['Time_Hour']  = @$matches[4]; // hour
            $photoalbum['Time_Minute']  = @$matches[5]; // minutes
            $photoalbum['Time_Second'] = @$matches[6]; //seconds

            // Don't allow spoofing
            unset($dirty['id']);

        }

        // Assign photoalbum
        // new dBug($photoalbum);
        $this->tpl->assign($photoalbum);

        // --------------------------------------------------------------------
        // Form logic
        // --------------------------------------------------------------------

        if (!empty($dirty)) $this->tpl->assign($dirty);
        else suxValidate::disconnect();

        if (!suxValidate::is_registered_form()) {

            suxValidate::connect($this->tpl, true); // Reset connection

            // Register our validators
            if ($this->id) suxValidate::register_validator('integrity', 'integrity:id', 'hasIntegrity');
            suxValidate::register_validator('title', 'title', 'notEmpty', false, false, 'trim');
            suxValidate::register_validator('body', 'body', 'notEmpty', false, false, 'trim');
            suxValidate::register_validator('date', 'Date:Date_Year:Date_Month:Date_Day', 'isDate', false, false, 'makeDate');
            suxValidate::register_validator('time', 'Time_Hour', 'isInt');
            suxValidate::register_validator('time2', 'Time_Minute', 'isInt');
            suxValidate::register_validator('time3', 'Time_Second', 'isInt');


        }

        // Additional variables
        $this->r->text['form_url'] = suxFunct::makeUrl('/photos/album/edit/' . $this->id);
        $this->r->text['back_url'] = suxFunct::getPreviousURL();

        if (!$this->tpl->getTemplateVars('Date_Year')) {
            // Today's Date
            $this->tpl->assign('Date_Year', date('Y'));
            $this->tpl->assign('Date_Month', date('m'));
            $this->tpl->assign('Date_Day', date('j'));
        }

        if (!$this->tpl->getTemplateVars('Time_Hour')) {
            // Current Time
            $this->tpl->assign('Time_Hour', date('H'));
            $this->tpl->assign('Time_Minute', date('i'));
            $this->tpl->assign('Time_Second', date('s'));
        }

        if ($this->id) $this->r->title .= " | {$this->r->gtext['edit_2']}";
        else $this->r->title .= " | {$this->r->gtext['new']}";

        // Template
        $this->tpl->display('edit.tpl');

    }



    /**
    * Process the form
    *
    * @param array $clean reference to validated $_POST
    */
    function formProcess(&$clean) {

        // --------------------------------------------------------------------
        // Sanity check
        // --------------------------------------------------------------------

        // Date
        $clean['published_on'] = "{$clean['Date']} {$clean['Time_Hour']}:{$clean['Time_Minute']}:{$clean['Time_Second']}";
        $clean['published_on'] = date('Y-m-d H:i:s', strtotime($clean['published_on'])); // Sanitize

        // Draft
        $clean['draft'] = (isset($clean['draft']) && $clean['draft']) ? true : false;

        // --------------------------------------------------------------------
        // Create $album array
        // --------------------------------------------------------------------

        $album = array(
                'title' => $clean['title'],
                'body' => $clean['body'],
                'published_on' => $clean['published_on'],
                'draft' => $clean['draft'],
            );

        if (isset($clean['id'])) $album['id'] = $clean['id'];

        // --------------------------------------------------------------------
        // Put $album in database
        // --------------------------------------------------------------------

        $id = $this->photo->saveAlbum($_SESSION['users_id'], $album);

        $this->log->write($_SESSION['users_id'], "sux0r::photoalbumsEdit() photoalbums_id: $id", 1); // Private

        $this->photo->setPublished(true);
        $tmp = $this->photo->getAlbumByID($id); // Is actually published?
        $this->photo->setPublished(null); // Revert

        if ($tmp) {

            // Clear all caches, cheap and easy
            $this->tpl->clearAllCache();

            if (!isset($clean['id'])) {
                // New album log message
                $log = '';
                $url = suxFunct::makeUrl("/user/profile/{$_SESSION['nickname']}", null, true);
                $log .= "<a href='$url'>{$_SESSION['nickname']}</a> ";
                $log .= mb_strtolower($this->r->gtext['created_album']);
                $url = suxFunct::makeUrl("/photos/album/{$tmp['id']}", null, true);
                $log .= " <a href='$url'>{$tmp['title']}</a>";
                $this->log->write($_SESSION['users_id'], $log);
            }

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


