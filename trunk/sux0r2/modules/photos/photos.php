<?php

/**
* photos
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

class photos {

    // Variables
    public $gtext = array();
    private $module = 'photos';

    // Objects
    public $tpl;
    public $r;
    private $user;
    private $photo;
    private $pager;


    /**
    * Constructor
    *
    */
    function __construct() {

        $this->tpl = new suxTemplate($this->module); // Template
        $this->r = new photosRenderer($this->module); // Renderer
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->r->text =& $this->gtext;
        $this->user = new suxUser();
        $this->photo = new suxPhoto();

        $this->pager = new suxPager();
        $this->pager->limit = 10;

    }


    /**
    * List albums
    */
    function listing() {

        // Start pager
        $this->pager->setStart();

        // Get nickname
        if (isset($_SESSION['nickname'])) $nn = $_SESSION['nickname'];
        else $nn = 'nobody';

        // "Cache Groups" using a vertical bar |
        $cache_id = $nn . '|listing|' . $this->pager->start;
        $this->tpl->caching = 0;

        if (!$this->tpl->is_cached('list.tpl', $cache_id)) {

            $this->pager->setPages($this->photo->countAlbums());
            $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl('/photos'));
            $this->r->pho = $this->photo->getAlbums(null, $this->pager->limit, $this->pager->start);

            if (!count($this->r->pho)) $this->tpl->caching = 0; // Nothing to cache, avoid writing to disk

        }


        $this->tpl->assign_by_ref('r', $this->r);
        $this->tpl->display('list.tpl', $cache_id);

    }


    /**
    * View photo
    */
    function view($id) {

        $this->tpl->assign_by_ref('r', $this->r);
        $this->tpl->display('view.tpl');

    }


    /**
    * List photos in an album
    */
    function album($id) {

        $this->tpl->assign_by_ref('r', $this->r);
        $this->tpl->display('album.tpl');

    }



}


?>