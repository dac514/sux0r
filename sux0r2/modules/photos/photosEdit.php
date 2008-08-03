<?php

/**
* photosEdit
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
require_once(dirname(__FILE__) . '/../../includes/suxPager.php');
require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once('photosRenderer.php');

class photosEdit {

    // Variables
    public $per_page; // Photos per page
    public $gtext = array();
    private $module = 'photos';
    private $prev_url_preg = '#^photos/album/[edit|annotate]|^cropper/#i';
    private $id;

    // Objects
    public $tpl;
    public $r;
    private $user;
    private $photo;
    private $pager;


    /**
    * Constructor
    *
    * @param int $id album id
    */
    function __construct($id = null) {

        $this->user = new suxUser(); // User
        $this->photo = new suxPhoto($this->module); // Photos
        $this->tpl = new suxTemplate($this->module); // Template
        $this->r = new photosRenderer($this->module); // Renderer
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->r->text =& $this->gtext;
        $this->pager = new suxPager();

        // Redirect if not logged in
        $this->user->loginCheck(suxfunct::makeUrl('/user/register'));

        if (filter_var($id, FILTER_VALIDATE_INT)) {
            // TODO:
            // Verfiy that we are allowed to edit this
            $this->id = $id;
        }

        // This module has config variables, load them
        $this->tpl->config_load('my.conf', $this->module);
        $this->per_page = $this->tpl->get_config_vars('perPage');

    }


    function annotator() {

        $this->tpl->assign_by_ref('r', $this->r);

        // Start pager
        $this->pager->setStart();
        // $this->pager->limit = $this->per_page;

        $this->pager->setPages($this->photo->countPhotos($this->id));
        $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl("/photos/album/annotate/{$this->id}"));
        $this->r->pho = $this->photo->getPhotos($this->id, $this->pager->limit, $this->pager->start);

        $this->r->text['back_url'] = suxFunct::getPreviousURL($this->prev_url_preg);

        $this->tpl->display('annotate.tpl');


    }



}


?>