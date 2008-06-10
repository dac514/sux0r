<?php

/**
* custom blog module renderer
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

require_once(dirname(__FILE__) . '/../../includes/suxRenderer.php');

class renderer extends suxRenderer {


    /**
    * Constructor
    *
    * @param string $module
    */
    function __construct($module) {
        parent::__construct($module); // Call parent

    }
    
    
    /**
    * TinyMCE Initialization
    *
    * @see http://tinymce.moxiecode.com/
    * @global string $CONFIG['URL']
    * @global string $CONFIG['PATH']  
    * @global string $CONFIG['LANGUAGE']  
    * @param int $width optional width parameter for editor window
    * @param int $height optional height parameter for editor window
    * @return string the javascript code
    */
    function tinyMceInit() {
        
        // TinyMCE Path
        $path = $GLOBALS['CONFIG']['URL'] . '/includes/symbionts/tinymce/jscripts/tiny_mce/tiny_mce.js';

        // TinyMCE Language
        if (!empty($_SESSION['language'])) $lang = $_SESSION['language'];
        else $lang = $GLOBALS['CONFIG']['LANGUAGE'];
        // Sanity check
        $test = $GLOBALS['CONFIG']['PATH'] . "/includes/symbionts/tinymce/jscripts/tiny_mce/langs/{$lang}.js";
        if (!is_file($test)) $lang = 'en'; // Revert back to english    
                
        // Javascript
        $js = '<script type="text/javascript" src="' . $path . '"></script>
        <script language="javascript" type="text/javascript">
        // <![CDATA[

        tinyMCE.init({
            mode : "textareas",
            theme : "advanced",
            editor_selector : "mceEditor",
            plugins : "paste,media,table,fullscreen,inlinepopups,autosave,safari",
            width: "100%",
            height: 400,
            theme_advanced_toolbar_location : "top",
            theme_advanced_toolbar_align : "left",
            theme_advanced_buttons1 : "bold,italic,underline,justifyleft,justifycenter,justifyright,justifyfull,numlist,bullist,outdent,indent,forecolor,backcolor,fontselect,fontsizeselect",
            theme_advanced_buttons2 : "undo,redo,pastetext,pasteword,selectall,link,unlink,table,image,media,removeformat,cleanup,code,fullscreen",
            theme_advanced_buttons3 : "",
            entity_encoding : "raw",
            language : "' . $lang . '",
            relative_urls : false
        });

        // ]]>
        </script>' . "\n";

        return $js;
        
    }    



}


?>