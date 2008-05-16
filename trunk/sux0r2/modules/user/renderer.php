<?php

/**
* custom user module renderer
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


    public $profile = array(); // User profile


    /**
    * Constructor
    */
    function __construct() {
        parent::__construct(); // Call parent
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
    * Get openid.server <link> tag
    *
    * @return string
    */
    function getOpenIDMeta() {

        $server = suxFunct::makeUrl('/openid', null, true);
        return '<link rel="openid.server" href="' . $server .'" />' . "\n";

    }


}


?>