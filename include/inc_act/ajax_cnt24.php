<?php
/**
 * phpwcms content management system
 *
 * @author Oliver Georgi <oliver@phpwcms.de>
 * @copyright Copyright (c) 2002-2012, Oliver Georgi
 * @license http://opensource.org/licenses/GPL-2.0 GNU GPL-2
 * @link http://www.phpwcms.de
 *
 **/

// general wrapper for ajax based queries, cnt24

session_start();
$phpwcms = array();
require_once ('../../config/phpwcms/conf.inc.php');
require_once ('../../include/inc_lib/default.inc.php');
require(PHPWCMS_ROOT.'/include/inc_lib/dbcon.inc.php');
require(PHPWCMS_ROOT.'/include/inc_lib/general.inc.php');
require(PHPWCMS_ROOT.'/include/inc_lib/backend.functions.inc.php');
require(PHPWCMS_ROOT.'/include/inc_lib/admin.functions.inc.php');

require_once PHPWCMS_ROOT.'/config/phpwcms/conf.indexpage.inc.php';
require_once PHPWCMS_ROOT.'/include/inc_lib/imagick.convert.inc.php';

// check against user's language
if(!empty($_SESSION["wcs_user_lang"]) && preg_match('/[a-z]{2}/i', $_SESSION["wcs_user_lang"])) {
	$BE['LANG'] = $_SESSION["wcs_user_lang"];
}
//load default language EN
require_once PHPWCMS_ROOT.'/include/inc_lang/backend/en/lang.inc.php';
include_once PHPWCMS_ROOT."/include/inc_lang/code.lang.inc.php";

$BL['modules']				= array();

if(!empty($_SESSION["wcs_user_lang_custom"])) {
	//use custom lang if available -> was set in login.php
	$BL['merge_lang_array'][0]		= $BL['be_admin_optgroup_label'];
	$BL['merge_lang_array'][1]		= $BL['be_cnt_field'];
	include PHPWCMS_ROOT.'/include/inc_lang/backend/'. $BE['LANG'] .'/lang.inc.php';
	$BL['be_admin_optgroup_label']	= array_merge($BL['merge_lang_array'][0], $BL['be_admin_optgroup_label']);
	$BL['be_cnt_field']				= array_merge($BL['merge_lang_array'][1], $BL['be_cnt_field']);
	unset($BL['merge_lang_array']);
}
// check modules
require_once PHPWCMS_ROOT.'/include/inc_lib/modules.check.inc.php';
// load array with actual content types
include PHPWCMS_ROOT.'/include/inc_lib/article.contenttype.inc.php';

if(empty($_SESSION["wcs_user"])) {
	die('Sorry, access forbidden');
}

//output article listing for given structid
$output_article_listing = "";
$cnt24_catList = array();
$cnt24_structid = 0;
$cnt24_sql = "";
$cnt24_artid = 0;
$cnt24_calias = 0;
$cnt24_pattern = '/\<a\s[^\>]+\>(.*)<\/a\>/i';
$cnt24_replacement = '$1';
$cnt24_sortierwert = 1;
$cnt24_cp_block = ' ';
$cnt24_cp_block_name	= '';
$cnt24_cp_block_color	= '';
$cnt24_cp_tab = '';
$cnt24_cp_title = '';
$cnt24_cinfo = NULL;
$cnt24_string = "";

