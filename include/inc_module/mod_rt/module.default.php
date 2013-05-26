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

// Module/Plug-in RT

// register module name
//DO NOT USE SPECIAL CHARS HERE, NO WHITE SPACES, USE LOWER CASE!!!
$_module_name 			= 'br_rt';

// module type - defines where used
// 0 = BE and FE, 1 = BE only, 2 = FE only
$_module_type 			= 1;

// Set if it should be listed as content part
// has content part: true or false
$_module_contentpart	= false;

// simple switch to allow fe render or fe init
$_module_fe_render		= false;
$_module_fe_init		= false;
$_module_fe_search		= false;

//load backend widget
$BE['BODY_MODULEWIDGET']['br_rt'] = '';
$rtdo	= isset($_GET["do"]) ? $_GET["do"] : 'default'; //which backend section and which $do action
$rtp = isset($_GET["p"]) ? intval($_GET["p"]) : 0; //which page should be opened
if( ($rtdo == "articles" && (($rtp == 1) || ($rtp == 2) || ($rtp == 3))) || ($rtdo == "admin" && ($rtp == 11)) ) {
  if( file_exists(PHPWCMS_ROOT.'/include/inc_module/mod_rt/rt/rt_default.tmpl') ) {
    // load template
    $BE['BODY_MODULEWIDGET']['br_rt'] =  file_get_contents(PHPWCMS_ROOT.'/include/inc_module/mod_rt/rt/rt_default.tmpl');
  }
}
unset($rtdo, $rtp);
?>