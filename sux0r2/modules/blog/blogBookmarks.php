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
require_once(dirname(__FILE__) . '/../../includes/suxValidate.php');
require_once(dirname(__FILE__) . '/../bayes/bayesUser.php');
require_once('blogRenderer.php');

class blogBookmarks {

    // Variables
    public $gtext = array();
    private $module = 'blog';
    private $prev_url_preg = '#^blog/[edit|reply|bookmarks]|^cropper/#i';
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
    * @param string $key PDO dsn key
    */
    function __construct($msg_id) {

        $this->tpl = new suxTemplate($this->module); // Template
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

        // --------------------------------------------------------------------
        // Scan post for href links
        // --------------------------------------------------------------------

        $msg = $this->msg->getMessage($msg_id, true);

        if (!$msg) {
            // TODO: No message, skip?
        }

        if ($msg['users_id'] != $_SESSION['users_id']) {
            // TODO: Not the user's message, skip?
        }

        $matches = array();
        $pattern = '/<a [^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/i'; // href pattern
        preg_match_all($pattern, $msg['body_html'], $matches);

        $count = count($matches[1]);
        if (!$count) {
            // TODO: No links, skip?
        }

        // Limit the amount of time we wait for a connection to a remote server to 5 seconds
        ini_set('default_socket_timeout', 5);
        for ($i = 0; $i < $count; ++$i) {
            if (mb_substr($matches[1][$i], 0, 7) == 'http://' || mb_substr($matches[1][$i], 0, 8) == 'https://') {

                // Basic info
                $url = suxFunct::canonicalizeUrl($matches[1][$i]);
                
                if (!filter_var($url, FILTER_VALIDATE_URL) || $this->bookmarks->getBookmark($url, true)) 
                    continue; // skip it 
                             
                $title = strip_tags($matches[2][$i]);
                $body = null;         
                
                if (!$this->r->detectPOST()) {
                    // Search the webpage for info we can use
                    $webpage = @file_get_contents($url);
                    // <title>
                    $found = array();
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
        // Replace what we have with what the user submitted.
        // --------------------------------------------------------------------

        $count = 0;
        if (isset($dirty['url']) && is_array($dirty['url'])) {
            $count = count($this->found_links); // Original count
            $this->found_links = array(); // Clear array
            for ($i = 0; $i < $count; ++$i) {
                if (!empty($dirty['url'][$i]) && !isset($this->found_links[$dirty['url'][$i]])) {
                    $this->found_links[$dirty['url'][$i]] = array('title' => $dirty['title'][$i], 'body' => $dirty['body'][$i]);
                }
                else {
                    $title = isset($dirty['title'][$i]) ? $dirty['title'][$i] : null;
                    $body = isset($dirty['body'][$i]) ? $dirty['body'][$i] : null;
                    $this->found_links[] = array('title' => $title, 'body' => $body);
                }
            }
        }

        // --------------------------------------------------------------------
        // Form logic
        // --------------------------------------------------------------------

        if (!empty($dirty)) $this->tpl->assign($dirty);
        else suxValidate::disconnect();

        if (!suxValidate::is_registered_form()) {

            suxValidate::connect($this->tpl, true); // Reset connection
            
            // Register our validators 
            $count = count($this->found_links);
            for ($i = 0; $i < $count; ++$i) {
                suxValidate::register_validator("url[$i]", "url[$i]", 'notEmpty', false, false, 'trim');
                suxValidate::register_validator("url2[$i]", "url[$i]", 'isURL');
                suxValidate::register_validator("title[$i]", "title[$i]", 'notEmpty', false, false, 'trim');
                suxValidate::register_validator("body[$i]", "body[$i]", 'notEmpty', false, false, 'trim');
            }

        }

        // Additional variables
        $this->r->text['form_url'] = suxFunct::makeUrl('/blog/bookmarks/' . $this->msg_id);
        $this->r->text['back_url'] = suxFunct::getPreviousURL($this->prev_url_preg);

        // Template
        $this->r->found_links = $this->found_links;
        $this->tpl->assign_by_ref('r', $this->r);
        $this->tpl->display('bookmarks.tpl');

    }



    /**
    * Process the form
    *
    * @param array $clean reference to validated $_POST
    */
    function formProcess(&$clean) {


        if (isset($clean['url']) && is_array($clean['url'])) {
            $count = count($clean['url']);
            for ($i = 0; $i < $count; ++$i) {
                $bookmark = array();
                if (!$this->bookmarks->getBookmark($clean['url'][$i],  true)) {
                    $bookmark['url'] = $clean['url'][$i];
                    $bookmark['title'] = $clean['title'][$i];
                    $bookmark['body'] = $clean['body'][$i];
                    $bookmark['draft'] = 1; // Admin approves bookmarks, like dmoz.org
                    $this->bookmarks->saveBookmark($_SESSION['users_id'], $bookmark);
                }
            }
        }

        // TODO, handoff
        exit;

    }

}


?>