<?php

/**
* userAvatar
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class userAvatar extends component  {

    // Module name
    protected $module = 'user';

    // Form name
    protected $form_name = 'userAvatar';

    // Var: supported image extensions
    private string $extensions = 'jpg,jpeg,gif,png';

    // Var
    private $nickname;

    // Var
    private $users_id;

    // Var
    private $image;



    /**
    * Constructor
    *
    */
    function __construct($nickname) {

        // Declare objects
        $this->r = new userRenderer($this->module); // Renderer
        (new suxValidate())->register_object('this', $this); // Register self to validator
        parent::__construct(); // Let the parent do the rest

        // Redirect if not logged in
        if (empty($_SESSION['users_id'])) suxFunct::redirect(suxFunct::makeUrl('/user/register'));

        // Security check. Is the user allowed to edit this?
        $tmp = $this->user->getByNickname($nickname, true);
        if (!$tmp) suxFunct::redirect(suxFunct::getPreviousURL()); // Invalid user
        elseif ($tmp['users_id'] != $_SESSION['users_id']) {
            // Check that the user is allowed to be here
            if (!$this->user->isRoot()) {
                suxFunct::redirect(suxFunct::getPreviousURL());
            }
        }

        // Declare properties
        $this->nickname = $nickname;
        $this->users_id = $tmp['users_id'];
        $this->image = $tmp['image'];

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

        // --------------------------------------------------------------------
        // Form logic
        // --------------------------------------------------------------------

        if (!empty($dirty)) $this->tpl->assign($dirty);
        else (new suxValidate())->disconnect();

        if (!(new suxValidate())->is_registered_form()) {

            (new suxValidate())->connect($this->tpl, true); // Reset connection

            // Register our validators
            // register_validator($id, $field, $criteria, $empty = false, $halt = false, $transform = null, $form = 'default')

            $empty = false;
            if ($this->image) $empty = true;

            (new suxValidate())->register_validator('integrity', 'integrity:users_id:nickname', 'hasIntegrity');
            (new suxValidate())->register_validator('image', 'image:' . $this->extensions, 'isFileType', $empty);
            (new suxValidate())->register_validator('image2', 'image:' . ini_get('upload_max_filesize'), 'isFileSize', $empty);

        }

        // --------------------------------------------------------------------
        // Template
        // --------------------------------------------------------------------

        // Additional
        $this->tpl->assign('nickname', $this->nickname);
        $this->tpl->assign('users_id', $this->users_id);
        $this->tpl->assign('image', $this->image);
        $this->r->text['upload_max_filesize'] =  ini_get('upload_max_filesize');
        $this->r->text['supported'] =  $this->extensions;
        $this->r->text['form_url'] = suxFunct::makeUrl("/user/avatar/{$this->nickname}");
        $this->r->text['back_url'] = suxFunct::getPreviousURL();
        $this->r->title .= " | {$this->r->gtext['edit_avatar']}";

        // Display template
        $this->tpl->display('avatar.tpl');

    }


    /**
    * Process the form
    *
    * @param array $clean reference to validated $_POST
    */
    function formProcess(&$clean) {

        $user = [];
        // Commence $clean array
        $user['users_id'] = $clean['users_id'];
        $user['image']  = false;

        // Unset image?
        if (!empty($clean['unset_image'])) $user['image'] = ''; // Set to empty string

        // Image?
        if (isset($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])) {

            $format = explode('.', (string) $_FILES['image']['name']);
            $format = strtolower(end($format)); // Extension

            [$resize, $fullsize] = suxPhoto::renameImage($_FILES['image']['name']);
            $user['image'] = $resize; // Add image to user array
            $resize = suxFunct::dataDir($this->module) . "/{$resize}";
            $fullsize = suxFunct::dataDir($this->module) . "/{$fullsize}";

            suxPhoto::resizeImage($format, $_FILES['image']['tmp_name'], $resize,
                $this->tpl->getConfigVars('thumbnailWidth'),
                $this->tpl->getConfigVars('thumbnailHeight')
                );
            move_uploaded_file($_FILES['image']['tmp_name'], $fullsize);

        }

        // Update $user into database
        if ($user['image'] !== false) $this->user->saveImage($user['users_id'], $user['image']);

        // Log
        if ($user['users_id'] == $_SESSION['users_id']) {
            // Self edit
            $log = '';
            $url = suxFunct::makeUrl("/user/profile/{$_SESSION['nickname']}", null, true);
            $log .= "<a href='$url'>{$_SESSION['nickname']}</a> ";
            $log .= mb_strtolower($this->r->gtext['changed_avatar']);
            $this->log->write($_SESSION['users_id'], $log);
        }
        else {
            // Administrator edit
            $this->log->write($_SESSION['users_id'], "sux0r::userAvatar() users_id: {$user['users_id']}", 1); // Log, private
        }

        // Clear caches, cheap and easy
        $this->tpl->clearCache(null, $_SESSION['nickname']);

    }


    /**
    * The form was successfuly processed
    */
    function formSuccess() {

        $this->r->bool['edit'] = true;
        $this->r->text['back_url'] = suxFunct::getPreviousURL();
        $this->r->title .= " | {$this->r->gtext['success']}";

        // Template
        $this->tpl->display('success.tpl');

    }



}


