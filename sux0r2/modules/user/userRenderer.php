<?php

/**
* userRenderer
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
require_once(dirname(__FILE__) . '/../../includes/suxSocialNetwork.php');
require_once(dirname(__FILE__) . '/../../includes/suxRenderer.php');

class userRenderer extends suxRenderer {

    // Variables
    public $profile = array(); // User profile
    public $ulist = array(); // User list
    public $minifeed = array();
    public $openids = array();


    /**
    * Constructor
    *
    * @param string $module
    */
    function __construct($module) {
        parent::__construct($module); // Call parent
    }


    /**
    * Get all timezones
    *
    * @return array
    */
    function getTimezones() {

        $tz = array();
        $tzId = timezone_identifiers_list();
        foreach ($tzId as $val) {
            $tz[$val] = $val;
        }
        return $tz;

    }


    /**
    * Get all genders
    *
    * @return array
    */
    function getGenders() {

        return array(
            'm' => $this->text['male'],
            'f' => $this->text['female'],
            );

    }


    /**
    * Get the gender
    *
    * @param string $gender m, f
    * @return string
    */
    function getGender($gender) {

        $gender = mb_strtolower(trim($gender));
        if ($gender == 'm') return $this->text['male'];
        else return $this->text['female'];

    }


    /**
    * Get all languages
    *
    * @return array
    */
    function getLanguages() {

        $lang = suxFunct::getLanguages();
        foreach ($lang as $key => $val) {
            if (isset($this->text[$key])) $lang[$key] = $this->text[$key];
        }
        asort($lang);
        return $lang;

    }


    /**
    * Get the language
    *
    * @param string $lang two letter language code
    * @return string
    */
    function getLanguage($lang) {

        $lang = mb_strtolower(trim($lang));
        $return = suxFunct::getLanguages();
        return $return[$lang];

    }



    /**
    * Get all countries
    *
    * @return array
    */
    function getCountries() {

        $c = suxFunct::getCountries();
        foreach ($c as $key => $val) {
            // Using key2 because key has several duplicates in 2-letter language codes
            if (isset($this->text["{$key}2"])) $c[$key] = $this->text["{$key}2"];
        }
        asort($c);
        return $c;

    }


    /**
    * Get the country
    *
    * @param string $lang two letter language code
    * @return string
    */
    function getCountry($c) {

        $c = mb_strtolower(trim($c));
        $return = suxFunct::getCountries();
        return $return[$c];

    }


    /**
    * Get the acquaintances
    *
    * @param int $users_id
    * @return string html
    */
    function acquaintances($users_id) {

        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1) return null;

        // Cache
        static $html = null;
        if ($html != null) return $html;
        $html = '';

        $soc = new suxSocialNetwork();

        $rel = $soc->getRelationships($users_id);
        if (!$rel) return $html;

        $user = new suxUser();
        $tpl = new suxTemplate('user');
        $tpl->config_load('my.conf', 'user');

        $tw = $tpl->get_config_vars('thumbnailWidth');
        $th = $tpl->get_config_vars('thumbnailHeight');

        foreach ($rel as $val) {

            $u = $user->getUser($val['friend_users_id'], true);
            if (!$u) continue; // Skip

            $url = suxFunct::makeUrl('/user/profile/' . $u['nickname']);

            if (empty($u['image'])) {
                $img = suxFunct::makeUrl('/') . "/media/{$this->partition}/assets/proletariat.gif";
            }
            else {
                $u['image'] = rawurlencode($u['image']);
                $img = suxFunct::makeUrl('/') . "/data/user/{$u['image']}";
            }

            $html .= "<a href='$url' rel='{$val['relationship']}' class='friend'>";
            $html .= "<img src='$img' class='friend' width='$tw' height='$th' alt='{$u['nickname']}' title = '{$u['nickname']}' />";
            $html .= "</a>";

        }

        return $html;

    }


    /**
    * Get the stalkers
    *
    * @param int $users_id
    * @return string html
    */
    function stalkers($users_id) {

        if (!filter_var($users_id, FILTER_VALIDATE_INT) || $users_id < 1) return null;

        // Cache
        static $html = null;
        if ($html != null) return $html;
        $html = '';

        $soc = new suxSocialNetwork();

        $rel = $soc->getStalkers($users_id);
        if (!$rel) return $html;

        $user = new suxUser();
        $tpl = new suxTemplate('user');
        $tpl->config_load('my.conf', 'user');

        $tw = $tpl->get_config_vars('thumbnailWidth');
        $th = $tpl->get_config_vars('thumbnailHeight');

        foreach ($rel as $val) {

            $u = $user->getUser($val['users_id'], true);
            if (!$u) continue; // Skip

            $url = suxFunct::makeUrl('/user/profile/' . $u['nickname']);

            if (empty($u['image'])) {
                $img = suxFunct::makeUrl('/') . "/media/{$this->partition}/assets/proletariat.gif";
            }
            else {
                $u['image'] = rawurlencode($u['image']);
                $img = suxFunct::makeUrl('/') . "/data/user/{$u['image']}";
            }

            $html .= "<a href='$url' class='stalker'>";
            $html .= "<img src='$img' class='stalker' width='$tw' height='$th' alt='{$u['nickname']}' title = '{$u['nickname']}' />";
            $html .= "</a>";

        }

        return $html;

    }


}


