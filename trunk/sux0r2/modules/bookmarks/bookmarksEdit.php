<?php

/**
* bookmarksEdit
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

require_once(dirname(__FILE__) . '/../../includes/suxLink.php');
require_once(dirname(__FILE__) . '/../../includes/suxTags.php');
require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once(dirname(__FILE__) . '/../../includes/suxBookmarks.php');
require_once(dirname(__FILE__) . '/../../includes/suxValidate.php');
require_once('bookmarksRenderer.php');

class bookmarksEdit {

    // Variables
    public $gtext = array();        
    private $id;
    private $prev_skip;    
    private $module = 'bookmarks';    

    // Objects
    public $tpl;
    public $r;
    private $user;
    private $bm;
    private $link;
    private $tags;


    /**
    * Constructor
    *
    * @param int $id message id
    */
    function __construct($id = null) {

        $this->tpl = new suxTemplate($this->module); // Template
        $this->r = new bookmarksRenderer($this->module); // Renderer
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->r->text =& $this->gtext;
        suxValidate::register_object('this', $this); // Register self to validator

        // Objects
        $this->user = new suxUser();
        $this->bm = new suxBookmarks();
        $this->link = new suxLink();
        $this->tags = new suxTags();

        // This module has config variables, load them
        // $this->tpl->config_load('my.conf', $this->module);

        // Redirect if not logged in
        $this->user->loginCheck(suxfunct::makeUrl('/user/register'));

        if (filter_var($id, FILTER_VALIDATE_INT)) {
            // TODO:
            // Verfiy that we are allowed to edit this
            $this->id = $id;
        }
        
        
        // This module can fallback on approve module        
        foreach ($GLOBALS['CONFIG']['PREV_SKIP'] as $val) {            
            if (mb_strpos($val, 'bookmarks/approve') === false) 
                $this->prev_skip[] = $val;            
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

        unset($dirty['id']); // Don't allow spoofing       
        $bookmark = array();

        if ($this->id) {
            
            // Editing a bookmark post
            $tmp = $this->bm->getBookmark($this->id, true);

            $bookmark['id'] = $tmp['id'];
            $bookmark['title'] = $tmp['title'];
            $bookmark['url'] = $tmp['url'];
            $bookmark['body'] = $tmp['body_html'];
            $bookmark['draft'] = $tmp['draft'];

            // Get publish date
            // regex must match '2008-06-18 16:53:29' or '2008-06-18T16:53:29-04:00'
            $matches = array();
            $regex = '/^(\d{4})-(0[0-9]|1[0,1,2])-([0,1,2][0-9]|3[0,1]).+(\d{2}):(\d{2}):(\d{2})/';
            preg_match($regex, $tmp['published_on'], $matches);
            $bookmark['Date_Year'] = @$matches[1]; // year
            $bookmark['Date_Month'] = @$matches[2]; // month
            $bookmark['Date_Day'] = @$matches[3]; // day
            $bookmark['Time_Hour']  = @$matches[4]; // hour
            $bookmark['Time_Minute']  = @$matches[5]; // minutes
            $bookmark['Time_Second'] = @$matches[6]; //seconds

            /* Tags */

            $links = $this->link->getLinks('link_bookmarks_tags', 'bookmarks', $bookmark['id']);
            $bookmark['tags'] = '';
            foreach($links as $val) {
                $tmp = $this->tags->getTag($val);
                $bookmark['tags'] .=  $tmp['tag'] . ', ';
            }
            $bookmark['tags'] = rtrim($bookmark['tags'], ', ');

            
            

        }

        // Assign bookmark
        // new dBug($bookmark);
        $this->tpl->assign($bookmark);

        // --------------------------------------------------------------------
        // Form logic
        // --------------------------------------------------------------------

        if (!empty($dirty)) $this->tpl->assign($dirty);
        else suxValidate::disconnect();

        if (!suxValidate::is_registered_form()) {

            suxValidate::connect($this->tpl, true); // Reset connection
            
            // Register our additional criterias
            suxValidate::register_criteria('isDuplicateBookmark', 'this->isDuplicateBookmark');  
            suxValidate::register_criteria('isValidBookmark', 'this->isValidBookmark');            

            // Register our validators
            if ($this->id) suxValidate::register_validator('integrity', 'integrity:id', 'hasIntegrity');

            suxValidate::register_validator('url', 'url', 'notEmpty', false, false, 'trim');
            suxValidate::register_validator('url2', 'url', 'isURL');
            suxValidate::register_validator('url3', 'url', 'isDuplicateBookmark');            
            suxValidate::register_validator('url4', 'url', 'isValidBookmark');
            suxValidate::register_validator('title', 'title', 'notEmpty', false, false, 'trim');
            suxValidate::register_validator('body', 'body', 'notEmpty', false, false, 'trim');
            suxValidate::register_validator('date', 'Date:Date_Year:Date_Month:Date_Day', 'isDate', false, false, 'makeDate');
            suxValidate::register_validator('time', 'Time_Hour', 'isInt');
            suxValidate::register_validator('time2', 'Time_Minute', 'isInt');
            suxValidate::register_validator('time3', 'Time_Second', 'isInt');


        }

        // Additional variables
        $this->r->text['form_url'] = suxFunct::makeUrl('/bookmarks/edit/' . $this->id);
        $this->r->text['back_url'] = suxFunct::getPreviousURL($this->prev_skip);

        if (!$this->tpl->get_template_vars('Date_Year')) {
            // Today's Date
            $this->tpl->assign('Date_Year', date('Y'));
            $this->tpl->assign('Date_Month', date('m'));
            $this->tpl->assign('Date_Day', date('j'));
        }

        if (!$this->tpl->get_template_vars('Time_Hour')) {
            // Current Time
            $this->tpl->assign('Time_Hour', date('H'));
            $this->tpl->assign('Time_Minute', date('i'));
            $this->tpl->assign('Time_Second', date('s'));
        }

        // Template
        $this->tpl->assign_by_ref('r', $this->r);
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

        // --------------------------------------------------------------------
        // Create $bookmark array
        // --------------------------------------------------------------------

        $bookmark = array(
                'url' => $clean['url'],
                'title' => $clean['title'],
                'body' => $clean['body'],
                'published_on' => $clean['published_on'],
                'draft' => @$clean['draft'],
            );
        
        // --------------------------------------------------------------------
        // Id
        // --------------------------------------------------------------------        
                        
        if (isset($clean['id']) && filter_var($clean['id'], FILTER_VALIDATE_INT) && $clean['id'] > 0) {            
            // TODO: Check to see if this user is allowed to modify this bookmark            
            $bookmark['id'] = $clean['id'];
        }        

        // --------------------------------------------------------------------
        // Put $bookmark in database
        // --------------------------------------------------------------------      
        
        $clean['id'] = $this->bm->saveBookmark($_SESSION['users_id'], $bookmark);

        // --------------------------------------------------------------------
        // Tags procedure
        // --------------------------------------------------------------------

        // Parse tags
        $tags = suxTags::parse($clean['tags']);

        // Save tags into database
        $tag_ids = array();
        foreach($tags as $tag) {
            $tag_ids[] = $this->tags->saveTag($_SESSION['users_id'], $tag);
        }

        //Delete current links
        $this->link->deleteLink('link_bookmarks_tags', 'bookmarks', $clean['id']);

        // Reconnect links
        foreach ($tag_ids as $id) {
            $this->link->saveLink('link_bookmarks_tags', 'bookmarks', $clean['id'], 'tags', $id);
        }


    }


    /**
    * The form was successfuly processed
    */
    function formSuccess() {

        // TODO: Clear caches

        suxFunct::redirect(suxFunct::getPreviousURL($this->prev_skip));          

    }
    
    
    /**
    * for suxValidate, check if a duplicate url exists
    *
    * @return bool
    */
    function isDuplicateBookmark($value, $empty, &$params, &$formvars) {
        
        if (empty($formvars['url'])) return false;
        
        $tmp = $this->bm->getBookmark($formvars['url']);
        if ($tmp === false ) return true; // No duplicate found    
        
        if ($this->id) {
            // This is an Bookmark editing itself, this is OK
            if ($tmp['id'] == $this->id) return true; 
        }
        
        return false;
        
    }
    
    
    /**
    * for suxValidate, check if a bookmark is valid
    *
    * @return bool
    */
    function isValidBookmark($value, $empty, &$params, &$formvars) {

        if (empty($formvars['url'])) return false;
        $bm = $this->bm->fetchBookmark($formvars['url']);
        if (!$bm) return false;
        return true;

    }    
    


}


?>