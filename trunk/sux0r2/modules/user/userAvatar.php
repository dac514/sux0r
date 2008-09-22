<?php

/**
* userAvatar
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
require_once('userRenderer.php');

class userAvatar  {

    // Variables
    private $nickname;
    private $users_id;
    private $image;
    public $gtext = array();
    private $extensions = 'jpg,jpeg,gif,png'; // Supported extensions
    private $module = 'user';


    // Objects
    public $tpl;
    public $r;
    private $user;

    /**
    * Constructor
    *
    */
    function __construct($nickname) {

        $this->user = new suxUser(); // User
        $this->tpl = new suxTemplate($this->module); // Template
        $this->r = new userRenderer($this->module); // Renderer
        $this->tpl->assign_by_ref('r', $this->r); // Renderer referenced in template
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->r->text =& $this->gtext;
        suxValidate::register_object('this', $this); // Register self to validator

        // Redirect if not logged in
        $this->user->loginCheck(suxfunct::makeUrl('/user/register'));

        // This module has config variables, load them
        $this->tpl->config_load('my.conf', $this->module);

        // Security check. Is the user allowed to edit this?
        $tmp = $this->user->getUserByNickname($nickname, true);
        if (!$tmp) suxFunct::redirect(suxFunct::getPreviousURL()); // Invalid user
        elseif ($tmp['users_id'] != $_SESSION['users_id']) {
            // Check that the user is allowed to be here
            if (!$this->user->isRoot()) {
                suxFunct::redirect(suxFunct::getPreviousURL());
            }
        }

        // Pre assign variables
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
        else suxValidate::disconnect();

        if (!suxValidate::is_registered_form()) {

            suxValidate::connect($this->tpl, true); // Reset connection

            // Register our validators
            // register_validator($id, $field, $criteria, $empty = false, $halt = false, $transform = null, $form = 'default')

            $empty = false;
            if ($this->image) $empty = true;

            suxValidate::register_validator('integrity', 'integrity:users_id:nickname', 'hasIntegrity');
            suxValidate::register_validator('image', 'image:' . $this->extensions, 'isFileType', $empty);
            suxValidate::register_validator('image2','image:' . ini_get('upload_max_filesize'), 'isFileSize', $empty);

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

        // Display template
        $this->tpl->display('avatar.tpl');

    }


    /**
    * Process the form
    *
    * @param array $clean reference to validated $_POST
    */
    function formProcess(&$clean) {

        // Security check
        if ($clean['users_id'] != $_SESSION['users_id']) {
            // Check that the user is allowed to be here
            if (!$this->user->isRoot()) {
                suxFunct::redirect(suxFunct::getPreviousURL());
            }
        }

        // Commence $clean array
        $user['users_id'] = $clean['users_id'];
        $user['image']  = false;

        // Unset image?
        if (!empty($clean['unset_image'])) $user['image'] = ''; // Set to empty string

        // Image?
        if (isset($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])) {

            $format = explode('.', $_FILES['image']['name']);
            $format = strtolower(end($format)); // Extension

            list($resize, $fullsize) = suxPhoto::renameImage($_FILES['image']['name']);
            $user['image'] = $resize; // Add image to user array
            $resize = suxFunct::dataDir($this->module) . "/{$resize}";
            $fullsize = suxFunct::dataDir($this->module) . "/{$fullsize}";

            suxPhoto::resizeImage($format, $_FILES['image']['tmp_name'], $resize,
                $this->tpl->get_config_vars('thumbnailWidth'),
                $this->tpl->get_config_vars('thumbnailHeight')
                );
            move_uploaded_file($_FILES['image']['tmp_name'], $fullsize);

        }

        // Update $user into database
        if ($user['image'] !== false) $this->user->saveImage($user['image'], $user['users_id']);

        // Clear approptiate template caches
        $this->tpl->clear_cache('profile.tpl', $this->nickname);

    }


    /**
    * The form was successfuly processed
    */
    function formSuccess() {

        $this->r->bool['edit'] = true;
        $this->r->text['back_url'] = suxFunct::getPreviousURL();

        // Template
        $this->tpl->display('success.tpl');

    }



}


?>