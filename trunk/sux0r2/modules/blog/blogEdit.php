<?php

/**
* blogEdit
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
require_once(dirname(__FILE__) . '/../../includes/suxPhoto.php');
require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once(dirname(__FILE__) . '/../../includes/suxThreadedMessages.php');
require_once(dirname(__FILE__) . '/../../includes/suxValidate.php');
require_once(dirname(__FILE__) . '/../bayes/bayesUser.php');
require_once('blogRenderer.php');

class blogEdit {

    // Variables
    public $gtext = array();
    private $module = 'blog';
    private $prev_url_preg = '#^blog/[edit|reply|bookmarks]|^cropper/#i';
    private $id;

    // Objects
    public $tpl;
    public $r;
    private $user;
    private $msg;
    private $nb;
    private $link;


    /**
    * Constructor
    *
    * @param int $id message id
    */
    function __construct($id = null) {

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
            4) stuff them into {$category_id} for template, append doc_id to {$link} string
            */

            $links = $this->link->getLinks('link_bayes_messages', 'messages', $blog['id']);
            $blog['linked'] = '';
            foreach($links as $val) {
                $cat = $this->nb->getCategoriesByDocument($val);
                foreach ($cat as $key => $val2) {
                    if ($this->nb->isCategoryTrainer($key, $_SESSION['users_id'])) {
                        $blog['linked'] .= "$val, ";
                        $blog['category_id'][] = $key;
                    }
                }
            }
            $blog['linked'] = rtrim($blog['linked'], ', '); // Remove trailing comma

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

            // Register our validators
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
        $clean['published_on'] = date('Y-m-d H:i:s', strtotime($clean['published_on'])); // Sanitize

        // Unset image?
        if (!empty($clean['unset_image'])) $clean['image'] = ''; // Set to empty string

        // Image?
        if (isset($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])) {

            list($resize, $fullsize) = suxPhoto::renameImage($_FILES['image']['name']);
            $clean['image'] = $resize; // Add image to clean array
            $format = explode('.', $_FILES['image']['name']);
            $format = strtolower(end($format));
            $filein = $_FILES['image']['tmp_name'];
            $resize = suxFunct::dataDir($this->module) . "/{$resize}";
            $fullsize = suxFunct::dataDir($this->module) . "/{$fullsize}";
            suxPhoto::resizeImage($format, $filein, $resize, 80, 80);
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
        a bayes document, it does not imply that it's the same document

        When a user edits their own document we can assume that we want
        the updated document to represent their selected categories

        However, we cannot assume this for the catgories of others.

        Example:

        I write and classify a 5000 word message.
        Several other users find my post and classify it too.
        Time passes, I'm drunk, I reduce the post to "Eat shit."

        Course of action:

        Deleting all links to a message for which I can train the vector seems
        the safest bet. Other users get to keep what they already classified,
        and can reclassify the modified document at a later date if they wish.
        They can also manually adjust the eroneous documents in the bayes module.

        Problem / TODO:

        I write and classify a 5000 word blog. Someone with permission to edit
        my blog, but who does not share my Bayesian vectors reduces the post to
        "Eat shit." Author's categories are now meaningless as blog tags.

        Now what?

        */

        // Get all the bayes_documents linked to this message where user is trainer
        // untrain it, delete links

        $innerjoin = "
        INNER JOIN link_bayes_messages ON link_bayes_messages.bayes_documents_id = bayes_documents.id
        INNER JOIN messages ON link_bayes_messages.messages_id = messages.id
        INNER JOIN bayes_categories ON bayes_categories.id = bayes_documents.bayes_categories_id
        INNER JOIN bayes_auth ON bayes_categories.bayes_vectors_id = bayes_auth.bayes_vectors_id
        ";

        $query = "
        SELECT bayes_documents.id FROM bayes_documents
        {$innerjoin}
        WHERE messages.id = ?
        AND bayes_auth.users_id = ? AND (bayes_auth.owner = 1 OR bayes_auth.trainer = 1)
        "; // Note: bayes_auth WHERE condition equivilant to nb->isCategoryTrainer()

        $db = suxDB::get();
        $st = $db->prepare($query);
        $st->execute(array($clean['id'], $_SESSION['users_id']));
        $tmp = $st->fetchAll(PDO::FETCH_ASSOC);

        foreach ($tmp as $val) {
            $this->nb->untrainDocument($val['id']);
            $this->link->deleteLink('link_bayes_messages', 'bayes_documents', $val['id']);
        }

        // Regcategorize
        // category ids submitted by the form

        if (isset($clean['category_id'])) foreach($clean['category_id'] as $val) {
            if (!empty($val) && $this->nb->isCategoryTrainer($val, $_SESSION['users_id'])) {
                $doc_id = $this->nb->trainDocument("{$clean['title']} \n\n {$clean['body']}", $val);
                $this->link->setLink('link_bayes_messages', 'bayes_documents', $doc_id, 'messages', $clean['id']);
            }
        }

        $this->id = $clean['id']; // Remember this id

    }


    /**
    * The form was successfuly processed
    */
    function formSuccess() {

        $this->tpl->clear_cache(null, $_SESSION['nickname']); // Clear cache
        suxFunct::redirect(suxFunct::makeUrl('/blog/bookmarks/' . $this->id)); // Pass this on to bookmarks for scanning

    }


}


?>