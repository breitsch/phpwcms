<?php
/**
 * phpwcms content management system
 *
 * @author Oliver Georgi <oliver@phpwcms.de>
 * @copyright Copyright (c) 2002-2013, Oliver Georgi
 * @license http://opensource.org/licenses/GPL-2.0 GNU GPL-2
 * @link http://www.phpwcms.de
 *
 **/


// ----------------------------------------------------------------
// obligate check for phpwcms constants
if (!defined('PHPWCMS_ROOT')) {
   die("You Cannot Access This Script Directly, Have a Nice Day.");
}
// ----------------------------------------------------------------



// Content Type Tabs

//$SQL .= "acontent_html	='".aporeplace($content["image_html"])."', ";
$SQL .= "acontent_template	='".aporeplace($content["tabs_template"])."', ";
$SQL .= "acontent_form		='".aporeplace(serialize($content["tabs"]))."', ";
$SQL .= "acontent_text		='".aporeplace($content['search'])."', ";
$SQL .= "acontent_html		='".aporeplace($content['html'])."' ";

?>