//do we have a var?
if (isset($_POST['idstruct'])) {

    $cnt24_structid = intval($_POST['idstruct']);

  	//Auslesen der kompletten Public Artikel pro Struktur s
	  $cnt24_sql  = "SELECT ".DB_PREPEND."phpwcms_article.article_id, ".DB_PREPEND."phpwcms_article.article_title ";
		$cnt24_sql .= "FROM ".DB_PREPEND."phpwcms_article ";
		$cnt24_sql .= "WHERE ".DB_PREPEND."phpwcms_article.article_public = 1 AND ".DB_PREPEND."phpwcms_article.article_deleted = 0 AND ".DB_PREPEND."phpwcms_article.article_cid = ".$cnt24_structid." ";
		$cnt24_sql .= "ORDER BY ".DB_PREPEND."phpwcms_article.article_title;";
	  $cnt24_data = mysql_query($cnt24_sql)
      or die("error while reading complete article/articlecategory list");
    $cnt24_j=0;
    while ($row = mysql_fetch_array($cnt24_data)) {
			$cnt24_catList['id'][$cnt24_j] = $row['article_id'];
			$cnt24_catList['title'][$cnt24_j] = $row['article_title'];
      $cnt24_j++;
		}
    //start output
    $output_article_listing = '<select name="ca_articleid" id="ca_articleid" onchange="sendRequestArt()" class="f11b width300">';
    $output_article_listing .= '<option value="0">none</option>'."\n";
      if ($cnt24_j>0) {
        for ($i=0;$i<count($cnt24_catList['id']);$i++) {
          $output_article_listing.='<option value="'.$cnt24_catList['id'][$i].'">';
            //no selected because list changes on every event
          $output_article_listing.=$cnt24_catList['title'][$i].'</option>'."\n";
        }
      }
    $output_article_listing.='</select>'."\n";

  echo $output_article_listing;
  unset($cnt24_catList, $cnt24_j, $i, $cnt24_data, $cnt24_sql, $cnt24_structid, $output_article_listing);

} elseif ( isset($_POST['idart']) ) {
//list all cp's for a given articleid

  $cnt24_artid = intval($_POST['idart']);
  $cnt24_calias = intval($_POST['calias']);

  //Auslesen der kompletten Public Artikel pro Struktur s
	  $cnt24_sql  = "SELECT * FROM phpwcms_article WHERE article_id=".$cnt24_artid;
    $cnt24_data = _dbQuery($cnt24_sql, 'SELECT');
    if (isset($cnt24_data[0])) {
      $article = $cnt24_data[0];
    }

    unset($cnt24_sql, $cnt24_data);
		//Listing zugehöriger Artikel Content Teile
		$cnt24_sql = 	"SELECT *, UNIX_TIMESTAMP(acontent_tstamp) as acontent_date FROM ".DB_PREPEND."phpwcms_articlecontent ".
				"WHERE acontent_aid=".$cnt24_artid." AND acontent_trash=0 ".
				"ORDER BY acontent_block, acontent_sorting, acontent_tab, acontent_id;";

		if($cnt24_data = mysql_query($cnt24_sql, $db) or die("error while listing contents for this article")) {
			$cnt24_sortierwert = 1;
			$cnt24_cp_block = ' ';
			$cnt24_cp_block_name	= '';
			$cnt24_cp_block_color	= '';
			$cnt24_cp_tab = '';
      $cnt24_cp_title = '';
      $cnt24_cinfo = NULL;
      $cnt24_string = "";
	?>
<div class="" style=""><?php

      while($row = mysql_fetch_assoc($cnt24_data)) {

					// if type of content part not enabled available
					if(!isset($wcs_content_type[ $row["acontent_type"] ]) || ($row["acontent_type"] == 30 && !isset($phpwcms['modules'][$row["acontent_module"]]))) {
						continue;
					}

					// now show current block name
					if($cnt24_cp_block != $row['acontent_block']) {
						$cnt24_cp_block = $row['acontent_block'];
						$cnt24_cp_block_name = html_specialchars(' {'.$row['acontent_block'].'}');
						$cnt24_cp_block_color = ' #E0D6EB';

						switch($cnt24_cp_block) {
							case ''			:	$cnt24_cp_block_name = $BL['be_main_content'].$cnt24_cp_block_name;
												$cnt24_cp_block_color = ' #F5CCCC';
												break;
							case 'CONTENT'	:	$cnt24_cp_block_name = $BL['be_main_content'].$cnt24_cp_block_name;
												if($article['article_paginate']) {
													$cnt24_cp_block_name .= ' / <img src="img/symbole/content_cppaginate.gif" alt="" style="margin-right:2px;" />';
													$cnt24_cp_block_name .= $BL['be_cnt_pagination'];
												}
												$cnt24_cp_block_color = ' #F5CCCC';
												break;
							case 'LEFT'		:	$cnt24_cp_block_name = $BL['be_cnt_left'].$cnt24_cp_block_name;
												$cnt24_cp_block_color = ' #E0EBD6';
												break;
							case 'RIGHT'	:	$cnt24_cp_block_name = $BL['be_cnt_right'].$cnt24_cp_block_name;
												$cnt24_cp_block_color = ' #FFF5CC';
												break;
							case 'HEADER'	:	$cnt24_cp_block_name = $BL['be_admin_page_header'].$cnt24_cp_block_name;
												$cnt24_cp_block_color = ' #EBEBD6';
												break;
							case 'FOOTER'	:	$cnt24_cp_block_name = $BL['be_admin_page_footer'].$cnt24_cp_block_name;
												$cnt24_cp_block_color = ' #E1E8F7';
												break;
						}
			?>

<div style="width:100%;background-color:<?php echo $cnt24_cp_block_color ?>;">
<img src="img/symbole/block.gif" alt="" width="9" height="11" border="0" />
<span style="font-size:9px;font-weight:bold;"><?php echo  $cnt24_cp_block_name ?></span>
</div><?php

          }

					// now check if content part is tabbed
					if($row['acontent_tab'] && $cnt24_cp_tab != $row['acontent_tab']) {

						$cnt24_cp_tab		= $row['acontent_tab'];

						$cnt24_cp_tabbed = explode('_', $cnt24_cp_tab, 2);
						$cnt24_cp_tab_title	= empty($cnt24_cp_tabbed[1]) ? '' : $cnt24_cp_tabbed[1];
						$cnt24_cp_tab_number = empty($cnt24_cp_tabbed[0]) ? 0 : intval($cnt24_cp_tabbed[0]);
						$cnt24_cp_tab_number++;

			?>
<div style="width:100%;background-color:<?php echo $cnt24_cp_block_color ?>;border-bottom:1px solid #D9DEE3;">
<img src="img/symbole/tabbed.gif" alt="" width="9" height="11" border="0" />
<span style="font-size:9px;"><?php
					echo html_specialchars($cnt24_cp_tab_title);
					if(empty($cnt24_cp_tab_title)) {
						echo ' [' . $cnt24_cp_tab_number . ']';
					}
				 ?>&nbsp;</span>
</div><?php

					} elseif($cnt24_cp_tab && empty($row['acontent_tab'])) {

					// not the same tab but following cp is not tabbed
					$cnt24_cp_tab = '';

					}
			?>

<div id="cont_cp_<?php echo $row["acontent_id"] ?>" class="cont_cp" style="<?php if ($cnt24_calias == $row["acontent_id"]) echo 'background-color:#F3F5F8;'; ?>cursor:pointer;position:relative;margin:3px 0 3px 0;padding:3px 0 3px 0;border-bottom:1px solid #cdcdcd;" onmouseover="this.style.backgroundColor='#F3F5F8';" onmouseout="if ($('calias').value == '<?php echo $row["acontent_id"] ?>' ) { this.style.backgroundColor='#F3F5F8';} else {this.style.backgroundColor='#FFF';}" onclick="bckcol(<?php echo $row["acontent_id"] ?>);$('calias').value = <?php echo intval($row["acontent_id"]) ?>;">
  <div style="position:relative;float:left;">
    <span style="float:left;margin-right:5px;width:12px;"><img src="img/symbole/content_9x11<?php if($row["acontent_granted"]) echo '_granted'; ?>.gif" alt="" width="9" height="11" border="0" /></span>
	  <span style="float:left;width:443px;"><table border="0" cellpadding="0" cellspacing="0" summary="" width="100%">
	            <tr>
	              <td width="150" style="font-size:9px;font-weight:bold;text-transform:uppercase;"><?php

				$cnt24_cp_title = $wcs_content_type[$row["acontent_type"]];
				if(!empty($row["acontent_module"])) {

					$cnt24_cp_title .= ': '.$BL['modules'][$row["acontent_module"]]['listing_title'];

				}
				echo $cnt24_cp_title;


				  ?></td>
	              <td width="23" nowrap="nowrap"></td>
	              <td class="v09" style="color:#727889;padding:0 4px 0 5px" width="60" nowrap="nowrap">[ID:<?php echo $row["acontent_id"] ?>]</td>
	              <td class="v09" nowrap="nowrap"><?php

				  echo date($BL['be_shortdatetime'], $row["acontent_date"]).'&nbsp;';

				  //Display cp paginate page number
				  if($article["article_paginate"]) {

					echo '<img src="img/symbole/content_cppaginate.gif" alt="subsection" title="subsection" />';
					echo $row["acontent_paginate_page"] == 0 ? 1 : $row["acontent_paginate_page"];
				  }


				  //Anzeigen der Space Before/After Info
				  if(intval($row["acontent_before"])) {
				  	//echo "<td><img src=\"img/symbole/content_space_before.gif\" width=\"12\" height=\"6\"></td>";
				  	//echo "<td class=\"v09\">".$row["acontent_before"]."</td>";
					echo '<img src="img/symbole/content_space_before.gif" alt="" />'.$row["acontent_before"];
				  }
				  if(intval($row["acontent_after"])) {
				  	//echo "<td><img src=\"img/symbole/content_space_after.gif\" width=\"12\" height=\"6\"></td>";
				  	//echo "<td class=\"v09\">".$row["acontent_after"]."</td>";
					echo '<img src="img/symbole/content_space_after.gif" alt="" />'.$row["acontent_after"];
				  }
				  if($row["acontent_top"]) {
				  	echo '<img src="img/symbole/content_top.gif" alt="TOP" title="TOP" />';
				  }
		 		 if($row["acontent_anchor"]) {
				  	echo '<img src="img/symbole/content_anchor.gif" alt="Anchor" title="Anchor" />';
				  }
				  ?></td>
	              <td align="right" style="padding-right:1px;">
	               <img src="img/button/visible_11x11a_<?php
	          echo $row["acontent_visible"]
	          ?>.gif" alt="" width="11" height="11" border="0" /></td>


	            </tr>
	  </table></span>
  </div>

  <div style="position:relative;clear:both;">
    <span style=""><table border="0" cellpadding="0" cellspacing="0" summary="" width=""><?php

	// list content type overview

	//$row["acontent_type"] = intval($row["acontent_type"]); -> it is always INT because coming from db INT field
	// check default content parts (system internals
	if($row['acontent_type'] != 24 && $row['acontent_type'] != 30 && file_exists(PHPWCMS_ROOT.'/include/inc_tmpl/content/cnt'.$row['acontent_type'].'.list.inc.php')) {
  //include(PHPWCMS_ROOT.'/include/inc_tmpl/content/cnt'.$row['acontent_type'].'.list.inc.php');
    $cnt24_string = get_include_contents(PHPWCMS_ROOT.'/include/inc_tmpl/content/cnt'.$row['acontent_type'].'.list.inc.php');
	} elseif($row['acontent_type'] == 30 && file_exists($phpwcms['modules'][$row['acontent_module']]['path'].'inc/cnt.list.php')) {

		// custom module
  //include($phpwcms['modules'][$row['acontent_module']]['path'].'inc/cnt.list.php');
    $cnt24_string = get_include_contents($phpwcms['modules'][$row['acontent_module']]['path'].'inc/cnt.list.php');
	} else {

		// default fallback
  //include(PHPWCMS_ROOT.'/include/inc_tmpl/content/cnt0.list.inc.php');
    $cnt24_string = get_include_contents(PHPWCMS_ROOT.'/include/inc_tmpl/content/cnt0.list.inc.php');
	}
	// end list

  //strip all links

  echo preg_replace($cnt24_pattern, $cnt24_replacement, $cnt24_string);

?>
    </table></span>
  </div>
</div>
<?php
      } //end while
?>
</div>
<?php
		} //Ende Listing Artikel Content Teile

unset($cnt24_sortierwert, $cnt24_cp_block, $cnt24_cp_block_name, $cnt24_cp_block_color, $cnt24_cp_tab, $cnt24_artid, $cnt24_calias, $cnt24_cp_tabbed, $cnt24_cp_tab_title, $cnt24_cp_tab_number, $cnt24_pattern, $cnt24_replacement, $cnt24_cp_title, $cnt24_cinfo, $cnt24_string);

}

function get_include_contents($filename) {
    global $row, $article, $phpwcms, $BL, $db;
    if (is_file($filename)) {
        ob_start();
        include $filename;
        return ob_get_clean();
    }
    return false;
}
?>