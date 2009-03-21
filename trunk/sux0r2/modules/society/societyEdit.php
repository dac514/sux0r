<?php

/**
* societyEdit
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/


require_once(dirname(__FILE__) . '/../../includes/suxSocialNetwork.php');
require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once(dirname(__FILE__) . '/../../includes/suxValidate.php');
require_once('societyRenderer.php');

class societyEdit {

    // Variables
    private $nickname;
    private $users_id;
    public $gtext = array();
    private $module = 'society';

    // Objects
    public $tpl;
    public $r;
    private $user;
    private $soc;



    /**
    * Constructor
    *
    * @param string $nickname
    */
    function __construct($nickname) {


        $this->user = new suxUser(); // User
        $this->soc = new suxSocialNetwork(); // User
        $this->tpl = new suxTemplate($this->module); // Template
        $this->tpl->assign_by_ref('r', $this->r); // Renderer referenced in template
        $this->r = new societyRenderer($this->module); // Renderer
        suxValidate::register_object('this', $this); // Register self to validator

        // Redirect if not logged in
        if (empty($_SESSION['users_id'])) suxFunct::redirect(suxFunct::makeUrl('/user/register'));

        $tmp = $this->user->getByNickname($nickname);
        if (!$tmp) suxFunct::redirect(suxFunct::getPreviousURL()); // Invalid user

        // Don't let the user establish a relationship with themselves
        if ($tmp['users_id'] == $_SESSION['users_id'])
            suxFunct::redirect(suxFunct::getPreviousURL());

        $this->nickname = $nickname;
        $this->users_id = $tmp['users_id'];

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
        // Pre assign template variables, maybe overwritten by &$dirty
        // --------------------------------------------------------------------

        $rel = $this->soc->getRelationship($_SESSION['users_id'], $this->users_id);
        if ($rel) {

            list($identity, $friendship, $physical, $professional, $geographical, $family, $romantic) = $this->soc->relationshipArray($rel['relationship']);

            // Radios
            $this->tpl->assign('friendship', $friendship);
            $this->tpl->assign('geographical', $geographical);
            $this->tpl->assign('family', $family);
            // Checkboxes
            $this->tpl->assign('identity', @explode(' ', $identity));
            $this->tpl->assign('physical', @explode(' ', $physical));
            $this->tpl->assign('professional', @explode(' ', $professional));
            $this->tpl->assign('romantic', @explode(' ', $romantic));
        }

        // --------------------------------------------------------------------
        // Form logic
        // --------------------------------------------------------------------

        if (!empty($dirty)) $this->tpl->assign($dirty);
        else suxValidate::disconnect();

        if (!suxValidate::is_registered_form()) {

            suxValidate::connect($this->tpl, true); // Reset connection

            // Register our validators
            // register_validator($id, $field, $criteria, $empty = false, $halt = false, $transform = null, $form = 'default')

            suxValidate::register_validator('integrity', 'integrity:users_id:nickname', 'hasIntegrity');

        }

        // --------------------------------------------------------------------
        // Template
        // --------------------------------------------------------------------

        $this->tpl->assign('nickname', $this->nickname);
        $this->tpl->assign('users_id', $this->users_id);
        $this->r->text['form_url'] = suxFunct::makeUrl("/society/relationship/{$this->nickname}");
        $this->r->text['back_url'] = suxFunct::getPreviousURL();
        $this->r->title .= " | {$this->r->gtext['edit_relationship']}";

        // Display template
        $this->tpl->display('relationship.tpl');


    }



    /**
    * Process the form
    *
    * @param array $clean reference to validated $_POST
    */
    function formProcess(&$clean) {

        $fid = $clean['users_id'];
        $rel = '';
        $u = $this->user->getByID($clean['users_id']);
        $log = '';

        // Don't let the user establish a relationship with themselves
        if ($fid == $_SESSION['users_id'])
            suxFunct::redirect(suxFunct::getPreviousURL());

        // Strings
        if (isset($clean['friendship'])) $rel .= $clean['friendship'] . ' ';
        if (isset($clean['geographical'])) $rel .= $clean['geographical'] . ' ';
        if (isset($clean['family'])) $rel .= $clean['family'] . ' ';
        // Arrays
        if (isset($clean['identity'])) foreach($clean['identity'] as $val) $rel .= $val . ' ';
        if (isset($clean['physical'])) foreach($clean['physical'] as $val) $rel .= $val . ' ';
        if (isset($clean['professional'])) foreach($clean['professional'] as $val) $rel .= $val . ' ';
        if (isset($clean['romantic'])) foreach($clean['romantic'] as $val) $rel .= $val . ' ';

        // Set relationship
        $rel = trim($rel);
        if (empty($rel)) {

            $this->soc->deleteRelationship($_SESSION['users_id'], $fid);

            // Log message
            $url = suxFunct::makeUrl("/user/profile/{$_SESSION['nickname']}", null, true);
            $log .= "<a href='$url'>{$_SESSION['nickname']}</a> ";
            $log .= mb_strtolower($this->r->gtext['end_relation']);
            $url = suxFunct::makeUrl("/user/profile/{$u['nickname']}", null, true);
            $log .= " <a href='$url'>{$u['nickname']}</a>";

        }
        else {
            $this->soc->saveRelationship($_SESSION['users_id'], $fid, $rel);

            // Log message
            $url = suxFunct::makeUrl("/user/profile/{$_SESSION['nickname']}", null, true);
            $log .= "<a href='$url'>{$_SESSION['nickname']}</a> ";
            $log .= mb_strtolower($this->r->gtext['change_relation']);
            $url = suxFunct::makeUrl("/user/profile/{$u['nickname']}", null, true);
            $log .= " <a href='$url'>{$u['nickname']}</a>";

        }

        // Log
        $this->user->log($log);
        $this->user->log($log, $u['users_id']);

        // Clear caches, cheap and easy
        $tpl = new suxTemplate('user');
        $tpl->clear_cache(null, $_SESSION['nickname']);

    }


    /**
    * The form was successfuly processed
    */
    function formSuccess() {

        suxFunct::redirect(suxFunct::getPreviousURL());

    }


}


?>