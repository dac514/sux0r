<?php

/**
* suxEdit
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

require_once(dirname(__FILE__) . '/../../includes/suxUser.php');
require_once(dirname(__FILE__) . '/../../includes/suxThreadedMessages.php');
require_once(dirname(__FILE__) . '/../bayes/suxNbUser.php');
require_once(dirname(__FILE__) . '/../../includes/suxLink.php');
require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once(dirname(__FILE__) . '/../../includes/suxValidate.php');
require_once('renderer.php');

class suxEdit {

    // Objects
    public $tpl;
    public $r;
    private $user;
    private $msg;
    private $nb;
    private $link;

    // Variables
    public $gtext = array();
    private $module = 'blog';
    private $prev_url_preg = '#^blog/[edit]#i';
    private $id;


    /**
    * Constructor
    *
    * @global string $CONFIG['PARTITION']
    * @param string $key PDO dsn key
    */
    function __construct($id = null) {

        $this->tpl = new suxTemplate($this->module, $GLOBALS['CONFIG']['PARTITION']); // Template
        $this->r = new renderer($this->module); // Renderer
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->r->text =& $this->gtext;
        suxValidate::register_object('this', $this); // Register self to validator

        // Objects
        $this->user = new suxuser();
        $this->msg = new suxThreadedMessages();
        $this->nb = new suxNbUser();
        $this->link = new suxLink();

        // Redirect if not logged in
        $this->user->loginCheck(suxfunct::makeUrl('/user/register'));

        if (filter_var($id, FILTER_VALIDATE_INT)) {
            // TODO:
            // Verfiy that we are allowed to edit this
            $this->id = $id;
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

        $blog = array();

        if ($this->id) {

            // Editing a blog post

            $tmp = $this->msg->getMessage($this->id);

            $blog['id'] = $tmp['id'];
            $blog['title'] = $tmp['title'];
            $blog['image'] = $tmp['image'];
            $blog['body'] = $tmp['body_html'];
            $blog['draft'] = $tmp['draft'];

            // Get publish date
            // regex must match '2008-06-18 16:53:29' or '2008-06-18T16:53:29-04:00'
            $matches = array();
            $regex = '/^(\d{4})-(0[0-9]|1[0,1,2])-([0,1,2][0-9]|3[0,1]).+(\d{2}):(\d{2}):(\d{2})/';
            preg_match($regex, $tmp['published_on'], $matches);
            $blog['Date_Year'] = @$matches[1]; // year
            $blog['Date_Month'] = @$matches[2]; // month
            $blog['Date_Day'] = @$matches[3]; // day
            $blog['Time_Hour']  = @$matches[4]; // hour
            $blog['Time_Minute']  = @$matches[5]; // minutes
            $blog['Time_Second'] = @$matches[6]; //seconds

            /*
            1) Get the `link_bayes_messages` matching this messages_id
            2) Foreach linking bayes_document_id
            3) get the categories I can train (nb::isCategoryTrainer($cat_id, $users_id)
            4) stuff them into {$category_id} for template
            */

            $links = $this->link->getLinks('link_bayes_messages', 'messages', $blog['id']);
            foreach($links as $val) {
                $cat = $this->nb->getCategoriesByDocument($val);
                foreach ($cat as $key2 => $val2) {
                    if ($this->nb->isCategoryTrainer($key2, $_SESSION['users_id'])) {
                        $blog['category_id'][] = $key2;
                    }
                }
            }

            // Don't allow spoofing
            unset($dirty['id']);

        }

        // Assign blog
        // new dBug($blog);
        $this->tpl->assign($blog);

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

            if ($this->id) suxValidate::register_validator('integrity', 'integrity:id', 'hasIntegrity');
            suxValidate::register_validator('title', 'title', 'notEmpty', false, false, 'trim');
            suxValidate::register_validator('image', 'image:jpg,jpeg,gif,png', 'isFileType', true);
            suxValidate::register_validator('body', 'body', 'notEmpty', false, false, 'trim');
            suxValidate::register_validator('date', 'Date:Date_Year:Date_Month:Date_Day', 'isDate', false, false, 'makeDate');
            suxValidate::register_validator('time', 'Time_Hour', 'isInt');
            suxValidate::register_validator('time2', 'Time_Minute', 'isInt');
            suxValidate::register_validator('time3', 'Time_Second', 'isInt');


        }

        // Additional variables
        $this->r->text['form_url'] = suxFunct::makeUrl('/blog/edit/' . $this->id);
        $this->r->text['back_url'] = suxFunct::getPreviousURL($this->prev_url_preg);

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

        // Message id, edit mode
        if (isset($clean['id']) && filter_var($clean['id'], FILTER_VALIDATE_INT)) {
            // TODO: Check to see if this user is allowed to modify this blog
            // $clean['id'] = false // on fail
        }

        // Date
        $clean['published_on'] = "{$clean['Date']} {$clean['Time_Hour']}:{$clean['Time_Minute']}:{$clean['Time_Second']}";

        // Image?
        if (isset($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])) {

            list($resize, $fullsize) = suxFunct::renameImage($_FILES['image']['name']);
            $clean['image'] = $resize; // Add image to clean array
            $format = explode('.', $_FILES['image']['name']);
            $format = strtolower(end($format));
            $filein = $_FILES['image']['tmp_name'];
            $resize = "{$GLOBALS['CONFIG']['PATH']}/data/{$this->module}/{$resize}";
            $fullsize = "{$GLOBALS['CONFIG']['PATH']}/data/{$this->module}/{$fullsize}";
            suxFunct::resizeImage($format, $filein, $resize, 80, 80);
            move_uploaded_file($_FILES['image']['tmp_name'], $fullsize);

        }

        // --------------------------------------------------------------------
        // Create $msg array
        // --------------------------------------------------------------------

        $msg = array(
                'title' => $clean['title'],
                'image' => @$clean['image'],
                'body' => $clean['body'],
                'published_on' => $clean['published_on'],
                'draft' => @$clean['draft'],
                'blog' => 1,
            );

        // --------------------------------------------------------------------
        // Put $msg in database
        // --------------------------------------------------------------------

        if (isset($clean['id'])) {

            // Edit
            $this->msg->editMessage($clean['id'], $_SESSION['users_id'], $msg, true);

        }
        else {

            // New
            $clean['id'] = $this->msg->saveMessage($_SESSION['users_id'], $msg, null, true);

        }


        // --------------------------------------------------------------------
        // Naive Bayesian stuff
        // --------------------------------------------------------------------

        /*
        `link_bayes_messages` asserts that a message was trained and copied into
        a bayes document,  it does not imply that it's the same document

        When a user edits their own document we can assume that we want
        the updated document to represent their selected categories

        However, we cannot assume this for the catgories of others.

        Example:

        I write and classify a 5000 word message.
        Several other users find my post and classify it too.
        Time passes, I'm drunk, I reduce the post to "Eat shit."

        Now what?

        Deleting all links to a message for which I can train the vector seems
        the safest bet. All users get to keep what they already classified, and
        can reclassify the modified document if they wish. They can also
        manually adjust the eroneous documents in the bayes module.
        */

        // links[] is an array of $clean['id'] associated bayes_documents ids
        $links = $this->link->getLinks('link_bayes_messages', 'messages', $clean['id']);
        foreach($links as $val) {
            // Get the vector associated with the bayes_document (i.e. $val)
            $vec = $this->nb->getVectorsByDocument($val);
            foreach ($vec as $key2 => $val2) {
                // If the user is allowed to train the vector, we delete all links to the bayes_document (i.e. $val)
                if ($this->nb->isVectorTrainer($key2, $_SESSION['users_id'])) {
                    $this->link->deleteLink('link_bayes_messages', 'bayes_documents', $val);
                }
            }
        }

        // category ids submitted by the form
        foreach($clean['category_id'] as $val) {
            if (!empty($val) && $this->nb->isCategoryTrainer($val, $_SESSION['users_id'])) {
                $doc_id = $this->nb->trainDocument($clean['body'], $val);
                $this->link->setLink('link_bayes_messages', 'bayes_documents', $doc_id, 'messages', $clean['id']);
            }
        }


    }


}


?>