// -------------------------------------------------------------------------
// Smarty {insert} functions
// -------------------------------------------------------------------------

/**
* Render editMenu
*
* @param array $params smarty {insert} parameters
* @return string html
*/
function insert_editMenu($params) {

    if (empty($params['nickname'])) return null;
    if (empty($_SESSION['users_id'])) return null;

    $text = suxFunct::gtext('user');

    $tmp = '';
    if ($params['nickname'] != $_SESSION['nickname']) {
        $tmp .= '<li><a href="' . suxFunct::makeUrl("/society/relationship/{$params['nickname']}") . '">' . $text['edit_relationship'] . '</a></li>' . "\n";
    }
    else {
        $tmp .= '<li><a href="' . suxFunct::makeUrl("/bayes") . '">' . $text['edit_bayes'] . '</a></li>' . "\n";
        $tmp .= '<li><a href="' . suxFunct::makeUrl("/user/edit/{$params['nickname']}") . '">' . $text['edit_profile'] . '</a></li>' . "\n";
        $tmp .= '<li><a href="' . suxFunct::makeUrl("/user/avatar/{$params['nickname']}") . '">' . $text['edit_avatar'] . '</a></li>' . "\n";
        $tmp .= '<li><a href="' . suxFunct::makeUrl("/user/openid/{$params['nickname']}") . '">' . $text['edit_openid'] . '</a></li>' . "\n";
    }

    return $tmp;

}

/**
* Render lament
*
* @param array $params smarty {insert} parameters
* @return string html
*/
function insert_lament($params) {

    if (empty($params['users_id'])) return null;
    if (empty($_SESSION['users_id'])) return null;
    if ($_SESSION['users_id'] != $params['users_id']) return null;

    $text = suxFunct::gtext('user');
    $url = suxFunct::makeUrl('/') . '/modules/user/lament.php';

    $html = "
        <div id='lament'>{$text['lament']}</div>
        <script type='text/javascript'>
        // <![CDATA[
        new Ajax.InPlaceEditor(
            'lament',
            '$url', {
                rows: 4,
                cols: 25,
                clickToEditText: '{$text['lament']}',
                savingText: '{$text['saving']}...',
                okControl: 'button',
                okText: '{$text['ok']}',
                cancelControl: 'button',
                cancelText: '{$text['cancel']}',
                callback: function(form, value) {
                    return 'lament='+encodeURIComponent(value)
                }
            });
        // ]]>
        </script>
    ";

    return $html;

}

?>