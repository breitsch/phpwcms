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

require_once(PHPWCMS_ROOT.'/include/inc_front/lib/js.jquery.default.php');

define('PHPWCMS_JSLIB', 'jquery-2.0-migrate');

/**
 * Init jQuery 2.0.x + jQuery Migrate Library
 */
function initJSLib() {
	if(empty($GLOBALS['block']['custom_htmlhead']['jquery.js'])) {
		if(!USE_GOOGLE_AJAX_LIB) {
			$GLOBALS['block']['custom_htmlhead']['jquery.js'] = getJavaScriptSourceLink(TEMPLATE_PATH.'lib/jquery/jquery-2.0.1.min.js');
			$GLOBALS['block']['custom_htmlhead']['jquery-migrate.js'] = getJavaScriptSourceLink(TEMPLATE_PATH.'lib/jquery/jquery-migrate.min.js');
		} else {
			// not available at Google
			$GLOBALS['block']['custom_htmlhead']['jquery.js'] = getJavaScriptSourceLink('//code.jquery.com/jquery-2.0.1.min.js');
			$GLOBALS['block']['custom_htmlhead']['jquery-migrate.js'] = getJavaScriptSourceLink('//code.jquery.com/jquery-migrate-1.2.1.min.js');
		}
	}
	return TRUE;
}

?>