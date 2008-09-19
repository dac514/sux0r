<?php

/**
* suxPager
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

class suxPager {

    public $range = 10; // pageList() variable
    public $limit = 10; // SQL Limit
    public $start = 0;
    public $pages = 0;


    /**
    * Constructor
    */
    function __construct() { }


    // -----------------------------------------------------------------------
    // Simple continue link with $_GET['start']
    // -----------------------------------------------------------------------

    /**
    * @param string $url
    * @return string returns a contine link
    */
	function continueLink($start, $url) {

        if (!(filter_var($start, FILTER_VALIDATE_INT))) $start = 0;
		if (trim($url) == '') return null;

        $text = suxFunct::gtext();
        $q = mb_strpos($url, '?') ? '&' : '?';
        $html = "<a href='{$url}{$q}start={$start}' class='nextPage'>{$text['continue']} &raquo;</a> ";
		return "<div class='pager'>{$html}</div> ";

    }


    // -----------------------------------------------------------------------
    // Pager links with $_GET['page']
    // -----------------------------------------------------------------------

    /*

    // Pseudo example:

    $p = new suxPager();
    $p->limit = 10; // Optional
    $p->setStart();
    $count = SELECT COUNT(*)
    $p->setPages($count);
    $query = "SELECT * FROM table WHERE condition = 1 ORDER BY title LIMIT {$p->start}, {$p->limit} ";
    $pagelist = $p->pageList('http://some.url/');
    echo $pagelist;

    */

    /**
    * @return int sets the start offset based on $_GET['page'] and $this->limit
    */
    function setStart() {

        if (isset($_GET['page'])) {
            if (filter_var($_GET['page'], FILTER_VALIDATE_INT) && $_GET['page'] > 0) {
                $this->start = ($_GET['page'] - 1) * $this->limit;
            }
        }
        else {
			$this->start = 0;
			$_GET['page'] = 1;
        }

    }


    /**
    * @param int $count
    * @return int returns the number of pages needed based on a count and a limit
    */
	function setPages($count) {

        if (!filter_var($count, FILTER_VALIDATE_INT)) return;

        $pages = (($count % $this->limit) == 0) ? $count / $this->limit : floor($count / $this->limit) + 1;
        $this->pages = $pages;

    }


    /**
    * @param string $url
    * @return string returns a list of pages in html
    */
	function pageList($url) {

        if ($this->pages <= 1) return null; // No pages

        // Sanitize
		if (trim($url) == '') return null;
        if (!isset($_GET['page']) || !filter_var($_GET['page'], FILTER_VALIDATE_INT) || $_GET['page'] < 1) {
            $_GET['page'] = 1;
        }
        if ($_GET['page'] > $this->pages) $_GET['page'] = $this->pages;

        $html = '';
        $q = mb_strpos($url, '?') ? '&' : '?';

		// Print the first and previous page links if necessary
		if ($_GET['page'] != 1 && $_GET['page']) {
            $html .= "<a href='{$url}{$q}page=1' class='firstPage'>[1]</a> ";
		}
		if (($_GET['page'] - 1) > 0) {
            $html .= "<a href='{$url}{$q}page=" . ($_GET['page'] - 1) . "' class='prevPage'>&laquo;</a> ";
		}

		// Print the numeric page list; make the current page unlinked and bold

        $rc = $this->range - $_GET['page']; // right count
        $lc = $this->pages - $_GET['page']; // left count

        if ($rc >= ($this->range / 2)) {
            $lc = $this->range - $rc;
        }
        elseif ($lc <= ($this->range / 2)) {
            $lc = min($this->range - $lc, $this->range);
            $rc = $this->range - $lc;
        }
        else {
            $rc = round($this->range / 2);
            $lc = $rc;
        }

        // Html mess
        $tmp = "<span class='currentPage'>{$_GET['page']}</span> ";
        $tmp2 = '';
        while($lc) {
            $p = $_GET['page'] - $lc;
            if ($p >= 1) $tmp2 .= "<a href='{$url}{$q}page={$p}' class='page'>{$p}</a> ";
            --$lc;
        }
        $tmp = $tmp2 . $tmp;
        $tmp2 = '';
        while($rc) {
            $p = $_GET['page'] + $rc;
            if ($p <= $this->pages) $tmp2 = "<a href='{$url}{$q}page={$p}' class='page'>{$p}</a> " . $tmp2;
            --$rc;
        }
        $tmp .= $tmp2;
        $html .= $tmp;

		// Print the Next and Last page links if necessary
		if (($_GET['page'] + 1) <= $this->pages) {
            $html .= "<a href='{$url}{$q}page=" . ($_GET['page'] + 1) . "' class='nextPage'>&raquo;</a> ";
		}
		if ($_GET['page'] != $this->pages && $this->pages != 0) {
            $html .= "<a href='{$url}{$q}page={$this->pages}' class='lastPage'>[{$this->pages}]</a> ";
		}

		return "<div class='pager'>{$html}</div> ";
    }


}

?>