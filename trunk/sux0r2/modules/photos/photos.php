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
    public $per_page; // Photos per page
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
        $this->tpl->assign_by_ref('r', $this->r); // Renderer referenced in template
        $this->r->bool['analytics'] = true; // Turn on analytics

        $this->user = new suxUser();
        $this->photo = new suxPhoto();
        $this->pager = new suxPager();

        // This module has config variables, load them
        $this->tpl->config_load('my.conf', $this->module);
        $this->per_page = $this->tpl->get_config_vars('perPage');

    }


    /**
    * List albums
    */
    function listing($nickname = null) {

        $users_id = null;
        if ($nickname) {
            $user = $this->user->getUserByNickname($nickname);
            if (!$user) suxFunct::redirect(suxFunct::makeUrl('/photos')); // Invalid user
            else $users_id = $user['users_id'];
        }

        // Get nickname
        if (isset($_SESSION['nickname'])) $nn = $_SESSION['nickname'];
        else $nn = 'nobody';

        // Start pager
        $this->pager->setStart();

        // "Cache Groups" using a vertical bar |
        $cache_id = "$nn|listing|$nickname|" . $this->pager->start;
        $this->tpl->caching = 1;

        if (!$this->tpl->is_cached('list.tpl', $cache_id)) {

            $this->pager->setPages($this->photo->countAlbums());
            $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl('/photos'));
            $this->r->arr['photos'] = $this->photo->getAlbums($users_id, $this->pager->limit, $this->pager->start);

            if ($this->r->arr['photos']) foreach ($this->r->arr['photos'] as $key => $val) {
                $tmp = $this->user->getUser($val['users_id']);
                $this->r->arr['photos'][$key]['nickname'] = $tmp['nickname'];
            }


            if ($this->r->arr['photos'] == false || !count($this->r->arr['photos']))
                $this->tpl->caching = 0; // Nothing to cache, avoid writing to disk

            $this->r->title .= " | {$this->r->gtext['photos']}";

        }


        $this->tpl->display('list.tpl', $cache_id);

    }


    /**
    * List photos in an album
    */
    function album($id) {

        // Get nickname
        if (isset($_SESSION['nickname'])) $nn = $_SESSION['nickname'];
        else $nn = 'nobody';

        $this->pager->limit = $this->per_page;

        // Start pager
        $this->pager->setStart();

        // "Cache Groups" using a vertical bar |
        $cache_id = "$nn|album|{$id}|" . $this->pager->start;
        $this->tpl->caching = 1;

        if (!$this->tpl->is_cached('album.tpl', $cache_id)) {

            $this->pager->setPages($this->photo->countPhotos($id));
            $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl("/photos/album/{$id}"));
            $this->r->arr['photos'] = $this->photo->getPhotos($id, $this->pager->limit, $this->pager->start);

            $this->r->arr['album'] = $this->photo->getAlbum($id);
            $tmp = $this->user->getUser($this->r->arr['album']['users_id']);
            $this->r->arr['album']['nickname'] = $tmp['nickname'];

            $this->r->title .= " | {$this->r->gtext['photos']} | {$this->r->arr['album']['title']}";

            if ($this->r->arr['photos'] == false || !count($this->r->arr['photos']))
                $this->tpl->caching = 0; // Nothing to cache, avoid writing to disk

        }

        $this->tpl->display('album.tpl', $cache_id);

    }


    /**
    * View photo
    */
    function view($id) {

        // Get nickname
        if (isset($_SESSION['nickname'])) $nn = $_SESSION['nickname'];
        else $nn = 'nobody';

        // "Cache Groups" using a vertical bar |
        $cache_id = "$nn|view|{$id}";
        $this->tpl->caching = 1;

        if (!$this->tpl->is_cached('view.tpl', $cache_id)) {

            $this->r->arr['photos'] = $this->photo->getPhoto($id);
            if ($this->r->arr['photos'] == false || !count($this->r->arr['photos']))
                suxFunct::redirect(suxFunct::getPreviousURL()); // Redirect
            else {

                $this->r->arr['photos']['image'] = suxPhoto::t2fImage($this->r->arr['photos']['image']); // Fullsize

                // Album info
                $this->r->arr['album'] = $this->photo->getAlbum($this->r->arr['photos']['photoalbums_id']);
                $tmp = $this->user->getUser($this->r->arr['album']['users_id']);
                $this->r->arr['album']['nickname'] = $tmp['nickname'];

                // Previous, next, and page number
                $prev_id = null;
                $next_id = null;
                $page = 1;
                $query = 'SELECT id FROM photos WHERE photoalbums_id = ? ORDER BY image '; // Same order as suxPhoto->getPhotos()

                $db = suxDB::get();
                $st = $db->prepare($query);
                $st->execute(array($this->r->arr['photos']['photoalbums_id']));

                $i = 0;
                while ($prev_next = $st->fetch(PDO::FETCH_ASSOC)) {
                    ++$i;
                    if ($prev_next['id'] == $id) break;
                    if ($i >= $this->per_page) {
                        $i = 0;
                        ++$page;
                    }
                    $prev_id = $prev_next['id'];
                }
                $prev_next = $st->fetch(PDO::FETCH_ASSOC);
                $next_id = $prev_next['id'];

                $this->r->text['prev_id'] = $prev_id;
                $this->r->text['next_id'] = $next_id;
                $this->r->text['back_url'] = suxFunct::makeUrl('photos/album/' . $this->r->arr['photos']['photoalbums_id'], array('page' => $page));

                $this->r->title .= " | {$this->r->gtext['photos']} | {$this->r->arr['album']['title']}";

            }

        }

        $this->tpl->display('view.tpl', $cache_id);

    }


    /**
    * Display RSS Feed
    */
    function rss() {

        // Cache
        $cache_id = 'rss';
        $this->tpl->caching = 1;

        if (!$this->tpl->is_cached('rss.tpl', $cache_id)) {

            $fp = $this->photo->getAlbums(null, $this->pager->limit);
            if ($fp) {

                require_once(dirname(__FILE__) . '/../../includes/suxRSS.php');
                $rss = new suxRSS();
                $title = "{$this->r->title} | {$this->r->gtext['photos']}";
                $url = suxFunct::makeUrl('/photos', null, true);
                $rss->outputRSS($title, $url, null);

                foreach($fp as $item) {
                    $url = suxFunct::makeUrl('/photos/album/' . $item['id'], null, true);
                    $rss->addOutputItem($item['title'], $url, $item['body_html']);
                }

                $this->tpl->assign('xml', $rss->saveXML());
            }

        }

        // Template
        header('Content-type: text/xml; charset=utf-8');
        $this->tpl->display('rss.tpl', $cache_id);

    }



}


?>