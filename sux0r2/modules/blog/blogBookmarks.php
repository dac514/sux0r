<?php

/**
* blogBookmarks
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

// Work in progress...

require_once(dirname(__FILE__) . '/../../includes/suxBookmarks.php');
require_once(dirname(__FILE__) . '/../../includes/suxLink.php');
require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once(dirname(__FILE__) . '/../../includes/suxThreadedMessages.php');
require_once(dirname(__FILE__) . '/../../includes/suxUser.php');
require_once(dirname(__FILE__) . '/../../includes/suxValidate.php');
require_once(dirname(__FILE__) . '/../bayes/bayesUser.php');
require_once('blogRenderer.php');

class blogBookmarks {

    // Variables
    public $gtext = array();
    private $module = 'blog';
    private $prev_url_preg = '#^blog/[edit|reply|bookmarks]#i';
    private $msg_id;
    private $found_links = array();

    // Objects
    public $tpl;
    public $r;
    private $user;
    private $msg;
    private $nb;
    private $link;
    private $bookmarks;


    /**
    * Constructor
    *
    * @global string $CONFIG['PARTITION']
    * @param string $key PDO dsn key
    */
    function __construct($msg_id) {

        $this->tpl = new suxTemplate($this->module, $GLOBALS['CONFIG']['PARTITION']); // Template
        $this->r = new blogRenderer($this->module); // Renderer
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->r->text =& $this->gtext;
        suxValidate::register_object('this', $this); // Register self to validator

        // Objects
        $this->user = new suxuser();
        $this->msg = new suxThreadedMessages();
        $this->nb = new bayesUser();
        $this->link = new suxLink();
        $this->bookmarks = new suxBookmarks();
        $this->msg_id = $msg_id;

        // Redirect if not logged in
        $this->user->loginCheck(suxfunct::makeUrl('/user/register'));

        if (count($_POST)) return; // Don't rescan

        // --------------------------------------------------------------------
        // Scan post for href links
        // --------------------------------------------------------------------

        $msg = $this->msg->getMessage($msg_id, true);

        if (!$msg) {
            // No message, skip?
        }

        if ($msg['users_id'] != $_SESSION['users_id']) {
            // Not the user's message, skip?
        }

        $matches = array();
        $pattern = '/<a [^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/i';
        preg_match_all($pattern, $msg['body_html'], $matches);

        $count = count($matches[1]);
        if (!$count) {

            // No links, skip?

        }
        else {
            // Limit the amount of time we wait for a connection to a remote server to 5 seconds
            ini_set('default_socket_timeout', 5);
            for ($i = 0; $i < $count; ++$i) {
                if (mb_substr($matches[1][$i], 0, 7) == 'http://' || mb_substr($matches[1][$i], 0, 8) == 'https://') {

                    // Basic info
                    $url = suxFunct::canonicalizeUrl($matches[1][$i]);
                    $title = $matches[2][$i];
                    $body = null;

                    if ($tmp = $this->bookmarks->getBookmark($url)) {
                        // Already in database, skip it
                        continue;
                    }
                    elseif (filter_var($url, FILTER_VALIDATE_URL)) {
                        // Search the webpage for info we can use
                        $webpage = @file_get_contents($url);
                        $found = array();
                        // <title>
                        if (preg_match('/<title>(.*?)<\/title>/is', $webpage, $found)) {
                            $title = html_entity_decode(strip_tags($found[1]), ENT_QUOTES, 'UTF-8');
                        }
                        // TODO: Meta?
                    }
                    // Add to array for use in template
                    $this->found_links[$url] = array('title' => $title, 'body' => $body);
                }
            }
        }

        new dBug($this->found_links);

    }


    /**
    * Validate the form
    *
    * @param array $dirty reference to unverified $_POST
    * @return bool
    */
    function formValidate(&$dirty) {

        if(!empty($dirty) && suxValidate::is_registered_form()) {
            // Validate
            suxValidate::connect($this->tpl);
            if(suxValidate::is_valid($dirty)) {
                suxValidate::disconnect();
                return true;
            }
        }
        return false;

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

            // Register our additional criterias
            //suxValidate::register_criteria('invalidShare', 'this->invalidShare', 'sharevec');
            //suxValidate::register_criteria('userExists', 'this->userExists', 'sharevec');

            // Register our validators
            // register_validator($id, $field, $criteria, $empty = false, $halt = false, $transform = null, $form = 'default')

            suxValidate::register_validator('title', 'title', 'notEmpty', false, false, 'trim');
            suxValidate::register_validator('body', 'body', 'notEmpty', false, false, 'trim');


        }

        // Additional variables
        $this->r->text['form_url'] = suxFunct::makeUrl('/blog/bookmarks/' . $this->msg_id);
        $this->r->text['back_url'] = suxFunct::getPreviousURL($this->prev_url_preg);

        // Template
        $this->tpl->assign_by_ref('r', $this->r);
        $this->tpl->display('bookmarks.tpl');

    }



    /**
    * Process the form
    *
    * @param array $clean reference to validated $_POST
    */
    function formProcess(&$clean) {


    }


}


?>