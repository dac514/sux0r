<?php

/**
* adminRenderer
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class adminRenderer extends suxRenderer {

    // Object: suxUser()
    private $user;


    /**
    * Constructor
    *
    * @param string $module
    */
    function __construct($module) {

        parent::__construct($module); // Call parent
        $this->user = new suxUser();

    }

    /**
    * Get a user's access levels
    *
    * @global $CONFIG['ACCESS']
    * @param int $users_id
    * @return string html
    */
    function getAccessLevels($users_id) {

        $access = array();
        foreach ($GLOBALS['CONFIG']['ACCESS']  as $key => $val) {
            $level = $this->user->getAccess($key, $users_id);
            if ($level) {
                $tmp = $GLOBALS['CONFIG']['ACCESS'][$key];
                $tmp = array_flip($tmp);
                $access[$key] = $tmp[$level];
            }
        }

        if (!count($access)) return null;

        $html = '';
        foreach ($access as $key => $val) {
            $key = ucfirst($key);
            $html .= "[ $key: $val ] ";
        }

        return $html;

    }


}


