<?php

/**
* userRenderer
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

require_once(dirname(__FILE__) . '/../../includes/suxUser.php');
require_once(dirname(__FILE__) . '/../../includes/suxSocialNetwork.php');
require_once(dirname(__FILE__) . '/../../includes/suxRenderer.php');


class userRenderer extends suxRenderer {

    // Object: suxUser()
    protected $user;

    // Var: user profile array
    public $profile = array();


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
            'm' => $this->gtext['male'],
            'f' => $this->gtext['female'],
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
        if ($gender == 'm') return $this->gtext['male'];
        else return $this->gtext['female'];

    }


    /**
    * Get all languages
    *
    * @return array
    */
    function getLanguages() {

        $lang = suxFunct::getLanguages();
        foreach ($lang as $key => $val) {
            if (isset($this->gtext[$key])) $lang[$key] = $this->gtext[$key];
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
        if (isset($this->gtext[$lang])) $return[$lang] = $this->gtext[$lang];
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
            if (isset($this->gtext["{$key}2"])) $c[$key] = $this->gtext["{$key}2"];
        }
        asort($c);
        return $c;

    }


    /**
    * Get the country
    *
    * @param string $c two letter country code
    * @return string
    */
    function getCountry($c) {

        $c = mb_strtolower(trim($c));
        $return = suxFunct::getCountries();
        // Using key2 because key has several duplicates in 2-letter language codes
        if (isset($this->gtext["{$c}2"])) $return[$c] = $this->gtext["{$c}2"];
        return $return[$c];

    }


    /**
    *
    */
    function getOpenIDs($users_id) {

        // Cache
        static $oids = null;
        if ($oids != null) return $oids;
        $oids = array();

        $oids = $this->user->getOpenIDs($users_id);
        return $oids;

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

        $tpl = new suxTemplate('user');
        $tpl->config_load('my.conf', 'user');

        $tw = $tpl->get_config_vars('thumbnailWidth');
        $th = $tpl->get_config_vars('thumbnailHeight');

        foreach ($rel as $val) {

            $u = $this->user->getByID($val['friend_users_id'], true);
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

        $tpl = new suxTemplate('user');
        $tpl->config_load('my.conf', 'user');

        $tw = $tpl->get_config_vars('thumbnailWidth');
        $th = $tpl->get_config_vars('thumbnailHeight');

        foreach ($rel as $val) {

            $u = $this->user->getByID($val['users_id'], true);
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

        $tmp .= '<li><a href="' . suxFunct::makeUrl("/user/edit/{$params['nickname']}") . '">' . $text['edit_profile'] . '</a></li>' . "\n";
        $tmp .= '<li><a href="' . suxFunct::makeUrl("/user/avatar/{$params['nickname']}") . '">' . $text['edit_avatar'] . '</a></li>' . "\n";

        // Is feature turned off?
        if ($GLOBALS['CONFIG']['FEATURE']['bayes'] != false)
            $tmp .= '<li><a href="' . suxFunct::makeUrl("/bayes") . '">' . $text['edit_bayes'] . '</a></li>' . "\n";

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
    $url = $GLOBALS['CONFIG']['URL'] . '/modules/user/ajax.lament.php';

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