<?php

/**
* cropper
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

require_once(dirname(__FILE__) . '/../../includes/suxUser.php');
require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once('cropperRenderer.php');

class cropper {

    //Variables
    public $gtext = array();
    private $module = 'cropper';

    // Objects
    public $tpl;
    public $r;
    private $user;


    /**
    * Constructor
    *
    * @global string $CONFIG['PARTITION']
    */
    function __construct() {

        $this->user = new suxUser(); // User

        $this->tpl = new suxTemplate($this->module, $GLOBALS['CONFIG']['PARTITION']); // Template
        $this->r = new cropperRenderer($this->module); // Renderer
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->r->text =& $this->gtext;

    }


    /**
    * Display
    */
    function display() {

        // TODO:

        $image = 'http://www.trotch.com/images/hammer.gif';
        $width = 0;
        $height = 0;

        $image = suxFunct::canonicalizeUrl($image);
        if (!filter_var($image, FILTER_VALIDATE_URL)) $image = null;
        if (!preg_match('/\.(jpe?g|gif|png)$/i', $image)) $image = null;
        if ($image) list($width, $height) = @getimagesize($image);


        if ($image && $width && $height) {

            // Test
            $this->tpl->assign('x2', 80); // Pavatar
            $this->tpl->assign('y2', 80);
            $this->tpl->assign('url_to_source', $image); // 135 x 290
            $this->tpl->assign('width', $width);
            $this->tpl->assign('height', $height);

            $this->tpl->assign_by_ref('r', $this->r);
            $this->tpl->display('cropper.tpl');

        }


    }


    /**
    * Process the form
    *
    * @param array $clean reference to validated $_POST
    */
    function formProcess(&$clean) {

        // TODO

        $path_to_source = '/location/to/source.png';
        $path_to_dest = '/tmp/dest.png'; // variable

        if (!is_writable($path_to_dest)) die('Destination is not writable? ' . $path_to_dest);

        // ----------------------------------------------------------------------------
        // Manipulate And Rewrite Image
        // ----------------------------------------------------------------------------

        // $image
        $format = mb_strtolower(end(explode('.', $path_to_source)));
        if ($format == 'jpg') $format = 'jpeg'; // fix stupid mistake
        if (!($format == 'jpeg' || $format == 'gif' || $format == 'png')) die('Invalid image format');
        $func = 'imagecreatefrom' . $format;
        $image = $func($path_to_source);
        if (!$image) die('Invalid image format');

        // $thumb
        $thumb = imagecreatetruecolor($clean['x2'] , $clean['y2']);
        $bgcolor = imagecolorallocate($thumb, 255, 255, 255);
        ImageFilledRectangle($thumb, 0, 0, $clean['x2'], $clean['y2'], $bgcolor);
        imagealphablending($thumb, true);

        // Output
        imagecopyresampled($thumb, $image, 0, 0, $clean['x1'], $clean['y1'], $clean['x2'], $clean['y2'], $clean['width'], $clean['height']);
        $func = 'image' . $format;
        $func($thumb, $path_to_dest);

    }



}


?>