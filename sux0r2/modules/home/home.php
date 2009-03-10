<?php

/**
* home
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once('homeRenderer.php');

class home {

    // Variables
    public $gtext = array();
    private $module = 'home';

    // Objects
    public $tpl;
    public $r;
    private $user;


    /**
    * Constructor
    *
    */
    function __construct() {

        $this->tpl = new suxTemplate($this->module); // Template
        $this->r = new homeRenderer($this->module); // Renderer
        $this->tpl->assign_by_ref('r', $this->r); // Renderer referenced in template
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->user = new suxUser();
        $this->r->text =& $this->gtext;
        $this->r->bool['analytics'] = true; // Turn on analytics

    }


    /**
    * Display home
    */
    function display() {

        // Get nickname
        if (isset($_SESSION['nickname'])) $nn = $_SESSION['nickname'];
        else $nn = 'nobody';

        $cache_id = "$nn|home";
        $this->tpl->caching = 1;

        $this->tpl->display('home.tpl', $cache_id);

    }


}


?>