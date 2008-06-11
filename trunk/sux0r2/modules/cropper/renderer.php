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
                    $( "x1" ).value = coords.x1;
                    $( "y1" ).value = coords.y1;
                    $( "width" ).value = dimensions.width;
                    $( "height" ).value = dimensions.height;
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