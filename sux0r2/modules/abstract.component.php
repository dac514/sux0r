<?php

/**
* Abstract module component class
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

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
    protected $link;

    // object: suxPager()
    protected $pager;

    // object: suxTags()
    protected $tags;

    // variable: module
    protected $module;

    // variable: form name
    protected $form_name = 'default';


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
        $this->tpl->assignByRef('r', $this->r); // Renderer referenced in template
        $this->tpl->configLoad('my.conf', $this->module); // Config variables

        // Form
        $this->tpl->assign('form_name', $this->form_name);
        suxValidate::set_form($this->form_name);

        // Common objects
        $this->user = new suxUser();
        $this->log = new suxLog();
        $this->link = new suxLink();
        $this->pager = new suxPager();
        $this->tags = new suxTags();

    }


}

?>
