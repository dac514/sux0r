<?php

/**
* blogEdit
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

require_once('blogRenderer.php');
require_once(dirname(__FILE__) . '/../abstract.component.php');
require_once(dirname(__FILE__) . '/../../includes/suxValidate.php');
require_once(dirname(__FILE__) . '/../../includes/suxPhoto.php');
require_once(dirname(__FILE__) . '/../../includes/suxThreadedMessages.php');
require_once(dirname(__FILE__) . '/../../extensions/bayesUser.php');


class blogEdit extends component {

    // Module name
    protected $module = 'blog';

    // Object: suxThreadedMessages()
    protected $msg;

    // Object: bayesUser()
    protected $nb;

    // Var: message id
    private $id;

    // Var: supported image extensions
    private $extensions = 'jpg,jpeg,gif,png';



    /**
    * Constructor
    *
    * @param int $id message id
    */
    function __construct($id = null) {

        // Declare objects
        $this->nb = new bayesUser();
        $this->msg = new suxThreadedMessages();
        $this->r = new blogRenderer($this->module); // Renderer
        suxValidate::register_object('this', $this); // Register self to validator
        parent::__construct(); // Let the parent do the rest

        // Declare properties
        $this->msg->setPublished(null);

        if ($id) {
            if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1)
                suxFunct::redirect(suxFunct::makeURL('/blog')); // Invalid id
        }

        // Redirect if not logged in
        if (empty($_SESSION['users_id'])) suxFunct::redirect(suxFunct::makeUrl('/user/register'));

        // Security check
        if (!$this->user->isRoot()) {
            $access = $this->user->getAccess($this->module);
            if ($access < $GLOBALS['CONFIG']['ACCESS'][$this->module]['admin']) {
                if ($access < $GLOBALS['CONFIG']['ACCESS'][$this->module]['publisher'])
                    suxFunct::redirect(suxFunct::makeUrl('/blog'));

                // Verfiy that we are allowed to edit this
                if (filter_var($id, FILTER_VALIDATE_INT)) {
                    $tmp = $this->msg->getByID($id);
                    if ($tmp['users_id'] != $_SESSION['users_id']) suxFunct::redirect(suxFunct::makeUrl('/blog'));
                }

            }
        }

        // Assign id:
        $this->id = $id;

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

        $blog = array();

        if ($this->id) {

            // Editing a blog post

            $tmp = $this->msg->getByID($this->id);

            $blog['id'] = $tmp['id'];
            $blog['title'] = $tmp['title'];
            $blog['image'] = $tmp['image'];
            $blog['body'] = htmlentities($tmp['body_html'], ENT_QUOTES, 'UTF-8'); // Textarea fix
            $blog['draft'] = $tmp['draft'];
            $blog['thread_pos'] = $tmp['thread_pos'];

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

            /* Tags */

            $links = $this->link->getLinks('link__messages__tags', 'messages', $blog['id']);
            $blog['tags'] = '';
            foreach($links as $val) {
                $tmp = $this->tags->getByID($val);
                $blog['tags'] .=  $tmp['tag'] . ', ';
            }
            $blog['tags'] = rtrim($blog['tags'], ', ');


            /* Naive Bayesian:
            1) Get the `link__bayes_documents__messages` matching this messages_id
            2) Foreach linking bayes_document_id
            3) get the categories I can train (nb::isCategoryTrainer($cat_id, $users_id)
            4) stuff them into {$category_id} for template, append doc_id to {$link} string
            */

            $links = $this->link->getLinks('link__bayes_documents__messages', 'messages', $blog['id']);
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
            suxValidate::register_validator('image', 'image:' . $this->extensions, 'isFileType', true);
            suxValidate::register_validator('image2','image:' . ini_get('upload_max_filesize'), 'isFileSize', true);
            suxValidate::register_validator('body', 'body', 'notEmpty', false, false, 'trim');
            suxValidate::register_validator('date', 'Date:Date_Year:Date_Month:Date_Day', 'isDate', false, false, 'makeDate');
            suxValidate::register_validator('time', 'Time_Hour', 'isInt');
            suxValidate::register_validator('time2', 'Time_Minute', 'isInt');
            suxValidate::register_validator('time3', 'Time_Second', 'isInt');


        }

        // --------------------------------------------------------------------
        // Template
        // --------------------------------------------------------------------

        // Additional
        $this->r->text['upload_max_filesize'] =  ini_get('upload_max_filesize');
        $this->r->text['supported'] =  $this->extensions;
        $this->r->text['form_url'] = suxFunct::makeUrl('/blog/edit/' . $this->id);
        $this->r->text['back_url'] = suxFunct::getPreviousURL();

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

        // Unset image?
        if (!empty($clean['unset_image'])) $clean['image'] = ''; // Set to empty string

        // Image?
        if (isset($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])) {

            $format = explode('.', $_FILES['image']['name']);
            $format = strtolower(end($format)); // Extension

            list($resize, $fullsize) = suxPhoto::renameImage($_FILES['image']['name']);
            $clean['image'] = $resize; // Add image to clean array
            $resize = suxFunct::dataDir($this->module) . "/{$resize}";
            $fullsize = suxFunct::dataDir($this->module) . "/{$fullsize}";

            suxPhoto::resizeImage($format, $_FILES['image']['tmp_name'], $resize,
                $this->tpl->get_config_vars('thumbnailWidth'),
                $this->tpl->get_config_vars('thumbnailHeight')
                );
            move_uploaded_file($_FILES['image']['tmp_name'], $fullsize);

        }

        // Draft
        $clean['draft'] = (isset($clean['draft']) && $clean['draft']) ? true : false;

        // --------------------------------------------------------------------
        // Create $msg array
        // --------------------------------------------------------------------

        $msg = array(
                'title' => $clean['title'],
                'image' => @$clean['image'],
                'body' => $clean['body'],
                'published_on' => $clean['published_on'],
                'draft' => $clean['draft'],
                'blog' => true,
            );
        if (isset($clean['id'])) $msg['id'] = $clean['id'];

        // --------------------------------------------------------------------
        // Put $msg in database
        // --------------------------------------------------------------------

        // New
        $clean['id'] = $this->msg->save($_SESSION['users_id'], $msg, true);

        $this->msg->setPublished(true);
        $tmp = $this->msg->getByID($clean['id']); // Is actually published?
        $this->msg->setPublished(null); // Revert

        if ($tmp) {

            // Clear all caches, cheap and easy
            $this->tpl->clear_all_cache();

            // Log message
            $log = '';
            $url = suxFunct::makeUrl("/user/profile/{$_SESSION['nickname']}", null, true);
            $log .= "<a href='$url'>{$_SESSION['nickname']}</a> ";
            $log .= mb_strtolower($this->r->gtext['posted_blog']);
            $url = suxFunct::makeUrl("/blog/view/{$tmp['thread_id']}", null, true);
            $log .= " <a href='$url'>{$tmp['title']}</a>";

            // Log
            $this->log->write($_SESSION['users_id'], $log);

            // Clear cache
            $tpl = new suxTemplate('user');
            $tpl->clear_cache('profile.tpl', $_SESSION['nickname']);
        }

        $this->log->write($_SESSION['users_id'], "sux0r::blogEdit()  messages_id: {$clean['id']}", 1); // Private


        // --------------------------------------------------------------------
        // Tags procedure
        // --------------------------------------------------------------------

        // Parse tags
        $tags = @suxTags::parse($clean['tags']);

        // Save tags into database
        $tag_ids = array();
        foreach($tags as $tag) {
            $tag_ids[] = $this->tags->save($_SESSION['users_id'], $tag);
        }

        //Delete current links
        $this->link->deleteLink('link__messages__tags', 'messages', $clean['id']);

        // Reconnect links
        foreach ($tag_ids as $id) {
            $this->link->saveLink('link__messages__tags', 'messages', $clean['id'], 'tags', $id);
        }


        // --------------------------------------------------------------------
        // Naive Bayesian procedure
        // --------------------------------------------------------------------

        /*
        `link__bayes_documents__messages` asserts that a message was trained and copied into
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
        INNER JOIN link__bayes_documents__messages ON link__bayes_documents__messages.bayes_documents_id = bayes_documents.id
        INNER JOIN messages ON link__bayes_documents__messages.messages_id = messages.id
        INNER JOIN bayes_categories ON bayes_categories.id = bayes_documents.bayes_categories_id
        INNER JOIN bayes_auth ON bayes_categories.bayes_vectors_id = bayes_auth.bayes_vectors_id
        ";

        $query = "
        SELECT bayes_documents.id FROM bayes_documents
        {$innerjoin}
        WHERE messages.id = ?
        AND bayes_auth.users_id = ? AND (bayes_auth.owner = true OR bayes_auth.trainer = true)
        "; // Note: bayes_auth WHERE condition equivilant to nb->isCategoryTrainer()

        $db = suxDB::get();
        $st = $db->prepare($query);
        $st->execute(array($clean['id'], $_SESSION['users_id']));
        $tmp = $st->fetchAll(PDO::FETCH_ASSOC);

        foreach ($tmp as $val) {
            $this->nb->untrainDocument($val['id']);
        }

        // Regcategorize
        // category ids submitted by the form

        if (isset($clean['category_id'])) foreach($clean['category_id'] as $val) {
            if (!empty($val) && $this->nb->isCategoryTrainer($val, $_SESSION['users_id'])) {

                $doc_id = $this->nb->trainDocument("{$clean['title']} \n\n {$clean['body']}", $val);
                $this->link->saveLink('link__bayes_documents__messages', 'bayes_documents', $doc_id, 'messages', $clean['id']);

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