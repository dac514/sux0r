<?php

/**
* photos
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

require_once('photosRenderer.php');
require_once(dirname(__FILE__) . '/../abstract.component.php');
require_once(dirname(__FILE__) . '/../../includes/suxPhoto.php');


class photos extends component {

    // Module name
    protected $module = 'photos';

    // Object: suxPhoto()
    protected $photo;

    // Var
    public $per_page; // Photos per page


    /**
    * Constructor
    *
    */
    function __construct() {

        // Declare objects
        $this->photo = new suxPhoto(); // Photos
        $this->r = new photosRenderer($this->module); // Renderer
        parent::__construct(); // Let the parent do the rest

        // Declare properties
        $this->r->bool['analytics'] = true; // Turn on analytics
        $this->per_page = $this->tpl->get_config_vars('perPage');

    }


    /**
    * List albums
    */
    function listing($nickname = null) {

        $users_id = null;
        if ($nickname) {
            $user = $this->user->getByNickname($nickname);
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

            $this->pager->setPages($this->photo->countAlbums($users_id));
            $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl('/photos'));
            $this->r->arr['photos'] = $this->photo->getAlbums($this->pager->limit, $this->pager->start, $users_id);

            if ($this->r->arr['photos']) foreach ($this->r->arr['photos'] as $key => $val) {
                $tmp = $this->user->getByID($val['users_id']);
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
            $this->r->arr['photos'] = $this->photo->getPhotos($this->pager->limit, $this->pager->start, $id);

            $this->r->arr['album'] = $this->photo->getAlbumByID($id);
            $tmp = $this->user->getByID($this->r->arr['album']['users_id']);
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

            $this->r->arr['photos'] = $this->photo->getPhotoByID($id);
            if ($this->r->arr['photos'] == false || !count($this->r->arr['photos']))
                suxFunct::redirect(suxFunct::getPreviousURL()); // Redirect
            else {

                $this->r->arr['photos']['image'] = suxPhoto::t2fImage($this->r->arr['photos']['image']); // Fullsize

                // Album info
                $this->r->arr['album'] = $this->photo->getAlbumByID($this->r->arr['photos']['photoalbums_id']);
                $tmp = $this->user->getByID($this->r->arr['album']['users_id']);
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

            $fp = $this->photo->getAlbums($this->pager->limit);
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