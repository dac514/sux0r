<?php

/**
 * suxTemplate
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
 */

require_once(dirname(__FILE__) . '/symbionts/Smarty/libs/Smarty.class.php');

class suxTemplate extends Smarty {

    public $module;
    public $partition;
    public $template_dir_fallback;


    /**
    * Constructor
    *
    * @global string $CONFIG['PATH']
    * @param string $module
    * @param string $partition
    */
    function __construct($module, $partition = 'sux0r') {

        // Call parent
        parent::__construct();

        // --------------------------------------------------------------------
        // Plugins directory
        // --------------------------------------------------------------------

        $this->plugins_dir = array(
            'plugins', // the default under SMARTY_DIR
            $GLOBALS['CONFIG']['PATH'] . '/includes/symbionts/SmartyAddons/plugins',
            );

        // --------------------------------------------------------------------
        // Setup
        // --------------------------------------------------------------------

        $this->setModule($module, $partition);

    }


    /**
    * Set the template for a module
    *
    * @global string $CONFIG['PATH']
    * @global string $CONFIG['CACHE_LIFETIME']
    * @param string $module
    * @param string $partition
    */
    function setModule($module, $partition = 'sux0r') {

        // --------------------------------------------------------------------
        // Compile directory
        // --------------------------------------------------------------------

        $compile_dir = $GLOBALS['CONFIG']['PATH'] . "/temporary/templates_c/$partition/$module/";
        if(!is_dir($compile_dir) && !mkdir($compile_dir, 0777, true)) {
            throw new Exception('Missing compile dir ' . $compile_dir);
        }
        $this->compile_dir = $compile_dir;


        // --------------------------------------------------------------------
        // Cache directory and variables
        // --------------------------------------------------------------------

        $cache_dir = $GLOBALS['CONFIG']['PATH'] . "/temporary/cache/$partition/$module/";
        if(!is_dir($cache_dir) && !mkdir($cache_dir, 0777, true)) {
            throw new Exception('Missing cache dir ' . $cache_dir);
        }
        $this->cache_dir = $cache_dir;
        $this->cache_lifetime = $GLOBALS['CONFIG']['CACHE_LIFETIME'];
        $this->caching = 0; // Caching off by default, enable in module if needed

        // --------------------------------------------------------------------
        // Template directory
        // --------------------------------------------------------------------

        // Assume the templates are located in templates directory
        $template_dir = $GLOBALS['CONFIG']['PATH'] . "/templates/$partition/$module/";
        $template_dir_fallback = $GLOBALS['CONFIG']['PATH'] . "/templates/sux0r/$module/";

        if($partition != 'sux0r' && !is_dir($template_dir)) {
            // We didn't find anything, but the partition wasn't default, let's try with default
            $template_dir = $template_dir_fallback;
        }

        if(!is_dir($template_dir)) {

            //  Still nothing, maybe they are in the module's directory
            $template_dir = $GLOBALS['CONFIG']['PATH'] . "/modules/$module/templates/$partition/";
            $template_dir_fallback = $GLOBALS['CONFIG']['PATH'] . "/modules/$module/templates/sux0r/";

            if($partition != 'sux0r' && !is_dir($template_dir)) {
                // Still nothing, but the partition wasn't default, let's try with default
                $template_dir = $template_dir_fallback;
            }

            if(!is_dir($template_dir)) {
                // No templates
                throw new Exception('Missing template dir ' . $template_dir);
            }
        }

        $this->module = $module;
        $this->partition = $partition;
        $this->template_dir = $template_dir;
        $this->template_dir_fallback = $template_dir_fallback;

    }


    /**
    * Get the language array
    *
    * @global string $CONFIG['PATH']
    * @param string $module
    * @param string $partition
    * @return array $gtext
    */
    function getLanguage($lang) {

        $gtext = array();

        // Default to english in case other language files are incomplete
        $default = $GLOBALS['CONFIG']['PATH'] . "/modules/{$this->module}/languages/en.php";
        // Second language will overwrite/merge with first
        $requested = $GLOBALS['CONFIG']['PATH'] . "/modules/{$this->module}/languages/$lang.php";

        if (!is_readable($default)) return false; // no default, something is wrong
        else include($default);

        if ($lang != 'en' && is_readable($requested)) include($requested);

        if (!is_array($gtext) || !count($gtext)) return false; // something is wrong
        else return $gtext;

    }


    // --------------------------------------------------------------------
    // Override Smarty Functions
    // --------------------------------------------------------------------

    /**
    * Override Smarty fetch() function to look for content in various places
    *
    * @param string $resource_name
    * @param string $cache_id
    * @param string $compile_id
    * @param boolean $display
    */
    function fetch($resource_name, $cache_id = null, $compile_id = null, $display = false) {

        if (preg_match('/^file:/', $resource_name) || file_exists($this->template_dir . $resource_name)) {
            return parent::fetch($resource_name, $cache_id, $compile_id, $display);
        }
        elseif ($this->template_dir != $this->template_dir_fallback)  {
            // Fallback
            $location = $this->template_dir_fallback . $resource_name;
            return parent::fetch("file:$location", $cache_id, $compile_id, $display);
        }

    }

}

?>
