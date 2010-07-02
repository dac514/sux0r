<?php

/**
* Abstract module component class
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

require_once(dirname(__FILE__) . '/../includes/suxTemplate.php');
require_once(dirname(__FILE__) . '/../includes/suxUser.php');
require_once(dirname(__FILE__) . '/../includes/suxLog.php');
require_once(dirname(__FILE__) . '/../includes/suxLink.php');
require_once(dirname(__FILE__) . '/../includes/suxPager.php');
require_once(dirname(__FILE__) . '/../includes/suxTags.php');

abstract class component {

    // object: suxRenderer()
    public $r;

    // object: suxTemplate()
    public $tpl;

    // object: suxUser()
    protected $user;

    // object: suxLog()
    protected $log;

    // object: suxLink()
    protected $liuk;

    // object: suxPager()
    protected $pager;

    // object: suxTags()
    protected $tags;

    // variable: module
    protected $module;


    /**
    * Constructor
    */
    function __construct() {

        // Pre-condition sanity check
        if (empty($this->module))
            throw new Exception('$this->module not set');

        if (!($this->r instanceof suxRenderer))
            throw new Exception('$this->r is not an instance of suxRenderer()');

        // Template
        $this->tpl = new suxTemplate($this->module); // Template
        $this->tpl->assign_by_ref('r', $this->r); // Renderer referenced in template
        $this->tpl->config_load('my.conf', $this->module); // Config variables

        // Common objects
        $this->user = new suxUser();
        $this->log = new suxLog();
        $this->link = new suxLink();
        $this->pager = new suxPager();
        $this->tags = new suxTags();

    }


}

?>
