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

require_once(dirname(__FILE__) . '/../../includes/suxPhoto.php');
require_once(dirname(__FILE__) . '/../../includes/suxValidate.php');
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
    */
    function __construct() {

        $this->user = new suxUser(); // User

        $this->tpl = new suxTemplate($this->module); // Template
        $this->r = new cropperRenderer($this->module); // Renderer
        $this->tpl->assign_by_ref('r', $this->r); // Renderer referenced in template
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->r->text =& $this->gtext;
        suxValidate::register_object('this', $this); // Register self to validator

    }


    /**
    * Validate the form
    *
    * @param array $dirty reference to unverified $_POST
    * @return bool
    */
    function formValidate(&$dirty) {
        return suxValidate::formValidate($dirty, $this->tpl);
    }


    /**
    * Build the form and show the template
    *
    * @global string $CONFIG['URL']
    * @param string $module
    * @param int $id
    * @param array $dirty reference to unverified $_POST
    */
    function formBuild($module, $id, &$dirty) {

        // Initialize width & height
        $width = 0;
        $height = 0;

        // Check $id
        if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1) throw new Exception ('Invalid $id');

        // Check $module, assign $table
        $table = $this->getTable($module);
        if (!$table) throw new Exception('Unsuported $module');

        // --------------------------------------------------------------------
        // Form logic
        // --------------------------------------------------------------------

        if (!empty($dirty)) $this->tpl->assign($dirty);
        else suxValidate::disconnect();

        if (!suxValidate::is_registered_form()) {
            suxValidate::connect($this->tpl, true); // Reset connection
            suxValidate::register_validator('integrity', 'integrity:module:id', 'hasIntegrity');
        }

        // --------------------------------------------------------------------
        // Get image from database
        // --------------------------------------------------------------------

        $query = "SELECT users_id, image FROM {$table} WHERE id = ? LIMIT 1 ";
        $db = suxDB::get();
        $st = $db->prepare($query);
        $st->execute(array($id));
        $image = $st->fetch(PDO::FETCH_ASSOC);

        if (!$image['image']) throw new Exception('$image not found');

        if ($image['users_id'] != $_SESSION['users_id']) {
            // Check that the user is allowed to be here
            if (!$this->user->isRoot($_SESSION['users_id'])) {
                $access = $this->user->getAccess($_SESSION['users_id'], $module);
                if ($access < $GLOBALS['CONFIG']['ACCESS'][$module]['admin'])
                    suxFunct::redirect(suxFunct::getPreviousURL('cropper'));
            }
        }

        // Assign a url to the fullsize version of the image
        $image = $image['image'];
        $image = rawurlencode(suxPhoto::t2fImage($image));
        $image = "{$GLOBALS['CONFIG']['URL']}/data/{$module}/{$image}";
        $image = suxFunct::myHttpServer() . $image;

        // Double check
        if (!filter_var($image, FILTER_VALIDATE_URL)) $image = null;
        if (!preg_match('/\.(jpe?g|gif|png)$/i', $image)) $image = null;
        if ($image) list($width, $height) = @getimagesize($image);

        // --------------------------------------------------------------------
        // Template
        // --------------------------------------------------------------------

        if ($image && $width && $height) {

            // Get config variables
            $this->tpl->config_load('my.conf', $module);

            $this->tpl->assign('module', $module);
            $this->tpl->assign('id', $id);
            $this->tpl->assign('x2', $this->tpl->get_config_vars('thumbnailWidth')); // Pavatar
            $this->tpl->assign('y2', $this->tpl->get_config_vars('thumbnailHeight'));
            $this->tpl->assign('url_to_source', $image);
            $this->tpl->assign('width', $width);
            $this->tpl->assign('height', $height);

            $this->tpl->assign('form_url', suxFunct::makeUrl("/cropper/{$module}/{$id}"));
            $this->tpl->assign('prev_url', suxFunct::getPreviousURL('cropper'));

            $this->tpl->display('cropper.tpl');

        }


    }


    /**
    * Process the form
    *
    * @param array $clean reference to validated $_POST
    */
    function formProcess(&$clean) {

        // Check $module, assign $table
        $table = $this->getTable($clean['module']);
        if (!$table) throw new Exception('Unsuported $module');

        // --------------------------------------------------------------------
        // Get image from database
        // --------------------------------------------------------------------

        $query = "SELECT users_id, image FROM {$table} WHERE id = ? LIMIT 1 ";
        $db = suxDB::get();
        $st = $db->prepare($query);
        $st->execute(array($clean['id']));
        $image = $st->fetch(PDO::FETCH_ASSOC);

        if (!$image['image']) throw new Exception('$image not found');

        if ($image['users_id'] != $_SESSION['users_id']) {
            // Check that the user is allowed to be here
            if (!$this->user->isRoot($_SESSION['users_id'])) {
                $access = $this->user->getAccess($_SESSION['users_id'], $clean['module']);
                    if ($access < $GLOBALS['CONFIG']['ACCESS'][$clean['module']]['admin'])
                    suxFunct::redirect(suxFunct::getPreviousURL('cropper'));
            }
        }

        $path_to_dest = "{$GLOBALS['CONFIG']['PATH']}/data/{$clean['module']}/{$image['image']}";
        $path_to_source = suxPhoto::t2fImage($path_to_dest);

        if (!is_writable($path_to_dest)) die('Destination is not writable? ' . $path_to_dest);

        // ----------------------------------------------------------------------------
        // Manipulate And Rewrite Image
        // ----------------------------------------------------------------------------

        // $image
        $format = explode('.', $path_to_source);
        $format = mb_strtolower(end($format));
        if ($format == 'jpg') $format = 'jpeg'; // fix stupid mistake
        if (!($format == 'jpeg' || $format == 'gif' || $format == 'png')) die('Invalid image format');

        // Try to adjust memory for big files
        suxPhoto::fudgeFactor($format, $path_to_source);

        $func = 'imagecreatefrom' . $format;
        $image = $func($path_to_source);
        if (!$image) die('Invalid image format');

        // $thumb
        $thumb = imagecreatetruecolor($clean['x2'] , $clean['y2']);

        $white = imagecolorallocate($thumb, 255, 255, 255);
        ImageFilledRectangle($thumb, 0, 0, $clean['x2'], $clean['y2'], $white);
        imagealphablending($thumb, true);

        // Output
        imagecopyresampled($thumb, $image, 0, 0, $clean['x1'], $clean['y1'], $clean['x2'], $clean['y2'], $clean['width'], $clean['height']);
        $func = 'image' . $format;
        $func($thumb, $path_to_dest);

        // Free memory
        imagedestroy($image);
        imagedestroy($thumb);

    }

    function formSuccess() {

        suxFunct::redirect(suxFunct::getPreviousURL('cropper'));

    }


    /**
    * Check module, return table
    *
    * @param string $module
    * @return string
    */
    private function getTable($module) {

        if ($module == 'blog') $table = 'messages';
        elseif ($module == 'photos') $table = 'photos';
        elseif ($module == 'user') $table = 'users_info';
        else $table = false;

        return $table;

    }



}


?>