<?php

/**
* feedsManage
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

require_once(dirname(__FILE__) . '/../../includes/suxRSS.php');
require_once(dirname(__FILE__) . '/../../includes/suxLink.php');
require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once(dirname(__FILE__) . '/../../includes/suxValidate.php');
require_once(dirname(__FILE__) . '/../../includes/suxRenderer.php');

class feedsManage  {

    // Variables
    public $gtext = array();
    protected $prev_url_preg = '#^feeds/[manage|admin]#i';
    private $module = 'feeds';

    // Objects
    public $tpl;
    public $r;
    protected $user;
    protected $rss;
    protected $link;

    /**
    * Constructor
    *
    */
    function __construct() {

        $this->rss = new suxRSS();
        $this->user = new suxUser(); // User
        $this->link = new suxLink();
        $this->tpl = new suxTemplate($this->module); // Template
        $this->r = new suxRenderer($this->module); // Renderer
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->r->text =& $this->gtext;
        suxValidate::register_object('this', $this); // Register self to validator

        // Redirect if not logged in
        $this->user->loginCheck(suxfunct::makeUrl('/user/register'));

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

            suxValidate::register_validator('subscriptions', 'subscriptions', 'isInt', true);

        }

        // Urls
        $this->r->text['form_url'] = suxFunct::makeUrl('/feeds/manage');
        $this->r->text['back_url'] = suxFunct::getPreviousURL($this->prev_url_preg);

        // Feeds
        $feeds = array();
        foreach ($this->rss->getFeeds() as $feed) {
            $feeds[$feed['id']] = $feed['title'];
        }
        $this->tpl->assign('feeds', $feeds);

        // Subscriptions
        if (!isset($_POST['subscriptions'])) {
            $subscriptions = $this->link->getLinks('link_rss_users', 'users', $_SESSION['users_id']);
            $this->tpl->assign('subscriptions', $subscriptions);
        }

        $this->tpl->assign_by_ref('r', $this->r);
        $this->tpl->display('manage.tpl');

    }


    /**
    * Process the form
    *
    * @param array $clean reference to validated $_POST
    */
    function formProcess(&$clean) {

        $this->link->deleteLink('link_rss_users', 'users', $_SESSION['users_id']);

        if (isset($clean['subscriptions']) && count($clean['subscriptions']))
            $this->link->setLink('link_rss_users', 'users', $_SESSION['users_id'], 'rss_feeds', $clean['subscriptions']);

    }


    /**
    * The form was successfuly processed
    */
    function formSuccess() {

        // clear all caches with "nickname" as the first cache_id group
        $this->tpl->clear_cache(null, "{$_SESSION['nickname']}");

        // Redirect
        suxFunct::redirect(suxFunct::getPreviousURL($this->prev_url_preg));

    }



}


?>