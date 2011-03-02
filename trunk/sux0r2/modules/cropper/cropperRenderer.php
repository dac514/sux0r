<?php

/**
* cropperRenderer
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class cropperRenderer extends suxRenderer {


    /**
    * Constructor
    *
    * @param string $module
    */
    function __construct($module) {
        parent::__construct($module); // Call parent

    }


    // -------------------------------------------------------------------------
    // Javascript
    // -------------------------------------------------------------------------

    /**
    * Cropper Initialization
    *
    * @see http://deepliquid.com/content/Jcrop_Manual.html
    * @global string $CONFIG['URL']
    * @param int $x ratio width
    * @param int $y ratio height
    * @return string the javascript code
    */
    function cropperInit($x, $y) {

        $js = $this->jQueryInit(false);
        $js .= '
        <script type="text/javascript" src="' . $GLOBALS['CONFIG']['URL'] . '/includes/symbionts/jqueryAddons/Jcrop/js/jquery.Jcrop.min.js"></script>
        <link rel="stylesheet" href="' . $GLOBALS['CONFIG']['URL'] . '/includes/symbionts/jqueryAddons/Jcrop/css/jquery.Jcrop.css" type="text/css" />
        <script type="text/javascript" language="javascript">
        // <![CDATA[

        $(function(){
            jQuery("#cropperImage").Jcrop({
                onChange: showCoords,
                onSelect: showCoords,
                setSelect: [ ' . "0, 0, $x, $y" . ' ],
                aspectRatio: ' . "$x / $y"  . '
            });
        });

        function showCoords(c) {
            jQuery("#x1").val(c.x);
            jQuery("#y1").val(c.y);
            jQuery("#width").val(c.w);
            jQuery("#height").val(c.h);
        };

        // ]]>
        </script>
        ';

        return $js;

    }


}


?>