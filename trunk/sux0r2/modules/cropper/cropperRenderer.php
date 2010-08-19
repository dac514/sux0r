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
    * @see http://www.defusion.org.uk/code/javascript-image-cropper-ui-using-prototype-scriptaculous/
    * @global string $CONFIG['URL']
    * @param int $x ratio width
    * @param int $y ratio height
    * @return string the javascript code
    */
    function cropperInit($x, $y) {

        global $CONFIG;

        $js = '
        <script type="text/javascript" src="' . $GLOBALS['CONFIG']['URL'] . '/includes/symbionts/scriptaculous/lib/prototype.js"></script>
        <script type="text/javascript" src="' . $GLOBALS['CONFIG']['URL'] . '/includes/symbionts/scriptaculous/src/scriptaculous.js"></script>
        <script type="text/javascript" src="' . $GLOBALS['CONFIG']['URL'] . '/includes/symbionts/cropper/cropper.js"></script>
        <script type="text/javascript" language="javascript">
        // <![CDATA[

                function onEndCrop( coords, dimensions ) {
                    $("x1").value = coords.x1;
                    $("y1").value = coords.y1;
                    $("width").value = dimensions.width;
                    $("height").value = dimensions.height;
                }

                Event.observe( window, "load", function() {
                    new Cropper.Img(
                        "cropperImage",
                        {
                            ratioDim: {
                                x: ' . $x . ',
                                y: ' . $y . '
                            },
                            displayOnInit: true,
                            onEndCrop: onEndCrop
                        }
                    );
                } );

        // ]]>
        </script>
        ';

        return $js;

    }


}


?>